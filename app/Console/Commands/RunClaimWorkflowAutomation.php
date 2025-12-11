<?php

namespace App\Console\Commands;

use App\Jobs\ProcessClaimWorkflowAutomation;
use App\Models\Claim;
use Illuminate\Console\Command;

class RunClaimWorkflowAutomation extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'claims:process-workflow-automation
                            {--hospital-id= : Process claims for specific hospital}
                            {--limit=100 : Maximum number of claims to process}
                            {--status=pending : Claim status to process (pending,submitted,denied)}
                            {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Process claim workflow automation for routing, appeals, and task management';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hospitalId = $this->option('hospital-id');
        $limit = (int) $this->option('limit');
        $status = $this->option('status');
        $dryRun = $this->option('dry-run');

        $this->info('Starting claim workflow automation processing...');

        // Build query for claims to process
        $query = Claim::query();

        if ($hospitalId) {
            $query->where('hospital_id', $hospitalId);
        }

        if ($status !== 'all') {
            $statuses = explode(',', $status);
            $query->whereIn('claim_status', $statuses);
        }

        $claims = $query->limit($limit)->get();

        $this->info("Found {$claims->count()} claims to process");

        if ($claims->isEmpty()) {
            $this->warn('No claims found matching the criteria.');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->displayDryRunResults($claims);
            return Command::SUCCESS;
        }

        // Dispatch the workflow automation job
        ProcessClaimWorkflowAutomation::dispatch($claims, $hospitalId ?? 1);

        $this->info('Workflow automation job dispatched successfully.');
        $this->info('Claims will be processed in the background.');

        return Command::SUCCESS;
    }

    /**
     * Display results for dry run
     */
    protected function displayDryRunResults($claims)
    {
        $this->info('DRY RUN - The following claims would be processed:');
        $this->newLine();

        // Group by status
        $statusGroups = $claims->groupBy('claim_status');

        foreach ($statusGroups as $status => $statusClaims) {
            $this->info("Status: {$status} ({$statusClaims->count()} claims)");

            // Show sample claims
            $statusClaims->take(5)->each(function ($claim) {
                $this->line("  - Claim #{$claim->claim_id}: {$claim->patient_name} - \${$claim->total_amount}");
            });

            if ($statusClaims->count() > 5) {
                $remaining = $statusClaims->count() - 5;
                $this->line("  ... and {$remaining} more claims");
            }

            $this->newLine();
        }

        // Summary statistics
        $totalAmount = $claims->sum('total_amount');
        $payers = $claims->pluck('payer')->unique()->filter()->values();
        $providers = $claims->pluck('provider_name')->unique()->filter()->values();

        $this->info('Summary:');
        $this->line("  Total Claims: {$claims->count()}");
        $this->line("  Total Amount: \${$totalAmount}");
        $this->line("  Unique Payers: " . $payers->count());
        $this->line("  Unique Providers: " . $providers->count());

        $this->newLine();
        $this->info('To execute the workflow automation, remove the --dry-run flag.');
    }
}
