<?php

namespace App\Console\Commands;

use App\Models\Claim;
use App\Services\ClaimDenialPredictionService;
use App\Services\UnderpaymentDetectionService;
use App\Jobs\SendBillingAlerts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class ProcessPendingClaims extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process-pending-claims
                            {--dry-run : Run without making changes}
                            {--limit= : Limit number of claims to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending claims for denial risk scoring and underpayment detection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting nightly batch processing of pending claims...');

        $denialRiskThreshold = Config::get('billing.denial_risk_threshold', 0.7);
        $this->info('Denial risk threshold: ' . $denialRiskThreshold);

        // Get pending claims
        $query = Claim::whereIn('claim_status', ['pending', 'submitted']);

        if ($this->option('limit')) {
            $query->limit($this->option('limit'));
        }

        $pendingClaims = $query->get();
        $this->info("Found {$pendingClaims->count()} pending claims to process");

        if ($pendingClaims->isEmpty()) {
            $this->info('No pending claims to process.');
            return Command::SUCCESS;
        }

        $processed = 0;
        $highRiskClaims = [];
        $underpaymentAlerts = [];

        $progressBar = $this->output->createProgressBar($pendingClaims->count());
        $progressBar->start();

        foreach ($pendingClaims as $claim) {
            try {
                $this->processClaim($claim, $denialRiskThreshold, $highRiskClaims, $underpaymentAlerts);
                $processed++;
                $progressBar->advance();
            } catch (\Exception $e) {
                Log::error("Error processing claim {$claim->claim_id}: " . $e->getMessage());
                $this->error("Failed to process claim {$claim->claim_id}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Processed {$processed} claims");
        $this->info("High risk claims: " . count($highRiskClaims));
        $this->info("Underpayment alerts: " . count($underpaymentAlerts));

        // Dispatch alerts job if not dry run
        if (!$this->option('dry-run') && (!empty($highRiskClaims) || !empty($underpaymentAlerts))) {
            $this->info('Dispatching alert notifications...');
            SendBillingAlerts::dispatch($highRiskClaims, $underpaymentAlerts);
        }

        // Log summary
        Log::info('Nightly claim processing completed', [
            'processed' => $processed,
            'high_risk_count' => count($highRiskClaims),
            'underpayment_count' => count($underpaymentAlerts),
            'dry_run' => $this->option('dry-run'),
        ]);

        return Command::SUCCESS;
    }

    /**
     * Process a single claim for risk scoring and underpayment detection.
     */
    private function processClaim(Claim $claim, float $denialRiskThreshold, array &$highRiskClaims, array &$underpaymentAlerts): void
    {
        $denialPredictionService = app(ClaimDenialPredictionService::class);
        $underpaymentService = app(UnderpaymentDetectionService::class);

        // Predict denial risk
        $denialResult = $denialPredictionService->predictDenialRisk($claim);

        if (isset($denialResult['denial_risk']) && $denialResult['denial_risk'] > $denialRiskThreshold) {
            $highRiskClaims[] = [
                'claim_id' => $claim->id,
                'claim_number' => $claim->claim_id,
                'denial_risk' => $denialResult['denial_risk'],
                'top_factors' => $denialResult['top_factors'] ?? [],
                'patient_id' => $claim->patient_id,
                'expected_amount' => $claim->expected_amount,
            ];
        }

        // Check for underpayments
        $underpaymentAlert = $underpaymentService->detectAndFlagUnderpayment($claim);
        if ($underpaymentAlert) {
            $underpaymentAlerts[] = [
                'alert_id' => $underpaymentAlert->id,
                'claim_id' => $claim->id,
                'claim_number' => $claim->claim_id,
                'expected_amount' => $underpaymentAlert->expected_amount,
                'paid_amount' => $underpaymentAlert->paid_amount,
                'variance' => $underpaymentAlert->variance,
                'threshold_percentage' => $underpaymentAlert->threshold_percentage,
            ];
        }
    }
}
