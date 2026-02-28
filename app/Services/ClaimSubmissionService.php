<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClearinghouseAccount;
use App\Models\ClearinghouseSubmission;
use App\Jobs\ProcessClaimSubmission;
use App\Services\PayerRulesEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClaimSubmissionService
{
    protected EDIGeneratorService $ediGenerator;
    protected ClearinghouseApiClient $apiClient;
    protected PayerRulesEngine $rulesEngine;

    public function __construct(
        EDIGeneratorService $ediGenerator,
        PayerRulesEngine $rulesEngine
    ) {
        $this->ediGenerator = $ediGenerator;
        $this->rulesEngine = $rulesEngine;
    }

    /**
     * Submit claims to clearinghouse
     */
    public function submitClaims(Collection $claims, ClearinghouseAccount $account, string $submissionType = '837P'): ClearinghouseSubmission
    {
        DB::beginTransaction();

        try {
            // Evaluate claims against payer rules before submission
            $ruleEvaluationResults = $this->evaluateClaimsAgainstRules($claims);

            // Check for blocking rules (denials)
            $blockingResults = $ruleEvaluationResults->filter(function ($results) {
                return $results->contains(function ($result) {
                    return isset($result['actions']) &&
                           collect($result['actions'])->contains(function ($action) {
                               return $action['type'] === 'denial';
                           });
                });
            });

            if ($blockingResults->isNotEmpty()) {
                throw new \Exception('Claim submission blocked by payer rules. Please review rule violations.');
            }

            // Generate batch ID
            $batchId = $this->generateBatchId($account);

            // Create submission record
            $submission = ClearinghouseSubmission::create([
                'clearinghouse_account_id' => $account->id,
                'batch_id' => $batchId,
                'submission_type' => $submissionType,
                'status' => 'pending',
                'claim_count' => $claims->count(),
                'total_amount' => $claims->sum('total_amount'),
                'metadata' => [
                    'hospital_id' => $claims->first()->hospital_id ?? null,
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'claim_ids' => $claims->pluck('id')->toArray(),
                    'rule_evaluation_results' => $ruleEvaluationResults->toArray(),
                ],
            ]);

            // Link claims to submission
            $claims->each(function ($claim) use ($submission, $account) {
                $claim->update([
                    'clearinghouse_submission_id' => $submission->id,
                    'clearinghouse_batch_id' => $submission->batch_id,
                    'clearinghouse_provider' => $account->provider,
                    'clearinghouse_submitted_at' => now(),
                ]);
            });

            DB::commit();

            // Dispatch job for background processing
            ProcessClaimSubmission::dispatch($submission);

            return $submission;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create claim submission', [
                'error' => $e->getMessage(),
                'claims_count' => $claims->count(),
                'account_id' => $account->id
            ]);
            throw $e;
        }
    }

    /**
     * Evaluate claims against payer rules
     */
    protected function evaluateClaimsAgainstRules(Collection $claims): Collection
    {
        $results = collect();

        foreach ($claims as $claim) {
            $claimResults = $this->rulesEngine->evaluateClaim($claim);
            $results->put($claim->id, $claimResults);
        }

        return $results;
    }

    /**
     * Process submission (called by job)
     */
    public function processSubmission(ClearinghouseSubmission $submission): void
    {
        $maxRetries = 3;
        $retryCount = $submission->metadata['retry_count'] ?? 0;

        try {
            $account = $submission->clearinghouseAccount;
            $claims = $submission->claims;

            if ($claims->isEmpty()) {
                throw new \Exception('No claims found for submission');
            }

            // Generate EDI content
            $ediContent = $this->generateEDIContent($claims, $account, $submission->submission_type);

            // Validate EDI
            $validationErrors = $this->ediGenerator->validateEDI($ediContent);
            if (!empty($validationErrors)) {
                $submission->update([
                    'status' => 'rejected',
                    'error_message' => 'EDI validation failed: ' . implode(', ', $validationErrors),
                ]);

                // Log validation failure
                AuditLoggingService::logClearinghouseTransaction(
                    $submission->id,
                    'edi_validation_failed',
                    null,
                    $account->id,
                    [
                        'validation_errors' => $validationErrors,
                        'claim_count' => $submission->claim_count
                    ]
                );

                return;
            }

            // Encrypt EDI content before storage
            $encryptedEdiContent = $this->encryptEdiContent($ediContent);

            // Store encrypted EDI content
            $submission->update(['edi_content' => $encryptedEdiContent]);

            // Submit to clearinghouse
            $this->apiClient = new ClearinghouseApiClient($account);
            $result = $this->apiClient->submitEDI($ediContent, [
                'batch_id' => $submission->batch_id,
                'claim_count' => $submission->claim_count,
                'total_amount' => $submission->total_amount,
            ]);

            if ($result['success']) {
                $submission->markAsSubmitted();

                // Update claims with clearinghouse claim IDs if provided
                if (isset($result['response']['claim_ids'])) {
                    $this->updateClaimIds($claims, $result['response']['claim_ids']);
                }

                // Log successful submission
                AuditLoggingService::logClearinghouseTransaction(
                    $submission->id,
                    'submission_successful',
                    null,
                    $account->id,
                    [
                        'batch_id' => $submission->batch_id,
                        'claim_count' => $submission->claim_count,
                        'total_amount' => $submission->total_amount,
                        'tracking_id' => $result['tracking_id'] ?? null
                    ]
                );

                Log::info('Claims submitted successfully', [
                    'submission_id' => $submission->id,
                    'batch_id' => $submission->batch_id,
                    'claim_count' => $submission->claim_count
                ]);
            } else {
                // Handle submission failure with retry logic
                $this->handleSubmissionFailure($submission, $result['error'], $retryCount, $maxRetries);

                // Log submission failure
                AuditLoggingService::logClearinghouseTransaction(
                    $submission->id,
                    'submission_failed',
                    null,
                    $account->id,
                    [
                        'error' => $result['error'],
                        'retry_count' => $retryCount,
                        'max_retries' => $maxRetries
                    ]
                );
            }

        } catch (\Exception $e) {
            // Handle processing exception with retry logic
            $this->handleProcessingException($submission, $e, $retryCount, $maxRetries);

            // Log processing failure
            AuditLoggingService::logClearinghouseTransaction(
                $submission->id,
                'processing_failed',
                null,
                $account->id ?? null,
                [
                    'error' => $e->getMessage(),
                    'retry_count' => $retryCount,
                    'max_retries' => $maxRetries
                ]
            );
        }
    }

    /**
     * Check submission status and retrieve responses
     */
    public function checkSubmissionStatus(ClearinghouseSubmission $submission): void
    {
        if (!$submission->batch_id) {
            return;
        }

        try {
            $account = $submission->clearinghouseAccount;
            $this->apiClient = new ClearinghouseApiClient($account);

            // Check overall status
            $statusResult = $this->apiClient->checkStatus($submission->batch_id);

            if ($statusResult['success']) {
                $this->updateSubmissionStatus($submission, $statusResult);

                // Retrieve responses
                $this->retrieveResponses($submission);
            }

        } catch (\Exception $e) {
            Log::error('Status check failed', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Retrieve and process responses for submission
     */
    protected function retrieveResponses(ClearinghouseSubmission $submission): void
    {
        try {
            $account = $submission->clearinghouseAccount;
            $this->apiClient = new ClearinghouseApiClient($account);

            // Get 277CA responses (Claim Acknowledgements)
            $ackResult = $this->apiClient->getResponses($submission->batch_id, '277CA');
            if ($ackResult['success']) {
                $this->processAcknowledgementResponses($submission, $ackResult['responses']);
            }

            // Get 835 responses (Remittance Advice)
            $remitResult = $this->apiClient->getResponses($submission->batch_id, '835');
            if ($remitResult['success']) {
                $this->processRemittanceResponses($submission, $remitResult['responses']);
            }

            // Get 999 responses (Implementation Acknowledgments)
            $implResult = $this->apiClient->getResponses($submission->batch_id, '999');
            if ($implResult['success']) {
                $this->processImplementationResponses($submission, $implResult['responses']);
            }

        } catch (\Exception $e) {
            Log::error('Response retrieval failed', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process 277CA acknowledgement responses
     */
    protected function processAcknowledgementResponses(ClearinghouseSubmission $submission, array $responses): void
    {
        $responseProcessor = app(ResponseProcessorService::class);

        foreach ($responses as $responseData) {
            try {
                $responseProcessor->process277CA($submission, $responseData);
            } catch (\Exception $e) {
                Log::error('Failed to process 277CA response', [
                    'submission_id' => $submission->id,
                    'response_data' => $responseData,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Process 835 remittance responses
     */
    protected function processRemittanceResponses(ClearinghouseSubmission $submission, array $responses): void
    {
        $responseProcessor = app(ResponseProcessorService::class);

        foreach ($responses as $responseData) {
            try {
                $responseProcessor->process835($submission, $responseData);
            } catch (\Exception $e) {
                Log::error('Failed to process 835 response', [
                    'submission_id' => $submission->id,
                    'response_data' => $responseData,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Process 999 implementation responses
     */
    protected function processImplementationResponses(ClearinghouseSubmission $submission, array $responses): void
    {
        $responseProcessor = app(ResponseProcessorService::class);

        foreach ($responses as $responseData) {
            try {
                $responseProcessor->process999($submission, $responseData);
            } catch (\Exception $e) {
                Log::error('Failed to process 999 response', [
                    'submission_id' => $submission->id,
                    'response_data' => $responseData,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Generate EDI content based on submission type
     */
    protected function generateEDIContent(Collection $claims, ClearinghouseAccount $account, string $type): string
    {
        return match($type) {
            '837P' => $this->ediGenerator->generate837P($claims, $account),
            '837I' => $this->ediGenerator->generate837I($claims, $account),
            default => throw new \Exception("Unsupported submission type: {$type}")
        };
    }

    /**
     * Update submission status based on API response
     */
    protected function updateSubmissionStatus(ClearinghouseSubmission $submission, array $statusResult): void
    {
        $status = $statusResult['status'] ?? 'unknown';

        switch ($status) {
            case 'accepted':
            case 'processed':
                $submission->markAsAccepted();
                break;
            case 'rejected':
                $submission->markAsRejected('Rejected by clearinghouse');
                break;
            case 'partial':
                $submission->update(['status' => 'partial_accept']);
                break;
            default:
                // Keep current status
                break;
        }

        if (isset($statusResult['last_updated'])) {
            $submission->update(['response_received_at' => $statusResult['last_updated']]);
        }
    }

    /**
     * Update claims with clearinghouse claim IDs
     */
    protected function updateClaimIds(Collection $claims, array $claimIds): void
    {
        foreach ($claims as $index => $claim) {
            if (isset($claimIds[$index])) {
                $claim->update([
                    'clearinghouse_claim_id' => $claimIds[$index]
                ]);
            }
        }
    }

    /**
     * Generate unique batch ID
     */
    protected function generateBatchId(ClearinghouseAccount $account): string
    {
        do {
            $batchId = strtoupper($account->provider) . '_' . date('Ymd_His') . '_' . Str::random(4);
        } while (ClearinghouseSubmission::where('batch_id', $batchId)->exists());

        return $batchId;
    }

    /**
     * Get submissions pending status check
     */
    public function getPendingStatusChecks(): Collection
    {
        return ClearinghouseSubmission::whereIn('status', ['submitted', 'accepted', 'partial_accept'])
            ->where('submitted_at', '<', now()->subHours(1)) // Check after 1 hour
            ->where(function ($query) {
                $query->whereNull('response_received_at')
                      ->orWhere('response_received_at', '<', now()->subHours(24)); // Re-check daily
            })
            ->get();
    }

    /**
     * Batch check status for multiple submissions
     */
    public function batchCheckStatuses(Collection $submissions): void
    {
        foreach ($submissions as $submission) {
            $this->checkSubmissionStatus($submission);
        }
    }

    /**
     * Handle submission failure with retry logic
     */
    protected function handleSubmissionFailure(ClearinghouseSubmission $submission, string $error, int $retryCount, int $maxRetries): void
    {
        if ($retryCount < $maxRetries && $this->isRetryableError($error)) {
            // Schedule retry
            $submission->update([
                'status' => 'retry_pending',
                'error_message' => 'Retry ' . ($retryCount + 1) . '/' . $maxRetries . ': ' . $error,
                'metadata' => array_merge($submission->metadata ?? [], [
                    'retry_count' => $retryCount + 1,
                    'last_retry_at' => now(),
                    'retry_errors' => array_merge($submission->metadata['retry_errors'] ?? [], [$error])
                ])
            ]);

            // Dispatch retry job with delay
            $delay = $this->calculateRetryDelay($retryCount);
            ProcessClaimSubmission::dispatch($submission)->delay($delay);

            Log::info('Submission scheduled for retry', [
                'submission_id' => $submission->id,
                'retry_count' => $retryCount + 1,
                'delay_minutes' => $delay->totalMinutes
            ]);
        } else {
            // Mark as failed after max retries
            $submission->update([
                'status' => 'failed',
                'error_message' => $error,
                'metadata' => array_merge($submission->metadata ?? [], [
                    'final_failure' => true,
                    'total_retries' => $retryCount
                ])
            ]);

            // Mark claims as failed
            $submission->claims->each(function ($claim) {
                $claim->update(['claim_status' => 'clearinghouse_failed']);
            });

            Log::error('Submission failed after max retries', [
                'submission_id' => $submission->id,
                'error' => $error,
                'total_retries' => $retryCount
            ]);
        }
    }

    /**
     * Handle processing exception with retry logic
     */
    protected function handleProcessingException(ClearinghouseSubmission $submission, \Exception $e, int $retryCount, int $maxRetries): void
    {
        if ($retryCount < $maxRetries && $this->isRetryableException($e)) {
            // Schedule retry
            $submission->update([
                'status' => 'retry_pending',
                'error_message' => 'Retry ' . ($retryCount + 1) . '/' . $maxRetries . ': ' . $e->getMessage(),
                'metadata' => array_merge($submission->metadata ?? [], [
                    'retry_count' => $retryCount + 1,
                    'last_retry_at' => now(),
                    'retry_exceptions' => array_merge($submission->metadata['retry_exceptions'] ?? [], [
                        'message' => $e->getMessage(),
                        'type' => get_class($e)
                    ])
                ])
            ]);

            // Dispatch retry job with delay
            $delay = $this->calculateRetryDelay($retryCount);
            ProcessClaimSubmission::dispatch($submission)->delay($delay);

            Log::info('Submission scheduled for retry after exception', [
                'submission_id' => $submission->id,
                'retry_count' => $retryCount + 1,
                'exception' => $e->getMessage(),
                'delay_minutes' => $delay->totalMinutes
            ]);
        } else {
            // Mark as failed after max retries
            $submission->update([
                'status' => 'failed',
                'error_message' => 'Processing failed: ' . $e->getMessage(),
                'metadata' => array_merge($submission->metadata ?? [], [
                    'final_failure' => true,
                    'total_retries' => $retryCount,
                    'final_exception' => [
                        'message' => $e->getMessage(),
                        'type' => get_class($e),
                        'trace' => $e->getTraceAsString()
                    ]
                ])
            ]);

            Log::error('Submission processing failed after max retries', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
                'total_retries' => $retryCount
            ]);
        }
    }

    /**
     * Check if error is retryable
     */
    protected function isRetryableError(string $error): bool
    {
        $retryablePatterns = [
            'timeout',
            'connection',
            'network',
            'temporary',
            'rate limit',
            'server error',
            '503',
            '502',
            '504'
        ];

        $errorLower = strtolower($error);
        foreach ($retryablePatterns as $pattern) {
            if (str_contains($errorLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if exception is retryable
     */
    protected function isRetryableException(\Exception $e): bool
    {
        $retryableExceptions = [
            \Illuminate\Http\Client\ConnectionException::class,
            \Illuminate\Http\Client\RequestTimeoutException::class,
        ];

        foreach ($retryableExceptions as $exceptionClass) {
            if ($e instanceof $exceptionClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate retry delay (exponential backoff)
     */
    protected function calculateRetryDelay(int $retryCount): \Carbon\CarbonInterval
    {
        // Exponential backoff: 5, 15, 45 minutes
        $minutes = 5 * pow(3, $retryCount);
        return now()->addMinutes($minutes)->diffAsCarbonInterval(now());
    }

    /**
     * Encrypt EDI content for HIPAA compliance
     */
    protected function encryptEdiContent(string $ediContent): string
    {
        $encryptionKey = config('app.edi_encryption_key', env('EDI_ENCRYPTION_KEY'));

        if (!$encryptionKey) {
            Log::warning('EDI encryption key not configured, storing unencrypted');
            return $ediContent;
        }

        // Use Laravel's encryption
        return encrypt($ediContent);
    }

    /**
     * Decrypt EDI content
     */
    public function decryptEdiContent(string $encryptedEdiContent): string
    {
        try {
            return decrypt($encryptedEdiContent);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt EDI content', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to decrypt EDI content');
        }
    }

    /**
     * Get failed submissions for reconciliation
     */
    public function getFailedSubmissions(int $hospitalId, array $filters = []): Collection
    {
        $query = ClearinghouseSubmission::with(['clearinghouseAccount', 'claims'])
            ->whereIn('status', ['failed', 'rejected'])
            ->whereHas('claims', function ($q) use ($hospitalId) {
                $q->where('hospital_id', $hospitalId);
            });

        if (isset($filters['provider'])) {
            $query->whereHas('clearinghouseAccount', function ($q) use ($filters) {
                $q->where('provider', $filters['provider']);
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Manually resubmit failed submission
     */
    public function manualResubmit(ClearinghouseSubmission $submission, array $options = []): array
    {
        try {
            // Reset submission status
            $submission->update([
                'status' => 'pending',
                'error_message' => null,
                'metadata' => array_merge($submission->metadata ?? [], [
                    'manual_resubmit' => true,
                    'resubmitted_at' => now(),
                    'resubmit_options' => $options
                ])
            ]);

            // Reset claims status
            $submission->claims->each(function ($claim) {
                $claim->update([
                    'claim_status' => 'submitted',
                    'clearinghouse_claim_id' => null,
                    'clearinghouse_response_received_at' => null
                ]);
            });

            // Dispatch job for reprocessing
            ProcessClaimSubmission::dispatch($submission);

            // Log manual resubmit
            AuditLoggingService::logClearinghouseTransaction(
                $submission->id,
                'manual_resubmit',
                auth()->id(),
                $submission->clearinghouse_account_id,
                [
                    'options' => $options,
                    'claim_count' => $submission->claim_count
                ]
            );

            return [
                'success' => true,
                'message' => 'Submission scheduled for manual resubmit'
            ];

        } catch (\Exception $e) {
            Log::error('Manual resubmit failed', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Manual resubmit failed: ' . $e->getMessage()
            ];
        }
    }
}
