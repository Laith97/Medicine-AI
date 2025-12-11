<?php

namespace App\Jobs;

use App\Models\ClearinghouseSubmission;
use App\Services\ClaimSubmissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessClaimSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 600]; // Retry after 1min, 5min, 10min

    protected ClearinghouseSubmission $submission;

    /**
     * Create a new job instance.
     */
    public function __construct(ClearinghouseSubmission $submission)
    {
        $this->submission = $submission;
    }

    /**
     * Execute the job.
     */
    public function handle(ClaimSubmissionService $submissionService): void
    {
        try {
            Log::info('Processing claim submission', [
                'submission_id' => $this->submission->id,
                'batch_id' => $this->submission->batch_id,
                'claim_count' => $this->submission->claim_count
            ]);

            $submissionService->processSubmission($this->submission);

        } catch (\Exception $e) {
            Log::error('Claim submission job failed', [
                'submission_id' => $this->submission->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Mark as failed if max retries reached
            if ($this->attempts() >= $this->tries) {
                $this->submission->update([
                    'status' => 'rejected',
                    'error_message' => 'Job failed after ' . $this->tries . ' attempts: ' . $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Claim submission job permanently failed', [
            'submission_id' => $this->submission->id,
            'error' => $exception->getMessage()
        ]);

        // Update submission status
        $this->submission->update([
            'status' => 'rejected',
            'error_message' => 'Job permanently failed: ' . $exception->getMessage(),
        ]);
    }
}
