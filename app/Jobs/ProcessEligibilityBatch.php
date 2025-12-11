<?php

namespace App\Jobs;

use App\Models\PatientInsurance;
use App\Services\EligibilityServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessEligibilityBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1 minute

    protected array $batchData;
    protected int $userId;
    protected string $batchId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $batchData, int $userId, string $batchId)
    {
        $this->batchData = $batchData;
        $this->userId = $userId;
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle(EligibilityServiceFactory $factory): array
    {
        $results = [];
        $errors = [];

        Log::info('Starting batch eligibility processing', [
            'batch_size' => count($this->batchData),
            'user_id' => $this->userId,
        ]);

        foreach ($this->batchData as $index => $checkData) {
            try {
                $patientInsurance = PatientInsurance::findOrFail($checkData['patient_insurance_id']);

                $service = $factory->getServiceForProvider($patientInsurance->insuranceProvider);
                $result = $service->checkEligibility($patientInsurance, $checkData['service_type']);

                $results[] = [
                    'index' => $index,
                    'patient_insurance_id' => $checkData['patient_insurance_id'],
                    'service_type' => $checkData['service_type'],
                    'result' => $result,
                ];

            } catch (Exception $e) {
                Log::error('Batch eligibility check failed', [
                    'index' => $index,
                    'patient_insurance_id' => $checkData['patient_insurance_id'],
                    'error' => $e->getMessage(),
                ]);

                $errors[] = [
                    'index' => $index,
                    'patient_insurance_id' => $checkData['patient_insurance_id'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Completed batch eligibility processing', [
            'successful' => count($results),
            'errors' => count($errors),
        ]);

        // Store batch results or send notification
        $this->storeBatchResults($results, $errors);

        return [
            'results' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * Store batch results for later retrieval
     */
    protected function storeBatchResults(array $results, array $errors): void
    {
        cache()->put("eligibility_batch:{$this->batchId}", [
            'results' => $results,
            'errors' => $errors,
            'completed_at' => now(),
        ], now()->addHours(24));

        Log::info('Batch results stored', ['batch_id' => $this->batchId]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Batch eligibility processing failed completely', [
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
            'batch_size' => count($this->batchData),
        ]);
    }
}
