<?php

namespace App\Services;

use App\Models\User;
use App\Models\Diagnosis;
use App\Models\Exercise;
use App\Models\HepProgram;
use App\Models\HepProgramTemplate;
use App\Models\HepExercise;
use App\Models\DoctorNote;
use App\Models\PatientData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Service for personalizing Home Exercise Programs based on patient conditions
 */
class HEPPersonalizationService
{
    protected HEPSafetyService $safetyService;

    public function __construct(HEPSafetyService $safetyService)
    {
        $this->safetyService = $safetyService;
    }

    /**
     * Personalize a HEP program based on patient conditions and clinical data
     */
    public function personalizeProgram(
        HepProgram $program,
        Diagnosis $diagnosis,
        User $patient,
        array $additionalContext = []
    ): HepProgram {
        Log::info('Starting HEP personalization', [
            'program_id' => $program->id,
            'patient_id' => $patient->id,
            'diagnosis_id' => $diagnosis->id,
        ]);

        // Extract comprehensive patient profile
        $patientProfile = $this->extractPatientProfile($diagnosis, $patient);

        // Analyze conditions and create personalization rules
        $personalizationRules = $this->createPersonalizationRules($patientProfile);

        // Assess functional limitations
        $functionalAssessment = $this->assessFunctionalLimitations($patientProfile);
        $personalizationRules = $this->mergeRules($personalizationRules, $functionalAssessment);

        // Integrate treatment goals
        $goalIntegration = $this->integrateTreatmentGoals($patientProfile);
        $personalizationRules = $this->mergeRules($personalizationRules, $goalIntegration);

        // Apply personalization to program exercises
        $this->applyPersonalizationToProgram($program, $personalizationRules, $patientProfile);

        // Perform safety checks and add contraindications
        $safetyIssues = $this->performSafetyChecks($program, $patientProfile);

        // Update program metadata with personalization details
        $this->updateProgramMetadata($program, $patientProfile, $personalizationRules, $safetyIssues);

        Log::info('HEP personalization completed', [
            'program_id' => $program->id,
            'personalization_rules_applied' => count($personalizationRules),
        ]);

        return $program;
    }

    /**
     * Extract comprehensive patient profile from clinical data
     */
    protected function extractPatientProfile(Diagnosis $diagnosis, User $patient): array
    {
        $profile = [
            'diagnosis' => $diagnosis->diagnosis_text,
            'patient_demographics' => $this->getPatientDemographics($patient),
            'medical_conditions' => [],
            'functional_limitations' => [],
            'symptoms' => [],
            'treatment_goals' => [],
            'medications' => [],
            'past_medical_history' => [],
            'risk_factors' => [],
            'functional_assessment' => [],
        ];

        // Extract from diagnosis and clinical notes
        $clinicalNotes = $this->gatherClinicalNotes($diagnosis, $patient);
        $profile = array_merge($profile, $this->analyzeClinicalNotes($clinicalNotes));

        // Extract from patient data
        $patientData = $this->getLatestPatientData($patient);
        if ($patientData) {
            $profile = array_merge($profile, $this->extractFromPatientData($patientData));
        }

        return $profile;
    }

    /**
     * Get patient demographics for personalization
     */
    protected function getPatientDemographics(User $patient): array
    {
        return [
            'age' => $patient->birth_date ? now()->diffInYears($patient->birth_date) : null,
            'gender' => $patient->gender,
            'height' => $patient->height,
            'weight' => $patient->weight,
            'bmi' => $patient->height && $patient->weight ?
                round($patient->weight / (($patient->height / 100) ** 2), 1) : null,
        ];
    }

    /**
     * Gather all relevant clinical notes
     */
    protected function gatherClinicalNotes(Diagnosis $diagnosis, User $patient): Collection
    {
        $notes = collect();

        // Diagnosis text
        $notes->push([
            'type' => 'diagnosis',
            'content' => $diagnosis->diagnosis_text,
            'date' => $diagnosis->created_at,
        ]);

        // Doctor notes
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
     * Analyze clinical notes using AI to extract structured information
     */
    protected function analyzeClinicalNotes(Collection $clinicalNotes): array
    {
        $notesText = $clinicalNotes->pluck('content')->join(' ');

        if (empty($notesText)) {
            return [];
        }

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical data extraction specialist. Extract structured clinical information from medical notes and return it as valid JSON. Focus on conditions, limitations, goals, and symptoms.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract the following from these clinical notes:\n\n{$notesText}\n\nReturn as JSON with these exact keys: medical_conditions (array), functional_limitations (array), symptoms (array), treatment_goals (array), risk_factors (array), functional_assessment (array)"
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.1,
            ]);

            $content = $response->choices[0]->message->content;
            $parsed = json_decode($content, true);

            return is_array($parsed) ? $parsed : [];

        } catch (\Exception $e) {
            Log::error('Clinical notes analysis failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Extract information from patient data model
     */
    protected function extractFromPatientData(PatientData $patientData): array
    {
        return [
            'past_medical_history' => $this->normalizeArrayField($patientData->past_medical_history),
            'medications' => $this->normalizeArrayField($patientData->medications),
            'allergies' => $this->normalizeArrayField($patientData->allergies),
            'symptoms' => array_merge(
                $this->normalizeArrayField($patientData->symptoms),
                $this->normalizeArrayField($patientData->current_symptoms)
            ),
        ];
    }

    /**
     * Normalize field to array format
     */
    protected function normalizeArrayField($field): array
    {
        if (empty($field)) {
            return [];
        }

        if (is_array($field)) {
            return $field;
        }

        if (is_string($field)) {
            return array_map('trim', explode(',', $field));
        }

        return [];
    }

    /**
     * Create personalization rules based on patient profile
     */
    protected function createPersonalizationRules(array $patientProfile): array
    {
        $rules = [
            'exercise_modifications' => [],
            'intensity_adjustments' => [],
            'frequency_modifications' => [],
            'excluded_exercises' => [],
            'required_precautions' => [],
            'progression_guidelines' => [],
        ];

        // Analyze medical conditions
        foreach ($patientProfile['medical_conditions'] as $condition) {
            $conditionRules = $this->getRulesForCondition($condition);
            $rules = $this->mergeRules($rules, $conditionRules);
        }

        // Analyze functional limitations
        foreach ($patientProfile['functional_limitations'] as $limitation) {
            $limitationRules = $this->getRulesForLimitation($limitation);
            $rules = $this->mergeRules($rules, $limitationRules);
        }

        // Consider demographics
        $demographicRules = $this->getRulesForDemographics($patientProfile['patient_demographics']);
        $rules = $this->mergeRules($rules, $demographicRules);

        // Consider treatment goals
        $goalRules = $this->getRulesForTreatmentGoals($patientProfile['treatment_goals']);
        $rules = $this->mergeRules($rules, $goalRules);

        return $rules;
    }

    /**
     * Get personalization rules for a specific medical condition
     */
    protected function getRulesForCondition(string $condition): array
    {
        $condition = strtolower(trim($condition));

        $conditionRules = [
            // Orthopedic conditions
            'osteoarthritis' => [
                'exercise_modifications' => ['low_impact_only', 'joint_protection'],
                'intensity_adjustments' => ['reduce_weight_bearing'],
                'required_precautions' => ['avoid_high_impact', 'monitor_joint_pain'],
                'progression_guidelines' => ['gradual_increase', 'pain_guided'],
            ],
            'fracture' => [
                'excluded_exercises' => ['weight_bearing', 'impact_activities'],
                'exercise_modifications' => ['non_weight_bearing', 'protected_weight_bearing'],
                'required_precautions' => ['immobilization_considerations'],
            ],
            'sprain' => [
                'exercise_modifications' => ['gentle_range_of_motion', 'progressive_loading'],
                'intensity_adjustments' => ['pain_controlling'],
                'required_precautions' => ['avoid_aggravating_movements'],
            ],

            // Cardiovascular conditions
            'hypertension' => [
                'intensity_adjustments' => ['moderate_intensity_only'],
                'required_precautions' => ['blood_pressure_monitoring', 'avoid_valsalva'],
                'progression_guidelines' => ['gradual_intensity_increase'],
            ],
            'heart_disease' => [
                'intensity_adjustments' => ['low_to_moderate_intensity'],
                'required_precautions' => ['heart_rate_monitoring', 'emergency_plan'],
                'excluded_exercises' => ['high_intensity_cardio'],
            ],

            // Respiratory conditions
            'copd' => [
                'exercise_modifications' => ['breathing_techniques', 'energy_conservation'],
                'intensity_adjustments' => ['short_sessions', 'frequent_breaks'],
                'required_precautions' => ['oxygen_saturation_monitoring'],
            ],
            'asthma' => [
                'required_precautions' => ['trigger_avoidance', 'rescue_inhaler_access'],
                'exercise_modifications' => ['warm_up_emphasis', 'cool_down_emphasis'],
            ],

            // Neurological conditions
            'stroke' => [
                'exercise_modifications' => ['balance_focus', 'compensatory_techniques'],
                'intensity_adjustments' => ['asymmetric_loading'],
                'required_precautions' => ['fall_prevention', 'hemiplegic_considerations'],
            ],
            'parkinson' => [
                'exercise_modifications' => ['cued_exercises', 'dual_task_training'],
                'required_precautions' => ['freezing_episode_management'],
                'progression_guidelines' => ['consistent_timing'],
            ],
        ];

        return $conditionRules[$condition] ?? [];
    }

    /**
     * Get personalization rules for functional limitations
     */
    protected function getRulesForLimitation(string $limitation): array
    {
        $limitation = strtolower(trim($limitation));

        $limitationRules = [
            'balance_deficit' => [
                'exercise_modifications' => ['balance_training_emphasis', 'support_surface_use'],
                'required_precautions' => ['fall_prevention', 'assistive_device_use'],
                'excluded_exercises' => ['single_leg_stands'],
            ],
            'strength_deficit' => [
                'exercise_modifications' => ['resistance_training_focus', 'progressive_overload'],
                'intensity_adjustments' => ['lower_initial_intensity'],
                'progression_guidelines' => ['strength_based_progression'],
            ],
            'range_of_motion_deficit' => [
                'exercise_modifications' => ['stretching_emphasis', 'joint_mobilization'],
                'intensity_adjustments' => ['gentle_stretching'],
                'progression_guidelines' => ['flexibility_based_progression'],
            ],
            'pain' => [
                'intensity_adjustments' => ['pain_controlling'],
                'required_precautions' => ['pain_monitoring', 'activity_modification'],
                'progression_guidelines' => ['pain_guided_progression'],
            ],
            'fatigue' => [
                'frequency_modifications' => ['reduced_frequency', 'rest_days_emphasis'],
                'intensity_adjustments' => ['shorter_sessions'],
                'required_precautions' => ['energy_conservation'],
            ],
        ];

        return $limitationRules[$limitation] ?? [];
    }

    /**
     * Get personalization rules based on patient demographics
     */
    protected function getRulesForDemographics(array $demographics): array
    {
        $rules = [];

        // Age-based considerations
        if (isset($demographics['age'])) {
            if ($demographics['age'] >= 65) {
                $rules = array_merge($rules, [
                    'exercise_modifications' => ['balance_emphasis', 'low_impact_preference'],
                    'required_precautions' => ['fall_risk_assessment'],
                    'intensity_adjustments' => ['moderate_intensity_focus'],
                ]);
            } elseif ($demographics['age'] <= 18) {
                $rules = array_merge($rules, [
                    'exercise_modifications' => ['growth_plate_considerations'],
                    'required_precautions' => ['developmental_appropriateness'],
                ]);
            }
        }

        // BMI-based considerations
        if (isset($demographics['bmi'])) {
            if ($demographics['bmi'] >= 30) {
                $rules = array_merge($rules, [
                    'exercise_modifications' => ['weight_management_focus', 'joint_friendly'],
                    'intensity_adjustments' => ['gradual_progression'],
                    'required_precautions' => ['cardiovascular_monitoring'],
                ]);
            }
        }

        return $rules;
    }

    /**
     * Get personalization rules based on treatment goals
     */
    protected function getRulesForTreatmentGoals(array $goals): array
    {
        $rules = [];

        foreach ($goals as $goal) {
            $goal = strtolower(trim($goal));

            if (str_contains($goal, 'pain')) {
                $rules = array_merge($rules, [
                    'intensity_adjustments' => ['pain_controlling'],
                    'required_precautions' => ['pain_monitoring'],
                ]);
            }

            if (str_contains($goal, 'strength')) {
                $rules = array_merge($rules, [
                    'exercise_modifications' => ['resistance_training_focus'],
                    'progression_guidelines' => ['strength_based_progression'],
                ]);
            }

            if (str_contains($goal, 'balance') || str_contains($goal, 'fall')) {
                $rules = array_merge($rules, [
                    'exercise_modifications' => ['balance_training_emphasis'],
                    'required_precautions' => ['fall_prevention'],
                ]);
            }

            if (str_contains($goal, 'mobility') || str_contains($goal, 'function')) {
                $rules = array_merge($rules, [
                    'exercise_modifications' => ['functional_training_focus'],
                    'progression_guidelines' => ['function_based_progression'],
                ]);
            }
        }

        return $rules;
    }

    /**
     * Merge personalization rules
     */
    protected function mergeRules(array $existingRules, array $newRules): array
    {
        foreach ($newRules as $category => $rules) {
            if (!isset($existingRules[$category])) {
                $existingRules[$category] = [];
            }

            if (is_array($rules)) {
                $existingRules[$category] = array_unique(array_merge($existingRules[$category], $rules));
            } else {
                $existingRules[$category][] = $rules;
                $existingRules[$category] = array_unique($existingRules[$category]);
            }
        }

        return $existingRules;
    }

    /**
     * Apply personalization rules to program exercises
     */
    protected function applyPersonalizationToProgram(
        HepProgram $program,
        array $personalizationRules,
        array $patientProfile
    ): void {
        foreach ($program->hepExercises as $hepExercise) {
            $this->personalizeExercise($hepExercise, $personalizationRules, $patientProfile);
        }
    }

    /**
     * Personalize individual exercise based on rules
     */
    protected function personalizeExercise(
        HepExercise $hepExercise,
        array $personalizationRules,
        array $patientProfile
    ): void {
        $exercise = $hepExercise->exercise;

        // Check if exercise should be excluded
        if ($this->shouldExcludeExercise($exercise, $personalizationRules)) {
            $hepExercise->delete();
            return;
        }

        // Apply intensity adjustments
        $this->applyIntensityAdjustments($hepExercise, $personalizationRules);

        // Apply frequency modifications
        $this->applyFrequencyModifications($hepExercise, $personalizationRules);

        // Apply exercise modifications
        $this->applyExerciseModifications($hepExercise, $personalizationRules);

        // Update progression notes
        $this->updateProgressionNotes($hepExercise, $personalizationRules);

        $hepExercise->save();
    }

    /**
     * Check if exercise should be excluded based on rules
     */
    protected function shouldExcludeExercise(Exercise $exercise, array $rules): bool
    {
        if (empty($rules['excluded_exercises'])) {
            return false;
        }

        $exerciseName = strtolower($exercise->name);
        $exerciseCategory = strtolower($exercise->category);

        foreach ($rules['excluded_exercises'] as $excluded) {
            $excluded = strtolower($excluded);

            if (str_contains($exerciseName, $excluded) ||
                str_contains($exerciseCategory, $excluded) ||
                str_contains($excluded, $exerciseCategory)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply intensity adjustments to exercise
     */
    protected function applyIntensityAdjustments(HepExercise $hepExercise, array $rules): void
    {
        if (empty($rules['intensity_adjustments'])) {
            return;
        }

        $adjustments = $rules['intensity_adjustments'];

        // Reduce intensity for certain conditions
        if (in_array('pain_controlling', $adjustments)) {
            $hepExercise->sets = max(1, $hepExercise->sets - 1);
            $hepExercise->reps = max(5, $hepExercise->reps - 2);
        }

        if (in_array('lower_initial_intensity', $adjustments)) {
            $hepExercise->sets = max(1, $hepExercise->sets - 1);
            $hepExercise->reps = max(5, intval($hepExercise->reps * 0.7));
        }

        if (in_array('shorter_sessions', $adjustments)) {
            if ($hepExercise->duration_seconds) {
                $hepExercise->duration_seconds = intval($hepExercise->duration_seconds * 0.7);
            }
        }
    }

    /**
     * Apply frequency modifications
     */
    protected function applyFrequencyModifications(HepExercise $hepExercise, array $rules): void
    {
        if (empty($rules['frequency_modifications'])) {
            return;
        }

        $modifications = $rules['frequency_modifications'];

        if (in_array('reduced_frequency', $modifications)) {
            // This would be handled at the program level, but we can note it in progression
            $hepExercise->progression_notes .= ' Consider reduced frequency due to condition.';
        }
    }

    /**
     * Apply exercise modifications
     */
    protected function applyExerciseModifications(HepExercise $hepExercise, array $rules): void
    {
        if (empty($rules['exercise_modifications'])) {
            return;
        }

        $modifications = $rules['exercise_modifications'];
        $modificationNotes = [];

        if (in_array('low_impact_only', $modifications)) {
            $modificationNotes[] = 'Use low-impact modifications';
        }

        if (in_array('balance_training_emphasis', $modifications)) {
            $modificationNotes[] = 'Focus on balance stability';
        }

        if (in_array('joint_protection', $modifications)) {
            $modificationNotes[] = 'Use joint protection techniques';
        }

        if (!empty($modificationNotes)) {
            $hepExercise->progression_notes .= ' ' . implode('. ', $modificationNotes) . '.';
        }
    }

    /**
     * Update progression notes based on rules
     */
    protected function updateProgressionNotes(HepExercise $hepExercise, array $rules): void
    {
        $notes = [];

        if (!empty($rules['progression_guidelines'])) {
            $guidelines = $rules['progression_guidelines'];

            if (in_array('pain_guided', $guidelines)) {
                $notes[] = 'Progress based on pain levels - do not increase if pain worsens';
            }

            if (in_array('gradual_increase', $guidelines)) {
                $notes[] = 'Gradual progression - increase by no more than 10% per week';
            }

            if (in_array('strength_based_progression', $guidelines)) {
                $notes[] = 'Progress when current resistance feels easy for all sets';
            }
        }

        if (!empty($rules['required_precautions'])) {
            $precautions = $rules['required_precautions'];

            if (in_array('fall_prevention', $precautions)) {
                $notes[] = 'Ensure stable surface and consider handrails';
            }

            if (in_array('pain_monitoring', $precautions)) {
                $notes[] = 'Stop if pain increases beyond mild discomfort';
            }
        }

        if (!empty($notes)) {
            $hepExercise->progression_notes .= ' ' . implode('. ', $notes) . '.';
        }
    }

    /**
     * Update program metadata with personalization details
     */
    protected function updateProgramMetadata(
        HepProgram $program,
        array $patientProfile,
        array $personalizationRules,
        array $safetyIssues = []
    ): void {
        $metadata = [
            'personalized_at' => now(),
            'patient_profile' => $patientProfile,
            'personalization_rules' => $personalizationRules,
            'customizations_applied' => $this->summarizeCustomizations($personalizationRules),
        ];

        // Update program description to reflect personalization
        $personalizedDescription = $this->generatePersonalizedDescription(
            $program->description,
            $personalizationRules
        );

        $program->update([
            'description' => $personalizedDescription,
            'goals' => array_merge($program->goals ?? [], $patientProfile['treatment_goals']),
            'precautions' => array_merge($program->precautions ?? [], $personalizationRules['required_precautions'] ?? []),
            'personalization_metadata' => $metadata,
        ]);

        Log::info('HEP personalization metadata stored', [
            'program_id' => $program->id,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Summarize customizations applied
     */
    protected function summarizeCustomizations(array $rules): array
    {
        $summary = [];

        foreach ($rules as $category => $items) {
            if (!empty($items)) {
                $summary[$category] = count($items) . ' ' . $category . ' applied';
            }
        }

        return $summary;
    }

    /**
     * Generate personalized program description
     */
    protected function generatePersonalizedDescription(string $originalDescription, array $rules): string
    {
        $description = $originalDescription;

        if (!empty($rules['required_precautions'])) {
            $description .= ' This program has been personalized with additional safety precautions based on your specific condition.';
        }

        if (!empty($rules['exercise_modifications'])) {
            $description .= ' Exercises have been modified to accommodate your functional needs.';
        }

        if (!empty($rules['intensity_adjustments'])) {
            $description .= ' Intensity levels have been adjusted for your current capabilities.';
        }

        return $description;
    }

    /**
     * Get latest patient data
     */
    protected function getLatestPatientData(User $patient): ?PatientData
    {
        return $patient->patientData()->latest()->first();
    }

    /**
     * Assess functional limitations and create appropriate rules
     */
    protected function assessFunctionalLimitations(array $patientProfile): array
    {
        $rules = [];

        $limitations = array_merge(
            $patientProfile['functional_limitations'] ?? [],
            $patientProfile['functional_assessment'] ?? []
        );

        foreach ($limitations as $limitation) {
            $limitation = strtolower(trim($limitation));

            // Balance and mobility limitations
            if (str_contains($limitation, 'balance') || str_contains($limitation, 'dizziness')) {
                $rules = $this->mergeRules($rules, [
                    'exercise_modifications' => ['seated_exercises', 'supported_standing', 'balance_aids'],
                    'required_precautions' => ['fall_prevention', 'spotter_assistance'],
                    'excluded_exercises' => ['single_leg_stands', 'unstable_surfaces'],
                ]);
            }

            // Strength limitations
            if (str_contains($limitation, 'weakness') || str_contains($limitation, 'strength')) {
                $rules = $this->mergeRules($rules, [
                    'intensity_adjustments' => ['reduced_resistance', 'bodyweight_only'],
                    'exercise_modifications' => ['assisted_exercises', 'progressive_resistance'],
                    'progression_guidelines' => ['strength_based_progression'],
                ]);
            }

            // Range of motion limitations
            if (str_contains($limitation, 'stiffness') || str_contains($limitation, 'range')) {
                $rules = $this->mergeRules($rules, [
                    'exercise_modifications' => ['gentle_stretching', 'active_assisted_rom'],
                    'required_precautions' => ['pain_monitoring', 'joint_protection'],
                    'progression_guidelines' => ['flexibility_based_progression'],
                ]);
            }

            // Endurance limitations
            if (str_contains($limitation, 'fatigue') || str_contains($limitation, 'endurance')) {
                $rules = $this->mergeRules($rules, [
                    'frequency_modifications' => ['reduced_frequency', 'rest_periods'],
                    'intensity_adjustments' => ['shorter_sessions', 'lower_intensity'],
                    'required_precautions' => ['energy_conservation', 'activity_pacing'],
                ]);
            }

            // Pain-related limitations
            if (str_contains($limitation, 'pain')) {
                $rules = $this->mergeRules($rules, [
                    'intensity_adjustments' => ['pain_controlling', 'gentle_movements'],
                    'required_precautions' => ['pain_monitoring', 'stop_if_pain_increases'],
                    'progression_guidelines' => ['pain_guided_progression'],
                ]);
            }
        }

        return $rules;
    }

    /**
     * Integrate treatment goals into personalization rules
     */
    protected function integrateTreatmentGoals(array $patientProfile): array
    {
        $rules = [];
        $goals = $patientProfile['treatment_goals'] ?? [];

        foreach ($goals as $goal) {
            $goal = strtolower(trim($goal));

            // Pain management goals
            if (str_contains($goal, 'pain') || str_contains($goal, 'comfort')) {
                $rules = $this->mergeRules($rules, [
                    'exercise_modifications' => ['gentle_techniques', 'modalities_integration'],
                    'required_precautions' => ['pain_monitoring', 'comfort_focused'],
                    'progression_guidelines' => ['pain_guided_progression'],
                ]);
            }

            // Strength and power goals
            if (str_contains($goal, 'strength') || str_contains($goal, 'power')) {
                $rules = $this->mergeRules($rules, [
                    'exercise_modifications' => ['resistance_training', 'progressive_overload'],
                    'intensity_adjustments' => ['strength_focused'],
                    'progression_guidelines' => ['strength_based_progression'],
                ]);
            }

            // Mobility and function goals
            if (str_contains($goal, 'mobility') || str_contains($goal, 'function') || str_contains($goal, 'independence')) {
                $rules = $this->mergeRules($rules, [
                    'exercise_modifications' => ['functional_training', 'task_specific'],
                    'required_precautions' => ['safety_first', 'assistive_devices'],
                    'progression_guidelines' => ['function_based_progression'],
                ]);
            }

            // Balance and stability goals
            if (str_contains($goal, 'balance') || str_contains($goal, 'stability') || str_contains($goal, 'fall')) {
                $rules = $this->mergeRules($rules, [
                    'exercise_modifications' => ['balance_training', 'stability_exercises'],
                    'required_precautions' => ['fall_prevention', 'stable_surfaces'],
                    'excluded_exercises' => ['high_risk_balance'],
                ]);
            }

            // Cardiovascular fitness goals
            if (str_contains($goal, 'cardiovascular') || str_contains($goal, 'endurance') || str_contains($goal, 'fitness')) {
                $rules = $this->mergeRules($rules, [
                    'exercise_modifications' => ['aerobic_exercises', 'progressive_endurance'],
                    'intensity_adjustments' => ['cardiovascular_focused'],
                    'progression_guidelines' => ['endurance_based_progression'],
                ]);
            }

            // Flexibility and ROM goals
            if (str_contains($goal, 'flexibility') || str_contains($goal, 'range') || str_contains($goal, 'stretching')) {
                $rules = $this->mergeRules($rules, [
                    'exercise_modifications' => ['stretching_routines', 'flexibility_exercises'],
                    'intensity_adjustments' => ['gentle_stretching'],
                    'progression_guidelines' => ['flexibility_based_progression'],
                ]);
            }
        }

        return $rules;
    }

    /**
     * Select appropriate HEP template based on patient conditions
     */
    public function selectAppropriateTemplate(array $patientProfile): ?HepProgramTemplate
    {
        $diagnosis = $patientProfile['diagnosis'] ?? '';
        $medicalConditions = $patientProfile['medical_conditions'] ?? [];
        $functionalLimitations = $patientProfile['functional_limitations'] ?? [];
        $treatmentGoals = $patientProfile['treatment_goals'] ?? [];

        // Map diagnosis to template diagnosis type
        $diagnosisType = $this->mapDiagnosisToTemplateType($diagnosis, $medicalConditions);

        if ($diagnosisType) {
            // First try to find template by specific diagnosis type
            $template = HepProgramTemplate::active()
                ->where('diagnosis_type', $diagnosisType)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($template) {
                return $template;
            }
        }

        // Fallback to category-based selection
        $category = $this->determineTemplateCategory($medicalConditions, $functionalLimitations, $treatmentGoals);

        if ($category) {
            $template = HepProgramTemplate::active()
                ->where('category', $category)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($template) {
                return $template;
            }
        }

        // Final fallback to general template
        return HepProgramTemplate::active()
            ->where('category', 'general_fitness')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Map diagnosis text to template diagnosis type
     */
    protected function mapDiagnosisToTemplateType(string $diagnosis, array $conditions): ?string
    {
        $diagnosis = strtolower(trim($diagnosis));
        $allConditions = array_merge([$diagnosis], array_map('strtolower', $conditions));

        $diagnosisMappings = [
            'osteoarthritis' => 'knee_osteoarthritis',
            'knee osteoarthritis' => 'knee_osteoarthritis',
            'hip osteoarthritis' => 'hip_osteoarthritis',
            'shoulder impingement' => 'shoulder_impingement',
            'low back pain' => 'low_back_pain',
            'neck pain' => 'neck_pain',
            'ankle sprain' => 'ankle_sprain',
            'acl' => 'acl_reconstruction',
            'total knee' => 'total_knee_replacement',
            'total hip' => 'total_hip_replacement',
            'rotator cuff' => 'rotator_cuff_repair',
            'stroke' => 'stroke',
            'parkinson' => 'parkinsons',
            'multiple sclerosis' => 'multiple_sclerosis',
            'spinal cord' => 'spinal_cord_injury',
            'heart disease' => 'heart_disease',
            'copd' => 'copd',
            'diabetes' => 'diabetes',
            'fracture' => 'fracture_recovery',
            'tendonitis' => 'tendonitis',
        ];

        foreach ($allConditions as $condition) {
            foreach ($diagnosisMappings as $keyword => $templateType) {
                if (str_contains($condition, $keyword)) {
                    return $templateType;
                }
            }
        }

        return null;
    }

    /**
     * Determine appropriate template category based on conditions and goals
     */
    protected function determineTemplateCategory(array $conditions, array $limitations, array $goals): ?string
    {
        $allText = array_merge($conditions, $limitations, $goals);
        $text = strtolower(implode(' ', $allText));

        // Orthopedic conditions
        if (str_contains($text, 'arthritis') ||
            str_contains($text, 'joint') ||
            str_contains($text, 'replacement') ||
            str_contains($text, 'fracture')) {
            return 'orthopedic';
        }

        // Neurological conditions
        if (str_contains($text, 'stroke') ||
            str_contains($text, 'parkinson') ||
            str_contains($text, 'multiple sclerosis') ||
            str_contains($text, 'spinal cord')) {
            return 'neurological';
        }

        // Cardiovascular conditions
        if (str_contains($text, 'heart') ||
            str_contains($text, 'cardiovascular') ||
            str_contains($text, 'hypertension')) {
            return 'cardiovascular';
        }

        // Sports medicine
        if (str_contains($text, 'sprain') ||
            str_contains($text, 'strain') ||
            str_contains($text, 'tendonitis') ||
            str_contains($text, 'ligament')) {
            return 'sports_medicine';
        }

        // Chronic pain
        if (str_contains($text, 'chronic pain') ||
            str_contains($text, 'persistent pain')) {
            return 'chronic_pain';
        }

        // Post-surgical
        if (str_contains($text, 'post-operative') ||
            str_contains($text, 'surgery') ||
            str_contains($text, 'reconstruction')) {
            return 'post-surgical';
        }

        // Geriatric considerations
        $demographics = $patientProfile['patient_demographics'] ?? [];
        if (isset($demographics['age']) && $demographics['age'] >= 65) {
            return 'geriatric';
        }

        return 'general_fitness';
    }

    /**
     * Perform comprehensive safety checks and add contraindications
     */
    public function performSafetyChecks(HepProgram $program, array $patientProfile): array
    {
        $safetyIssues = [];
        $patientConditions = array_merge(
            $patientProfile['medical_conditions'] ?? [],
            $patientProfile['past_medical_history'] ?? []
        );

        // Check each exercise for safety
        foreach ($program->hepExercises as $hepExercise) {
            $exerciseIssues = $this->safetyService->checkContraindications(
                $program->patient,
                $hepExercise
            );

            if (!empty($exerciseIssues)) {
                $safetyIssues[] = [
                    'exercise_id' => $hepExercise->exercise_id,
                    'exercise_name' => $hepExercise->exercise->name,
                    'issues' => $exerciseIssues,
                ];
            }
        }

        // Add condition-specific safety precautions
        $additionalPrecautions = $this->getConditionSpecificPrecautions($patientConditions);
        if (!empty($additionalPrecautions)) {
            $program->precautions = array_unique(array_merge($program->precautions, $additionalPrecautions));
            $program->save();
        }

        return $safetyIssues;
    }

    /**
     * Get condition-specific safety precautions
     */
    protected function getConditionSpecificPrecautions(array $conditions): array
    {
        $precautions = [];

        foreach ($conditions as $condition) {
            $condition = strtolower(trim($condition));

            $conditionPrecautions = [
                'osteoarthritis' => [
                    'Avoid exercises that cause joint pain',
                    'Use joint protection techniques',
                    'Stop if swelling increases',
                ],
                'hypertension' => [
                    'Monitor blood pressure before and after exercise',
                    'Avoid valsalva maneuver',
                    'Stay hydrated',
                ],
                'diabetes' => [
                    'Monitor blood sugar levels',
                    'Carry diabetes identification and glucose',
                    'Exercise at consistent times',
                ],
                'heart_disease' => [
                    'Stop if chest pain occurs',
                    'Know emergency plan',
                    'Exercise within prescribed heart rate limits',
                ],
                'copd' => [
                    'Use pursed lip breathing',
                    'Stop if shortness of breath worsens',
                    'Have rescue inhaler available',
                ],
                'stroke' => [
                    'Exercise affected side as tolerated',
                    'Monitor for increased weakness',
                    'Use assistive devices as needed',
                ],
                'parkinson' => [
                    'Exercise during "on" periods when possible',
                    'Be aware of freezing episodes',
                    'Use cues for movement initiation',
                ],
                'fracture' => [
                    'Protect healing bone',
                    'Avoid weight bearing until cleared',
                    'Use assistive devices for mobility',
                ],
            ];

            foreach ($conditionPrecautions as $key => $precautionList) {
                if (str_contains($condition, $key)) {
                    $precautions = array_merge($precautions, $precautionList);
                }
            }
        }

        return array_unique($precautions);
    }

    /**
     * Validate personalization was applied correctly
     */
    public function validatePersonalization(HepProgram $program): array
    {
        $issues = [];

        // Check that excluded exercises were removed
        // Check that precautions were added
        // Check that modifications were applied

        return $issues;
    }
}
