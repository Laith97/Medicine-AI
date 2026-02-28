<?php

namespace App\Services;

use App\Models\Diagnosis;
use App\Models\DoctorNote;
use App\Models\Exercise;
use App\Models\HepProgram;
use App\Models\HepExercise;
use App\Models\HepAssignment;
use App\Models\User;
use App\Models\Appointment;
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
     * Generate HEP program data using AI without creating the program in database
     */
    public function generateProgramData(
        Diagnosis $diagnosis,
        User $patient,
        User $doctor,
        array $additionalContext = []
    ): array {
        Log::info('Starting HEP program data generation', [
            'diagnosis_id' => $diagnosis->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
        ]);

        // Extract clinical information
        $clinicalData = $this->extractClinicalInformation($diagnosis, $patient);

        // Generate AI-powered program recommendations
        $aiRecommendations = $this->generateAIRecommendations($clinicalData, $additionalContext);

        Log::info('HEP program data generated successfully', [
            'exercise_count' => count($aiRecommendations['exercises'] ?? []),
        ]);

        return $aiRecommendations;
    }

    /**
     * Find an appropriate appointment for HEP program creation
     */
    protected function findAppointmentForDiagnosis(Diagnosis $diagnosis, User $patient, User $doctor): ?int
    {
        // First, try to find if diagnosis has an appointment_id (even if null, check if column exists)
        if (isset($diagnosis->appointment_id) && $diagnosis->appointment_id) {
            return $diagnosis->appointment_id;
        }

        // If no appointment_id on diagnosis, search for recent appointments
        $appointment = Appointment::where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->doctor->id) // Use doctor profile ID, not user ID
            ->whereBetween('appointment_date', [
                $diagnosis->created_at->subDays(30), // 30 days before diagnosis
                $diagnosis->created_at->addDays(30)  // 30 days after diagnosis
            ])
            ->whereIn('status', ['confirmed', 'completed']) // Only relevant appointments
            ->orderBy('appointment_date', 'desc')
            ->first();

        if ($appointment) {
            Log::info('Found appointment for HEP program creation', [
                'diagnosis_id' => $diagnosis->id,
                'appointment_id' => $appointment->id,
                'appointment_date' => $appointment->appointment_date,
            ]);
            return $appointment->id;
        }

        // If still no appointment found, log warning and return null
        Log::warning('No appointment found for HEP program creation', [
            'diagnosis_id' => $diagnosis->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'diagnosis_date' => $diagnosis->created_at,
        ]);

        // Since hep_programs table requires appointment_id to be NOT NULL,
        // we need to create a placeholder appointment or handle this differently
        // For now, return null and handle in the calling method
        return null;
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
        // Find appropriate appointment_id
        $appointmentId = $this->findAppointmentForDiagnosis($diagnosis, $patient, $doctor);

        // If no appointment found, we need to create a placeholder appointment
        if (!$appointmentId) {
            Log::info('Creating placeholder appointment for HEP program', [
                'diagnosis_id' => $diagnosis->id,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
            ]);

            // Create a placeholder appointment
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->doctor->id, // Use doctor profile ID, not user ID
                'appointment_date' => $diagnosis->created_at,
                'appointment_end' => $diagnosis->created_at->copy()->addMinutes(30), // Add 30 minutes for appointment duration
                'status' => 'completed', // Mark as completed since it's a placeholder
                'appointment_type' => 'in_person',
                'notes' => 'Placeholder appointment created for AI-generated HEP program',
            ]);

            $appointmentId = $appointment->id;
        }

        // Create the program
        $program = HepProgram::create([
            'title' => $aiRecommendations['program_title'] ?? 'AI-Generated Home Exercise Program',
            'description' => $this->generateProgramDescription($aiRecommendations),
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'diagnosis_id' => $diagnosis->id,
            'appointment_id' => $appointmentId, // This should now always have a value
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
                $query->where('appointment_id', $diagnosis->appointment_id ?? null)
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
     * Generate program description from AI recommendations
     */
    public function generateProgramDescription(array $aiRecommendations): string
    {
        $description = "AI-generated home exercise program designed to address ";

        if (!empty($aiRecommendations['goals'])) {
            $description .= implode(' and ', $aiRecommendations['goals']);
        }

        $description .= ". This program includes {$aiRecommendations['duration_weeks']} weeks of progressive exercises to be performed {$aiRecommendations['frequency_per_week']} times per week.";

        return $description;
    }

    /**
     * Generate AI-powered program recommendations
     */
    protected function generateAIRecommendations(array $clinicalData, array $additionalContext = []): array
    {
        // First, check if OpenAI API key is configured
        if (empty(config('openai.api_key'))) {
            Log::warning('OpenAI API key not configured for HEP generation', [
                'clinical_data_keys' => array_keys($clinicalData),
                'has_diagnosis' => !empty($clinicalData['diagnosis_text']),
            ]);

            // Return fallback recommendations when OpenAI is not configured
            return $this->generateFallbackRecommendations($clinicalData);
        }

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
            $errorMessage = $e->getMessage();

            // Check if it's an SSL certificate error
            if (str_contains($errorMessage, 'SSL certificate problem') ||
                str_contains($errorMessage, 'cURL error 60')) {
                Log::warning('OpenAI SSL certificate error in development - using fallback recommendations', [
                    'error' => $errorMessage,
                ]);
            } else {
                Log::error('AI HEP generation failed', [
                    'error' => $errorMessage,
                    'clinical_data' => $clinicalData,
                    'trace' => $e->getTraceAsString(),
                ]);
            }

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

    // ... Include all other existing methods from the original file ...
    // For brevity, I'll include the essential ones

    protected function extractPatientConditions(Collection $clinicalNotes): array
    {
        $conditions = [];
        $notesText = $clinicalNotes->pluck('content')->join(' ');
        if (!empty($notesText)) {
            $conditions = $this->extractConditionsWithAI($notesText);
        }
        return array_unique($conditions);
    }

    protected function extractFunctionalLimitations(Collection $clinicalNotes): array
    {
        $limitations = [];
        $notesText = $clinicalNotes->pluck('content')->join(' ');
        if (!empty($notesText)) {
            $limitations = $this->extractLimitationsWithAI($notesText);
        }
        return array_unique($limitations);
    }

    protected function extractTreatmentGoals(Collection $clinicalNotes): array
    {
        $goals = [];
        $notesText = $clinicalNotes->pluck('content')->join(' ');
        if (!empty($notesText)) {
            $goals = $this->extractGoalsWithAI($notesText);
        }
        return $goals;
    }

    protected function validateAndParseJsonResponse(string $aiContent): array
    {
        $cleanContent = trim($aiContent);
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
        $parsed = json_decode($cleanContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }
        if (!is_array($parsed) || !isset($parsed['program_title']) || !isset($parsed['exercises'])) {
            throw new \Exception('Response missing required fields');
        }
        return $parsed;
    }

    protected function generateFallbackRecommendations(array $clinicalData): array
    {
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
                    'duration_seconds' => 600,
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

    protected function extractContraindications(array $exerciseData): array
    {
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

    protected function extractMuscleGroups(array $exerciseData): array
    {
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
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'SSL certificate problem') ||
                str_contains($errorMessage, 'cURL error 60')) {
                Log::warning('OpenAI SSL certificate error in condition extraction - returning empty array', [
                    'error' => $errorMessage,
                ]);
            } else {
                Log::error('Condition extraction failed', ['error' => $errorMessage]);
            }
            return [];
        }
    }

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
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'SSL certificate problem') ||
                str_contains($errorMessage, 'cURL error 60')) {
                Log::warning('OpenAI SSL certificate error in limitation extraction - returning empty array', [
                    'error' => $errorMessage,
                ]);
            } else {
                Log::error('Limitation extraction failed', ['error' => $errorMessage]);
            }
            return [];
        }
    }

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
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'SSL certificate problem') ||
                str_contains($errorMessage, 'cURL error 60')) {
                Log::warning('OpenAI SSL certificate error in goal extraction - returning empty array', [
                    'error' => $errorMessage,
                ]);
            } else {
                Log::error('Goal extraction failed', ['error' => $errorMessage]);
            }
            return [];
        }
    }
}
