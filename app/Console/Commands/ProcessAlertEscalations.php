<?php

namespace App\Console\Commands;

use App\Services\AdvancedAlertService;
use Illuminate\Console\Command;

class ProcessAlertEscalations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:process-escalations {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process alert escalations and send notifications for overdue alerts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $alertService = app(AdvancedAlertService::class);
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode - no changes will be made');
        }

        $this->info('Processing alert escalations...');

        try {
            // Process escalations
            $alertService->processEscalation();

            // Get escalation statistics
            $stats = $alertService->getAlertStatistics();

            $this->info('Alert escalation processing completed successfully');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Alerts', $stats['total']],
                    ['Active Alerts', $stats['by_status']['active']],
                    ['Acknowledged Alerts', $stats['by_status']['acknowledged']],
                    ['Resolved Alerts', $stats['by_status']['resolved']],
                    ['Escalated Alerts', $stats['by_status']['escalated']],
                    ['Overdue for Escalation', $stats['overdue_for_escalation']],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to process alert escalations: ' . $e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
