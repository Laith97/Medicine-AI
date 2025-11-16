<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HEPDataRetentionService;
use App\Models\AuditLog;

class ProcessHEPDataRetention extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hep:process-data-retention {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process HIPAA-compliant data retention for HEP records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting HEP data retention processing...');

        $retentionService = new HEPDataRetentionService();
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            if (!$isDryRun) {
                $results = $retentionService->processDataRetention();
            } else {
                // For dry run, just get statistics
                $results = $retentionService->getRetentionStatistics();
                $results['dry_run'] = true;
            }

            $this->displayResults($results);

            // Log the retention processing
            if (!$isDryRun) {
                AuditLog::log('data_retention', null, null, null, [
                    'action_type' => 'hep_data_retention',
                    'action' => 'retention_processing_completed',
                    'results' => $results,
                    'processed_at' => now(),
                ]);
            }

            $this->info('HEP data retention processing completed successfully.');

        } catch (\Exception $e) {
            $this->error('HEP data retention processing failed: ' . $e->getMessage());

            // Log the error
            AuditLog::log('data_retention', null, null, null, [
                'action_type' => 'hep_data_retention',
                'action' => 'retention_processing_failed',
                'error' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            return 1;
        }

        return 0;
    }

    /**
     * Display the processing results
     */
    private function displayResults(array $results): void
    {
        $this->info('=== HEP Data Retention Results ===');

        if (isset($results['dry_run']) && $results['dry_run']) {
            $this->warn('DRY RUN - These are the records that would be processed:');
        }

        $this->line('Retention Periods:');
        foreach ($results['retention_periods'] ?? [] as $type => $years) {
            $this->line("  {$type}: {$years} years");
        }

        $this->line('');
        $this->line('Records Processed:');

        if (isset($results['processed_assignments'])) {
            $this->line("  Assignments: {$results['processed_assignments']}");
        }

        if (isset($results['processed_progress'])) {
            $this->line("  Progress Records: {$results['processed_progress']}");
        }

        if (isset($results['processed_audit_logs'])) {
            $this->line("  Audit Logs: {$results['processed_audit_logs']}");
        }

        if (isset($results['archived_records'])) {
            $this->line("  Archived Records: {$results['archived_records']}");
        }

        if (isset($results['assignments_pending_deletion'])) {
            $this->line("  Assignments Pending Deletion: {$results['assignments_pending_deletion']}");
        }

        if (isset($results['progress_pending_deletion'])) {
            $this->line("  Progress Records Pending Deletion: {$results['progress_pending_deletion']}");
        }

        if (isset($results['audit_logs_pending_deletion'])) {
            $this->line("  Audit Logs Pending Deletion: {$results['audit_logs_pending_deletion']}");
        }

        if (isset($results['last_retention_run'])) {
            $lastRun = $results['last_retention_run'];
            if ($lastRun) {
                $this->line("  Last Retention Run: {$lastRun->format('Y-m-d H:i:s')}");
            } else {
                $this->line("  Last Retention Run: Never");
            }
        }

        if (!empty($results['errors'])) {
            $this->error('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }
    }
}
