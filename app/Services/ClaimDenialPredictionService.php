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

            // Create temporary file for Python script
            $tempFile = $this->createTempDataFile($claimData);

            // Call Python prediction script
            $result = $this->callPredictionScript($tempFile);

            // Clean up temp file
            $this->cleanupTempFile($tempFile);

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
        $filename = 'claim_' . $data['claim_id'] . '_' . time() . '.json';
        $tempPath = storage_path('app/temp/' . $filename);

        // Ensure temp directory exists
        $tempDir = dirname($tempPath);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
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
        $command = "python \"{$pythonScript}\" \"{$dataFile}\" 2>&1";

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
        if (file_exists($filePath)) {
            unlink($filePath);
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
            $command = "python \"{$pythonScript}\" \"{$tempFile}\" 2>&1";

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
}
