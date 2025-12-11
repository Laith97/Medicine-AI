<?php

namespace App\Console\Commands;

use App\Services\ComplianceAuditTrailService;
use Illuminate\Console\Command;

class ComplianceDataRetentionCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compliance:data-retention-cleanup
                            {--dry-run : Perform a dry run without actually deleting data}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired compliance data according to HIPAA retention requirements';

    protected ComplianceAuditTrailService $auditService;

    public function __construct(ComplianceAuditTrailService $auditService)
    {
        parent::__construct();
        $this->auditService = $auditService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Starting compliance data retention cleanup...');
        $this->info($dryRun ? '🔍 DRY RUN MODE - No data will be deleted' : '⚠️  LIVE MODE - Data will be permanently deleted');

        if (!$force && !$dryRun) {
            if (!$this->confirm('This will permanently delete expired compliance data. Are you sure?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->newLine();
        $results = $this->auditService->performDataRetentionCleanup($dryRun);

        $this->displayResults($results, $dryRun);

        // Log the cleanup operation
        $this->auditService->logComplianceAuditEvent('data_retention_cleanup', [
            'dry_run' => $dryRun,
            'processed' => $results['processed'],
            'deleted' => $results['deleted'],
            'errors' => $results['errors'],
        ]);

        if ($results['errors'] > 0) {
            $this->error("⚠️  {$results['errors']} errors occurred during cleanup. Check logs for details.");
            return Command::FAILURE;
        }

        $this->info('✅ Compliance data retention cleanup completed successfully.');
        return Command::SUCCESS;
    }

    /**
     * Display the cleanup results.
     */
    protected function displayResults(array $results, bool $dryRun): void
    {
        $this->newLine();
        $this->line('📊 Cleanup Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Records Processed', $results['processed']],
                ['Records ' . ($dryRun ? 'Would Be ' : '') . 'Deleted', $results['deleted']],
                ['Errors', $results['errors']],
            ]
        );

        if (!empty($results['details'])) {
            $this->newLine();
            $this->line('📋 Details:');

            $tableData = array_map(function ($detail) {
                return [
                    $detail['application_id'],
                    $detail['rule_name'] ?? 'Unknown',
                    $detail['data_classification'] ?? 'Unknown',
                    $detail['retention_until'] ?? 'N/A',
                    $detail['action'],
                ];
            }, array_slice($results['details'], 0, 10)); // Show first 10

            $this->table(
                ['Application ID', 'Rule Name', 'Classification', 'Retention Until', 'Action'],
                $tableData
            );

            if (count($results['details']) > 10) {
                $remaining = count($results['details']) - 10;
                $this->info("... and {$remaining} more records.");
            }
        }
    }
}
