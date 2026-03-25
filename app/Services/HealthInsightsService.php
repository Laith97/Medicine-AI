<?php

namespace App\Services;

use App\Models\HealthInsight;
use App\Models\HealthJournal;
use App\Models\HealthMedicationSchedule;
use App\Models\HealthMedicationLog;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

class HealthInsightsService
{
    private const DAYS_TO_ANALYZE = 14;
    private const MODEL = 'gpt-4o-mini';

    /**
     * Generate AI health insights for a patient.
     * Returns cached insight if one exists and is not expired (unless $force is true).
     */
    public function generateInsights(User $patient, bool $force = false): HealthInsight
    {
        if (!$force) {
            $cached = HealthInsight::getFreshForUser($patient->id);
            if ($cached) {
                return $cached;
            }
        } else {
            // Delete only expired insights on force-regenerate
            try {
                HealthInsight::where('user_id', $patient->id)
                    ->where('expires_at', '<=', now())
                    ->delete();
            } catch (\Exception $e) {
                Log::error('Failed to delete expired insights', [
                    'user_id' => $patient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $data = $this->gatherPatientData($patient);

        if (empty($data['has_data'])) {
            throw new \RuntimeException('No health data available. Please log symptoms or medications first.');
        }

        $prompt = $this->buildPrompt($data);
        $response = $this->callOpenAI($prompt, $patient->id);
        $content = $this->parseResponse($response);

        try {
            $insight = HealthInsight::create([
                'user_id' => $patient->id,
                'insight_type' => 'combined',
                'summary' => $content['summary'] ?? 'Health insights generated',
                'content' => $content,
                'expires_at' => now()->addHours(24),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save health insight', [
                'user_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to save insight. Please try again.');
        }

        Log::info('Health insight generated', [
            'user_id' => $patient->id,
            'insight_id' => $insight->id,
            'pattern_count' => count($content['patterns'] ?? []),
        ]);

        return $insight;
    }

    /**
     * Gather all relevant patient health data for the prompt.
     */
    private function gatherPatientData(User $patient): array
    {
        $since = now()->subDays(self::DAYS_TO_ANALYZE)->toDateString();

        $journals = HealthJournal::where('user_id', $patient->id)
            ->where('entry_date', '>=', $since)
            ->orderBy('entry_date', 'asc')
            ->get();

        $schedules = HealthMedicationSchedule::where('user_id', $patient->id)
            ->where('active', true)
            ->where('start_date', '<=', now()->toDateString())
            ->get()
            ->filter(fn($s) => $s->isActiveOnDate(now()->toDateString()));

        $medLogs = HealthMedicationLog::whereIn('medication_schedule_id', $schedules->pluck('id'))
            ->where('scheduled_date', '>=', $since)
            ->get();

        $recentDiagnoses = Diagnosis::where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get(['id', 'diagnosis_text', 'created_at']);

        $journalData = $journals->map(fn($j) => [
            'date' => $j->entry_date->format('Y-m-d'),
            'symptoms' => $j->symptoms ?? [],
            'severity' => $j->severity ?? [],
            'notes' => $j->notes,
        ]);

        $medData = $schedules->map(function ($schedule) use ($medLogs) {
            $logs = $medLogs->where('medication_schedule_id', $schedule->id);
            $taken = $logs->whereNotNull('taken_at')->count();
            $skipped = $logs->where('skipped', true)->count();
            $total = $logs->count();
            return [
                'name' => $schedule->medication_name,
                'dosage' => $schedule->dosage,
                'frequency' => $schedule->frequency,
                'taken' => $taken,
                'skipped' => $skipped,
                'total' => $total,
                'adherence_rate' => $total > 0 ? round(($taken / $total) * 100) : 100,
            ];
        });

        $hasData = $journals->isNotEmpty() || $schedules->isNotEmpty();

        return [
            'has_data' => $hasData,
            'journals' => $journalData,
            'medications' => $medData,
            'recent_diagnoses' => $recentDiagnoses->toArray(),
            'patient_name' => $patient->name,
            'days_analyzed' => self::DAYS_TO_ANALYZE,
        ];
    }

    /**
     * Build the system + user prompt for the AI.
     */
    private function buildPrompt(array $data): array
    {
        $systemPrompt = <<<'SYSTEM'
You are a compassionate health insights assistant for a patient portal. Your role is to analyze patient health data and generate actionable, easy-to-understand insights.

IMPORTANT RULES:
- Only analyze data that is provided. Do not invent or assume symptoms, medications, or diagnoses.
- Always include a disclaimer that this is not medical advice and the patient should consult their healthcare provider.
- Be encouraging and supportive in tone — this is for patient empowerment, not alarm.
- If you detect a potentially serious pattern (e.g., daily chest pain, high fever for 5+ days), flag it as "alert" severity.
- Return ONLY valid JSON matching the structure specified in the user prompt.
- Use "info" severity for positive observations, "warning" for concerns, "alert" for urgent patterns.
SYSTEM;

        $userPrompt = <<<USER
Analyze the following patient health data and generate insights. Return a JSON object with this exact structure:

{
  "summary": "A 1-sentence headline summarizing the overall picture (e.g., '3 notable patterns detected' or 'No significant concerns this week')",
  "patterns": [
    {
      "type": "symptom_trend|medication_adherence|hep_progress|positive",
      "title": "Short descriptive title (e.g., 'Increasing headache frequency')",
      "description": "2-3 sentence explanation of the pattern with specific data points",
      "severity": "info|warning|alert",
      "recommendation": "1-sentence actionable recommendation for the patient"
    }
  ],
  "medication_insight": {
    "adherence_rate": 0-100,
    "missed_doses": 0,
    "overall_status": "good|concerning|excellent",
    "medications": [
      {"name": "...", "adherence_rate": 0-100, "status": "good|missed|skipped", "note": "1 sentence"}
    ]
  },
  "overall_assessment": "2-3 sentence holistic assessment of the patient's health this period",
  "next_steps": [
    "Specific actionable tip or reminder (1 sentence each)"
  ]
}

PATIENT DATA:
Patient: {$data['patient_name']}
Period: Last {$data['days_analyzed']} days

SYMPTOM JOURNALS:
USER;

        foreach ($data['journals'] as $journal) {
            $userPrompt .= "- {$journal['date']}: ";
            if (!empty($journal['symptoms'])) {
                $symptomsWithSeverity = [];
                foreach ($journal['symptoms'] as $symptom) {
                    $sev = $journal['severity'][$symptom] ?? null;
                    $symptomsWithSeverity[] = $sev ? "{$symptom} (severity {$sev}/5)" : $symptom;
                }
                $userPrompt .= implode(', ', $symptomsWithSeverity);
            } else {
                $userPrompt .= 'No symptoms logged';
            }
            if ($journal['notes']) {
                $userPrompt .= " | Note: {$journal['notes']}";
            }
            $userPrompt .= "\n";
        }

        $userPrompt .= "\nMEDICATIONS:\n";
        if (empty($data['medications'])) {
            $userPrompt .= "No active medications tracked.\n";
        } else {
            foreach ($data['medications'] as $med) {
                $userPrompt .= "- {$med['name']} {$med['dosage']} ({$med['frequency']}): ";
                $userPrompt .= "taken {$med['taken']}/{$med['total']} days, ";
                $userPrompt .= "skipped {$med['skipped']}/{$med['total']} days, ";
                $userPrompt .= "adherence: {$med['adherence_rate']}%\n";
            }
        }

        $userPrompt .= "\nRECENT DIAGNOSES:\n";
        if (empty($data['recent_diagnoses'])) {
            $userPrompt .= "No recent diagnoses.\n";
        } else {
            foreach ($data['recent_diagnoses'] as $dx) {
                $userPrompt .= "- [{$dx['created_at']}] {$dx['diagnosis_text']}\n";
            }
        }

        $userPrompt .= "\nReturn the JSON now. Only return JSON, no markdown formatting or extra text.";

        return [
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
        ];
    }

    /**
     * Call OpenAI with the built prompt.
     */
    private function callOpenAI(array $prompt, int $userId): string
    {
        if (!config('ai.enabled', true)) {
            throw new \RuntimeException('AI insights are currently disabled.');
        }

        if (empty(config('openai.api_key'))) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        try {
            $response = OpenAI::chat()->create([
                'model' => self::MODEL,
                'messages' => [
                    ['role' => 'system', 'content' => $prompt['system_prompt']],
                    ['role' => 'user', 'content' => $prompt['user_prompt']],
                ],
                'max_tokens' => 1500,
                'temperature' => 0.3,
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            Log::error('OpenAI health insights error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            throw $e;
        }
    }

    /**
     * Parse and validate the AI JSON response.
     */
    private function parseResponse(string $raw): array
    {
        // Strip markdown code blocks if present
        $raw = preg_replace('/^```json\s*/', '', trim($raw)) ?? $raw;
        $raw = preg_replace('/```$/s', '', trim($raw)) ?? $raw;

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('HealthInsights JSON parse error', [
                'raw' => substr($raw, 0, 500),
                'json_error' => json_last_error_msg(),
            ]);
            throw new \RuntimeException('Failed to parse AI response. Please try again.');
        }

        return $decoded;
    }
}
