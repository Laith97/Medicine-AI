<?php

namespace App\Console\Commands;

use App\Models\EligibilityCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessEligibilityDataRetention extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eligibility:retention-process
                            {--days=365 : Number of days to retain eligibility data}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process data retention for eligibility records based on HIPAA compliance requirements';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $retentionDays = $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $this->info("Processing eligibility data retention...");
        $this->info("Retention period: {$retentionDays} days");
        $this->info("Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No data will be deleted");
        }

        // Find expired eligibility checks
        $expiredChecks = EligibilityCheck::where('created_at', '<', $cutoffDate)
            ->whereNull('deleted_at') // Not already soft deleted
            ->get();

        $this->info("Found {$expiredChecks->count()} expired eligibility records");

        if ($expiredChecks->isEmpty()) {
            $this->info("No records to process.");
            return Command::SUCCESS;
        }

        // Group by status for reporting
        $stats = [
            'eligible' => 0,
            'ineligible' => 0,
            'pending' => 0,
            'error' => 0,
        ];

        foreach ($expiredChecks as $check) {
            $stats[$check->eligibility_status] = ($stats[$check->eligibility_status] ?? 0) + 1;
        }

        $this->table(
            ['Status', 'Count'],
            collect($stats)->map(function ($count, $status) {
                return [$status, $count];
            })->toArray()
        );

        if (!$dryRun && $this->confirm('Do you want to proceed with data retention processing?')) {
            $processed = 0;
            $errors = 0;

            foreach ($expiredChecks as $check) {
                try {
                    // Log the retention action
                    $check->logAuditEvent('retained', [
                        'retention_days' => $retentionDays,
                        'cutoff_date' => $cutoffDate->toISOString(),
                        'reason' => 'HIPAA data retention policy'
                    ]);

                    // Soft delete the record (secure deletion)
                    $check->delete();

                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Failed to process eligibility retention', [
                        'check_id' => $check->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->info("Processed: {$processed} records");
            if ($errors > 0) {
                $this->error("Errors: {$errors} records failed to process");
            }

            Log::info('Eligibility data retention completed', [
                'retention_days' => $retentionDays,
                'processed' => $processed,
                'errors' => $errors,
                'cutoff_date' => $cutoffDate->toISOString()
            ]);
        }

        return Command::SUCCESS;
    }
}
