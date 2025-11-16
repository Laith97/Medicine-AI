<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Prescription;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AIAssistant
{
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

        try {
            // Create intelligent prompt for OpenAI with enhanced safety instructions
            $prompt = $this->buildMedicationPrompt($symptoms, $allergies, $pastMeds, $appointment);

            Log::info('Calling OpenAI API for prescription suggestions', [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'model' => 'gpt-4o',
                'prompt_length' => strlen($prompt),
                'max_tokens' => 1000,
                'temperature' => 0.1,
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

REQUIRED JSON FORMAT:
{
  "suggestions": [
    {
      "med": "Medication Name",
      "dosage": "Standard dosage",
      "freq": "Frequency",
      "dur": "Duration",
      "confidence": 0-100,
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
  "disclaimer": "These suggestions are for informational purposes only and do not constitute medical advice. All medication decisions must be made by qualified healthcare professionals."
}'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1200, // Increased for safety information
                'temperature' => 0.1, // Very low temperature for consistency
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
            if (!empty($symptomsText)) $clinicalDataUsed['symptoms'] = $symptomsText;
            if (!empty($additionalData['doctor_notes'] ?? '')) $clinicalDataUsed['doctor_notes'] = $additionalData['doctor_notes'];
            if (!empty($additionalData['appointment_symptoms'] ?? '')) $clinicalDataUsed['appointment_symptoms'] = $additionalData['appointment_symptoms'];

            // Only use DOCTOR-WRITTEN diagnosis text, NOT AI-generated analysis
            if (!empty($additionalData['recent_diagnosis']['diagnosis_text'] ?? '')) {
                // Check if this is doctor-written vs AI-generated
                $diagnosisText = $additionalData['recent_diagnosis']['diagnosis_text'];
                $aiAnalysis = $additionalData['recent_diagnosis']['ai_analysis'] ?? '';

                // Only include if it's different from AI analysis (meaning doctor modified it)
                // or if there's no AI analysis (manual diagnosis)
                if (empty($aiAnalysis) || trim($diagnosisText) !== trim($aiAnalysis)) {
                    $clinicalDataUsed['doctor_diagnosis'] = $diagnosisText;
                }
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
                'confidence_level' => 'support_only' // Not diagnostic
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
            return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'OpenAI authentication failed - check API key');

        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            Log::error('OpenAI Rate Limit Error', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
            ]);
            return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'OpenAI rate limit exceeded');

        } catch (\OpenAI\Exceptions\InvalidArgumentException $e) {
            Log::error('OpenAI Invalid Argument Error', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
            ]);
            return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'OpenAI invalid request parameters');

        } catch (\OpenAI\Exceptions\TransporterException $e) {
            Log::error('OpenAI Transporter Error', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
            ]);
            return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'OpenAI network/connection error');

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
                                'content' => 'CRITICAL: Respond ONLY with valid JSON. No text before or after. Format: {"suggestions": [], "risk_flags": []}'
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt . "\n\nREMINDER: Respond with valid JSON only."
                            ]
                        ],
                        'max_tokens' => 800,
                        'temperature' => 0.0, // Zero temperature for maximum consistency
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
            return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'OpenAI API error: ' . $e->getMessage());
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
     */
    private function buildMedicationPrompt($symptoms, $allergies, $pastMeds, Appointment $appointment, $additionalData = [])
    {
        $symptomsText = is_array($symptoms) ? implode(', ', $symptoms) : $symptoms;

        // Get patient demographics from appointment
        $patient = $appointment->patient;
        $patientAge = $patient ? ($patient->age ?? ($patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : 'Unknown')) : 'Unknown';
        $patientGender = $patient ? ($patient->gender ?? 'Unknown') : 'Unknown';
        $patientName = $patient ? $patient->name : 'Unknown Patient';

        // Use doctor-verified clinical data from multiple sources
        $doctorNotes = $additionalData['doctor_notes'] ?? '';
        $appointmentSymptoms = $additionalData['appointment_symptoms'] ?? '';
        $recentDiagnosis = $additionalData['recent_diagnosis'] ?? null;

        // Combine verified clinical data sources - ONLY DOCTOR-WRITTEN CONTENT
        $clinicalData = [];
        if (!empty($symptomsText)) $clinicalData[] = "Symptoms: " . $symptomsText;
        if (!empty($doctorNotes)) $clinicalData[] = "Doctor Notes: " . $doctorNotes;
        if (!empty($appointmentSymptoms)) $clinicalData[] = "Appointment Symptoms: " . $appointmentSymptoms;

        // Only include DOCTOR-WRITTEN diagnosis, not AI-generated analysis
        if ($recentDiagnosis && isset($recentDiagnosis['diagnosis_text'])) {
            $diagnosisText = $recentDiagnosis['diagnosis_text'];
            $aiAnalysis = $recentDiagnosis['ai_analysis'] ?? '';

            // Only use if doctor actually wrote/modified the diagnosis
            if (empty($aiAnalysis) || trim($diagnosisText) !== trim($aiAnalysis)) {
                $clinicalData[] = "Doctor Diagnosis: " . $diagnosisText;
            }
        }

        $verifiedClinicalText = implode("\n", $clinicalData);

        // CRITICAL SAFETY: Only provide suggestions when we have verified clinical data
        if (empty($verifiedClinicalText)) {
            $symptomsText = 'No verified clinical data available. This Clinical Decision Support system requires doctor-documented symptoms, diagnosis, or clinical notes before providing medication suggestions.';
        } else {
            $symptomsText = $verifiedClinicalText;
        }

        $hasVerifiedData = !empty($verifiedClinicalText);

        $prompt = "CLINICAL DECISION SUPPORT - MEDICATION SUGGESTIONS\n";
        $prompt .= "===============================================\n\n";

        $prompt .= "PATIENT DEMOGRAPHICS:\n";
        $prompt .= "- Patient Name: {$patientName}\n";
        $prompt .= "- Age: {$patientAge} years\n";
        $prompt .= "- Gender: {$patientGender}\n\n";

        $prompt .= "VERIFIED CLINICAL DATA:\n";
        $prompt .= "- Clinical Presentation: {$symptomsText}\n";
        $prompt .= "- Appointment Type: {$appointment->appointment_type}\n";

        // CRITICAL SAFETY: Only use doctor-verified clinical data
        if ($hasVerifiedData) {
            $prompt .= "- Data Source: Doctor-verified clinical documentation (symptoms, diagnosis, clinical notes)\n";
        } else {
            $prompt .= "- Data Source: No verified clinical data available - requires professional documentation\n";
        }
        $prompt .= "\n";

        // Critical safety information
        $prompt .= "CRITICAL SAFETY INFORMATION:\n";

        if (!empty($allergies)) {
            $prompt .= "- KNOWN ALLERGIES: " . implode(', ', $allergies) . "\n";
        } else {
            $prompt .= "- Allergies: None reported (verify with patient)\n";
        }

        // Add current active medications with detailed information
        $activeMeds = [];
        if ($appointment->patient_id) {
            $activePrescriptions = Prescription::getActiveForPatient($appointment->patient_id);
            $activeMeds = $activePrescriptions->map(function ($prescription) {
                return $prescription->medication_name . ' (' . $prescription->dosage . ' ' . $prescription->frequency . ', started ' .
                       ($prescription->start_date ? $prescription->start_date->format('M j, Y') : 'unknown') . ')';
            })->toArray();
        }

        if (!empty($activeMeds)) {
            $prompt .= "- CURRENT MEDICATIONS: " . implode('; ', $activeMeds) . "\n";
        } else {
            $prompt .= "- Current Medications: None active\n";
        }

        if (!empty($pastMeds)) {
            $prompt .= "- PAST MEDICATIONS: " . implode(', ', $pastMeds) . "\n";
        } else {
            $prompt .= "- Past Medications: None reported\n";
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
        $prompt .= "- Flag all potential risks, interactions, and contraindications\n";
        $prompt .= "- Consider patient age and gender for dosing, contraindications, and special considerations\n";
        $prompt .= "- If data is insufficient, recommend further evaluation instead of guessing\n";
        $prompt .= "- Always emphasize that these are suggestions requiring professional judgment\n\n";

        $prompt .= "REQUIRED JSON RESPONSE FORMAT:\n";
        $prompt .= "{\n";
        $prompt .= '  "suggestions": [' . "\n";
        $prompt .= '    {' . "\n";
        $prompt .= '      "med": "Generic medication name",' . "\n";
        $prompt .= '      "dosage": "Standard adult dosage range",' . "\n";
        $prompt .= '      "freq": "Dosing frequency",' . "\n";
        $prompt .= '      "dur": "Treatment duration",' . "\n";
        $prompt .= '      "confidence": 0-100,' . "\n";
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
        $prompt .= '  ]' . "\n";
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

                // Try to parse JSON
                $parsed = json_decode($cleanContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON decode error: ' . json_last_error_msg());
                }

                // Validate that we have the expected structure
                if (!is_array($parsed) || !array_key_exists('suggestions', $parsed) || !array_key_exists('risk_flags', $parsed)) {
                    throw new \Exception('Response missing required keys: suggestions and risk_flags');
                }

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
                    if (preg_match('/\{.*\}/s', $aiContent, $matches)) {
                        $aiContent = $matches[0];
                        continue;
                    }
                }
            }
        }

        // All attempts failed
        Log::error('All JSON parsing attempts failed', [
            'max_retries' => $maxRetries,
            'final_error' => $lastError ? $lastError->getMessage() : 'Unknown error',
            'raw_content' => $aiContent,
        ]);

        throw new \Exception('Failed to parse OpenAI response as valid JSON after ' . ($maxRetries + 1) . ' attempts: ' . ($lastError ? $lastError->getMessage() : 'Unknown error'));
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
    private function generateFallbackSuggestions($symptoms, $allergies, $pastMeds, $errorReason = null)
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

        // CRITICAL SAFETY: Only provide suggestions when we have verified clinical data
        if (empty($suggestions) && empty($verifiedClinicalText)) {
            $suggestions[] = [
                'med' => 'Clinical Documentation Required',
                'dosage' => 'N/A',
                'freq' => 'N/A',
                'dur' => 'N/A',
                'confidence' => 0,
                'reason' => 'No verified clinical data available. Please document symptoms, diagnosis, or clinical findings in the appointment before requesting AI medication suggestions.',
                'warnings' => ['Clinical assessment and documentation required', 'Doctor must verify patient symptoms and medical history'],
                'interactions' => ['Cannot provide medication suggestions without verified clinical data']
            ];
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