<?php

namespace App\Services;

use App\Models\Diagnosis;
use App\Models\DoctorNote;
use App\Models\Exercise;
use App\Models\HepProgram;
use App\Models\HepExercise;
use App\Models\HepAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Collection;

class HEPGenerator
{
    protected $aiAssistant;
    protected $personalizationService;

    public function __construct(AIAssistant $aiAssistant, HEPPersonalizationService $personalizationService)
    {
        $this->aiAssistant = $aiAssistant;
        $this->personalizationService = $personalizationService;
    }

    /**
     * Generate a HEP program using AI based on diagnosis and clinical notes
     */
    public function generateProgram(
        Diagnosis $diagnosis,
        User $patient,
        User $doctor,
        array $additionalContext = []
    ): HepProgram {
        Log::info('Starting HEP program generation', [
            'diagnosis_id' => $diagnosis->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        // Extract clinical information
        $clinicalData = $this->extractClinicalInformation($diagnosis, $patient);

        // Generate AI-powered program recommendations
        $aiRecommendations = $this->generateAIRecommendations($clinicalData, $additionalContext);

        // Create the HEP program
        $program = $this->createProgramFromRecommendations(
            $aiRecommendations,
            $diagnosis,
            $patient,
            $doctor
        );

        // Apply personalization based on patient conditions
        $program = $this->personalizationService->personalizeProgram(
            $program,
            $diagnosis,
            $patient,
            $additionalContext
        );

        Log::info('HEP program generated and personalized successfully', [
            'program_id' => $program->id,
            'exercise_count' => $program->hepExercises()->count(),
        ]);

        return $program;
    }

    /**
     * Extract clinical information from diagnosis and related notes
     */
    protected function extractClinicalInformation(Diagnosis $diagnosis, User $patient): array
    {
        $clinicalNotes = $this->gatherClinicalNotes($diagnosis, $patient);
        $patientConditions = $this->extractPatientConditions($clinicalNotes);
        $functionalLimitations = $this->extractFunctionalLimitations($clinicalNotes);
        $treatmentGoals = $this->extractTreatmentGoals($clinicalNotes);

        return [
            'diagnosis_text' => $diagnosis->diagnosis_text,
            'clinical_notes' => $clinicalNotes,
            'patient_conditions' => $patientConditions,
            'functional_limitations' => $functionalLimitations,
            'treatment_goals' => $treatmentGoals,
            'patient_data' => $patient->patientData ?? null,
        ];
    }

    /**
     * Gather all relevant clinical notes for the diagnosis
     */
    protected function gatherClinicalNotes(Diagnosis $diagnosis, User $patient): Collection
    {
        $notes = collect();

        // Add diagnosis text
        $notes->push([
            'type' => 'diagnosis',
            'content' => $diagnosis->diagnosis_text,
            'date' => $diagnosis->created_at,
        ]);

        // Add doctor notes related to this diagnosis/patient
        $doctorNotes = DoctorNote::where('patient_id', $patient->id)
            ->where(function ($query) use ($diagnosis) {
                $query->where('appointment_id', $diagnosis->appointment_id)
                      ->orWhere('created_at', '>=', $diagnosis->created_at);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($doctorNotes as $note) {
            $notes->push([
                'type' => 'doctor_note',
                'content' => $note->note_text . ($note->transcript ? ' ' . $note->transcript : ''),
                'date' => $note->created_at,
                'category' => $note->category,
            ]);
        }

        return $notes;
    }

    /**
     * Extract patient conditions using NLP processing
     */
    protected function extractPatientConditions(Collection $clinicalNotes): array
    {
        $conditions = [];

        // Use AI to extract conditions from clinical notes
        $notesText = $clinicalNotes->pluck('content')->join(' ');

        if (!empty($notesText)) {
            $conditions = $this->extractConditionsWithAI($notesText);
        }

        return array_unique($conditions);
    }

    /**
     * Extract functional limitations from clinical notes
     */
    protected function extractFunctionalLimitations(Collection $clinicalNotes): array
    {
        $limitations = [];

        $notesText = $clinicalNotes->pluck('content')->join(' ');

        if (!empty($notesText)) {
            $limitations = $this->extractLimitationsWithAI($notesText);
        }

        return array_unique($limitations);
    }

    /**
     * Extract treatment goals from clinical notes
     */
    protected function extractTreatmentGoals(Collection $clinicalNotes): array
    {
        $goals = [];

        $notesText = $clinicalNotes->pluck('content')->join(' ');

        if (!empty($notesText)) {
            $goals = $this->extractGoalsWithAI($notesText);
        }

        return $goals;
    }

    /**
     * Generate AI-powered program recommendations
     */
    protected function generateAIRecommendations(array $clinicalData, array $additionalContext = []): array
    {
        $prompt = $this->buildHEPGenerationPrompt($clinicalData, $additionalContext);

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert physical therapist and rehabilitation specialist. You must respond ONLY with valid JSON containing exercise program recommendations. Format: {"program_title": "string", "duration_weeks": number, "frequency_per_week": number, "goals": ["array"], "precautions": ["array"], "exercises": [{"name": "string", "category": "string", "difficulty": "beginner|intermediate|advanced", "sets": number, "reps": number|null, "duration_seconds": number|null, "frequency": "string", "rationale": "string", "progression": "string"}]}'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.3,
            ]);

            $aiContent = $response->choices[0]->message->content;
            $parsedResponse = $this->validateAndParseJsonResponse($aiContent);

            return $parsedResponse;

        } catch (\Exception $e) {
            Log::error('AI HEP generation failed', [
                'error' => $e->getMessage(),
                'clinical_data' => $clinicalData,
            ]);

            // Return fallback recommendations
            return $this->generateFallbackRecommendations($clinicalData);
        }
    }

    /**
     * Build the AI prompt for HEP generation
     */
    protected function buildHEPGenerationPrompt(array $clinicalData, array $additionalContext = []): string
    {
        $prompt = "Generate a comprehensive Home Exercise Program (HEP) based on the following clinical information:\n\n";

        $prompt .= "DIAGNOSIS: {$clinicalData['diagnosis_text']}\n\n";

        if (!empty($clinicalData['patient_conditions'])) {
            $prompt .= "PATIENT CONDITIONS: " . implode(', ', $clinicalData['patient_conditions']) . "\n\n";
        }

        if (!empty($clinicalData['functional_limitations'])) {
            $prompt .= "FUNCTIONAL LIMITATIONS: " . implode(', ', $clinicalData['functional_limitations']) . "\n\n";
        }

        if (!empty($clinicalData['treatment_goals'])) {
            $prompt .= "TREATMENT GOALS: " . implode(', ', $clinicalData['treatment_goals']) . "\n\n";
        }

        $prompt .= "CLINICAL NOTES SUMMARY:\n";
        foreach ($clinicalData['clinical_notes'] as $note) {
            $prompt .= "- " . substr($note['content'], 0, 200) . "...\n";
        }
        $prompt .= "\n";

        if (isset($additionalContext['patient_age'])) {
            $prompt .= "PATIENT AGE: {$additionalContext['patient_age']}\n";
        }

        if (isset($additionalContext['patient_gender'])) {
            $prompt .= "PATIENT GENDER: {$additionalContext['patient_gender']}\n";
        }

        $prompt .= "\nINSTRUCTIONS:\n";
        $prompt .= "1. Design a progressive 4-8 week program appropriate for the diagnosis and patient condition\n";
        $prompt .= "2. Include 4-8 exercises per session, 3-5 sessions per week\n";
        $prompt .= "3. Ensure exercises are safe and evidence-based for the specific condition\n";
        $prompt .= "4. Include proper warm-up and cool-down recommendations in precautions\n";
        $prompt .= "5. Provide clear progression guidelines for each exercise\n";
        $prompt .= "6. Consider any contraindications and modify exercises accordingly\n";
        $prompt .= "7. Focus on functional improvement and pain management\n\n";

        $prompt .= "Return ONLY valid JSON with this exact structure:\n";
        $prompt .= "{\n";
        $prompt .= '  "program_title": "Descriptive program title",' . "\n";
        $prompt .= '  "duration_weeks": 6,' . "\n";
        $prompt .= '  "frequency_per_week": 4,' . "\n";
        $prompt .= '  "goals": ["Improve strength", "Reduce pain"],' . "\n";
        $prompt .= '  "precautions": ["Stop if pain increases", "Warm up before exercising"],' . "\n";
        $prompt .= '  "exercises": [' . "\n";
        $prompt .= '    {' . "\n";
        $prompt .= '      "name": "Exercise name",' . "\n";
        $prompt .= '      "category": "strength|flexibility|balance|cardiovascular",' . "\n";
        $prompt .= '      "difficulty": "beginner",' . "\n";
        $prompt .= '      "sets": 3,' . "\n";
        $prompt .= '      "reps": 10,' . "\n";
        $prompt .= '      "duration_seconds": null,' . "\n";
        $prompt .= '      "frequency": "daily",' . "\n";
        $prompt .= '      "rationale": "Why this exercise helps",' . "\n";
        $prompt .= '      "progression": "How to progress this exercise"' . "\n";
        $prompt .= '    }' . "\n";
        $prompt .= '  ]' . "\n";
        $prompt .= "}\n\n";

        $prompt .= "IMPORTANT: Respond with valid JSON only. No explanations or additional text.";

        return $prompt;
    }

    /**
     * Create HEP program from AI recommendations
     */
    protected function createProgramFromRecommendations(
        array $aiRecommendations,
        Diagnosis $diagnosis,
        User $patient,
        User $doctor
    ): HepProgram {
        // Create the program
        $program = HepProgram::create([
            'title' => $aiRecommendations['program_title'] ?? 'AI-Generated Home Exercise Program',
            'description' => $this->generateProgramDescription($aiRecommendations),
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'diagnosis_id' => $diagnosis->id,
            'appointment_id' => $diagnosis->appointment_id,
            'duration_weeks' => $aiRecommendations['duration_weeks'] ?? 6,
            'frequency_per_week' => $aiRecommendations['frequency_per_week'] ?? 4,
            'goals' => $aiRecommendations['goals'] ?? [],
            'precautions' => $aiRecommendations['precautions'] ?? [],
            'status' => 'active',
        ]);

        // Create exercises for the program
        $this->createProgramExercises($program, $aiRecommendations['exercises'] ?? []);

        return $program;
    }

    /**
     * Generate program description from AI recommendations
     */
    protected function generateProgramDescription(array $aiRecommendations): string
    {
        $description = "AI-generated home exercise program designed to address ";

        if (!empty($aiRecommendations['goals'])) {
            $description .= implode(' and ', $aiRecommendations['goals']);
        }

        $description .= ". This program includes {$aiRecommendations['duration_weeks']} weeks of progressive exercises to be performed {$aiRecommendations['frequency_per_week']} times per week.";

        return $description;
    }

    /**
     * Create program exercises from AI recommendations
     */
    protected function createProgramExercises(HepProgram $program, array $exerciseRecommendations): void
    {
        $order = 1;

        foreach ($exerciseRecommendations as $exerciseData) {
            // Try to find existing exercise or create new one
            $exercise = $this->findOrCreateExercise($exerciseData);

            // Create HEP exercise for each week with progression
            for ($week = 1; $week <= $program->duration_weeks; $week++) {
                $weekData = $this->adjustExerciseForWeek($exerciseData, $week);

                HepExercise::create([
                    'hep_program_id' => $program->id,
                    'exercise_id' => $exercise->id,
                    'sets' => $weekData['sets'],
                    'reps' => $weekData['reps'],
                    'duration_seconds' => $weekData['duration_seconds'],
                    'rest_seconds' => $weekData['rest_seconds'] ?? 60,
                    'frequency' => $weekData['frequency'],
                    'progression_notes' => $weekData['progression'],
                    'week_number' => $week,
                    'order' => $order,
                ]);
            }

            $order++;
        }
    }

    /**
     * Find existing exercise or create new one
     */
    protected function findOrCreateExercise(array $exerciseData): Exercise
    {
        // Try to find existing exercise by name
        $exercise = Exercise::where('name', $exerciseData['name'])->first();

        if (!$exercise) {
            // Create new exercise
            $exercise = Exercise::create([
                'name' => $exerciseData['name'],
                'description' => $exerciseData['rationale'] ?? 'AI-generated exercise',
                'category' => $exerciseData['category'] ?? 'functional',
                'difficulty_level' => $exerciseData['difficulty'] ?? 'intermediate',
                'instructions' => $this->generateExerciseInstructions($exerciseData),
                'contraindications' => $this->extractContraindications($exerciseData),
                'target_muscle_groups' => $this->extractMuscleGroups($exerciseData),
                'duration' => $exerciseData['duration_seconds'] ?? 60,
            ]);
        }

        return $exercise;
    }

    /**
     * Adjust exercise parameters for specific week (progression)
     */
    protected function adjustExerciseForWeek(array $exerciseData, int $week): array
    {
        $baseData = $exerciseData;

        // Simple progression logic - increase reps/sets over weeks
        if ($week > 1) {
            if (isset($baseData['reps']) && $baseData['reps']) {
                $baseData['reps'] = min($baseData['reps'] + ($week - 1), $baseData['reps'] * 2);
            }
            if (isset($baseData['sets']) && $baseData['sets']) {
                $baseData['sets'] = min($baseData['sets'] + floor(($week - 1) / 2), $baseData['sets'] + 2);
            }
            if (isset($baseData['duration_seconds']) && $baseData['duration_seconds']) {
                $baseData['duration_seconds'] = min(
                    $baseData['duration_seconds'] + ($week - 1) * 10,
                    $baseData['duration_seconds'] * 1.5
                );
            }
        }

        return $baseData;
    }

    /**
     * Generate compliance document for the HEP program
     */
    public function generateComplianceDocument(HepProgram $program): string
    {
        $document = $this->buildComplianceDocumentPrompt($program);

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical documentation specialist. Generate a professional compliance document for a Home Exercise Program. Include all necessary medical disclaimers, instructions, and legal language.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $document
                    ]
                ],
                'max_tokens' => 1500,
                'temperature' => 0.2,
            ]);

            return $response->choices[0]->message->content;

        } catch (\Exception $e) {
            Log::error('Compliance document generation failed', [
                'error' => $e->getMessage(),
                'program_id' => $program->id,
            ]);

            return $this->generateFallbackComplianceDocument($program);
        }
    }

    /**
     * Extract patient conditions using AI
     */
    protected function extractConditionsWithAI(string $notesText): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Extract medical conditions and diagnoses from clinical notes. Return only a JSON array of condition names.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract all medical conditions, diagnoses, and health issues from this text: {$notesText}\n\nReturn as JSON array: [\"condition1\", \"condition2\"]"
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.1,
            ]);

            $content = $response->choices[0]->message->content;
            $parsed = json_decode($content, true);

            return is_array($parsed) ? $parsed : [];

        } catch (\Exception $e) {
            Log::error('Condition extraction failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract functional limitations using AI
     */
    protected function extractLimitationsWithAI(string $notesText): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Extract functional limitations and impairments from clinical notes. Return only a JSON array of limitations.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract functional limitations, impairments, and mobility issues from this text: {$notesText}\n\nReturn as JSON array: [\"limitation1\", \"limitation2\"]"
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.1,
            ]);

            $content = $response->choices[0]->message->content;
            $parsed = json_decode($content, true);

            return is_array($parsed) ? $parsed : [];

        } catch (\Exception $e) {
            Log::error('Limitation extraction failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract treatment goals using AI
     */
    protected function extractGoalsWithAI(string $notesText): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Extract treatment goals and rehabilitation objectives from clinical notes. Return only a JSON array of goals.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract treatment goals, rehabilitation objectives, and desired outcomes from this text: {$notesText}\n\nReturn as JSON array: [\"goal1\", \"goal2\"]"
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.1,
            ]);

            $content = $response->choices[0]->message->content;
            $parsed = json_decode($content, true);

            return is_array($parsed) ? $parsed : [];

        } catch (\Exception $e) {
            Log::error('Goal extraction failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Validate and parse JSON response from AI
     */
    protected function validateAndParseJsonResponse(string $aiContent): array
    {
        // Clean the content
        $cleanContent = trim($aiContent);

        // Remove markdown code blocks if present
        if (strpos($cleanContent, '```json') === 0) {
            $cleanContent = substr($cleanContent, 7);
        }
        if (strpos($cleanContent, '```') === 0) {
            $cleanContent = substr($cleanContent, 3);
        }
        if (str_ends_with($cleanContent, '```')) {
            $cleanContent = substr($cleanContent, 0, -3);
        }

        $cleanContent = trim($cleanContent);

        // Try to parse JSON
        $parsed = json_decode($cleanContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

        // Validate required structure
        if (!is_array($parsed) ||
            !isset($parsed['program_title']) ||
            !isset($parsed['exercises'])) {
            throw new \Exception('Response missing required fields');
        }

        return $parsed;
    }

    /**
     * Generate fallback recommendations when AI fails
     */
    protected function generateFallbackRecommendations(array $clinicalData): array
    {
        // Basic fallback program structure
        return [
            'program_title' => 'Basic Home Exercise Program',
            'duration_weeks' => 4,
            'frequency_per_week' => 3,
            'goals' => ['Improve mobility', 'Reduce pain', 'Increase strength'],
            'precautions' => [
                'Stop if pain increases significantly',
                'Consult healthcare provider before starting',
                'Warm up before exercising',
                'Cool down after exercising'
            ],
            'exercises' => [
                [
                    'name' => 'Gentle Walking',
                    'category' => 'cardiovascular',
                    'difficulty' => 'beginner',
                    'sets' => 1,
                    'reps' => null,
                    'duration_seconds' => 600, // 10 minutes
                    'frequency' => 'daily',
                    'rationale' => 'Improves cardiovascular health and mobility',
                    'progression' => 'Increase duration by 2 minutes each week'
                ],
                [
                    'name' => 'Seated Leg Lifts',
                    'category' => 'strength',
                    'difficulty' => 'beginner',
                    'sets' => 2,
                    'reps' => 10,
                    'duration_seconds' => null,
                    'frequency' => 'daily',
                    'rationale' => 'Strengthens lower body muscles',
                    'progression' => 'Increase reps by 2 each week'
                ]
            ]
        ];
    }

    /**
     * Generate exercise instructions
     */
    protected function generateExerciseInstructions(array $exerciseData): string
    {
        $instructions = "Perform {$exerciseData['name']} ";

        if (isset($exerciseData['sets']) && isset($exerciseData['reps'])) {
            $instructions .= "{$exerciseData['sets']} sets of {$exerciseData['reps']} repetitions";
        } elseif (isset($exerciseData['duration_seconds'])) {
            $minutes = floor($exerciseData['duration_seconds'] / 60);
            $seconds = $exerciseData['duration_seconds'] % 60;
            $instructions .= "for {$minutes} minutes {$seconds} seconds";
        }

        $instructions .= ". " . (isset($exerciseData['rationale']) ? $exerciseData['rationale'] : '');

        if (isset($exerciseData['progression'])) {
            $instructions .= " Progression: {$exerciseData['progression']}";
        }

        return $instructions;
    }

    /**
     * Extract contraindications from exercise data
     */
    protected function extractContraindications(array $exerciseData): array
    {
        // This would use AI to determine contraindications based on exercise type
        // For now, return basic contraindications
        $contraindications = [];

        if (isset($exerciseData['category'])) {
            switch ($exerciseData['category']) {
                case 'strength':
                    $contraindications = ['acute injury', 'severe pain', 'unstable fractures'];
                    break;
                case 'cardiovascular':
                    $contraindications = ['uncontrolled hypertension', 'recent cardiac event'];
                    break;
                case 'flexibility':
                    $contraindications = ['acute inflammation', 'joint instability'];
                    break;
            }
        }

        return $contraindications;
    }

    /**
     * Extract target muscle groups from exercise data
     */
    protected function extractMuscleGroups(array $exerciseData): array
    {
        // This would use AI to determine muscle groups
        // For now, return basic muscle groups based on category
        $muscleGroups = [];

        if (isset($exerciseData['category'])) {
            switch ($exerciseData['category']) {
                case 'strength':
                    $muscleGroups = ['quadriceps', 'hamstrings', 'calves'];
                    break;
                case 'cardiovascular':
                    $muscleGroups = ['cardiovascular system'];
                    break;
                case 'flexibility':
                    $muscleGroups = ['various muscle groups'];
                    break;
            }
        }

        return $muscleGroups;
    }

    /**
     * Build compliance document prompt
     */
    protected function buildComplianceDocumentPrompt(HepProgram $program): string
    {
        $prompt = "Generate a professional compliance document for this Home Exercise Program:\n\n";
        $prompt .= "PROGRAM TITLE: {$program->title}\n";
        $prompt .= "DIAGNOSIS: {$program->diagnosis->diagnosis_text}\n";
        $prompt .= "DURATION: {$program->duration_weeks} weeks\n";
        $prompt .= "FREQUENCY: {$program->frequency_per_week} times per week\n\n";

        $prompt .= "GOALS:\n";
        foreach ($program->goals as $goal) {
            $prompt .= "- {$goal}\n";
        }
        $prompt .= "\n";

        $prompt .= "PRECAUTIONS:\n";
        foreach ($program->precautions as $precaution) {
            $prompt .= "- {$precaution}\n";
        }
        $prompt .= "\n";

        $prompt .= "EXERCISES:\n";
        foreach ($program->hepExercises->groupBy('week_number') as $week => $exercises) {
            $prompt .= "Week {$week}:\n";
            foreach ($exercises as $exercise) {
                $prompt .= "- {$exercise->exercise->name}: {$exercise->sets} sets";
                if ($exercise->reps) {
                    $prompt .= " of {$exercise->reps} reps";
                }
                if ($exercise->duration_seconds) {
                    $minutes = floor($exercise->duration_seconds / 60);
                    $prompt .= " for {$minutes} minutes";
                }
                $prompt .= "\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "\nGenerate a professional medical compliance document that includes:\n";
        $prompt .= "1. Patient consent and acknowledgment\n";
        $prompt .= "2. Medical disclaimers and liability statements\n";
        $prompt .= "3. Instructions for proper exercise execution\n";
        $prompt .= "4. When to stop and seek medical attention\n";
        $prompt .= "5. Progress tracking requirements\n";
        $prompt .= "6. Contact information for healthcare provider\n";

        return $prompt;
    }

    /**
     * Generate fallback compliance document
     */
    protected function generateFallbackComplianceDocument(HepProgram $program): string
    {
        return "
HOME EXERCISE PROGRAM COMPLIANCE DOCUMENT

Program: {$program->title}
Date: " . now()->format('F j, Y') . "

PATIENT CONSENT AND ACKNOWLEDGMENT

I, the undersigned patient, acknowledge that I have received and understand the Home Exercise Program prescribed by my healthcare provider. I agree to follow all instructions and precautions outlined in this program.

Patient Signature: ___________________________ Date: __________

HEALTHCARE PROVIDER CERTIFICATION

I certify that this Home Exercise Program is medically appropriate for the patient's current condition and that all exercises have been selected based on clinical assessment.

Provider Signature: ___________________________ Date: __________

MEDICAL DISCLAIMER

This program is designed for the specific needs of the individual patient. The exercises should only be performed as directed. Stop immediately if you experience increased pain, dizziness, shortness of breath, or any unusual symptoms. Contact your healthcare provider immediately if any adverse reactions occur.

PROGRESS TRACKING

Patient agrees to track exercise completion and report progress to healthcare provider as requested.

Emergency Contact: Contact healthcare provider or emergency services if severe symptoms develop.
        ";
    }
}
