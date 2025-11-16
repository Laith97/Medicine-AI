<?php

namespace App\Console\Commands;

use App\Services\Monitoring\AlertMonitoringService;
use App\Services\Monitoring\MetricsService;
use App\Services\Monitoring\StructuredLoggingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitorSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitor:system
                            {--check-alerts : Check alert rules and create alerts}
                            {--process-escalations : Process alert escalations}
                            {--auto-resolve : Auto-resolve alerts when conditions clear}
                            {--cleanup-logs : Clean up old log files}
                            {--all : Run all monitoring tasks}';

    /**
     * The console command description.
     */
    protected $description = 'Run system monitoring tasks including alerts, escalations, and cleanup';

    protected AlertMonitoringService $alertMonitoringService;
    protected MetricsService $metricsService;
    protected StructuredLoggingService $loggingService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        AlertMonitoringService $alertMonitoringService,
        MetricsService $metricsService,
        StructuredLoggingService $loggingService
    ) {
        parent::__construct();

        $this->alertMonitoringService = $alertMonitoringService;
        $this->metricsService = $metricsService;
        $this->loggingService = $loggingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        try {
            $this->info('Starting system monitoring...');

            $runAll = $this->option('all');
            $checkAlerts = $this->option('check-alerts') || $runAll;
            $processEscalations = $this->option('process-escalations') || $runAll;
            $autoResolve = $this->option('auto-resolve') || $runAll;
            $cleanupLogs = $this->option('cleanup-logs') || $runAll;

            if ($checkAlerts) {
                $this->checkAlertRules();
            }

            if ($processEscalations) {
                $this->processEscalations();
            }

            if ($autoResolve) {
                $this->autoResolveAlerts();
            }

            if ($cleanupLogs) {
                $this->cleanupLogs();
            }

            $this->collectSystemMetrics();

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->info("System monitoring completed in {$duration}ms");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('System monitoring failed: ' . $e->getMessage());

            $this->loggingService->logError($e, [
                'command' => 'monitor:system',
                'options' => $this->options(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Check alert rules and create alerts if needed
     */
    protected function checkAlertRules(): void
    {
        $this->info('Checking alert rules...');

        $this->alertMonitoringService->checkAllAlertRules();

        $this->info('Alert rules check completed.');
    }

    /**
     * Process alert escalations
     */
    protected function processEscalations(): void
    {
        $this->info('Processing alert escalations...');

        $this->alertMonitoringService->processAlertEscalations();

        $this->info('Alert escalations processing completed.');
    }

    /**
     * Auto-resolve alerts when conditions clear
     */
    protected function autoResolveAlerts(): void
    {
        $this->info('Auto-resolving alerts...');

        $this->alertMonitoringService->autoResolveAlerts();

        $this->info('Auto-resolve process completed.');
    }

    /**
     * Clean up old log files
     */
    protected function cleanupLogs(): void
    {
        $this->info('Cleaning up old log files...');

        $this->loggingService->cleanupOldLogs();

        $this->info('Log cleanup completed.');
    }

    /**
     * Collect and cache system metrics for monitoring
     */
    protected function collectSystemMetrics(): void
    {
        try {
            // Collect queue health stats
            $this->collectQueueHealthStats();

            // Collect security metrics
            $this->collectSecurityMetrics();

            // Collect performance metrics
            $this->collectPerformanceMetrics();

        } catch (\Exception $e) {
            $this->loggingService->logError($e, ['context' => 'system_metrics_collection']);
        }
    }

    /**
     * Collect queue health statistics
     */
    protected function collectQueueHealthStats(): void
    {
        try {
            $queueStats = [
                'total_jobs' => \DB::table('jobs')->count(),
                'failed_jobs' => \DB::table('failed_jobs')->where('failed_at', '>=', now()->subHours(1))->count(),
                'processing_time_avg' => 0, // Would need to be calculated from job processing logs
            ];

            $totalJobs = $queueStats['total_jobs'] + $queueStats['failed_jobs'];
            $queueStats['failure_rate'] = $totalJobs > 0 ? ($queueStats['failed_jobs'] / $totalJobs) : 0;

            Cache::put('queue_health_stats', $queueStats, now()->addMinutes(5));

        } catch (\Exception $e) {
            Log::warning('Failed to collect queue health stats', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Collect security metrics
     */
    protected function collectSecurityMetrics(): void
    {
        try {
            // Get failed login attempts from logs (simplified)
            $failedLogins = Cache::get('security_failed_logins', []);

            // Clean up old entries (older than 1 hour)
            $failedLogins = array_filter($failedLogins, function ($timestamp) {
                return $timestamp > now()->subHour()->timestamp;
            });

            Cache::put('security_failed_logins', $failedLogins, now()->addHours(2));

            // Count suspicious activities (would be populated by security middleware)
            $suspiciousActivities = Cache::get('security_suspicious_activities', 0);

            Cache::put('security_suspicious_activities', $suspiciousActivities, now()->addHours(2));

        } catch (\Exception $e) {
            Log::warning('Failed to collect security metrics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Collect performance metrics
     */
    protected function collectPerformanceMetrics(): void
    {
        try {
            $middlewareClass = \App\Http\Middleware\MetricsCollectionMiddleware::class;

            $performanceStats = [
                'response_time_p95' => $middlewareClass::getHistogramStats('http_request_duration_seconds')['p95'] ?? 0,
                'memory_usage_mb' => memory_get_peak_usage(true) / 1024 / 1024,
                'active_users' => $middlewareClass::getCounterValue('active_users_total'),
                'error_rate' => $this->calculateErrorRate(),
            ];

            Cache::put('performance_stats', $performanceStats, now()->addMinutes(5));

        } catch (\Exception $e) {
            Log::warning('Failed to collect performance metrics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate current error rate
     */
    protected function calculateErrorRate(): float
    {
        $middlewareClass = \App\Http\Middleware\MetricsCollectionMiddleware::class;

        $totalRequests = 0;
        $errorRequests = 0;

        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        $statuses = [200, 201, 400, 401, 403, 404, 422, 500];

        foreach ($methods as $method) {
            foreach ($statuses as $status) {
                $count = $middlewareClass::getCounterValue("http_requests_total{method=\"{$method}\",status=\"{$status}\"}");
                $totalRequests += $count;
                if ($status >= 400) {
                    $errorRequests += $count;
                }
            }
        }

        return $totalRequests > 0 ? ($errorRequests / $totalRequests) : 0;
    }
}
