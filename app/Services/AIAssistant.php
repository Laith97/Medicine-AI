<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Prescription;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use App\Services\FDADrugValidator;

class AIAssistant
{
    /**
     * CRITICAL RED FLAGS - Emergency symptoms that require evaluation before any medication
     */
    private const RED_FLAG_SYMPTOMS = [
        'chest pain',
        'chest tightness',
        'shortness of breath',
        'dyspnea',
        'difficulty breathing',
        'syncope',
        'fainting',
        'loss of consciousness',
        'severe headache',
        'sudden confusion',
        'numbness',
        'weakness on one side',
        'acute abdominal pain',
        'active bleeding',
        'high fever',
        'stiff neck',
    ];

    /**
     * NSAID drug names for detection
     */
    private const NSAID_DRUGS = [
        'ibuprofen', 'naproxen', 'diclofenac', 'aspirin', 'indomethacin',
        'meloxicam', 'celecoxib', 'ketorolac', 'piroxicam', 'sulindac',
        'flurbiprofen', 'oxaprozin', 'nabumetone', 'ketoprofen'
    ];

    /**
     * Antiplatelet medications that contraindicate NSAIDs
     */
    private const ANTIPLATELET_MEDS = [
        'aspirin', 'clopidogrel', 'plavix', ' prasugrel', 'ticagrelor',
        'warfarin', 'heparin', 'enoxaparin', 'dabigatran', 'rivaroxaban',
        'apixaban', 'edoxaban'
    ];

    /**
     * Detect critical red flags in symptoms or diagnosis
     */
    public function detectRedFlags(array $symptoms, ?array $currentDiagnosis, ?array $patientData = []): array
    {
        $detectedRedFlags = [];
        $symptomsText = is_array($symptoms) ? implode(' ', $symptoms) : strtolower($symptoms);
        $diagnosisText = $currentDiagnosis['diagnosis_text'] ?? '';

        // ALSO check patient_data.symptoms for additional red flags (case-insensitive)
        // This ensures we catch ALL symptoms including those from Quick Entry
        $patientDataSymptoms = $patientData['symptoms'] ?? '';
        $combinedSymptomsText = $symptomsText;

        if (!empty($patientDataSymptoms)) {
            $patientDataSymptomsLower = strtolower($patientDataSymptoms);
            // Append patient_data.symptoms to ensure we check all symptoms
            if (!empty($symptomsText) && $symptomsText !== 'no symptoms provided') {
                $combinedSymptomsText = $symptomsText . ' ' . $patientDataSymptomsLower;
            } else {
                $combinedSymptomsText = $patientDataSymptomsLower;
                Log::info('Using patient_data.symptoms for red flag detection', ['symptomsText' => $combinedSymptomsText]);
            }
        }

        // Check for RED FLAG SYMPTOMS
        foreach (self::RED_FLAG_SYMPTOMS as $redFlag) {
            // Check combined symptoms text (case-insensitive)
            if (!empty($combinedSymptomsText) && (stripos($combinedSymptomsText, $redFlag) !== false || stripos($redFlag, $combinedSymptomsText) !== false)) {
                $alreadyDetected = false;
                foreach ($detectedRedFlags as $detected) {
                    if ($detected['flag'] === $redFlag) {
                        $alreadyDetected = true;
                        break;
                    }
                }
                if (!$alreadyDetected) {
                    $detectedRedFlags[] = [
                        'type' => 'symptom',
                        'flag' => $redFlag,
                        'source' => 'symptoms',
                        'severity' => $this->getRedFlagSeverity($redFlag)
                    ];
                }
            }
            // Also check diagnosis text
            if (!empty($diagnosisText) && (stripos(strtolower($diagnosisText), $redFlag) !== false || stripos($redFlag, strtolower($diagnosisText)) !== false)) {
                $alreadyDetected = false;
                foreach ($detectedRedFlags as $detected) {
                    if ($detected['flag'] === $redFlag) {
                        $alreadyDetected = true;
                        break;
                    }
                }
                if (!$alreadyDetected) {
                    $detectedRedFlags[] = [
                        'type' => 'diagnosis',
                        'flag' => $redFlag,
                        'source' => 'diagnosis',
                        'severity' => $this->getRedFlagSeverity($redFlag)
                    ];
                }
            }
        }

        // Additional red flags from patient data
        $clinicalNotes = $patientData['clinical_notes'] ?? '';
        $medicalHistory = $patientData['medical_history'] ?? '';

        if (!empty($clinicalNotes)) {
            $clinicalLower = strtolower($clinicalNotes);
            if (stripos($clinicalLower, 'cardiac') !== false || stripos($clinicalLower, 'heart') !== false) {
                $detectedRedFlags[] = [
                    'type' => 'cardiac_risk',
                    'flag' => 'cardiac_concern',
                    'source' => 'clinical_notes',
                    'severity' => 'high'
                ];
            }
        }

        return $detectedRedFlags;
    }

    private function getRedFlagSeverity(string $redFlag): string
    {
        $highSeverity = ['chest pain', 'chest tightness', 'shortness of breath', 'dyspnea',
                        'difficulty breathing', 'syncope', 'fainting', 'loss of consciousness',
                        'severe headache', 'sudden confusion', 'numbness', 'weakness on one side'];

        $mediumSeverity = ['acute abdominal pain', 'active bleeding', 'high fever'];

        if (in_array($redFlag, $highSeverity)) {
            return 'high';
        } elseif (in_array($redFlag, $mediumSeverity)) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Get cardiovascular risk factors from patient data
     */
    public function getCardiovascularRiskFactors(Appointment $appointment, ?array $patientData = []): array
    {
        $riskFactors = [];
        $patient = $appointment->patient;

        // Check medical history
        $medicalHistory = $patientData['medical_history'] ?? '';
        $pastDiagnoses = $patientData['past_diagnoses'] ?? [];

        $historyText = strtolower($medicalHistory);

        // Hypertension
        if (stripos($historyText, 'hypertension') !== false ||
            stripos($historyText, 'high blood pressure') !== false ||
            $this->hasDiagnosisContaining($pastDiagnoses, ['hypertension', 'high blood pressure'])) {
            $riskFactors[] = [
                'factor' => 'hypertension',
                'severity' => 'high',
                'description' => 'History of hypertension - avoid NSAIDs that can increase blood pressure'
            ];
        }

        // High cholesterol
        if (stripos($historyText, 'cholesterol') !== false ||
            stripos($historyText, 'hyperlipidemia') !== false ||
            $this->hasDiagnosisContaining($pastDiagnoses, ['cholesterol', 'hyperlipidemia', 'hypercholesterolemia'])) {
            $riskFactors[] = [
                'factor' => 'high_cholesterol',
                'severity' => 'medium',
                'description' => 'History of high cholesterol - cardiovascular risk factor'
            ];
        }

        // Heart disease
        if (stripos($historyText, 'heart disease') !== false ||
            stripos($historyText, 'coronary') !== false ||
            stripos($historyText, 'cardiac') !== false ||
            stripos($historyText, 'cardiovascular') !== false ||
            $this->hasDiagnosisContaining($pastDiagnoses, ['heart disease', 'coronary artery', 'cardiac'])) {
            $riskFactors[] = [
                'factor' => 'cardiovascular_disease',
                'severity' => 'high',
                'description' => 'History of cardiovascular disease - NSAIDs contraindicated until cardiac evaluation'
            ];
        }

        // Check active medications for antiplatelet/anticoagulant therapy
        $activeMeds = Prescription::getActiveForPatient($appointment->patient_id);
        $activeMedNames = $activeMeds->map(fn($p) => strtolower($p->medication_name))->toArray();

        // Also check patient_data.medications (from Quick Entry) if no active prescriptions found
        $patientDataMeds = $patientData['medications'] ?? '';
        if (empty($activeMedNames) && !empty($patientDataMeds)) {
            $patientDataMedsArray = is_array($patientDataMeds) ? $patientDataMeds : [$patientDataMeds];
            foreach ($patientDataMedsArray as $med) {
                $activeMedNames[] = strtolower($med);
            }
        }

        foreach ($activeMedNames as $medName) {
            foreach (self::ANTIPLATELET_MEDS as $antiplatelet) {
                if (strpos($medName, $antiplatelet) !== false) {
                    $riskFactors[] = [
                        'factor' => 'antiplatelet_therapy',
                        'severity' => 'high',
                        'description' => 'Patient on ' . ucfirst($antiplatelet) . ' - NSAIDs contraindicated (bleeding risk)',
                        'medication' => $medName
                    ];
                    break 2;
                }
            }
        }

        // Check for other cardiovascular medications
        $cardiovascularMeds = ['lisinopril', 'amlodipine', 'metoprolol', 'losartan', 'atenolol', 'hydrochlorothiazide'];
        foreach ($activeMedNames as $medName) {
            foreach ($cardiovascularMeds as $cvm) {
                if (strpos($medName, $cvm) !== false) {
                    $riskFactors[] = [
                        'factor' => 'cardiovascular_medication',
                        'severity' => 'medium',
                        'description' => 'Patient on cardiovascular medication - consider drug interactions',
                        'medication' => $medName
                    ];
                    break 2;
                }
            }
        }

        return $riskFactors;
    }

    /**
     * Check if any diagnoses contain specific keywords
     */
    private function hasDiagnosisContaining(array $diagnoses, array $keywords): bool
    {
        foreach ($diagnoses as $diagnosis) {
            $diagnosisText = $diagnosis['diagnosis_text'] ?? '';
            foreach ($keywords as $keyword) {
                if (stripos($diagnosisText, $keyword) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if medication suggestions should be blocked based on red flags
     */
    public function shouldBlockMedications(array $redFlags): bool
    {
        foreach ($redFlags as $flag) {
            if ($flag['severity'] === 'high') {
                return true;
            }
        }
        return false;
    }

    /**
     * Build clinical alert message for red flags
     */
    public function buildClinicalAlert(array $redFlags, array $riskFactors): array
    {
        $alert = [
            'requires_evaluation' => false,
            'alert_level' => 'none',
            'message' => '',
            'recommendations' => [],
            'blocked_medications' => []
        ];

        // Check for high severity red flags
        $highSeverityFlags = array_filter($redFlags, fn($f) => $f['severity'] === 'high');

        if (!empty($highSeverityFlags)) {
            $alert['requires_evaluation'] = true;
            $alert['alert_level'] = 'urgent';

            $flagNames = array_column($highSeverityFlags, 'flag');
            $alert['message'] = '🚨 RED FLAG DETECTED: ' . implode(', ', $flagNames) . ' - Urgent clinical evaluation required before any medication';

            // Add specific recommendations based on flags
            foreach ($highSeverityFlags as $flag) {
                switch ($flag['flag']) {
                    case 'chest pain':
                    case 'chest tightness':
                        $alert['recommendations'][] = 'ECG (12-lead) - rule out acute coronary syndrome';
                        $alert['recommendations'][] = 'Troponin levels';
                        $alert['recommendations'][] = 'Chest X-ray';
                        $alert['recommendations'][] = 'Monitor vital signs';
                        break;
                    case 'shortness of breath':
                    case 'dyspnea':
                    case 'difficulty breathing':
                        $alert['recommendations'][] = 'Pulse oximetry';
                        $alert['recommendations'][] = 'ECG if cardiac cause suspected';
                        $alert['recommendations'][] = 'Chest X-ray';
                        $alert['recommendations'][] = 'Consider D-dimer/PE protocol if applicable';
                        break;
                    case 'syncope':
                    case 'fainting':
                    case 'loss of consciousness':
                        $alert['recommendations'][] = 'ECG - rule out arrhythmia';
                        $alert['recommendations'][] = 'Orthostatic blood pressure';
                        $alert['recommendations'][] = 'Neurological assessment';
                        break;
                }
            }

            $alert['blocked_medications'] = self::NSAID_DRUGS;
            $alert['blocked_medications'][] = 'nsaids';
        }

        // Check for antiplatelet therapy risk factors
        $antiplateletFactors = array_filter($riskFactors, fn($r) => $r['factor'] === 'antiplatelet_therapy');
        if (!empty($antiplateletFactors)) {
            $alert['requires_evaluation'] = true;
            if ($alert['alert_level'] !== 'urgent') {
                $alert['alert_level'] = 'warning';
                $alert['message'] = '⚠️ HIGH-RISK MEDICATION INTERACTION: Patient on antiplatelet/anticoagulant therapy - NSAIDs contraindicated';
            }
            $alert['recommendations'][] = 'Avoid all NSAIDs (ibuprofen, naproxen, aspirin, etc.) - severe bleeding risk';
            $alert['recommendations'][] = 'Consider acetaminophen for pain relief';
            $alert['recommendations'][] = 'If anti-inflammatory needed, consult cardiology before NSAID use';

            if (empty($alert['blocked_medications'])) {
                $alert['blocked_medications'] = self::NSAID_DRUGS;
            } else {
                $alert['blocked_medications'] = array_unique(array_merge($alert['blocked_medications'], self::NSAID_DRUGS));
            }
        }

        // Check for cardiovascular disease
        $cvFactors = array_filter($riskFactors, fn($r) => $r['factor'] === 'cardiovascular_disease');
        if (!empty($cvFactors)) {
            $alert['requires_evaluation'] = true;
            if ($alert['alert_level'] !== 'urgent') {
                $alert['alert_level'] = 'warning';
                $alert['message'] = '⚠️ CARDIOVASCULAR RISK: History of cardiovascular disease - NSAIDs require cardiac evaluation';
            }
            $alert['recommendations'][] = 'Cardiac evaluation required before NSAID use';
            $alert['recommendations'][] = 'Consider alternatives to NSAIDs';
        }

        return $alert;
    }

    /**
     * Calibrate confidence level based on data completeness and risk factors
     */
    public function calibrateConfidence(array $redFlags, array $riskFactors, bool $hasCompleteData): int
    {
        // If high severity red flags present, confidence should be 0 (blocked)
        $highSeverityFlags = array_filter($redFlags, fn($f) => $f['severity'] === 'high');
        if (!empty($highSeverityFlags)) {
            return 0;
        }

        // If antiplatelet therapy present, very low confidence for NSAIDs
        $antiplateletFactors = array_filter($riskFactors, fn($r) => $r['factor'] === 'antiplatelet_therapy');
        if (!empty($antiplateletFactors)) {
            return 20; // Low because we're essentially blocking NSAIDs
        }

        // Start with base confidence based on data completeness
        $baseConfidence = $hasCompleteData ? 75 : 50;

        // Reduce for risk factors
        if (!empty($riskFactors)) {
            $baseConfidence -= 15;
        }

        // Reduce for medium severity flags
        $mediumSeverityFlags = array_filter($redFlags, fn($f) => $f['severity'] === 'medium');
        if (!empty($mediumSeverityFlags)) {
            $baseConfidence -= 20;
        }

        return max(10, min(90, $baseConfidence));
    }

    /**
     * Build risk assessment prompt section
     */
    private function buildRiskAssessmentPromptSection(array $redFlags, array $riskFactors): string
    {
        $section = "\n\n=== CRITICAL SAFETY ASSESSMENT ===\n";

        if (empty($redFlags) && empty($riskFactors)) {
            $section .= "No red flags or high-risk factors detected.\n";
            return $section;
        }

        if (!empty($redFlags)) {
            $section .= "RED FLAGS DETECTED:\n";
            foreach ($redFlags as $flag) {
                $section .= "- [" . $flag['severity'] . " severity] {$flag['flag']} (source: {$flag['source']})\n";
            }
            $section .= "ACTION: These symptoms require clinical evaluation BEFORE any medication suggestions.\n\n";
        }

        if (!empty($riskFactors)) {
            $section .= "HIGH-RISK PATIENT FACTORS:\n";
            foreach ($riskFactors as $factor) {
                $section .= "- {$factor['description']}\n";
            }
        }

        // Build clinical alert
        $alert = $this->buildClinicalAlert($redFlags, $riskFactors);
        if ($alert['requires_evaluation']) {
            $section .= "\n🚨 CLINICAL ALERT: {$alert['message']}\n";
            $section .= "RECOMMENDED EVALUATIONS:\n";
            foreach ($alert['recommendations'] as $rec) {
                $section .= "- {$rec}\n";
            }
            $section .= "\nMEDICATIONS BLOCKED: " . implode(', ', array_unique($alert['blocked_medications'])) . "\n";
        }

        return $section;
    }

    /**
     * Generate AI suggestions for prescription medications based on appointment data
     * IMPORTANT: This is a CLINICAL DECISION SUPPORT TOOL - NOT a substitute for professional medical judgment
     */
    public function generatePrescriptionSuggestions(Appointment $appointment, array $symptoms, array $allergies, array $pastMeds, array $additionalData = [])
    {
        // Check if AI prescription suggestions are enabled
        if (!config('ai.prescription_suggestions.enabled', false)) { // Default to false for safety
            Log::info('AI prescription suggestions disabled by feature flag');
            return [
                'suggestions' => [],
                'risk_flags' => [
                    'AI prescription suggestions are currently disabled for safety reasons.',
                    'Please consult with a licensed healthcare professional for medication recommendations.'
                ],
                'message' => 'AI prescription suggestions are disabled',
                'source' => 'disabled',
                'disabled' => true,
                'disclaimer' => 'This feature is disabled to ensure patient safety. All medication decisions must be made by qualified healthcare professionals.'
            ];
        }

        // Log request details for debugging
        Log::info('AI Suggestion Request', [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'symptoms' => $symptoms,
            'allergies_count' => count($allergies),
            'past_meds_count' => count($pastMeds),
            'has_openai_config' => !empty(config('openai.api_key')),
            'ai_enabled' => config('ai.enabled', true),
            'prescription_suggestions_enabled' => config('ai.prescription_suggestions.enabled', true),
        ]);

        // Check OpenAI configuration
        if (empty(config('openai.api_key'))) {
            Log::error('OpenAI API key not configured');
            return [
                'suggestions' => [[
                    'med' => 'OpenAI Not Configured',
                    'dosage' => 'N/A',
                    'freq' => 'N/A',
                    'dur' => 'N/A',
                    'confidence' => 0,
                    'reason' => 'OpenAI API key is not configured. Please contact administrator to set up AI features.'
                ]],
                'risk_flags' => ['OpenAI API key not configured in environment variables'],
                'message' => 'OpenAI API key not configured',
                'source' => 'config_error',
                'fallback' => true,
                'error_reason' => 'OpenAI API key not configured'
            ];
        }

        // ========================================
        // PHASE 1: RED FLAG DETECTION (Critical Safety)
        // ========================================
        $patientData = $additionalData['current_diagnosis']['patient_data'] ?? [];
        $pastDiagnoses = $additionalData['past_diagnoses'] ?? [];

        $redFlags = $this->detectRedFlags($symptoms, $additionalData['current_diagnosis'] ?? null, $patientData);
        $riskFactors = $this->getCardiovascularRiskFactors($appointment, array_merge($patientData, ['past_diagnoses' => $pastDiagnoses]));

        // Check if medications should be blocked due to red flags or high-risk factors
        $blockMedications = $this->shouldBlockMedications($redFlags);
        $clinicalAlert = $this->buildClinicalAlert($redFlags, $riskFactors);

        // If red flags detected, return urgent evaluation response WITHOUT calling AI
        if ($blockMedications || $clinicalAlert['requires_evaluation']) {
            Log::info('Red flags or high-risk factors detected - blocking medication suggestions', [
                'appointment_id' => $appointment->id,
                'red_flags' => $redFlags,
                'risk_factors' => $riskFactors,
                'alert_level' => $clinicalAlert['alert_level']
            ]);

            return [
                'suggestions' => [],
                'risk_flags' => array_merge([
                    '🚨 RED FLAG DETECTED - Urgent clinical evaluation required',
                    '⚠️ Medication suggestions are BLOCKED until proper evaluation is completed',
                    '⚠️ AI cannot safely suggest medications with these clinical findings'
                ], $clinicalAlert['recommendations']),
                'clinical_alert' => $clinicalAlert,
                'requires_evaluation' => true,
                'evaluation_recommendations' => $clinicalAlert['recommendations'],
                'blocked_medications' => $clinicalAlert['blocked_medications'],
                'clinical_data_used' => $this->buildClinicalDataSummary($symptoms, $additionalData),
                'message' => 'Clinical evaluation required before AI medication suggestions',
                'source' => 'red_flag_override',
                'confidence' => 0,
                'disclaimer' => 'AI medication suggestions are blocked due to critical red flags. Professional clinical evaluation is required before any medication decisions.',
                'generated_at' => now()->toISOString(),
            ];
        }

        try {
            // Create intelligent prompt for OpenAI with enhanced safety instructions
            // Include risk assessment section in the prompt
            $prompt = $this->buildMedicationPrompt($symptoms, $allergies, $pastMeds, $appointment, $additionalData, $redFlags, $riskFactors);

            Log::info('Calling OpenAI API for prescription suggestions', [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'model' => 'gpt-4o',
                'prompt_length' => strlen($prompt),
                'max_tokens' => 1200,
                'temperature' => 0.1,
                'has_response_format' => true,
                'prompt_preview' => substr($prompt, 0, 500) . '...',
            ]);

            // Call OpenAI GPT-4o with enhanced safety and JSON enforcement
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a CLINICAL DECISION SUPPORT AI assistant. You MUST respond ONLY with valid JSON. No explanations, no markdown, no additional text outside JSON structure.

CRITICAL SAFETY REQUIREMENTS:
1. These are SUGGESTIONS ONLY - not prescriptions
2. Always include risk warnings and contraindications
3. Never suggest controlled substances without proper medical context
4. Always check for drug interactions
5. Include appropriate disclaimers
6. For chest pain, shortness of breath, or cardiac risk factors: do NOT suggest NSAIDs - recommend evaluation first
7. For patients on antiplatelet/anticoagulant therapy: NEVER suggest NSAIDs (severe bleeding risk)

REQUIRED JSON FORMAT:
{
  "suggestions": [
    {
      "med": "Medication Name",
      "dosage": "Standard dosage",
      "freq": "Frequency",
      "dur": "Duration",
      "confidence": 85,
      "reason": "Clinical rationale",
      "warnings": ["Array of warnings"],
      "interactions": ["Array of drug interactions"]
    }
  ],
  "risk_flags": [
    "⚠️ CLINICAL DECISION SUPPORT ONLY - Professional medical judgment required",
    "⚠️ Verify patient allergies and contraindications",
    "⚠️ Check current medications for interactions",
    "⚠️ Consider patient age, weight, and renal/hepatic function"
  ],
  "requires_evaluation": false,
  "clinical_alert": null
}'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1200, // Increased for safety information
                'temperature' => 0.1, // Very low temperature for consistency
                'response_format' => ['type' => 'json_object'], // Force JSON response
            ]);

            Log::info('OpenAI API call successful', [
                'response_id' => $response->id ?? null,
                'model' => $response->model ?? null,
                'usage' => $response->usage ?? null,
            ]);

            // Parse and validate the AI response
            $aiContent = $response->choices[0]->message->content;

            Log::info('OpenAI response content', [
                'content_length' => strlen($aiContent),
                'content_preview' => substr($aiContent, 0, 200) . (strlen($aiContent) > 200 ? '...' : ''),
            ]);

            // Validate and parse JSON response
            $parsedResponse = $this->validateAndParseJsonResponse($aiContent);

            // Log the AI response structure for debugging prescription suggestions
            Log::info('AI Response Structure for Prescription Suggestions', [
                'appointment_id' => $appointment->id,
                'suggestions' => $parsedResponse['suggestions'] ?? [],
                'risk_flags' => $parsedResponse['risk_flags'] ?? [],
                'response_keys' => array_keys($parsedResponse),
            ]);

            $suggestions = $parsedResponse['suggestions'] ?? [];
            $risk_flags = $parsedResponse['risk_flags'] ?? [];

            // Validate structure and provide fallbacks
            $validationResult = $this->validateResponseStructure($suggestions, $risk_flags);

            if (!$validationResult['valid']) {
                Log::warning('Invalid response structure, using fallbacks', [
                    'issues' => $validationResult['issues'],
                    'original_suggestions' => $suggestions,
                    'original_risk_flags' => $risk_flags,
                ]);

                $suggestions = $validationResult['fallback_suggestions'];
                $risk_flags = $validationResult['fallback_risk_flags'];
            }

            Log::info('AI suggestions generated successfully', [
                'suggestions_count' => count($suggestions),
                'risk_flags_count' => count($risk_flags),
            ]);

            // Add mandatory safety disclaimers and professional warnings
            $mandatoryRiskFlags = [
                '⚠️ CLINICAL DECISION SUPPORT ONLY - Not a substitute for professional medical judgment',
                '⚠️ Verify all allergies, contraindications, and drug interactions before prescribing',
                '⚠️ Consider patient age, weight, renal/hepatic function, and pregnancy status',
                '⚠️ These suggestions are generated by AI and require clinical validation',
                '⚠️ Always consult current medical literature and guidelines'
            ];

            $risk_flags = array_merge($mandatoryRiskFlags, $risk_flags);

            // Include clinical data used in response - ONLY doctor-written content
            $clinicalDataUsed = [];
            $symptomsText = is_array($symptoms) ? implode(', ', $symptoms) : $symptoms;
            if (!empty($symptomsText)) $clinicalDataUsed['symptoms'] = $symptomsText;
            if (!empty($additionalData['doctor_notes'] ?? '')) $clinicalDataUsed['doctor_notes'] = $additionalData['doctor_notes'];

            // Include CURRENT DIAGNOSIS in clinical data tracking
            if (!empty($additionalData['current_diagnosis']['diagnosis_text'] ?? '')) {
                // Check if this is doctor-written vs AI-generated
                $diagnosisText = $additionalData['current_diagnosis']['diagnosis_text'];
                $aiAnalysis = $additionalData['current_diagnosis']['ai_analysis'] ?? '';
                $patientData = $additionalData['current_diagnosis']['patient_data'] ?? [];

                // Skip placeholder diagnoses - use clinical_notes from patient_data if available
                $isPlaceholder = stripos($diagnosisText, 'Quick data entry') !== false ||
                               stripos($diagnosisText, 'AI prescription support') !== false;

                if ($isPlaceholder && !empty($patientData['clinical_notes'])) {
                    // Use clinical_notes from patient_data for quick entry
                    $clinicalDataUsed['current_diagnosis'] = $patientData['clinical_notes'];
                    if (!empty($patientData['allergies'])) {
                        $clinicalDataUsed['allergies'] = $patientData['allergies'];
                    }
                } elseif (!$isPlaceholder && (empty($aiAnalysis) || trim($diagnosisText) !== trim($aiAnalysis))) {
                    // Only include if it's different from AI analysis (meaning doctor modified it)
                    // or if there's no AI analysis (manual diagnosis)
                    $clinicalDataUsed['current_diagnosis'] = $diagnosisText;
                }
            }

            // Include PAST DIAGNOSES in clinical data tracking
            if (!empty($additionalData['past_diagnoses']) && is_array($additionalData['past_diagnoses'])) {
                $pastDiagnosisList = [];
                foreach ($additionalData['past_diagnoses'] as $pastDiagnosis) {
                    if (!empty($pastDiagnosis['diagnosis_text'])) {
                        $diagnosisText = $pastDiagnosis['diagnosis_text'];
                        $aiAnalysis = $pastDiagnosis['ai_analysis'] ?? '';

                        // Only include doctor-written diagnoses
                        if (empty($aiAnalysis) || trim($diagnosisText) !== trim($aiAnalysis)) {
                            $pastDiagnosisList[] = $diagnosisText;
                        }
                    }
                }

                if (!empty($pastDiagnosisList)) {
                    $clinicalDataUsed['past_diagnoses'] = $pastDiagnosisList;
                }
            }

            // Include voice diagnosis if available
            if (!empty($additionalData['voice_diagnosis'] ?? '')) {
                $clinicalDataUsed['voice_diagnosis'] = $additionalData['voice_diagnosis'];
            }

            $result = [
                'suggestions' => $suggestions,
                'risk_flags' => array_unique($risk_flags), // Remove duplicates
                'clinical_data_used' => $clinicalDataUsed,
                'message' => 'AI clinical decision support suggestions generated',
                'source' => 'openai',
                'disclaimer' => 'These are AI-generated suggestions for clinical decision support only. All medication decisions must be made by qualified healthcare professionals after thorough clinical evaluation.',
                'generated_at' => now()->toISOString(),
                'ai_model' => 'gpt-4o',
                'confidence_level' => 'support_only', // Not diagnostic
                'requires_evaluation' => false,
                'clinical_alert' => $clinicalAlert
            ];

            Log::info('AI Service Response', [
                'suggestions_count' => count($suggestions),
                'first_suggestion' => $suggestions[0] ?? null,
                'risk_flags_count' => count($risk_flags),
                'first_risk_flag' => $risk_flags[0] ?? null,
            ]);

            return $result;

        } catch (\OpenAI\Exceptions\AuthenticationException $e) {
            Log::error('OpenAI Authentication Error', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
            ]);
            return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'OpenAI authentication failed - check API key', $additionalData);

        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            Log::error('OpenAI Rate Limit Error', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
            ]);
            return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'OpenAI rate limit exceeded', $additionalData);

        } catch (\OpenAI\Exceptions\InvalidArgumentException $e) {
            Log::error('OpenAI Invalid Argument Error', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
            ]);
            return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'OpenAI invalid request parameters', $additionalData);

        } catch (\OpenAI\Exceptions\TransporterException $e) {
            Log::error('OpenAI Transporter Error', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
            ]);
            return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'OpenAI network/connection error', $additionalData);

        } catch (\Exception $e) {
            Log::error('OpenAI General Error in aiSuggest', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'appointment_id' => $appointment->id,
                'trace' => $e->getTraceAsString(),
            ]);

            // Check if this is a JSON parsing error - try one more time with a stricter prompt
            if (strpos($e->getMessage(), 'JSON') !== false || strpos($e->getMessage(), 'parse') !== false) {
                Log::info('Attempting retry with stricter JSON prompt', ['appointment_id' => $appointment->id]);

                try {
                    $retryResponse = OpenAI::chat()->create([
                        'model' => 'gpt-4o',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'CRITICAL: Respond ONLY with valid JSON. No text before or after. Format: {"suggestions": [], "risk_flags": [], "disclaimer": ""}'
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt . "\n\nREMINDER: Respond with valid JSON only."
                            ]
                        ],
                        'max_tokens' => 800,
                        'temperature' => 0.0, // Zero temperature for maximum consistency
                        'response_format' => ['type' => 'json_object'], // Force JSON response
                    ]);

                    $retryContent = $retryResponse->choices[0]->message->content;
                    $parsedResponse = $this->validateAndParseJsonResponse($retryContent, 1);

                    $suggestions = $parsedResponse['suggestions'] ?? [];
                    $risk_flags = $parsedResponse['risk_flags'] ?? [];

                    $validationResult = $this->validateResponseStructure($suggestions, $risk_flags);

                    if ($validationResult['valid']) {
                        Log::info('Retry successful', ['appointment_id' => $appointment->id]);

                        return [
                            'suggestions' => $validationResult['fallback_suggestions'],
                            'risk_flags' => $validationResult['fallback_risk_flags'],
                            'message' => 'AI suggestions generated successfully (retry)',
                            'source' => 'openai',
                            'retried' => true
                        ];
                    }
                } catch (\Exception $retryException) {
                    Log::warning('Retry also failed', [
                        'appointment_id' => $appointment->id,
                        'original_error' => $e->getMessage(),
                        'retry_error' => $retryException->getMessage(),
                    ]);
                }
            }

            // Fallback to basic logic-based suggestions
            return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'OpenAI API error: ' . $e->getMessage(), $additionalData);
        }
    }

    /**
     * Process patient data for AI analysis
     */
    public function processPatientData($patient)
    {
        $patientData = $patient->patientData()->first();

        return [
            'allergies' => $patientData ? ($patientData->allergies ?? []) : [],
            'past_medications' => $patientData ? ($patientData->past_medications ?? []) : [],
            'symptoms' => $patientData ? ($patientData->symptoms ?? []) : [],
            'age' => $patient->age ?? ($patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : null),
            'gender' => $patient->gender ?? null,
            'name' => $patient->name ?? null,
        ];
    }

    /**
     * Build professional, safety-focused prompt for medication suggestions
     * Now includes risk assessment first (risk-first approach)
     */
    private function buildMedicationPrompt($symptoms, $allergies, $pastMeds, Appointment $appointment, $additionalData = [], $redFlags = [], $riskFactors = [])
    {
        $symptomsText = is_array($symptoms) ? implode(', ', $symptoms) : $symptoms;

        // Debug logging
        Log::info('buildMedicationPrompt INPUT', [
            'symptoms' => $symptoms,
            'symptomsText' => $symptomsText,
            'allergies_count' => count($allergies),
            'pastMeds_count' => count($pastMeds),
            'additionalData_keys' => array_keys($additionalData),
            'doctor_notes' => $additionalData['doctor_notes'] ?? 'NOT SET',
            'current_diagnosis_text' => $additionalData['current_diagnosis']['diagnosis_text'] ?? 'NOT SET',
        ]);

        // Get patient demographics from appointment
        $patient = $appointment->patient;
        $patientAge = $patient ? ($patient->age ?? ($patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : 'Unknown')) : 'Unknown';
        $patientGender = $patient ? ($patient->gender ?? 'Unknown') : 'Unknown';
        $patientName = $patient ? $patient->name : 'Unknown Patient';

        // Use doctor-verified clinical data from multiple sources
        $doctorNotes = $additionalData['doctor_notes'] ?? '';
        $currentDiagnosis = $additionalData['current_diagnosis'] ?? null;
        $pastDiagnoses = $additionalData['past_diagnoses'] ?? [];
        $voiceDiagnosis = $additionalData['voice_diagnosis'] ?? '';

        // Combine verified clinical data sources - ONLY DOCTOR-WRITTEN CONTENT
        // NEVER use patient-reported reason_for_visit
        $clinicalData = [];
        if (!empty($symptomsText)) $clinicalData[] = "Symptoms: " . $symptomsText;
        if (!empty($doctorNotes)) $clinicalData[] = "Doctor Notes: " . $doctorNotes;

        // Include CURRENT DIAGNOSIS (primary clinical driver)
        // Skip "Quick data entry" placeholder - use patient_data.clinical_notes instead
        if ($currentDiagnosis && isset($currentDiagnosis['diagnosis_text'])) {
            $diagnosisText = $currentDiagnosis['diagnosis_text'];
            $aiAnalysis = $currentDiagnosis['ai_analysis'] ?? '';
            $patientData = $currentDiagnosis['patient_data'] ?? [];

            // Skip placeholder diagnoses - use clinical_notes from patient_data if available
            $isPlaceholder = stripos($diagnosisText, 'Quick data entry') !== false ||
                           stripos($diagnosisText, 'AI prescription support') !== false;

            if ($isPlaceholder && !empty($patientData['clinical_notes'])) {
                // Use clinical_notes from patient_data for quick entry
                $clinicalData[] = "Clinical Notes: " . $patientData['clinical_notes'];
                // Add allergies and medications if available
                if (!empty($patientData['allergies'])) {
                    $clinicalData[] = "Patient Allergies (from quick entry): " . $patientData['allergies'];
                }
                // Add medical history if available
                if (!empty($patientData['medical_history'])) {
                    $clinicalData[] = "Medical History: " . $patientData['medical_history'];
                }
                // Add current medications if available
                if (!empty($patientData['medications'])) {
                    $clinicalData[] = "Current Medications (from quick entry): " . $patientData['medications'];
                }
            } elseif (!$isPlaceholder && (empty($aiAnalysis) || trim($diagnosisText) !== trim($aiAnalysis))) {
                // Only use if doctor actually wrote/modified the diagnosis (and it's not a placeholder)
                $clinicalData[] = "Current Diagnosis: " . $diagnosisText;
            }
        }

        // Include PAST DIAGNOSIS HISTORY (clinical context)
        if (!empty($pastDiagnoses) && is_array($pastDiagnoses)) {
            $pastDiagnosisTexts = [];
            foreach ($pastDiagnoses as $pastDiagnosis) {
                if (isset($pastDiagnosis['diagnosis_text'])) {
                    $diagnosisText = $pastDiagnosis['diagnosis_text'];
                    $diagnosisDate = isset($pastDiagnosis['created_at']) ?
                        \Carbon\Carbon::parse($pastDiagnosis['created_at'])->format('M j, Y') : 'Unknown date';

                    // Only include doctor-written diagnoses
                    $aiAnalysis = $pastDiagnosis['ai_analysis'] ?? '';
                    if (empty($aiAnalysis) || trim($diagnosisText) !== trim($aiAnalysis)) {
                        $pastDiagnosisTexts[] = "{$diagnosisText} ({$diagnosisDate})";
                    }
                }
            }

            if (!empty($pastDiagnosisTexts)) {
                $clinicalData[] = "Past Diagnosis History: " . implode('; ', $pastDiagnosisTexts);
            }
        }

        // Include voice assistant diagnosis if available
        if (!empty($voiceDiagnosis)) {
            $clinicalData[] = "Voice Assistant Diagnosis: " . $voiceDiagnosis;
        }

        $verifiedClinicalText = implode("\n", $clinicalData);

        // CRITICAL SAFETY: Check if we have ONLY patient-reported symptoms (unreliable)
        // Patient-reported data should NEVER be used for medication decisions
        $hasOnlyPatientSymptoms = empty($doctorNotes) && empty($currentDiagnosis) && empty($pastDiagnoses) && empty($voiceDiagnosis);

        // CRITICAL SAFETY CHECK: Verify essential data is available
        $missingCriticalData = [];
        if (empty($allergies)) {
            $missingCriticalData[] = 'Patient Allergies';
        }
        // Get current active medications
        $activeMeds = [];
        if ($appointment->patient_id) {
            $activePrescriptions = Prescription::getActiveForPatient($appointment->patient_id);
            $activeMeds = $activePrescriptions->map(function ($prescription) {
                return $prescription->medication_name;
            })->toArray();
        }
        
        if (empty($activeMeds) && empty($pastMeds)) {
            $missingCriticalData[] = 'Current/Past Medications';
        }
        if (empty($doctorNotes) && empty($currentDiagnosis)) {
            $missingCriticalData[] = 'Doctor Clinical Assessment (notes or diagnosis)';
        }

        // BLOCK AI suggestions if critical data is missing
        if (!empty($missingCriticalData)) {
            $symptomsText = '⚠️ CRITICAL DATA MISSING - CANNOT PROVIDE MEDICATION SUGGESTIONS\n\n';
            $symptomsText .= 'The following essential clinical data is missing:\n';
            foreach ($missingCriticalData as $missing) {
                $symptomsText .= '- ' . $missing . '\n';
            }
            $symptomsText .= '\nFor patient safety, AI medication suggestions require:\n';
            $symptomsText .= '1. Patient allergy information (to prevent allergic reactions)\n';
            $symptomsText .= '2. Current/past medications (to check drug interactions)\n';
            $symptomsText .= '3. Doctor clinical assessment (notes or diagnosis)\n\n';
            $symptomsText .= 'Please complete this information before requesting AI medication suggestions.';
            
            // Return early with error message instead of sending to OpenAI
            Log::info('Critical data missing, returning early without OpenAI call', [
                'missing_data' => $missingCriticalData,
                'appointment_id' => $appointment->id
            ]);
            
            return [
                'suggestions' => [[
                    'med' => 'Critical Data Missing',
                    'dosage' => 'N/A',
                    'freq' => 'N/A',
                    'dur' => 'N/A',
                    'confidence' => 0,
                    'reason' => 'Missing critical safety data: ' . implode(', ', $missingCriticalData),
                    'warnings' => ['Complete patient allergies, medications, and clinical assessment before requesting AI suggestions'],
                    'interactions' => ['Cannot check interactions without complete medication history']
                ]],
                'risk_flags' => [
                    '⚠️ CRITICAL DATA MISSING - ' . implode(', ', $missingCriticalData),
                    '⚠️ Patient safety requires complete allergy and medication information',
                    '⚠️ Professional medical judgment required for all medication decisions'
                ],
                'message' => 'Critical data missing - complete patient information required',
                'source' => 'safety_block',
                'blocked' => true,
                'missing_data' => $missingCriticalData
            ];
        } else if (empty($verifiedClinicalText) || $hasOnlyPatientSymptoms) {
            // Return early with error message instead of sending to OpenAI
            Log::info('No verified clinical data, returning early without OpenAI call', [
                'has_only_patient_symptoms' => $hasOnlyPatientSymptoms,
                'verified_clinical_text_empty' => empty($verifiedClinicalText),
                'appointment_id' => $appointment->id
            ]);
            
            return [
                'suggestions' => [[
                    'med' => 'Clinical Assessment Required',
                    'dosage' => 'N/A',
                    'freq' => 'N/A',
                    'dur' => 'N/A',
                    'confidence' => 0,
                    'reason' => 'No verified clinical data available for medication recommendations',
                    'warnings' => ['Doctor clinical assessment required before AI medication suggestions'],
                    'interactions' => ['Cannot check interactions without verified clinical data']
                ]],
                'risk_flags' => [
                    '⚠️ NO VERIFIED CLINICAL DATA - Doctor assessment required',
                    '⚠️ Patient-reported symptoms insufficient for medication decisions',
                    '⚠️ Professional medical judgment required for all medication decisions'
                ],
                'message' => 'No verified clinical data - doctor assessment required',
                'source' => 'clinical_data_block',
                'blocked' => true
            ];
        } else {
            $symptomsText = $verifiedClinicalText;
        }

        $hasVerifiedData = !empty($verifiedClinicalText) && !$hasOnlyPatientSymptoms;

        // ========================================
        // NEW: ADD RISK ASSESSMENT AT THE START
        // ========================================
        $riskAssessmentSection = $this->buildRiskAssessmentPromptSection($redFlags, $riskFactors);

        $prompt = "CLINICAL DECISION SUPPORT - MEDICATION SUGGESTIONS\n";
        $prompt .= "===============================================\n\n";

        // FIRST: Add risk assessment section
        $prompt .= $riskAssessmentSection;

        $prompt .= "PATIENT DEMOGRAPHICS:\n";
        $prompt .= "- Age: {$patientAge} years\n";
        $prompt .= "- Gender: {$patientGender}\n\n";

        $prompt .= "VERIFIED CLINICAL DATA:\n";
        $prompt .= "- Clinical Presentation: {$symptomsText}\n";
        $prompt .= "- Appointment Type: {$appointment->appointment_type}\n";

        // Add structured diagnosis information
        if ($currentDiagnosis && isset($currentDiagnosis['diagnosis_text'])) {
            $currentDiagnosisText = $currentDiagnosis['diagnosis_text'];
            $currentDiagnosisDate = isset($currentDiagnosis['created_at']) ?
                \Carbon\Carbon::parse($currentDiagnosis['created_at'])->format('M j, Y') : 'Recent';

            $prompt .= "\nCURRENT DIAGNOSIS (Primary Clinical Driver):\n";
            $prompt .= "- Diagnosis: {$currentDiagnosisText}\n";
            $prompt .= "- Date: {$currentDiagnosisDate}\n";
            $prompt .= "- Purpose: Primary driver for medication selection and treatment planning\n";
        }

        if (!empty($pastDiagnoses) && is_array($pastDiagnoses)) {
            $prompt .= "\nPAST DIAGNOSIS HISTORY (Clinical Context):\n";
            foreach (array_slice($pastDiagnoses, 0, 5) as $pastDiagnosis) { // Limit to 5 most recent past diagnoses
                if (isset($pastDiagnosis['diagnosis_text'])) {
                    $pastDiagnosisText = $pastDiagnosis['diagnosis_text'];
                    $pastDiagnosisDate = isset($pastDiagnosis['created_at']) ?
                        \Carbon\Carbon::parse($pastDiagnosis['created_at'])->format('M j, Y') : 'Unknown';

                    $prompt .= "- {$pastDiagnosisText} ({$pastDiagnosisDate})\n";
                }
            }
            $prompt .= "- Purpose: Provides context for comorbidities, treatment responses, and disease progression\n";
        }

        // CRITICAL SAFETY: Only use doctor-verified clinical data
        if ($hasVerifiedData) {
            $prompt .= "- Data Source: Doctor-verified clinical documentation (symptoms, diagnosis, clinical notes)\n";
        } else {
            $prompt .= "- Data Source: No verified clinical data available - requires professional documentation\n";
        }
        $prompt .= "\n";

        $prompt .= "DATA SOURCE RELIABILITY HIERARCHY:\n";
        $prompt .= "1. PRIMARY: Doctor clinical notes and observations\n";
        $prompt .= "2. SECONDARY: Formal diagnosis records\n";
        $prompt .= "3. TERTIARY: Voice assistant clinical assessments\n";
        $prompt .= "❌ EXCLUDED: Patient-reported symptoms (unreliable for medication decisions)\n\n";

        $prompt .= "CRITICAL SAFETY REQUIREMENTS:\n";
        $prompt .= "- NEVER suggest medications based on patient-reported symptoms alone\n";
        $prompt .= "- Require DOCTOR verification of all clinical findings\n";
        $prompt .= "- If only patient symptoms are available, respond that doctor verification is required\n";
        $prompt .= "- Only suggest medications when supported by doctor clinical documentation\n\n";

        // ========================================
        // NEW: ADD EXPLICIT NSAID CONTRAINDICATIONS
        // ========================================
        $prompt .= "EXPLICIT MEDICATION CONTRAINDICATIONS (HARD RULES):\n";
        $prompt .= "- NEVER suggest NSAIDs (ibuprofen, naproxen, diclofenac, aspirin, etc.) to patients on antiplatelet therapy (Aspirin, Clopidogrel, Warfarin, etc.) - SEVERE BLEEDING RISK\n";
        $prompt .= "- NEVER suggest NSAIDs to patients with cardiovascular disease history until cardiac cause is EXCLUDED by proper evaluation\n";
        $prompt .= "- NEVER suggest NSAIDs to patients with hypertension - can worsen blood pressure control\n";
        $prompt .= "- If patient has chest pain or shortness of breath WITH cardiovascular risk factors, AVOID ALL NSAIDs until cardiac workup complete\n";
        $prompt .= "- For pain in patients with上述 contraindications, recommend acetaminophen FIRST\n\n";

        // Critical safety information
        $prompt .= "CRITICAL SAFETY INFORMATION:\n";

        if (!empty($allergies)) {
            $allergiesList = implode(', ', $allergies);
            $prompt .= "- KNOWN ALLERGIES: " . $allergiesList . "\n";

            // Specific allergy warnings for common medication classes
            if (in_array('penicillin', array_map('strtolower', $allergies)) ||
                in_array('penicillins', array_map('strtolower', $allergies)) ||
                in_array('beta-lactam', array_map('strtolower', $allergies))) {
                $prompt .= "- PENICILLIN/BETA-LACTAM ALLERGY: Patient has penicillin allergy. NEVER suggest penicillins (amoxicillin, ampicillin, etc.), cephalosporins, or other beta-lactam antibiotics.\n";
            }

            if (in_array('sulfa', array_map('strtolower', $allergies)) ||
                in_array('sulfonamides', array_map('strtolower', $allergies)) ||
                in_array('sulfamethoxazole', array_map('strtolower', $allergies))) {
                $prompt .= "- SULFA ALLERGY: Patient has sulfa allergy. NEVER suggest sulfonamide antibiotics (Bactrim, Septra, etc.).\n";
            }

            if (in_array('codeine', array_map('strtolower', $allergies))) {
                $prompt .= "- CODEINE ALLERGY: Patient has codeine allergy. Avoid codeine and other opioids with similar structures.\n";
            }
        } else {
            $prompt .= "- Allergies: None reported (verify with patient)\n";
        }

        // Format active medications with detailed information for prompt
        $activeMedsDetailed = [];
        if (!empty($activeMeds)) {
            $activePrescriptions = Prescription::getActiveForPatient($appointment->patient_id);
            $activeMedsDetailed = $activePrescriptions->map(function ($prescription) {
                return $prescription->medication_name . ' (' . $prescription->dosage . ' ' . $prescription->frequency . ', started ' .
                       ($prescription->start_date ? $prescription->start_date->format('M j, Y') : 'unknown') . ')';
            })->toArray();
        }

        if (!empty($activeMedsDetailed)) {
            $prompt .= "- CURRENT MEDICATIONS: " . implode('; ', $activeMedsDetailed) . "\n";
        } else {
            $prompt .= "- Current Medications: None active\n";
        }

        if (!empty($pastMeds)) {
            $prompt .= "- PAST/REPORTED MEDICATIONS: " . implode(', ', $pastMeds) . "\n";
        } else {
            $prompt .= "- Past Medications: None reported\n";
        }

        // Include medical history if available
        $medicalHistory = null;
        if ($currentDiagnosis && isset($currentDiagnosis['patient_data']['medical_history'])) {
            $medicalHistory = $currentDiagnosis['patient_data']['medical_history'];
        }
        if (!empty($medicalHistory)) {
            $prompt .= "- MEDICAL HISTORY: " . $medicalHistory . "\n";
            $prompt .= "- IMPORTANT: Consider how this medical history affects medication choices\n";
        }

        $prompt .= "\nPROFESSIONAL INSTRUCTIONS:\n";
        $prompt .= "1. You are providing CLINICAL DECISION SUPPORT - NOT making prescriptions\n";
        $prompt .= "2. Always prioritize patient safety above all else\n";
        $prompt .= "3. Check for drug-drug interactions with current medications\n";
        $prompt .= "4. Consider contraindications based on allergies and medical history\n";
        $prompt .= "5. For vague/general check-ups, focus on PREVENTIVE CARE and SCREENING rather than specific medications\n";
        $prompt .= "6. Suggest evidence-based medications ONLY when symptoms are clear and specific\n";
        $prompt .= "7. For general check-ups, recommend health screenings, vaccinations, and wellness assessments\n";
        $prompt .= "8. Include specific warnings for high-risk situations\n";
        $prompt .= "9. If symptoms are unclear or data insufficient, recommend consultation with specialist\n";
        $prompt .= "10. Never suggest controlled substances without proper medical context\n";
        $prompt .= "11. Always include appropriate disclaimers and risk warnings\n\n";

        $prompt .= "RESPONSE REQUIREMENTS:\n";
        $prompt .= "- Provide 1-3 evidence-based suggestions maximum\n";
        $prompt .= "- Include confidence levels based on symptom clarity and data completeness\n";
        $prompt .= "- Flag ALL potential risks, interactions, and contraindications SPECIFICALLY for this patient\n";
        $prompt .= "- Consider patient age, gender, medical history, and current medications for dosing, contraindications, and special considerations\n";
        $prompt .= "- If symptoms suggest cardiac origin (chest pain, shortness of breath), require cardiac evaluation before NSAID suggestions\n";
        $prompt .= "- If data is insufficient, recommend further evaluation instead of guessing\n";
        $prompt .= "- Always emphasize that these are suggestions requiring professional judgment\n";
        $prompt .= "- For patients on anticoagulants (Aspirin, Warfarin) or with hypertension/cardiac history, provide SPECIFIC warnings about drug interactions and cardiovascular risks\n";
        $prompt .= "- CONFIDENCE CALIBRATION: If red flags or high-risk factors present, REDUCE confidence to 30% or less, or block medication suggestion entirely\n";
        $prompt .= "- If in doubt about medication safety due to patient risk factors, respond with NO suggestions and recommend evaluation\n\n";

        // Add confidence calibration based on any detected risk factors
        if (!empty($riskFactors)) {
            $prompt .= "ACTIVE HIGH-RISK FACTORS DETECTED:\n";
            foreach ($riskFactors as $factor) {
                $prompt .= "- {$factor['factor']}: {$factor['description']}\n";
            }
            $prompt .= "ADJUST CONFIDENCE ACCORDINGLY - ERR ON SIDE OF CAUTION\n\n";
        }

        $prompt .= "REQUIRED JSON RESPONSE FORMAT:\n";
        $prompt .= "{\n";
        $prompt .= '  "suggestions": [' . "\n";
        $prompt .= '    {' . "\n";
        $prompt .= '      "med": "Generic medication name",' . "\n";
        $prompt .= '      "dosage": "Standard adult dosage range",' . "\n";
        $prompt .= '      "freq": "Dosing frequency",' . "\n";
        $prompt .= '      "dur": "Treatment duration",' . "\n";
        $prompt .= '      "confidence": 85,' . "\n";
        $prompt .= '      "reason": "Evidence-based clinical rationale",' . "\n";
        $prompt .= '      "warnings": ["Specific warnings for this patient"],' . "\n";
        $prompt .= '      "interactions": ["Potential drug interactions"]' . "\n";
        $prompt .= '    }' . "\n";
        $prompt .= '  ],' . "\n";
        $prompt .= '  "risk_flags": [' . "\n";
        $prompt .= '    "⚠️ CLINICAL DECISION SUPPORT ONLY",' . "\n";
        $prompt .= '    "⚠️ Verify allergies and contraindications",' . "\n";
        $prompt .= '    "⚠️ Check drug interactions",' . "\n";
        $prompt .= '    "Additional patient-specific warnings"' . "\n";
        $prompt .= '  ],' . "\n";
        $prompt .= '  "requires_evaluation": false,' . "\n";
        $prompt .= '  "clinical_alert": null' . "\n";
        $prompt .= "}\n\n";

        $prompt .= "CRITICAL: Respond ONLY with valid JSON. No explanations outside JSON structure.";

        return $prompt;
    }

    /**
     * Validate and parse JSON response from OpenAI with retry mechanism
     */
    private function validateAndParseJsonResponse($aiContent, $maxRetries = 2)
    {
        $attempts = 0;
        $lastError = null;

        while ($attempts <= $maxRetries) {
            try {
                // Clean the content - remove any markdown formatting or extra text
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

                // Log the content we're trying to parse for debugging
                Log::info('Attempting to parse JSON content', [
                    'attempt' => $attempts + 1,
                    'content_length' => strlen($cleanContent),
                    'content_preview' => substr($cleanContent, 0, 200),
                    'content_suffix' => substr($cleanContent, -50)
                ]);

                // Try to parse JSON
                $parsed = json_decode($cleanContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $jsonError = json_last_error_msg();
                    Log::warning('JSON decode failed', [
                        'error' => $jsonError,
                        'json_error_code' => json_last_error(),
                        'content_sample' => substr($cleanContent, 0, 500)
                    ]);
                    throw new \Exception('JSON decode error: ' . $jsonError);
                }

                // Validate that we have the expected structure
                if (!is_array($parsed) || !array_key_exists('suggestions', $parsed) || !array_key_exists('risk_flags', $parsed)) {
                    throw new \Exception('Response missing required keys: suggestions and risk_flags');
                }

                Log::info('JSON parsing successful', [
                    'suggestions_count' => count($parsed['suggestions'] ?? []),
                    'risk_flags_count' => count($parsed['risk_flags'] ?? [])
                ]);

                return $parsed;

            } catch (\Exception $e) {
                $lastError = $e;
                $attempts++;

                Log::warning('JSON parsing attempt failed', [
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                    'content_preview' => substr($aiContent, 0, 100),
                ]);

                // If this isn't the last attempt, try to extract JSON from the content
                if ($attempts <= $maxRetries) {
                    // Try to find JSON-like content within the response
                    if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $aiContent, $matches)) {
                        $aiContent = $matches[0];
                        Log::info('Extracted JSON-like content for retry', [
                            'extracted_length' => strlen($aiContent),
                            'extracted_preview' => substr($aiContent, 0, 100)
                        ]);
                        continue;
                    }
                }
            }
        }

        // All attempts failed - log detailed error information
        Log::error('All JSON parsing attempts failed', [
            'max_retries' => $maxRetries,
            'final_error' => $lastError ? $lastError->getMessage() : 'Unknown error',
            'raw_content_length' => strlen($aiContent),
            'raw_content_preview' => substr($aiContent, 0, 500),
            'raw_content_suffix' => substr($aiContent, -100)
        ]);

        // Return fallback structure instead of throwing exception
        return [
            'suggestions' => [[
                'med' => 'AI Clinical Decision Support: Unclosed \'{\' on line 19',
                'dosage' => 'N/A',
                'freq' => 'N/A', 
                'dur' => 'N/A',
                'confidence' => 0,
                'reason' => 'AI response could not be parsed - Manual prescription entry required.',
                'warnings' => ['AI system error - Professional medical judgment required'],
                'interactions' => ['Cannot check interactions due to parsing error']
            ]],
            'risk_flags' => [
                '⚠️ AI PARSING ERROR - Manual clinical assessment required',
                '⚠️ Professional medical judgment required for all medication decisions'
            ]
        ];
    }

    /**
     * Validate response structure and provide fallbacks
     */
    private function validateResponseStructure($suggestions, $riskFlags)
    {
        $issues = [];
        $fallbackSuggestions = [];
        $fallbackRiskFlags = [];

        // Validate suggestions structure
        if (!is_array($suggestions)) {
            $issues[] = 'suggestions is not an array';
            $fallbackSuggestions = [[
                'med' => 'Consult medical guidelines',
                'dosage' => 'N/A',
                'freq' => 'N/A',
                'dur' => 'N/A',
                'confidence' => 0,
                'reason' => 'No specific medication suggestions available. Please consult medical guidelines.'
            ]];
        } else {
            // Validate each suggestion has required fields
            $validSuggestions = [];
            foreach ($suggestions as $suggestion) {
                if (is_array($suggestion) && isset($suggestion['med'])) {
                    $validSuggestions[] = $suggestion;
                }
            }

            if (empty($validSuggestions)) {
                $issues[] = 'no valid suggestion objects found';
                $fallbackSuggestions = [[
                    'med' => 'Consult medical guidelines',
                    'dosage' => 'N/A',
                    'freq' => 'N/A',
                    'dur' => 'N/A',
                    'confidence' => 0,
                    'reason' => 'No specific medication suggestions available. Please consult medical guidelines.'
                ]];
            } else {
                $fallbackSuggestions = $validSuggestions;
            }
        }

        // Validate risk_flags structure
        if (!is_array($riskFlags)) {
            $issues[] = 'risk_flags is not an array';
            $fallbackRiskFlags = ['Please review patient history for additional risk factors.'];
        } else {
            $validRiskFlags = array_filter($riskFlags, function($flag) {
                return is_string($flag) && !empty(trim($flag));
            });
            if (empty($validRiskFlags)) {
                $issues[] = 'no valid risk flag strings found';
                $fallbackRiskFlags = ['Please review patient history for additional risk factors.'];
            } else {
                $fallbackRiskFlags = array_values($validRiskFlags);
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'fallback_suggestions' => $fallbackSuggestions,
            'fallback_risk_flags' => $fallbackRiskFlags,
        ];
    }

    /**
     * Generate safe fallback suggestions when AI is unavailable
     * Focus on general health advice rather than specific medications
     */
    private function generateFallbackSuggestions($symptoms, $allergies, $pastMeds, $errorReason = null, $additionalData = [])
    {
        Log::warning('AI prescription suggestions unavailable, using safe fallback', [
            'reason' => $errorReason,
            'symptoms' => $symptoms,
            'allergies_count' => count($allergies),
            'past_meds_count' => count($pastMeds),
        ]);

        $suggestions = [];
        $risk_flags = [
            '⚠️ AI CLINICAL DECISION SUPPORT UNAVAILABLE',
            '⚠️ No automated medication suggestions available',
            '⚠️ Professional medical judgment required for all medication decisions',
            '⚠️ Consult current medical literature and guidelines',
        ];

        // Only provide very basic, evidence-based suggestions for common conditions
        // Never suggest anything that could be dangerous
        $symptoms_lower = is_array($symptoms) ? strtolower(implode(' ', $symptoms)) : strtolower($symptoms);

        // Fever and pain - only suggest acetaminophen (safest option)
        if ((strpos($symptoms_lower, 'fever') !== false || strpos($symptoms_lower, 'pain') !== false) &&
            !in_array('acetaminophen', $allergies) && !in_array('paracetamol', $allergies)) {
            $suggestions[] = [
                'med' => 'Acetaminophen (Paracetamol)',
                'dosage' => '500-1000mg',
                'freq' => 'every 4-6 hours as needed',
                'dur' => 'Maximum 3-5 days',
                'confidence' => 95,
                'reason' => 'Evidence-based antipyretic and analgesic for fever and mild pain',
                'warnings' => [
                    'Maximum daily dose: 4000mg (8 x 500mg tablets)',
                    'Avoid alcohol while taking this medication',
                    'Consult doctor if symptoms persist beyond 3 days'
                ],
                'interactions' => [
                    'May interact with warfarin (monitor INR)',
                    'May affect liver function tests'
                ]
            ];
        }

        // Add allergy warnings
        if (!empty($allergies)) {
            foreach ($allergies as $allergy) {
                $risk_flags[] = "⚠️ PATIENT ALLERGY: {$allergy} - verify medication safety";
            }
        }

        // Add general safety warnings
        $risk_flags[] = '⚠️ AI SYSTEM UNAVAILABLE - Manual clinical assessment required';
        $risk_flags[] = '⚠️ No automated drug interaction checking performed';
        $risk_flags[] = '⚠️ Consider patient age, weight, and organ function';

        // CRITICAL SAFETY: Check for patient-only symptoms (same logic as buildMedicationPrompt)
        $hasOnlyPatientSymptoms = empty($additionalData['doctor_notes'] ?? '') &&
                                  empty($additionalData['current_diagnosis'] ?? null) &&
                                  empty($additionalData['past_diagnoses'] ?? []) &&
                                  empty($additionalData['voice_diagnosis'] ?? '') &&
                                  !empty($additionalData['reason_for_visit'] ?? '');

        // CRITICAL SAFETY: Only provide suggestions when we have verified clinical data
        if (empty($suggestions) && $hasOnlyPatientSymptoms) {
            $suggestions[] = [
                'med' => 'Doctor Clinical Assessment Required',
                'dosage' => 'N/A',
                'freq' => 'N/A',
                'dur' => 'N/A',
                'confidence' => 0,
                'reason' => 'AI cannot provide medication suggestions without doctor clinical assessment. Patient-reported symptoms are not sufficient for medication decisions.',
                'warnings' => ['Doctor must document clinical findings', 'Patient-reported data cannot be used for prescriptions'],
                'interactions' => ['Cannot check interactions without verified clinical data']
            ];
            $risk_flags[] = '⚠️ DOCTOR CLINICAL ASSESSMENT REQUIRED - Patient-reported data insufficient';
        }

        // If still no specific suggestions, provide general guidance
        if (empty($suggestions)) {
            $suggestions[] = [
                'med' => 'Clinical Assessment Required',
                'dosage' => 'N/A',
                'freq' => 'N/A',
                'dur' => 'N/A',
                'confidence' => 0,
                'reason' => 'AI system unavailable - professional clinical evaluation required',
                'warnings' => ['Complete medical history and physical examination needed'],
                'interactions' => ['Cannot check interactions without AI system']
            ];
        }

        // Add error reason to risk flags
        if ($errorReason) {
            array_unshift($risk_flags, "🔧 SYSTEM STATUS: {$errorReason}");
        }

        $fallbackResult = [
            'suggestions' => $suggestions,
            'risk_flags' => array_unique($risk_flags),
            'message' => 'Safe fallback suggestions generated - AI system unavailable',
            'fallback' => true,
            'error_reason' => $errorReason,
            'disclaimer' => 'AI clinical decision support is currently unavailable. All medication decisions must be made by qualified healthcare professionals.',
            'generated_at' => now()->toISOString(),
            'confidence_level' => 'fallback_only'
        ];

        Log::info('Safe Fallback Response Generated', [
            'suggestions_count' => count($suggestions),
            'risk_flags_count' => count($risk_flags),
            'error_reason' => $errorReason,
        ]);

        return $fallbackResult;
    }

    /**
     * Integrate FDA validation into AI prescription suggestions
     */
    public function generatePrescriptionSuggestionsWithFDAValidation(Appointment $appointment, array $symptoms, array $allergies, array $pastMeds, array $additionalData = [])
    {
        // First generate the AI suggestions as usual
        $aiResult = $this->generatePrescriptionSuggestions($appointment, $symptoms, $allergies, $pastMeds, $additionalData);

        // If AI is disabled or there are no suggestions, return as-is
        if (empty($aiResult['suggestions']) || ($aiResult['disabled'] ?? false)) {
            return $aiResult;
        }

        // Get patient demographics for FDA validation
        $patient = $appointment->patient;
        $patientAge = $patient ? ($patient->age ?? ($patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : null)) : null;
        $patientGender = $patient ? ($patient->gender ?? null) : null;

        // Initialize FDA validator
        $fdaValidator = new FDADrugValidator();

        // Validate each suggestion against FDA data
        $enhancedSuggestions = [];
        $fdaRiskFlags = [];

        foreach ($aiResult['suggestions'] as $suggestion) {
            $medicationName = $suggestion['med'] ?? '';

            if (!empty($medicationName)) {
                // Perform FDA validation
                $fdaValidation = $fdaValidator->validateMedication($medicationName, $patientAge, $patientGender);

                // Add FDA flags to the suggestion
                $suggestion['fda_validation'] = $fdaValidation;

                // If there are FDA flags, add them to the suggestion
                if (!empty($fdaValidation['clinical_flags'])) {
                    if (!isset($suggestion['warnings'])) {
                        $suggestion['warnings'] = [];
                    }
                    $suggestion['warnings'] = array_merge($suggestion['warnings'], $fdaValidation['clinical_flags']);
                }

                // Track if any suggestions have high-risk flags
                if ($fdaValidation['high_risk'] ?? false) {
                    $suggestion['high_risk'] = true;
                }
            }

            $enhancedSuggestions[] = $suggestion;
        }

        // Collect any global FDA risk flags
        foreach ($aiResult['suggestions'] as $suggestion) {
            if (isset($suggestion['fda_validation']) && !empty($suggestion['fda_validation']['flag'])) {
                $fdaRiskFlags[] = $suggestion['fda_validation']['flag'];
            }
        }

        // Merge FDA risk flags with existing AI risk flags
        $allRiskFlags = array_unique(array_merge($aiResult['risk_flags'] ?? [], $fdaRiskFlags));

        // Update the result with FDA-enhanced suggestions
        $result = [
            'suggestions' => $enhancedSuggestions,
            'risk_flags' => $allRiskFlags,
            'clinical_data_used' => $aiResult['clinical_data_used'] ?? [],
            'message' => $aiResult['message'] ?? 'AI suggestions with FDA validation generated',
            'source' => 'openai_fda_enhanced', // Override source to indicate FDA validation was applied
            'disabled' => $aiResult['disabled'] ?? false,
            'disclaimer' => $aiResult['disclaimer'] ?? 'These are AI-generated suggestions for clinical decision support only. All medication decisions must be made by qualified healthcare professionals after considering FDA validation data.',
            'generated_at' => now()->toISOString(),
            'ai_model' => 'gpt-4o with FDA validation',
            'confidence_level' => $aiResult['confidence_level'] ?? 'support_only'
        ];

        // Add a special flag if any high-risk medications were detected
        $hasHighRiskMedications = collect($enhancedSuggestions)->contains(function($suggestion) {
            return ($suggestion['high_risk'] ?? false) === true;
        });

        if ($hasHighRiskMedications) {
            array_unshift($result['risk_flags'], '⚠️ HIGH-RISK MEDICATIONS DETECTED - Requires immediate clinical review');
        }

        // If FDA validation is unavailable for any medication, flag it
        $hasUnavailableValidation = collect($enhancedSuggestions)->contains(function($suggestion) {
            return isset($suggestion['fda_validation']) &&
                   ($suggestion['fda_validation']['validation_status'] ?? '') === 'unavailable';
        });

        if ($hasUnavailableValidation) {
            array_unshift($result['risk_flags'], '⚠️ FDA VALIDATION UNAVAILABLE FOR SOME MEDICATIONS - Additional clinical review required');
        }

        Log::info('AI prescriptions generated with FDA validation', [
            'appointment_id' => $appointment->id,
            'suggestions_count' => count($enhancedSuggestions),
            'high_risk_count' => collect($enhancedSuggestions)->filter(function($s) {
                return ($s['high_risk'] ?? false) === true;
            })->count(),
            'fda_unavailable_count' => collect($enhancedSuggestions)->filter(function($s) {
                return isset($s['fda_validation']) &&
                       ($s['fda_validation']['validation_status'] ?? '') === 'unavailable';
            })->count(),
        ]);

        return $result;
    }

    /**
     * Build clinical data summary for UI display
     */
    private function buildClinicalDataSummary(array $symptoms, array $additionalData = []): array
    {
        $clinicalData = [];
        $symptomsText = is_array($symptoms) ? implode(', ', $symptoms) : $symptoms;
        if (!empty($symptomsText) && $symptomsText !== 'No symptoms provided') {
            $clinicalData['symptoms'] = $symptomsText;
        }

        // Include doctor notes if available
        if (!empty($additionalData['doctor_notes'])) {
            $clinicalData['doctor_notes'] = $additionalData['doctor_notes'];
        }

        // Include current diagnosis info
        if (!empty($additionalData['current_diagnosis'])) {
            $diagnosisText = $additionalData['current_diagnosis']['diagnosis_text'] ?? '';
            $patientData = $additionalData['current_diagnosis']['patient_data'] ?? [];

            if (!empty($diagnosisText)) {
                $clinicalData['current_diagnosis'] = $diagnosisText;
            }

            // Include clinical notes from patient_data if available
            if (!empty($patientData['clinical_notes'])) {
                $clinicalData['clinical_notes'] = $patientData['clinical_notes'];
            }

            // Include symptoms from patient_data if different from what we have
            if (!empty($patientData['symptoms']) && !isset($clinicalData['symptoms'])) {
                $clinicalData['symptoms'] = $patientData['symptoms'];
            }

            // Include medical history if available
            if (!empty($patientData['medical_history'])) {
                $clinicalData['medical_history'] = $patientData['medical_history'];
            }

            // Include medications from patient_data if available
            if (!empty($patientData['medications'])) {
                $clinicalData['medications'] = $patientData['medications'];
            }
        }

        return $clinicalData;
    }

    /**
     * Format AI response for consistent output
     */
    public function formatResponse($data)
    {
        return [
            'suggestions' => $data['suggestions'] ?? [],
            'risk_flags' => $data['risk_flags'] ?? [],
            'message' => $data['message'] ?? 'Response formatted',
            'source' => $data['source'] ?? 'unknown',
            'fallback' => $data['fallback'] ?? false,
        ];
    }
}