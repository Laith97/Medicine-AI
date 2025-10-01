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
     */
    public function generatePrescriptionSuggestions(Appointment $appointment, array $symptoms, array $allergies, array $pastMeds)
    {
        // Check if AI prescription suggestions are enabled
        if (!config('ai.prescription_suggestions.enabled', true)) {
            Log::info('AI prescription suggestions disabled by feature flag');
            if (config('ai.prescription_suggestions.fallback_enabled', true)) {
                return $this->generateFallbackSuggestions($symptoms, $allergies, $pastMeds, 'AI prescription suggestions disabled');
            } else {
                return [
                    'suggestions' => [],
                    'risk_flags' => ['AI prescription suggestions are currently disabled'],
                    'message' => 'AI prescription suggestions are disabled',
                    'source' => 'disabled',
                    'disabled' => true
                ];
            }
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
            // Create intelligent prompt for OpenAI
            $prompt = $this->buildMedicationPrompt($symptoms, $allergies, $pastMeds, $appointment);

            Log::info('Calling OpenAI API', [
                'model' => 'gpt-4o',
                'prompt_length' => strlen($prompt),
                'max_tokens' => 1000,
                'temperature' => 0.3,
            ]);

            // Call OpenAI GPT-4o with enhanced JSON enforcement
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical AI assistant that MUST respond ONLY with valid JSON. No explanations, no markdown, no additional text. Your response must be parseable JSON containing exactly two keys: "suggestions" (array) and "risk_flags" (array). Format: {"suggestions": [...], "risk_flags": [...]}. If you cannot provide suggestions, use empty arrays. Never include text outside the JSON structure.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.1, // Even lower temperature for JSON consistency
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

            $result = [
                'suggestions' => $suggestions,
                'risk_flags' => $risk_flags,
                'message' => 'AI suggestions generated successfully',
                'source' => 'openai'
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
        ];
    }

    /**
     * Build intelligent prompt for medication suggestions
     */
    private function buildMedicationPrompt($symptoms, $allergies, $pastMeds, Appointment $appointment)
    {
        $symptomsText = is_array($symptoms) ? implode(', ', $symptoms) : $symptoms;
        if (empty($symptomsText)) {
            $symptomsText = 'No symptoms provided';
        }

        $prompt = "Please analyze the following patient information and provide medication suggestions:\n\n";

        $prompt .= "PATIENT SYMPTOMS: {$symptomsText}\n\n";

        if (!empty($allergies)) {
            $prompt .= "PATIENT ALLERGIES: " . implode(', ', $allergies) . "\n\n";
        } else {
            $prompt .= "PATIENT ALLERGIES: None reported\n\n";
        }

        if (!empty($pastMeds)) {
            $prompt .= "PAST MEDICATIONS: " . implode(', ', $pastMeds) . "\n\n";
        } else {
            $prompt .= "PAST MEDICATIONS: None reported\n\n";
        }

        // Add current active medications
        $activeMeds = [];
        if ($appointment->patient_id) {
            $activePrescriptions = Prescription::getActiveForPatient($appointment->patient_id);
            $activeMeds = $activePrescriptions->map(function ($prescription) {
                return $prescription->medication_name . ' (' . $prescription->dosage . ', ' . $prescription->frequency . ')';
            })->toArray();
        }

        if (!empty($activeMeds)) {
            $prompt .= "CURRENT ACTIVE MEDICATIONS: " . implode(', ', $activeMeds) . "\n\n";
        } else {
            $prompt .= "CURRENT ACTIVE MEDICATIONS: None\n\n";
        }

        // Add appointment context
        $prompt .= "APPOINTMENT REASON: {$appointment->reason}\n";
        $prompt .= "APPOINTMENT TYPE: {$appointment->appointment_type}\n\n";

        $prompt .= "INSTRUCTIONS:\n";
        $prompt .= "1. Suggest 2-4 appropriate medications based on symptoms and medical evidence\n";
        $prompt .= "2. Include dosage information where appropriate\n";
        $prompt .= "3. Identify potential risks, drug interactions, and contraindications\n";
        $prompt .= "4. Consider patient allergies, past medications, and CURRENT ACTIVE MEDICATIONS\n";
        $prompt .= "5. Check for drug-drug interactions between suggested medications and current active prescriptions\n";
        $prompt .= "6. Flag any medications that should be avoided due to interactions or contraindications\n";
        $prompt .= "7. Provide brief rationale for each suggestion, including interaction considerations\n";
        $prompt .= "8. If patient history is limited or minimal (few or no allergies, past medications, or symptoms), provide general preventive care recommendations such as routine health screenings, vaccinations, healthy lifestyle advice, and wellness check-ups\n";
        $prompt .= "9. Always return meaningful suggestions - never return empty arrays. If specific medications cannot be determined, suggest appropriate preventive care measures or general health recommendations\n\n";

        $prompt .= "FORMAT YOUR RESPONSE AS VALID JSON:\n";
        $prompt .= "{\n";
        $prompt .= '  "suggestions": [' . "\n";
        $prompt .= '    {"med": "Medication 1", "dosage": "dosage info", "freq": "frequency", "dur": "duration", "confidence": 85, "reason": "brief reason"},' . "\n";
        $prompt .= '    {"med": "Medication 2", "dosage": "dosage info", "freq": "frequency", "dur": "duration", "confidence": 72, "reason": "brief reason"}' . "\n";
        $prompt .= '  ],' . "\n";
        $prompt .= '  "risk_flags": ["Risk factor 1", "Risk factor 2", "Contraindication warning"]' . "\n";
        $prompt .= "}\n\n";

        $prompt .= "IMPORTANT: Only respond with valid JSON. No additional text or explanations outside the JSON structure.";

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
     * Generate fallback suggestions when OpenAI fails
     */
    private function generateFallbackSuggestions($symptoms, $allergies, $pastMeds, $errorReason = null)
    {
        Log::info('Generating fallback suggestions', [
            'reason' => $errorReason,
            'symptoms' => $symptoms,
            'allergies_count' => count($allergies),
            'past_meds_count' => count($pastMeds),
        ]);

        $suggestions = [];
        $risk_flags = [];

        // Convert symptoms to string if array
        $symptoms_lower = is_array($symptoms) ? strtolower(implode(' ', $symptoms)) : strtolower($symptoms);

        // Basic symptom-based suggestions
        if (strpos($symptoms_lower, 'pain') !== false || strpos($symptoms_lower, 'headache') !== false) {
            if (!in_array('ibuprofen', $allergies) && !in_array('nsaids', $allergies)) {
                $suggestions[] = [
                    'med' => 'Ibuprofen',
                    'dosage' => '400mg',
                    'freq' => 'every 6-8 hours as needed',
                    'dur' => '7-10 days',
                    'confidence' => 85,
                    'reason' => 'NSAID with anti-inflammatory properties for pain relief'
                ];
            }
            if (!in_array('acetaminophen', $allergies)) {
                $suggestions[] = [
                    'med' => 'Acetaminophen',
                    'dosage' => '500mg',
                    'freq' => 'every 4-6 hours as needed',
                    'dur' => '7-10 days',
                    'confidence' => 90,
                    'reason' => 'Alternative analgesic for pain relief'
                ];
            }
        }

        if (strpos($symptoms_lower, 'fever') !== false) {
            if (!in_array('acetaminophen', $allergies)) {
                $suggestions[] = [
                    'med' => 'Acetaminophen',
                    'dosage' => '500mg',
                    'freq' => 'every 4-6 hours as needed',
                    'dur' => '3-5 days',
                    'confidence' => 95,
                    'reason' => 'Antipyretic medication for fever reduction'
                ];
            }
        }

        if (strpos($symptoms_lower, 'infection') !== false || strpos($symptoms_lower, 'bacterial') !== false) {
            if (!in_array('penicillin', $allergies) && !in_array('amoxicillin', $allergies)) {
                $suggestions[] = [
                    'med' => 'Amoxicillin',
                    'dosage' => '250mg',
                    'freq' => 'three times daily',
                    'dur' => '7-10 days',
                    'confidence' => 80,
                    'reason' => 'Broad-spectrum antibiotic for bacterial infections'
                ];
            }
        }

        // Risk assessment based on allergies
        if (!empty($allergies)) {
            foreach ($allergies as $allergy) {
                $risk_flags[] = "Avoid medications containing or related to: {$allergy}";
            }
        }

        // Risk assessment based on past medications
        if (!empty($pastMeds)) {
            if (in_array('liver_issues', $pastMeds) || in_array('hepatitis', $pastMeds)) {
                $risk_flags[] = 'Monitor liver function with acetaminophen or other hepatotoxic medications';
            }
            if (in_array('kidney_issues', $pastMeds) || in_array('renal', $pastMeds)) {
                $risk_flags[] = 'Adjust dosages for medications excreted by kidneys';
            }
            if (in_array('heart_disease', $pastMeds) || in_array('cardiovascular', $pastMeds)) {
                $risk_flags[] = 'Caution with medications that may affect heart rate or blood pressure';
            }
        }

        // Default suggestions if none matched
        if (empty($suggestions)) {
            $suggestions[] = [
                'med' => 'Consult medical guidelines',
                'dosage' => 'N/A',
                'freq' => 'N/A',
                'dur' => 'N/A',
                'confidence' => 0,
                'reason' => 'No specific medication suggestions available based on provided symptoms'
            ];
        }

        // Default risk flags if none identified
        if (empty($risk_flags)) {
            $risk_flags[] = 'No major risks identified from provided information';
            $risk_flags[] = 'Please review complete patient medical history';
        }

        // Add error reason to risk flags if provided
        if ($errorReason) {
            array_unshift($risk_flags, "AI Analysis Error: {$errorReason}");
        }

        $fallbackResult = [
            'suggestions' => $suggestions,
            'risk_flags' => $risk_flags,
            'message' => 'Fallback suggestions generated (OpenAI unavailable)',
            'fallback' => true,
            'error_reason' => $errorReason
        ];

        Log::info('Fallback Response', [
            'suggestions_count' => count($suggestions),
            'first_suggestion' => $suggestions[0] ?? null,
            'risk_flags_count' => count($risk_flags),
            'first_risk_flag' => $risk_flags[0] ?? null,
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