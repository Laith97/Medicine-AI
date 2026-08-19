<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AICopilotUsageLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Carbon;

class AIMedicalCopilotService
{
    /**
     * Generate comprehensive medical analysis for clinical decision support
     *
     * @param Appointment $appointment
     * @param array $structuredData
     * @return array
     */
    public function generateMedicalAnalysis(Appointment $appointment, array $structuredData)
    {
        // Validate AI feature is enabled
        if (!config('ai.enabled', true)) {
            return $this->getDisabledResponse();
        }

        // Validate required data structure
        $validation = $this->validateInputData($structuredData);
        if (!$validation['valid']) {
            return [
                'error' => 'Invalid input data structure',
                'missing_fields' => $validation['missing_fields'],
                'message' => 'Required data structure validation failed'
            ];
        }

        // Log the AI copilot request for audit trail
        $this->logAICopilotRequest($appointment, $structuredData);

        try {
            // Build the medical analysis prompt with structured system/user separation
            $prompts = $this->buildMedicalAnalysisPrompt($appointment, $structuredData);

            // Call OpenAI with optimized clinical decision support parameters
            $response = $this->callOpenAIForMedicalAnalysis($prompts);

            // Parse and validate the response
            $parsedResponse = $this->parseAndValidateResponse($response);

            // Enhance with red flags detection
            $enhancedResponse = $this->enhanceWithRedFlagsDetection($parsedResponse, $structuredData);

            // Add patient history to the response
            $enhancedResponse['patient_history'] = $this->getPatientMedicalHistory($appointment);

            // Add compliance and audit information
            $finalResponse = $this->addComplianceInformation($enhancedResponse, $appointment);

            return $finalResponse;

        } catch (\Exception $e) {
            Log::error('AI Medical Copilot Error', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->getFallbackResponse($structuredData, $e->getMessage());
        }
    }

    /**
     * Save AI copilot analysis to patient medical history for future reference
     *
     * @param Appointment $appointment
     * @param array $analysisResult
     * @return bool
     */
    public function saveAnalysisToMedicalHistory(Appointment $appointment, array $analysisResult)
    {
        try {
            // Prepare the data array
            $data = [
                'analysis_data' => $analysisResult,
                'generated_at' => now(),
                'doctor_id' => $appointment->doctor->user_id,
                'status' => 'active',
                'summary' => $analysisResult['medical_case_summary'] ?? 'No summary available',
                'considerations' => json_encode($analysisResult['differential_considerations'] ?? []),
                'questions' => json_encode($analysisResult['follow_up_questions'] ?? []),
                'red_flags' => json_encode($analysisResult['red_flags'] ?? [])
            ];

            // Handle guest vs registered patient
            if ($appointment->isGuestAppointment()) {
                // For guest appointments, use guest fields and null patient_id
                $data = array_merge($data, [
                    'patient_id' => null,
                    'guest_name' => $appointment->guest_name,
                    'guest_email' => $appointment->guest_email,
                    'guest_phone' => $appointment->guest_phone,
                    'guest_date_of_birth' => $appointment->guest_date_of_birth,
                    'guest_gender' => $appointment->guest_gender,
                    'guest_address' => $appointment->guest_address,
                ]);

                // Use unique constraint for guest appointments (appointment_id only since patient_id is null)
                $analysis = \App\Models\AICopilotAnalysis::updateOrCreate(
                    ['appointment_id' => $appointment->id],
                    $data
                );
            } else {
                // For registered patients, use patient_id in the unique constraint
                $data['patient_id'] = $appointment->patient_id;

                $analysis = \App\Models\AICopilotAnalysis::updateOrCreate(
                    [
                        'appointment_id' => $appointment->id,
                        'patient_id' => $appointment->patient_id
                    ],
                    $data
                );
            }

            Log::info('AI Copilot analysis saved to medical history', [
                'analysis_id' => $analysis->id,
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'is_guest' => $appointment->isGuestAppointment()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to save AI copilot analysis', [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'is_guest' => $appointment->isGuestAppointment(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get patient medical history for enhanced AI analysis
     *
     * @param Appointment $appointment
     * @return array
     */
    public function getPatientMedicalHistory(Appointment $appointment)
    {
        $patient = $appointment->patient;
        $history = [];

        if (!$patient) {
            return $history;
        }

        // Get previous diagnoses
        $previousDiagnoses = \App\Models\Diagnosis::where('patient_id', $patient->id)
            ->where('id', '!=', $appointment->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->pluck('diagnosis_text')
            ->toArray();

        // Get previous appointments
        $previousAppointments = Appointment::where('patient_id', $patient->id)
            ->where('id', '!=', $appointment->id)
            ->where('status', 'completed')
            ->orderBy('appointment_date', 'desc')
            ->limit(5)
            ->get();

        // Get previous AI analyses
        $previousAIAnalyses = \App\Models\AICopilotAnalysis::where('patient_id', $patient->id)
            ->where('appointment_id', '!=', $appointment->id)
            ->orderBy('generated_at', 'desc')
            ->limit(3)
            ->get();

        // Compile comprehensive medical history
        $history = [
            'previous_diagnoses' => $previousDiagnoses,
            'previous_appointments' => $previousAppointments->map(function($appt) {
                return [
                    'date' => $appt->appointment_date->format('M j, Y'),
                    'reason' => $appt->reason,
                    'doctor_notes' => $appt->doctor_notes,
                    'diagnosis' => $appt->diagnosis
                ];
            })->toArray(),
            'previous_ai_analyses' => $previousAIAnalyses->map(function($analysis) {
                return [
                    'generated_at' => $analysis->generated_at->format('M j, Y g:i A'),
                    'summary' => $analysis->summary,
                    'key_considerations' => json_decode($analysis->considerations, true),
                    'red_flags' => json_decode($analysis->red_flags, true)
                ];
            })->toArray(),
            'chronic_conditions' => $patient->patientData->chronic_conditions ?? [],
            'medication_history' => $patient->patientData->medication_history ?? [],
            'allergies' => $patient->patientData->allergies ?? [],
            'surgical_history' => $patient->patientData->surgical_history ?? []
        ];

        return $history;
    }

    /**
     * Build enhanced medical analysis prompt with structured system/user separation
     */
    protected function buildMedicalAnalysisPrompt(Appointment $appointment, array $structuredData)
    {
        $patient = $appointment->patient;

        // Extract structured data
        $complaint = $structuredData['complaint'];
        $vitals = $structuredData['vitals'];
        $labs = $structuredData['labs'] ?? [];
        $history = $structuredData['history'];
        $previousVisits = $structuredData['previous_visits'] ?? [];

        // Get enhanced patient medical history
        $medicalHistory = $this->getPatientMedicalHistory($appointment);

        // Build patient demographics
        $patientAge = $patient ? ($patient->age ?? ($patient->date_of_birth ? Carbon::parse($patient->date_of_birth)->age : 'Unknown')) : 'Unknown';
        $patientGender = $patient ? ($patient->gender ?? 'Unknown') : 'Unknown';

        // Build structured user prompt (data only)
        $userPrompt = [
            'task' => 'Generate clinical decision support analysis',
            'constraints' => [
                'no diagnosis',
                'no treatment',
                'clinical consideration only'
            ],
            'patient_context' => [
                'demographics' => [
                    'age' => $patientAge,
                    'gender' => $patientGender,
                    'appointment_type' => $appointment->appointment_type
                ],
                'chief_complaint' => $complaint['chief_complaint'],
                'onset' => $complaint['onset'],
                'severity' => $complaint['severity'],
                'associated_symptoms' => $complaint['associated_symptoms'] ?? [],
                'vitals' => $vitals,
                'labs' => $labs,
                'medical_history' => [
                    'chronic_conditions' => array_merge(
                        $medicalHistory['chronic_conditions'] ?? [],
                        $history['chronic_conditions'] ?? []
                    ),
                    'medications' => $history['medications'] ?? [],
                    'allergies' => array_merge(
                        $medicalHistory['allergies'] ?? [],
                        $history['allergies'] ?? []
                    ),
                    'surgical_history' => $medicalHistory['surgical_history'] ?? [],
                    'previous_diagnoses' => $medicalHistory['previous_diagnoses'] ?? [],
                    'previous_visits' => $previousVisits
                ],
                'previous_ai_analyses' => $medicalHistory['previous_ai_analyses'] ?? []
            ],
            'required_output_schema' => [
                'medical_case_summary' => 'string (3-5 lines)',
                'differential_considerations' => 'array (max 5 items, each with consideration and rationale)',
                'follow_up_questions' => 'array (max 6 clinically relevant questions)',
                'red_flags' => 'array (prioritize high-risk findings)',
                'disclaimer' => 'string'
            ]
        ];

        // Add anti-hallucination guard
        if (empty($labs)) {
            $userPrompt['notes'][] = 'Laboratory data limited or unavailable. Avoid assumptions.';
        }

        return [
            'system_prompt' => $this->getSystemPrompt(),
            'user_prompt' => json_encode($userPrompt, JSON_PRETTY_PRINT)
        ];
    }

    /**
     * Get structured system prompt for clinical decision support
     */
    protected function getSystemPrompt()
    {
        return <<<'SYS'
You are a CLINICAL DECISION SUPPORT ASSISTANT for licensed physicians.

ROLE:
- You assist clinical reasoning.
- You do NOT diagnose.
- You do NOT recommend treatment, medication, dosage, or procedures.

HARD SAFETY RULES (NON-NEGOTIABLE):
1. NEVER state or imply a confirmed diagnosis.
2. NEVER recommend treatment, medication, or management.
3. NEVER use definitive language ("is", "confirms", "diagnosis is").
4. Use ONLY cautious phrasing: "may suggest", "consider", "for clinical review".
5. If data is insufficient, explicitly state that.

OUTPUT RULES:
- Respond ONLY with valid JSON.
- Follow the exact schema provided.
- No markdown, no explanations, no extra text.

CLINICAL STYLE:
- Think like a physician.
- Prioritize life-threatening possibilities in red flags.
- Prefer fewer, higher-quality considerations over many weak ones.

DISCLAIMERS:
- Always include a legal disclaimer string.
SYS;
    }

    /**
     * Validate input data structure
     */
    protected function validateInputData(array $structuredData)
    {
        $requiredFields = ['complaint', 'vitals', 'history'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (empty($structuredData[$field])) {
                $missingFields[] = $field;
            }
        }

        return [
            'valid' => empty($missingFields),
            'missing_fields' => $missingFields
        ];
    }

    /**
     * Call OpenAI with optimized clinical decision support parameters
     */
    protected function callOpenAIForMedicalAnalysis(array $prompts)
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $prompts['system_prompt']
                ],
                [
                    'role' => 'user',
                    'content' => $prompts['user_prompt']
                ]
            ],
            'max_tokens' => 1500,
            'temperature' => 0.2, // Lower temperature for medical consistency
            'top_p' => 0.9,
            'presence_penalty' => 0,
            'frequency_penalty' => 0,
        ]);

        return $response;
    }

    /**
     * Parse and validate OpenAI response with quality enforcement
     */
    protected function parseAndValidateResponse($response)
    {
        $aiContent = $response->choices[0]->message->content;

        // Clean and parse JSON
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

        // Parse JSON
        $parsed = json_decode($cleanContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

        // Validate required structure
        $requiredFields = ['medical_case_summary', 'differential_considerations', 'follow_up_questions', 'red_flags', 'disclaimer'];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $parsed)) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        // LEVEL 2: Output Quality Enforcement

        // Force structured differential considerations
        $parsed['differential_considerations'] = $this->enforceStructuredDifferentials($parsed['differential_considerations']);

        // Limit array sizes to prevent noise
        $parsed['differential_considerations'] = array_slice($parsed['differential_considerations'], 0, 5);
        $parsed['follow_up_questions'] = array_slice($parsed['follow_up_questions'], 0, 6);
        $parsed['red_flags'] = array_slice($parsed['red_flags'], 0, 5);

        return $parsed;
    }

    /**
     * Enforce structured differential considerations with consideration and rationale
     */
    protected function enforceStructuredDifferentials($differentials)
    {
        $structured = [];

        if (!is_array($differentials)) {
            return [['consideration' => 'Manual clinical evaluation required', 'rationale' => 'AI response format invalid']];
        }

        foreach ($differentials as $item) {
            // If already structured, validate and use
            if (is_array($item) && isset($item['consideration'], $item['rationale'])) {
                $structured[] = [
                    'consideration' => trim($item['consideration']),
                    'rationale' => trim($item['rationale'])
                ];
            }
            // If string, convert to structured format
            elseif (is_string($item) && !empty($item)) {
                $structured[] = [
                    'consideration' => trim($item),
                    'rationale' => 'Clinical consideration based on patient presentation and history'
                ];
            }
        }

        // Ensure we have at least one valid consideration
        if (empty($structured)) {
            $structured[] = [
                'consideration' => 'Manual clinical evaluation required',
                'rationale' => 'Unable to generate structured differential considerations'
            ];
        }

        return $structured;
    }

    /**
     * Enhance response with contextual red flags detection (LEVEL 3)
     */
    protected function enhanceWithRedFlagsDetection(array $response, array $structuredData)
    {
        $complaint = $structuredData['complaint'];
        $vitals = $structuredData['vitals'];
        $redFlags = $response['red_flags'] ?? [];

        // LEVEL 3: Contextual Red Flags - Symptom + Vitals Coupling

        $chiefComplaint = strtolower($complaint['chief_complaint'] ?? '');
        $associatedSymptoms = array_map('strtolower', $complaint['associated_symptoms'] ?? []);

        // Chest pain + tachycardia (potential ACS)
        if (str_contains($chiefComplaint, 'chest') && $vitals['hr'] > 100) {
            $redFlags[] = "Chest pain with tachycardia ({$vitals['hr']} bpm) may represent a high-risk presentation requiring prompt evaluation";
        }

        // Chest pain + hypotension (shock)
        if (str_contains($chiefComplaint, 'chest') && isset($vitals['bp'])) {
            list($systolic, $diastolic) = explode('/', $vitals['bp']);
            if ($systolic < 90) {
                $redFlags[] = "Chest pain with hypotension ({$vitals['bp']}) may indicate cardiogenic shock requiring immediate intervention";
            }
        }

        // Dyspnea + low SpO2 (respiratory distress)
        if ((str_contains($chiefComplaint, 'shortness') || str_contains($chiefComplaint, 'breath') ||
             in_array('dyspnea', $associatedSymptoms) || in_array('shortness of breath', $associatedSymptoms))
            && $vitals['spo2'] < 90) {
            $redFlags[] = "Dyspnea with hypoxemia ({$vitals['spo2']}%) may indicate acute respiratory distress requiring urgent evaluation";
        }

        // Fever + tachycardia (sepsis)
        if ($vitals['temperature'] > 38.0 && $vitals['hr'] > 100) {
            $redFlags[] = "Fever with tachycardia ({$vitals['hr']} bpm, {$vitals['temperature']}°C) may suggest systemic inflammatory response requiring assessment";
        }

        // Altered mental status + abnormal vitals
        if ((str_contains($chiefComplaint, 'confusion') || str_contains($chiefComplaint, 'dizziness') ||
             in_array('confusion', $associatedSymptoms) || in_array('altered mental status', $associatedSymptoms))) {

            $abnormalVitals = [];
            if ($vitals['hr'] > 100 || $vitals['hr'] < 50) $abnormalVitals[] = 'heart rate';
            if ($vitals['spo2'] < 90) $abnormalVitals[] = 'oxygen saturation';
            if (isset($vitals['bp'])) {
                list($systolic, $diastolic) = explode('/', $vitals['bp']);
                if ($systolic > 180 || $systolic < 90) $abnormalVitals[] = 'blood pressure';
            }

            if (!empty($abnormalVitals)) {
                $redFlags[] = "Altered mental status with abnormal " . implode(' and ', $abnormalVitals) . " may indicate critical illness requiring urgent evaluation";
            }
        }

        // Abdominal pain + hypotension (peritonitis/shock)
        if ((str_contains($chiefComplaint, 'abdominal') || str_contains($chiefComplaint, 'stomach')) &&
            isset($vitals['bp'])) {
            list($systolic, $diastolic) = explode('/', $vitals['bp']);
            if ($systolic < 90) {
                $redFlags[] = "Abdominal pain with hypotension ({$vitals['bp']}) may indicate intra-abdominal catastrophe requiring immediate surgical evaluation";
            }
        }

        // Headache + severe hypertension
        if ((str_contains($chiefComplaint, 'headache') || in_array('headache', $associatedSymptoms)) &&
            isset($vitals['bp'])) {
            list($systolic, $diastolic) = explode('/', $vitals['bp']);
            if ($systolic > 180) {
                $redFlags[] = "Severe headache with hypertension ({$vitals['bp']}) may indicate hypertensive emergency requiring immediate treatment";
            }
        }

        // Individual vital sign abnormalities (baseline checks)
        if ($vitals['hr'] > 120) {
            $redFlags[] = "Severe tachycardia detected ({$vitals['hr']} bpm) - consider urgent evaluation if clinically indicated";
        }

        if ($vitals['spo2'] < 85) {
            $redFlags[] = "Severe hypoxemia ({$vitals['spo2']}%) - consider urgent evaluation if clinically indicated";
        }

        if (strpos($vitals['bp'], '/') !== false) {
            list($systolic, $diastolic) = explode('/', $vitals['bp']);
            if ($systolic > 200 || $diastolic > 120) {
                $redFlags[] = "Hypertensive crisis detected ({$vitals['bp']}) - consider urgent evaluation if clinically indicated";
            }
        }

        if ($vitals['temperature'] > 39.5) {
            $redFlags[] = "High fever ({$vitals['temperature']}°C) - consider infection or inflammatory process requiring assessment";
        }

        // Remove duplicates and limit to 5
        $redFlags = array_unique($redFlags);
        $redFlags = array_slice($redFlags, 0, 5);

        $response['red_flags'] = array_values($redFlags);
        return $response;
    }

    /**
     * Add compliance and audit information
     */
    protected function addComplianceInformation(array $response, Appointment $appointment)
    {
        $response['compliance'] = [
            'ai_generated' => true,
            'physician_verification_required' => true,
            'label' => 'AI-generated draft. Physician verified.',
            'timestamp' => now()->toISOString(),
            'generated_by' => 'AI Medical Copilot',
            'version' => 'ai-copilot-clinical-v1.1'
        ];

        $response['legal_disclaimer'] = 'This content is generated by AI Medical Copilot for clinical decision support only. All medical decisions must be made by qualified healthcare professionals.';

        return $response;
    }

    /**
     * Log AI copilot request for audit trail
     */
    protected function logAICopilotRequest(Appointment $appointment, array $structuredData)
    {
        try {
            AICopilotUsageLog::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor->user_id,
                'request_data' => json_encode($structuredData),
                'requested_at' => now(),
                'status' => 'processing',
                'user_id' => Auth::id()
            ]);

            Log::info('AI Medical Copilot request logged', [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log AI copilot request', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id
            ]);
        }
    }

    /**
     * Update audit log with response
     */
    public function logAICopilotResponse($appointmentId, array $response)
    {
        try {
            $log = AICopilotUsageLog::where('appointment_id', $appointmentId)
                ->where('status', 'processing')
                ->latest()
                ->first();

            if ($log) {
                $log->update([
                    'response_data' => json_encode($response),
                    'status' => 'completed',
                    'completed_at' => now()
                ]);

                Log::info('AI Medical Copilot response logged', [
                    'log_id' => $log->id,
                    'appointment_id' => $appointmentId
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to log AI copilot response', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointmentId
            ]);
        }
    }

    /**
     * Get disabled response when AI is not enabled
     */
    protected function getDisabledResponse()
    {
        return [
            'error' => 'AI Medical Copilot is disabled',
            'message' => 'AI Medical Copilot feature is currently disabled in system configuration',
            'disabled' => true,
            'medical_case_summary' => 'AI Medical Copilot is disabled',
            'differential_considerations' => ['AI Medical Copilot is disabled'],
            'follow_up_questions' => ['AI Medical Copilot is disabled'],
            'red_flags' => ['AI Medical Copilot is disabled'],
            'disclaimer' => 'AI Medical Copilot is disabled'
        ];
    }

    /**
     * Get fallback response when AI fails
     */
    protected function getFallbackResponse(array $structuredData, $errorMessage)
    {
        $complaint = $structuredData['complaint']['chief_complaint'] ?? 'Unknown complaint';

        return [
            'error' => 'AI Medical Copilot unavailable',
            'message' => 'AI Medical Copilot is temporarily unavailable',
            'fallback' => true,
            'error_reason' => $errorMessage,
            'medical_case_summary' => "Patient presenting with {$complaint}. AI Medical Copilot unavailable - manual clinical assessment required.",
            'differential_considerations' => [
                'Manual clinical evaluation required',
                'Consider broad differential based on clinical presentation'
            ],
            'follow_up_questions' => [
                'What is the exact nature and location of the symptoms?',
                'When did the symptoms first appear and have they changed?',
                'Are there any associated symptoms or aggravating factors?',
                'What is the patient\'s complete medical history?'
            ],
            'red_flags' => [
                '⚠️ AI Medical Copilot unavailable - complete manual assessment required',
                '⚠️ Verify all clinical findings through standard evaluation',
                '⚠️ Consider urgent evaluation if any concerning symptoms present'
            ],
            'disclaimer' => 'AI Medical Copilot is unavailable. All medical decisions must be made by qualified healthcare professionals based on complete clinical evaluation.'
        ];
    }

    /**
     * Generate clinical case summary from structured data
     */
    public function generateCaseSummary(array $structuredData)
    {
        $complaint = $structuredData['complaint'];
        $vitals = $structuredData['vitals'];
        $history = $structuredData['history'];
        $patientAge = $structuredData['patient_age'] ?? 'Unknown';
        $patientGender = $structuredData['patient_gender'] ?? 'Unknown';

        $summary = "{$patientAge}-year-old {$patientGender} with history of ";

        if (!empty($history['chronic_conditions'])) {
            $summary .= implode(', ', $history['chronic_conditions']) . " ";
        } else {
            $summary .= "no significant chronic conditions ";
        }

        $summary .= "presenting with " . strtolower($complaint['onset']) . " history of " . strtolower($complaint['chief_complaint']);

        if (!empty($complaint['associated_symptoms'])) {
            $summary .= " and " . strtolower(implode(', ', $complaint['associated_symptoms']));
        }

        $summary .= ". ";

        // Add vital signs summary
        $vitalIssues = [];
        if (($vitals['hr'] ?? 0) > 100) $vitalIssues[] = "tachycardia";
        if (($vitals['spo2'] ?? 100) < 90) $vitalIssues[] = "hypoxemia";
        if (isset($vitals['bp']) && strpos($vitals['bp'], '/') !== false) {
            list($systolic, $diastolic) = explode('/', $vitals['bp']);
            if ($systolic > 140 || $diastolic > 90) $vitalIssues[] = "elevated blood pressure";
        }
        if (($vitals['temperature'] ?? 36.5) > 37.8) $vitalIssues[] = "fever";

        if (!empty($vitalIssues)) {
            $summary .= "Vitals show " . implode(', ', $vitalIssues) . ". ";
        } else {
            $summary .= "Vitals are stable. ";
        }

        // Add labs if available
        if (!empty($structuredData['labs'])) {
            $pendingLabs = [];
            $abnormalLabs = [];

            foreach ($structuredData['labs'] as $lab => $value) {
                if (is_array($value)) {
                    foreach ($value as $subLab => $subValue) {
                        if ($subValue === 'pending') {
                            $pendingLabs[] = $subLab;
                        }
                    }
                } else {
                    if ($value === 'pending') {
                        $pendingLabs[] = $lab;
                    }
                }
            }

            if (!empty($pendingLabs)) {
                $summary .= "Initial labs pending for " . implode(', ', $pendingLabs) . ". ";
            }
        }

        return $summary;
    }
}