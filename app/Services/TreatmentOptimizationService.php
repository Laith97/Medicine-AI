<?php

namespace App\Services;

use App\Models\TreatmentOptimizationRecommendation;
use App\Models\PatientTreatmentResponse;
use App\Models\TreatmentPathway;
use App\Models\ClinicalIndicator;
use App\Models\Prescription;
use App\Models\Diagnosis;
use App\Models\User;
use App\Models\Appointment;
use App\Services\AIAssistant;
use App\Services\DrugInteractionService;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class TreatmentOptimizationService
{
    private AIAssistant $aiAssistant;
    private DrugInteractionService $drugInteractionService;

    public function __construct(AIAssistant $aiAssistant, DrugInteractionService $drugInteractionService)
    {
        $this->aiAssistant = $aiAssistant;
        $this->drugInteractionService = $drugInteractionService;
    }

    /**
     * Generate optimized treatment recommendations for a patient
     */
    public function generateTreatmentOptimization(
        int $patientId,
        int $appointmentId,
        array $currentConditions,
        array $patientDemographics = []
    ): TreatmentOptimizationRecommendation {
        
        // Get patient data
        $patient = User::findOrFail($patientId);
        $appointment = Appointment::findOrFail($appointmentId);
        
        // Analyze patient's treatment history
        $treatmentHistory = $this->analyzeTreatmentHistory($patientId);
        
        // Get current clinical indicators
        $clinicalIndicators = $this->getCurrentClinicalIndicators($patientId);
        
        // Get active prescriptions
        $activePrescriptions = Prescription::where('patient_id', $patientId)
            ->where('status', 'active')
            ->get();
        
        // Generate optimization recommendations
        $recommendations = $this->createOptimizedRecommendations(
            $patient,
            $currentConditions,
            $treatmentHistory,
            $clinicalIndicators,
            $activePrescriptions,
            $patientDemographics
        );
        
        // Calculate optimization scores
        $scores = $this->calculateOptimizationScores(
            $recommendations,
            $treatmentHistory,
            $clinicalIndicators
        );
        
        // Create the optimization record
        $optimization = TreatmentOptimizationRecommendation::create([
            'patient_id' => $patientId,
            'appointment_id' => $appointmentId,
            'ai_session_id' => uniqid('to_'),
            'recommended_medications' => $recommendations['medications'] ?? [],
            'alternative_medications' => $recommendations['alternatives'] ?? [],
            'dosage_adjustments' => $recommendations['dosage_adjustments'] ?? [],
            'timing_optimizations' => $recommendations['timing_optimizations'] ?? [],
            'outcome_predictions' => $recommendations['outcome_predictions'] ?? [],
            'risk_assessment' => $recommendations['risk_assessment'] ?? [],
            'adherence_factors' => $recommendations['adherence_factors'] ?? [],
            'effectiveness_score' => $scores['effectiveness'],
            'safety_score' => $scores['safety'],
            'cost_efficiency_score' => $scores['cost_efficiency'],
        ]);
        
        return $optimization;
    }

    /**
     * Analyze patient's treatment history for optimization
     */
    private function analyzeTreatmentHistory(int $patientId): array
    {
        $responses = PatientTreatmentResponse::where('patient_id', $patientId)
            ->orderBy('start_date', 'desc')
            ->limit(20) // Last 20 treatments
            ->get();

        $analysis = [
            'effective_treatments' => [],
            'ineffective_treatments' => [],
            'adverse_reactions' => [],
            'common_side_effects' => [],
            'adherence_patterns' => [],
            'average_adherence' => 0
        ];

        foreach ($responses as $response) {
            switch ($response->outcome) {
                case 'effective':
                    $analysis['effective_treatments'][] = [
                        'medication' => $response->medication_name,
                        'dosage' => $response->dosage,
                        'effectiveness_score' => $response->effectiveness_score,
                        'duration' => $response->duration
                    ];
                    break;
                case 'ineffective':
                    $analysis['ineffective_treatments'][] = [
                        'medication' => $response->medication_name,
                        'dosage' => $response->dosage,
                        'reason' => 'low_effectiveness',
                        'effectiveness_score' => $response->effectiveness_score
                    ];
                    break;
                case 'adverse_reaction':
                    $analysis['adverse_reactions'][] = [
                        'medication' => $response->medication_name,
                        'side_effects' => $response->side_effects,
                    ];
                    break;
            }

            // Track common side effects
            if (!empty($response->side_effects)) {
                foreach ($response->side_effects as $sideEffect) {
                    $analysis['common_side_effects'][$sideEffect] = 
                        ($analysis['common_side_effects'][$sideEffect] ?? 0) + 1;
                }
            }

            // Track adherence patterns
            if ($response->adherence_rate !== null) {
                $analysis['adherence_patterns'][] = $response->adherence_rate;
            }
        }

        // Calculate average adherence
        if (!empty($analysis['adherence_patterns'])) {
            $analysis['average_adherence'] = array_sum($analysis['adherence_patterns']) / 
                                           count($analysis['adherence_patterns']);
        }

        return $analysis;
    }

    /**
     * Get current clinical indicators for optimization
     */
    private function getCurrentClinicalIndicators(int $patientId): array
    {
        $indicators = ClinicalIndicator::where('patient_id', $patientId)
            ->where('measured_at', '>=', now()->subMonths(6))
            ->get();

        $vitals = [];
        $labValues = [];
        $symptoms = [];

        foreach ($indicators as $indicator) {
            switch ($indicator->type) {
                case 'vital_sign':
                    $vitals[$indicator->name] = [
                        'value' => $indicator->value,
                        'unit' => $indicator->unit,
                        'timestamp' => $indicator->measured_at
                    ];
                    break;
                case 'lab_result':
                    $labValues[$indicator->name] = [
                        'value' => $indicator->value,
                        'unit' => $indicator->unit,
                        'timestamp' => $indicator->measured_at
                    ];
                    break;
                case 'symptom':
                    $symptoms[$indicator->name] = [
                        'severity' => $indicator->value,
                        'timestamp' => $indicator->measured_at
                    ];
                    break;
            }
        }

        return [
            'vitals' => $vitals,
            'lab_values' => $labValues,
            'symptoms' => $symptoms
        ];
    }

    /**
     * Create optimized recommendations based on patient data
     */
    private function createOptimizedRecommendations(
        $patient,
        array $currentConditions,
        array $treatmentHistory,
        array $clinicalIndicators,
        $activePrescriptions,
        array $demographics
    ): array {
        
        // Use AI to generate initial recommendations based on conditions
        $aiRecommendations = $this->getAIRecommendations(
            $currentConditions,
            $treatmentHistory,
            $clinicalIndicators,
            $activePrescriptions,
            $demographics
        );

        return $aiRecommendations;
    }

    /**
     * Get AI-generated recommendations
     */
    private function getAIRecommendations(
        array $conditions,
        array $treatmentHistory,
        array $clinicalIndicators,
        $activePrescriptions,
        array $demographics
    ): array {
        
        $prompt = $this->buildOptimizationPrompt(
            $conditions,
            $treatmentHistory,
            $clinicalIndicators,
            $activePrescriptions,
            $demographics
        );

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert clinical decision support system specializing in treatment optimization. Provide evidence-based, personalized treatment recommendations considering patient history, demographics, and current clinical status. Respond ONLY with valid JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object']
            ]);

            $content = $response->choices[0]->message->content;
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from AI');
            }

            return $parsed;

        } catch (\Exception $e) {
            Log::error('Treatment Optimization AI Failed', [
                'error' => $e->getMessage(),
                'conditions' => $conditions
            ]);

            // Return fallback structure
            return [
                'medications' => [],
                'alternatives' => [],
                'dosage_adjustments' => [],
                'timing_optimizations' => [],
                'outcome_predictions' => [],
                'risk_assessment' => [],
                'adherence_factors' => []
            ];
        }
    }

    /**
     * Build optimization prompt for AI
     */
    private function buildOptimizationPrompt(
        array $conditions,
        array $treatmentHistory,
        array $clinicalIndicators,
        $activePrescriptions,
        array $demographics
    ): string {
        
        $prompt = "TREATMENT OPTIMIZATION ANALYSIS\n";
        $prompt .= "=============================\n\n";

        $prompt .= "PATIENT PROFILE:\n";
        $prompt .= "- Age: " . ($demographics['age'] ?? 'Unknown') . "\n";
        $prompt .= "- Gender: " . ($demographics['gender'] ?? 'Unknown') . "\n";
        $prompt .= "- Weight: " . ($demographics['weight'] ?? 'Unknown') . " kg\n";
        $prompt .= "- Height: " . ($demographics['height'] ?? 'Unknown') . " cm\n";
        $prompt .= "- Known allergies: " . implode(', ', $demographics['allergies'] ?? []) . "\n\n";

        $prompt .= "CURRENT CONDITIONS:\n";
        foreach ($conditions as $condition) {
            $prompt .= "- {$condition}\n";
        }
        $prompt .= "\n";

        $prompt .= "CLINICAL INDICATORS:\n";
        if (!empty($clinicalIndicators['vitals'])) {
            $prompt .= "Vital Signs:\n";
            foreach ($clinicalIndicators['vitals'] as $vital => $data) {
                $prompt .= "  - {$vital}: {$data['value']} {$data['unit']} ({$data['timestamp']})\n";
            }
        }
        if (!empty($clinicalIndicators['lab_values'])) {
            $prompt .= "Lab Values:\n";
            foreach ($clinicalIndicators['lab_values'] as $lab => $data) {
                $prompt .= "  - {$lab}: {$data['value']} {$data['unit']} ({$data['timestamp']})\n";
            }
        }
        if (!empty($clinicalIndicators['symptoms'])) {
            $prompt .= "Symptoms:\n";
            foreach ($clinicalIndicators['symptoms'] as $symptom => $data) {
                $prompt .= "  - {$symptom}: Severity {$data['severity']} ({$data['timestamp']})\n";
            }
        }
        $prompt .= "\n";

        $prompt .= "TREATMENT HISTORY:\n";
        if (!empty($treatmentHistory['effective_treatments'])) {
            $prompt .= "Effective Treatments:\n";
            foreach ($treatmentHistory['effective_treatments'] as $treatment) {
                $prompt .= "  - {$treatment['medication']} ({$treatment['dosage']}): Effectiveness {$treatment['effectiveness_score']}\n";
            }
        }
        if (!empty($treatmentHistory['ineffective_treatments'])) {
            $prompt .= "Ineffective Treatments:\n";
            foreach ($treatmentHistory['ineffective_treatments'] as $treatment) {
                $prompt .= "  - {$treatment['medication']} ({$treatment['dosage']}): {$treatment['reason']}\n";
            }
        }
        if (!empty($treatmentHistory['adverse_reactions'])) {
            $prompt .= "Adverse Reactions:\n";
            foreach ($treatmentHistory['adverse_reactions'] as $reaction) {
                $prompt .= "  - {$reaction['medication']}: " . implode(', ', $reaction['side_effects'] ?? []) . "\n";
            }
        }
        $prompt .= "\n";

        $prompt .= "ACTIVE PRESCRIPTIONS:\n";
        foreach ($activePrescriptions as $prescription) {
            $prompt .= "- {$prescription->medication_name} ({$prescription->dosage} {$prescription->frequency})\n";
        }
        $prompt .= "\n";

        $prompt .= "OPTIMIZATION REQUEST:\n";
        $prompt .= "Based on the patient profile and current conditions, provide:\n";
        $prompt .= "1. Recommended medications with dosages and justifications\n";
        $prompt .= "2. Alternative medication options\n";
        $prompt .= "3. Dosage adjustments if necessary\n";
        $prompt .= "4. Timing optimizations (e.g., morning vs evening)\n";
        $prompt .= "5. Outcome predictions (likelihood of success)\n";
        $prompt .= "6. Risk assessment (potential side effects or interactions)\n";
        $prompt .= "7. Adherence factors to watch for\n\n";
        $prompt .= "Respond ONLY with a JSON object containing these keys: medications, alternatives, dosage_adjustments, timing_optimizations, outcome_predictions, risk_assessment, adherence_factors.";

        return $prompt;
    }

    /**
     * Calculate optimization scores
     */
    private function calculateOptimizationScores(array $recommendations, array $treatmentHistory, array $clinicalIndicators): array
    {
        // This is a simplified scoring logic. In a real scenario, this would be more complex.
        $effectiveness = 0.85; // Default high score for AI recommendations
        $safety = 0.90;
        $costEfficiency = 0.75;

        // Adjust based on history
        if (!empty($treatmentHistory['adverse_reactions'])) {
            $safety -= 0.1;
        }

        if ($treatmentHistory['average_adherence'] < 0.7) {
            $effectiveness -= 0.15;
        }

        return [
            'effectiveness' => max(0, min(1, $effectiveness)),
            'safety' => max(0, min(1, $safety)),
            'cost_efficiency' => max(0, min(1, $costEfficiency))
        ];
    }
}
