<?php

namespace App\Jobs;

use App\Models\Claim;
use App\Services\ClaimWorkflowAutomationService;
use App\Services\BatchOptimizationService;
use App\Services\AppealWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessClaimWorkflowAutomation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Collection $claims;
    protected int $hospitalId;

    /**
     * Create a new job instance.
     */
    public function __construct(Collection $claims, int $hospitalId)
    {
        $this->claims = $claims;
        $this->hospitalId = $hospitalId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        ClaimWorkflowAutomationService $automationService,
        BatchOptimizationService $batchService,
        AppealWorkflowService $appealService
    ): void {
        Log::info('Starting claim workflow automation', [
            'claim_count' => $this->claims->count(),
            'hospital_id' => $this->hospitalId
        ]);

        try {
            // Step 1: Route claims based on provider contracts
            $routingResult = $automationService->routeClaims($this->claims, $this->hospitalId);

            Log::info('Claim routing completed', [
                'routed_count' => count($routingResult['routed']),
                'unrouted_count' => count($routingResult['unrouted']),
                'error_count' => count($routingResult['errors'])
            ]);

            // Step 2: Create workflow tasks for routed claims
            if (!empty($routingResult['routed'])) {
                $automationService->createRoutingTasks($routingResult['routed']);
            }

            // Step 3: Process auto-submission tasks
            $automationService->processAutoSubmissionTasks();

            // Step 4: Check for denied claims and create appeal workflows
            $deniedClaims = $this->claims->where('claim_status', 'denied');
            foreach ($deniedClaims as $claim) {
                $appealService->createAppealWorkflow($claim);
            }

            // Step 5: Process auto-appeals
            $appealService->processAutoAppeals();

            // Step 6: Check for overdue appeals
            $appealService->checkOverdueAppeals();

            Log::info('Claim workflow automation completed successfully', [
                'hospital_id' => $this->hospitalId,
                'processed_claims' => $this->claims->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Claim workflow automation failed', [
                'hospital_id' => $this->hospitalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Claim workflow automation job failed', [
            'hospital_id' => $this->hospitalId,
            'claim_count' => $this->claims->count(),
            'error' => $exception->getMessage()
        ]);
    }
}
