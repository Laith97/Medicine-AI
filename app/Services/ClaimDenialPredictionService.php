<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClaimDenialPredictionService
{
    /**
     * Predict denial risk for a claim
     */
    public function predictDenialRisk(Claim $claim): array
    {
        try {
            // Prepare claim data with patient demographics
            $claimData = $this->prepareClaimData($claim);

            // Evaluate payer rules and include violations in prediction
            $ruleViolations = $this->evaluateRuleViolations($claim);
            $claimData['rule_violations'] = $ruleViolations;

            // Create temporary file for Python script
            $tempFile = $this->createTempDataFile($claimData);

            // Call Python prediction script
            $result = $this->callPredictionScript($tempFile);

            // Clean up temp file
            $this->cleanupTempFile($tempFile);

            // Enhance result with rule violation information
            if (isset($result['top_factors']) && is_array($result['top_factors'])) {
                $ruleFactors = $this->extractRuleViolationFactors($ruleViolations);
                $result['top_factors'] = array_merge($ruleFactors, $result['top_factors']);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Claim denial prediction failed: ' . $e->getMessage());
            return [
                'claim_id' => $claim->claim_id,
                'denial_risk' => null,
                'top_factors' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Prepare claim data with patient demographics
     */
    private function prepareClaimData(Claim $claim): array
    {
        $patient = $claim->patient;

        return [
            'claim_id' => $claim->claim_id,
            'patient_id' => $claim->patient_id,
            'patient_age' => $patient ? $patient->age : null,
            'patient_gender' => $patient ? $patient->gender : null,
            'primary_doctor_id' => $patient ? $patient->primary_doctor_id : null,
            'diagnosis_text' => $claim->diagnosis_text,
            'procedure_text' => $claim->procedure_text,
            'icd10_codes' => $claim->icd10_codes ?? [],
            'cpt_codes' => $claim->cpt_codes ?? [],
            'payer' => $claim->payer,
            'expected_amount' => $claim->expected_amount,
            'service_date' => $claim->service_date?->format('Y-m-d'),
            'submission_date' => $claim->submission_date?->format('Y-m-d'),
        ];
    }

    /**
     * Create temporary JSON file with claim data
     */
    private function createTempDataFile(array $data): string
    {
        $filename = 'claim_' . $data['claim_id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.json';
        $tempPath = storage_path('app/temp/' . $filename);

        // Ensure temp directory exists with proper error handling
        $tempDir = dirname($tempPath);
        if (!is_dir($tempDir)) {
            // Use mkdir with recursive flag and suppress errors, then verify
            @mkdir($tempDir, 0755, true);
            if (!is_dir($tempDir)) {
                throw new \RuntimeException("Failed to create temp directory: {$tempDir}");
            }
        }

        file_put_contents($tempPath, json_encode($data));

        return $tempPath;
    }

    /**
     * Call Python prediction script
     */
    private function callPredictionScript(string $dataFile): array
    {
        $pythonScript = base_path('python/predict_denial.py');
        // Sanitize file paths to prevent command injection
        $safeDataFile = escapeshellarg($dataFile);
        $safeScript = escapeshellarg($pythonScript);
        $command = "python {$safeScript} {$safeDataFile} 2>&1";

        Log::info('Executing Python prediction command: ' . $command);

        $output = shell_exec($command);

        if ($output === null) {
            throw new \Exception('Python script execution failed');
        }

        Log::info('Python script output: ' . $output);

        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse Python script output: ' . $output);
        }

        return $result;
    }

    /**
     * Clean up temporary file
     */
    private function cleanupTempFile(string $filePath): void
    {
        if (!@unlink($filePath)) {
            Log::warning('Failed to delete temp file: ' . $filePath);
        }
    }

    /**
     * Train the model with historical data
     */
    public function trainModel(): bool
    {
        try {
            // Export normalized claims data
            $claims = Claim::with('patient')->get();
            $normalizedData = app(ClaimDataNormalizationService::class)->generateNormalizedData($claims);

            // Add patient demographics to normalized data
            $enrichedData = $this->enrichWithPatientData($normalizedData);

            // Create temp file for training
            $tempFile = $this->createTempDataFile($enrichedData);

            // Call training script
            $pythonScript = base_path('python/train_denial_predictor.py');
            $safeScript = escapeshellarg($pythonScript);
            $safeTempFile = escapeshellarg($tempFile);
            $command = "python {$safeScript} {$safeTempFile} 2>&1";

            Log::info('Executing Python training command: ' . $command);

            $output = shell_exec($command);

            // Clean up
            $this->cleanupTempFile($tempFile);

            if ($output === null) {
                throw new \Exception('Training script execution failed');
            }

            Log::info('Training completed: ' . $output);

            return true;

        } catch (\Exception $e) {
            Log::error('Model training failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Predict denial for claim data
     */
    public function predictDenial(array $data): array
    {
        try {
            // Prepare data for prediction
            $claimData = $this->preparePredictionData($data);

            // Create temporary file for Python script
            $tempFile = $this->createTempDataFile($claimData);

            // Call Python prediction script
            $result = $this->callPredictionScript($tempFile);

            // Clean up temp file
            $this->cleanupTempFile($tempFile);

            // Format result for controller
            return [
                'probability' => $result['denial_risk'] ?? 0.0,
                'explanations' => $result['top_factors'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Claim denial prediction failed: ' . $e->getMessage());
            return [
                'probability' => 0.0,
                'explanations' => []
            ];
        }
    }

    /**
     * Prepare prediction data from input array
     */
    private function preparePredictionData(array $data): array
    {
        return [
            'claim_id' => 'temp_' . time(),
            'patient_id' => null,
            'patient_age' => $data['patient_age'] ?? null,
            'patient_gender' => $data['patient_gender'] ?? null,
            'primary_doctor_id' => null,
            'diagnosis_text' => '',
            'procedure_text' => '',
            'icd10_codes' => $data['icd10_codes'] ?? [],
            'cpt_codes' => $data['cpt_codes'] ?? [],
            'payer' => '',
            'expected_amount' => $data['amount'] ?? 0,
            'service_date' => now()->format('Y-m-d'),
            'submission_date' => now()->format('Y-m-d'),
        ];
    }

    /**
     * Enrich normalized data with patient demographics
     */
    private function enrichWithPatientData(array $claimsData): array
    {
        $enriched = [];

        foreach ($claimsData as $claim) {
            $patient = User::find($claim['patient_id']);

            $enriched[] = array_merge($claim, [
                'patient_age' => $patient ? $patient->age : null,
                'patient_gender' => $patient ? $patient->gender : null,
                'primary_doctor_id' => $patient ? $patient->primary_doctor_id : null,
            ]);
        }

        return $enriched;
    }

    /**
     * Predict appeal success probability for a denied claim
     */
    public function predictAppealSuccess(Claim $claim, string $denialCategory): array
    {
        try {
            // Prepare claim data with denial information
            $appealData = $this->prepareAppealData($claim, $denialCategory);

            // Create temporary file for Python script
            $tempFile = $this->createTempDataFile($appealData);

            // Call appeal prediction script
            $result = $this->callAppealPredictionScript($tempFile);

            // Clean up temp file
            $this->cleanupTempFile($tempFile);

            return $result;

        } catch (\Exception $e) {
            Log::error('Appeal success prediction failed: ' . $e->getMessage());
            return [
                'probability' => 0.5, // Default neutral probability
                'reason' => 'Prediction failed: ' . $e->getMessage(),
                'confidence' => 0.0
            ];
        }
    }

    /**
     * Prepare appeal prediction data
     */
    private function prepareAppealData(Claim $claim, string $denialCategory): array
    {
        $patient = $claim->patient;

        return [
            'claim_id' => $claim->claim_id,
            'patient_id' => $claim->patient_id,
            'patient_age' => $patient ? $patient->age : null,
            'patient_gender' => $patient ? $patient->gender : null,
            'primary_doctor_id' => $patient ? $patient->primary_doctor_id : null,
            'diagnosis_text' => $claim->diagnosis_text,
            'procedure_text' => $claim->procedure_text,
            'icd10_codes' => $claim->icd10_codes ?? [],
            'cpt_codes' => $claim->cpt_codes ?? [],
            'payer' => $claim->payer,
            'expected_amount' => $claim->expected_amount,
            'paid_amount' => $claim->paid_amount,
            'denial_category' => $denialCategory,
            'raw_denial_code' => $claim->raw_denial_code,
            'service_date' => $claim->service_date?->format('Y-m-d'),
            'denial_date' => $claim->updated_at?->format('Y-m-d'), // Approximate denial date
            'provider_name' => $claim->provider_name,
            'facility_name' => $claim->facility_name,
        ];
    }

    /**
     * Evaluate rule violations for a claim
     */
    private function evaluateRuleViolations(Claim $claim): array
    {
        try {
            $rulesEngine = app(\App\Services\PayerRulesEngine::class);
            $ruleResults = $rulesEngine->evaluateClaim($claim);

            $violations = [];
            foreach ($ruleResults as $result) {
                if (isset($result['actions'])) {
                    foreach ($result['actions'] as $action) {
                        if ($action['type'] === 'denial' || $action['type'] === 'warning') {
                            $violations[] = [
                                'rule_id' => $result['rule_id'],
                                'rule_type' => $result['rule_type'],
                                'action_type' => $action['type'],
                                'severity' => $action['severity'] ?? 'medium',
                                'message' => $action['message'] ?? 'Rule violation detected',
                                'reason' => $action['reason'] ?? null,
                            ];
                        }
                    }
                }
            }

            return $violations;
        } catch (\Exception $e) {
            Log::error('Error evaluating rule violations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract rule violation factors for prediction
     */
    private function extractRuleViolationFactors(array $violations): array
    {
        $factors = [];

        foreach ($violations as $violation) {
            $factor = '';

            switch ($violation['action_type']) {
                case 'denial':
                    $factor = 'Critical rule violation: ' . $violation['message'];
                    break;
                case 'warning':
                    $factor = 'Rule warning: ' . $violation['message'];
                    break;
            }

            if ($factor) {
                $factors[] = $factor;
            }
        }

        return $factors;
    }

    /**
     * Call Python appeal prediction script
     */
    private function callAppealPredictionScript(string $dataFile): array
    {
        $pythonScript = base_path('python/predict_appeal_success.py');

        // Check if script exists before attempting to run
        if (!file_exists($pythonScript)) {
            throw new \Exception('Appeal prediction script not found: ' . $pythonScript);
        }

        $safeScript = escapeshellarg($pythonScript);
        $safeDataFile = escapeshellarg($dataFile);
        $command = "python {$safeScript} {$safeDataFile} 2>&1";

        Log::info('Executing Python appeal prediction command: ' . $command);

        exec($command, $outputLines, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Appeal prediction script failed with code ' . $returnCode . ': ' . implode("\n", $outputLines));
        }

        $output = implode("\n", $outputLines);
        Log::info('Appeal prediction script output: ' . $output);

        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse appeal prediction script output: ' . $output);
        }

        return $result;
    }
}
