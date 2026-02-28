<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\WaitlistCleanupJob;
use App\Jobs\WaitlistMonitoringJob;
use App\Services\WaitlistQueueMonitoringService;

class WaitlistMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waitlist:maintenance
                            {action : Action to perform (cleanup|monitor|health-check|all)}
                            {--type= : Cleanup type (expired_entries|old_waitlists|orphaned_data|all)}
                            {--age=30 : Maximum age in days for cleanup}
                            {--doctor= : Specific doctor ID for monitoring}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform waitlist maintenance operations including cleanup, monitoring, and health checks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'cleanup':
                $this->runCleanup();
                break;
            case 'monitor':
                $this->runMonitoring();
                break;
            case 'health-check':
                $this->runHealthCheck();
                break;
            case 'all':
                $this->runAll();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: cleanup, monitor, health-check, all");
                return 1;
        }

        return 0;
    }

    /**
     * Run cleanup operations
     */
    private function runCleanup(): void
    {
        $type = $this->option('type') ?? 'all';
        $age = (int) $this->option('age');

        $this->info("Starting waitlist cleanup: {$type} (max age: {$age} days)");

        // Dispatch cleanup job
        WaitlistCleanupJob::dispatch($type, $age);

        $this->info("Cleanup job dispatched to queue. Check logs for progress.");
    }

    /**
     * Run monitoring operations
     */
    private function runMonitoring(): void
    {
        $doctorId = $this->option('doctor') ? (int) $this->option('doctor') : null;

        $this->info($doctorId ? "Monitoring waitlist for doctor ID: {$doctorId}" : "Monitoring all waitlists");

        // Dispatch monitoring job
        WaitlistMonitoringJob::dispatch($doctorId);

        $this->info("Monitoring job dispatched to queue.");
    }

    /**
     * Run health check
     */
    private function runHealthCheck(): void
    {
        $this->info("Running waitlist system health check...");

        $monitoringService = app(WaitlistQueueMonitoringService::class);
        $metrics = $monitoringService->getQueueHealthMetrics();

        // Display queue status
        $this->info("\n=== Queue Status ===");
        $headers = ['Queue', 'Active', 'Jobs', 'Workers', 'Stalled', 'Last Processed'];
        $rows = [];

        foreach ($metrics['queue_status'] as $queue => $status) {
            $rows[] = [
                $queue,
                $status['active'] ? '✓' : '✗',
                $metrics['job_counts'][$queue] ?? 0,
                $status['workers'],
                $status['stalled_jobs'],
                $status['last_processed'] ? $status['last_processed']->diffForHumans() : 'Never'
            ];
        }

        $this->table($headers, $rows);

        // Display failure rates
        $this->info("\n=== Failure Rates ===");
        $headers = ['Queue', 'Failure Rate (%)'];
        $rows = [];

        foreach ($metrics['failure_rates'] as $queue => $rate) {
            $rows[] = [$queue, number_format($rate, 2)];
        }

        $this->table($headers, $rows);

        // Display backlog
        $this->info("\n=== Waitlist Backlog ===");
        $backlog = $metrics['waitlist_backlog'];
        $this->info("Active Waitlists: {$backlog['total_active_waitlists']}");
        $this->info("Urgent Priority: {$backlog['urgent_waitlists']}");
        $this->info("High Priority: {$backlog['high_waitlists']}");
        $this->info("Pending Offers: {$backlog['pending_offers']}");
        $this->info("Expired Offers: {$backlog['expired_offers']}");
        $this->info("Average Wait Time: {$backlog['average_wait_time_days']} days");

        // Display performance indicators
        $this->info("\n=== Performance Indicators ===");
        $indicators = $metrics['performance_indicators'];
        $this->info("Slot Fulfillment Rate: " . number_format($indicators['slot_fulfillment_rate'], 2) . "%");
        $this->info("Patient Satisfaction: " . number_format($indicators['patient_satisfaction_score'] * 100, 1) . "%");
        $this->info("System Health Score: " . number_format($indicators['system_health_score'], 1) . "/100");

        // Generate and display alerts
        $alerts = $monitoringService->generateHealthAlerts();
        if (!empty($alerts)) {
            $this->info("\n=== Active Alerts ===");
            foreach ($alerts as $alert) {
                $severityColor = match($alert['severity']) {
                    'critical' => 'red',
                    'warning' => 'yellow',
                    'info' => 'blue',
                    default => 'white'
                };

                $this->line("<{$severityColor}>{$alert['severity']}: {$alert['title']}</{$severityColor}>");
                $this->line("  {$alert['description']}");
            }
        } else {
            $this->info("\n=== No Active Alerts ===");
        }

        $this->info("\nHealth check completed.");
    }

    /**
     * Run all maintenance operations
     */
    private function runAll(): void
    {
        $this->info("Running all waitlist maintenance operations...");

        // Run cleanup
        $this->runCleanup();

        // Run monitoring
        $this->runMonitoring();

        // Run health check
        $this->runHealthCheck();

        $this->info("\nAll maintenance operations completed.");
    }
}
