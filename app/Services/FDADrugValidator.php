<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FDADrugValidator
{
    private const FDA_LABEL_ENDPOINT = 'https://api.fda.gov/drug/label.json';
    private const FDA_RECALL_ENDPOINT = 'https://api.fda.gov/drug/enforcement.json';
    private const FDA_ADVERSE_ENDPOINT = 'https://api.fda.gov/drug/event.json';
    private const CACHE_TTL = 86400; // 24 hours in seconds

    /**
     * Validate medication against FDA data
     *
     * @param string $medicationName The medication name to validate
     * @param int|null $patientAge Patient age for demographic checks
     * @param string|null $patientGender Patient gender for demographic checks
     * @return array Validation results with flags and risk indicators
     */
    public function validateMedication(string $medicationName, ?int $patientAge = null, ?string $patientGender = null): array
    {
        $cacheKey = $this->getCacheKey($medicationName, $patientAge, $patientGender);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($medicationName, $patientAge, $patientGender) {
            return $this->performFDAValidation($medicationName, $patientAge, $patientGender);
        });
    }

    /**
     * Perform the actual FDA validation (without caching)
     */
    private function performFDAValidation(string $medicationName, ?int $patientAge, ?string $patientGender): array
    {
        $fdaData = $this->fetchFDAData($medicationName);

        if (!$fdaData['available']) {
            return [
                'flag' => 'FDA validation unavailable – clinician review required',
                'high_risk' => false,
                'validation_status' => 'unavailable',
                'risk_indicators' => [],
                'clinical_flags' => [],
                'metadata' => [
                    'validated_at' => now()->toISOString(),
                    'source' => 'fda_api_unavailable',
                    'medication_name' => $medicationName,
                ]
            ];
        }

        return $this->processFDAData($fdaData, $medicationName, $patientAge, $patientGender);
    }

    /**
     * Fetch data from openFDA endpoints
     */
    private function fetchFDAData(string $medicationName): array
    {
        try {
            // Query API endpoints in parallel to improve performance
            $labelResponse = Http::timeout(10)
                ->get(self::FDA_LABEL_ENDPOINT, [
                    'search' => "generic_name:\"{$medicationName}\"",
                    'limit' => 1
                ]);

            $recallResponse = Http::timeout(10)
                ->get(self::FDA_RECALL_ENDPOINT, [
                    'search' => "product_description:\"{$medicationName}\" OR brand_name:\"{$medicationName}\" OR generic_name:\"{$medicationName}\"",
                    'limit' => 1
                ]);

            $adverseResponse = Http::timeout(10)
                ->get(self::FDA_ADVERSE_ENDPOINT, [
                    'search' => "patient.drug.medicinalproduct.exact:\"{$medicationName}\"",
                    'count' => 'patient.reaction.reactionmeddrapt.exact',
                    'limit' => 5
                ]);

            // Handle API errors gracefully
            if ($labelResponse->failed()) {
                Log::warning('FDA Label API call failed', [
                    'medication' => $medicationName,
                    'status' => $labelResponse->status(),
                    'error' => $labelResponse->body()
                ]);
            }

            if ($recallResponse->failed()) {
                Log::warning('FDA Recall API call failed', [
                    'medication' => $medicationName,
                    'status' => $recallResponse->status(),
                    'error' => $recallResponse->body()
                ]);
            }

            if ($adverseResponse->failed()) {
                Log::warning('FDA Adverse Events API call failed', [
                    'medication' => $medicationName,
                    'status' => $adverseResponse->status(),
                    'error' => $adverseResponse->body()
                ]);
            }

            // Only return available data if at least one endpoint succeeded
            $hasValidData = false;
            $fdaData = [
                'available' => false,
                'warnings' => [],
                'boxed_warning' => null,
                'contraindications' => [],
                'recalls' => [],
                'adverse_effects' => []
            ];

            if ($labelResponse->successful()) {
                $labelData = $labelResponse->json();
                if (isset($labelData['results']) && !empty($labelData['results'])) {
                    $firstResult = $labelData['results'][0];
                    
                    $fdaData['warnings'] = $firstResult['warnings'] ?? [];
                    $fdaData['boxed_warning'] = $firstResult['boxed_warning'] ?? null;
                    $fdaData['contraindications'] = $firstResult['contraindications'] ?? [];
                    
                    $hasValidData = true;
                }
            }

            if ($recallResponse->successful()) {
                $recallData = $recallResponse->json();
                if (isset($recallData['results']) && !empty($recallData['results'])) {
                    $fdaData['recalls'] = $recallData['results'];
                    $hasValidData = true;
                }
            }

            if ($adverseResponse->successful()) {
                $adverseData = $adverseResponse->json();
                if (isset($adverseData['results']) && !empty($adverseData['results'])) {
                    $fdaData['adverse_effects'] = $adverseData['results'];
                    $hasValidData = true;
                }
            }

            $fdaData['available'] = $hasValidData;

            return $fdaData;

        } catch (\Exception $e) {
            Log::error('FDA API validation failed', [
                'medication' => $medicationName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'available' => false,
                'warnings' => [],
                'boxed_warning' => null,
                'contraindications' => [],
                'recalls' => [],
                'adverse_effects' => []
            ];
        }
    }

    /**
     * Process FDA data and create appropriate flags
     */
    private function processFDAData(array $fdaData, string $medicationName, ?int $patientAge, ?string $patientGender): array
    {
        $flags = [];
        $highRisk = false;
        $riskIndicators = [
            'black_box_warning' => false,
            'recall_status' => false,
            'pregnancy_contraindication' => false,
            'contraindication' => false,
        ];

        // Check recalls
        if (!empty($fdaData['recalls'])) {
            foreach ($fdaData['recalls'] as $recall) {
                $recallReason = $recall['reason_for_recall'] ?? 'Unknown recall reason';
                $recallStatus = $recall['status'] ?? 'Unknown status';
                $recallClassification = $recall['classification'] ?? 'Class III (Low Risk)';
                
                $flags[] = "⚠️ FDA RECALL ({$recallClassification}): {$recallReason} (Status: {$recallStatus})";
                $highRisk = true;
                $riskIndicators['recall_status'] = true;
            }
        }

        // Check Black Box Warning
        if (!empty($fdaData['boxed_warning'])) {
            $boxedWarning = is_array($fdaData['boxed_warning']) ? 
                implode(' ', $fdaData['boxed_warning']) : 
                $fdaData['boxed_warning'];
                
            $flags[] = "🚨 BLACK BOX WARNING: " . $boxedWarning;
            $highRisk = true;
            $riskIndicators['black_box_warning'] = true;
        }

        // Check contraindications against demographics
        if (!empty($fdaData['contraindications'])) {
            foreach ($fdaData['contraindications'] as $contra) {
                $contraindicationText = is_array($contra) ? implode(' ', $contra) : $contra;
                
                // Check for pregnancy contraindications
                if ($patientGender === 'female' && $patientAge !== null) {
                    if ($patientAge >= 12 && $patientAge <= 50) { // Reproductive age range
                        if (stripos($contraindicationText, 'pregnancy') !== false || 
                            stripos($contraindicationText, 'pregnant') !== false || 
                            stripos($contraindicationText, 'fetus') !== false) {
                            
                            $flags[] = "⚠️ PREGNANCY CONTRAINDICATION: " . $contraindicationText;
                            $highRisk = true;
                            $riskIndicators['pregnancy_contraindication'] = true;
                        }
                    }
                }
                
                // Check for pediatric contraindications
                if ($patientAge !== null && $patientAge < 18) {
                    if (stripos($contraindicationText, 'pediatric') !== false || 
                        stripos($contraindicationText, 'child') !== false || 
                        stripos($contraindicationText, 'adolescent') !== false) {
                        
                        $flags[] = "⚠️ PEDIATRIC CONTRAINDICATION: " . $contraindicationText;
                        $highRisk = true;
                        $riskIndicators['contraindication'] = true;
                    }
                }
                
                // Check for geriatric contraindications
                if ($patientAge !== null && $patientAge > 65) {
                    if (stripos($contraindicationText, 'geriatric') !== false || 
                        stripos($contraindicationText, 'elderly') !== false || 
                        stripos($contraindicationText, 'elderly patients') !== false) {
                        
                        $flags[] = "⚠️ GERIATRIC CONTRAINDICATION: " . $contraindicationText;
                        $highRisk = true;
                        $riskIndicators['contraindication'] = true;
                    }
                }
            }
        }

        // Add general warnings
        if (!empty($fdaData['warnings'])) {
            $warnings = is_array($fdaData['warnings']) ? $fdaData['warnings'] : [$fdaData['warnings']];
            
            foreach ($warnings as $warning) {
                $warningText = is_array($warning) ? implode(' ', $warning) : $warning;
                $flags[] = "⚠️ FDA WARNING: " . $warningText;
            }
        }

        // Add top adverse effects
        if (!empty($fdaData['adverse_effects'])) {
            // Check if the adverse events response has the expected structure
            if (isset($fdaData['adverse_effects']['results']) && is_array($fdaData['adverse_effects']['results'])) {
                // Handle structure: ['results' => [...]]
                $topEffects = array_slice($fdaData['adverse_effects']['results'], 0, 3);
                $effectTerms = array_column($topEffects, 'term');
                $effectCount = array_column($topEffects, 'count');

                $effectsList = [];
                foreach ($effectTerms as $index => $term) {
                    $count = $effectCount[$index] ?? 0;
                    $effectsList[] = "{$term} ({$count} reports)";
                }

                if (!empty($effectsList)) {
                    $flags[] = "⚠️ TOP REPORTED ADVERSE EFFECTS: " . implode(', ', $effectsList);
                }
            } elseif (is_array($fdaData['adverse_effects'])) {
                // Handle structure: directly as array
                $topEffects = array_slice($fdaData['adverse_effects'], 0, 3);
                $effectTerms = array_column($topEffects, 'term');
                $effectCount = array_column($topEffects, 'count');

                $effectsList = [];
                foreach ($effectTerms as $index => $term) {
                    $count = $effectCount[$index] ?? 0;
                    $effectsList[] = "{$term} ({$count} reports)";
                }

                if (!empty($effectsList)) {
                    $flags[] = "⚠️ TOP REPORTED ADVERSE EFFECTS: " . implode(', ', $effectsList);
                }
            }
        }

        // Determine final validation status
        $validationStatus = $highRisk ? 'high_risk' : ($flags ? 'moderate_risk' : 'low_risk');
        
        // Add appropriate flag message
        $flagMessage = $highRisk ? 'HIGH-RISK FDA FLAGS DETECTED' : 
                      ($flags ? 'FDA FLAGS DETECTED' : 'FDA Validated - No Critical Issues');

        return [
            'flag' => $flagMessage,
            'high_risk' => $highRisk,
            'validation_status' => $validationStatus,
            'risk_indicators' => $riskIndicators,
            'clinical_flags' => $flags,
            'metadata' => [
                'validated_at' => now()->toISOString(),
                'source' => 'openfda_api',
                'medication_name' => $medicationName,
                'source_cache_hit' => false, // This will be true if cache was used
                'fda_data_available' => $fdaData['available'],
            ]
        ];
    }

    /**
     * Generate a unique cache key for the medication validation
     */
    private function getCacheKey(string $medicationName, ?int $patientAge, ?string $patientGender): string
    {
        $baseKey = "fda_" . md5(strtolower($medicationName));
        $demographicKey = $patientAge . "_" . ($patientGender ?? 'unknown');
        
        return $baseKey . "_" . md5($demographicKey);
    }

    /**
     * Validate multiple medications at once for efficiency
     */
    public function validateMultipleMedications(array $medications, ?int $patientAge = null, ?string $patientGender = null): array
    {
        $results = [];

        foreach ($medications as $medication) {
            $medicationName = is_string($medication) ? $medication : $medication['med'] ?? $medication['medication_name'] ?? '';
            
            if (!empty($medicationName)) {
                $results[$medicationName] = $this->validateMedication($medicationName, $patientAge, $patientGender);
            }
        }

        return $results;
    }

    /**
     * Clear FDA cache for a specific medication
     */
    public function clearCache(string $medicationName, ?int $patientAge = null, ?string $patientGender = null): void
    {
        $cacheKey = $this->getCacheKey($medicationName, $patientAge, $patientGender);
        Cache::forget($cacheKey);
    }
}