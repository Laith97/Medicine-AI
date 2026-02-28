<?php

namespace App\Console\Commands;

use App\Models\ClearinghouseAccount;
use App\Models\ClearinghouseSubmission;
use App\Services\ClaimSubmissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MonitorClearinghouseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clearinghouse:monitor
                            {--alerts : Send alert notifications}
                            {--detailed : Show detailed monitoring information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor clearinghouse operations and send alerts for issues';

    protected ClaimSubmissionService $submissionService;

    // Monitoring thresholds
    protected const CRITICAL_SUBMISSION_AGE_HOURS = 24;
    protected const WARNING_SUBMISSION_AGE_HOURS = 6;
    protected const MAX_RETRY_COUNT = 3;
    protected const MIN_SUCCESS_RATE_PERCENTAGE = 95.0;

    public function __construct(ClaimSubmissionService $submissionService)
    {
        parent::__construct();
        $this->submissionService = $submissionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting clearinghouse monitoring...');

        $issues = [];

        // Monitor submission statuses
        $issues = array_merge($issues, $this->monitorSubmissionStatuses());

        // Monitor account health
        $issues = array_merge($issues, $this->monitorAccountHealth());

        // Monitor system performance
        $issues = array_merge($issues, $this->monitorSystemPerformance());

        // Monitor queue health
        $issues = array_merge($issues, $this->monitorQueueHealth());

        // Report findings
        $this->reportMonitoringResults($issues);

        // Send alerts if requested
        if ($this->option('alerts') && !empty($issues)) {
            $this->sendAlerts($issues);
        }

        $this->info('Clearinghouse monitoring completed.');
    }

    /**
     * Monitor submission statuses for issues
     */
    protected function monitorSubmissionStatuses(): array
    {
        $issues = [];

        // Check for stuck submissions
        $stuckSubmissions = ClearinghouseSubmission::whereIn('status', ['pending', 'submitted'])
            ->where('created_at', '<', now()->subHours(self::CRITICAL_SUBMISSION_AGE_HOURS))
            ->get();

        if ($stuckSubmissions->isNotEmpty()) {
            $issues[] = [
                'type' => 'critical',
                'category' => 'submissions',
                'message' => "Found {$stuckSubmissions->count()} submissions stuck for more than " . self::CRITICAL_SUBMISSION_AGE_HOURS . " hours",
                'data' => $stuckSubmissions->pluck('batch_id')->toArray()
            ];
        }

        // Check for submissions with excessive retries
        $excessiveRetries = ClearinghouseSubmission::where('metadata->retry_count', '>', self::MAX_RETRY_COUNT)
            ->where('status', '!=', 'failed')
            ->get();

        if ($excessiveRetries->isNotEmpty()) {
            $issues[] = [
                'type' => 'warning',
                'category' => 'submissions',
                'message' => "Found {$excessiveRetries->count()} submissions with excessive retry attempts",
                'data' => $excessiveRetries->pluck('batch_id')->toArray()
            ];
        }

        // Check success rate over last 24 hours
        $recentSubmissions = ClearinghouseSubmission::where('created_at', '>=', now()->subDay())->get();
        if ($recentSubmissions->isNotEmpty()) {
            $successfulSubmissions = $recentSubmissions->whereIn('status', ['submitted', 'accepted', 'completed'])->count();
            $successRate = ($successfulSubmissions / $recentSubmissions->count()) * 100;

            if ($successRate < self::MIN_SUCCESS_RATE_PERCENTAGE) {
                $issues[] = [
                    'type' => 'warning',
                    'category' => 'performance',
                    'message' => "Submission success rate is {$successRate}% (below " . self::MIN_SUCCESS_RATE_PERCENTAGE . "% threshold)",
                    'data' => ['success_rate' => $successRate, 'total_submissions' => $recentSubmissions->count()]
                ];
            }
        }

        // Check for submissions needing status updates
        $pendingStatusChecks = $this->submissionService->getPendingStatusChecks();
        if ($pendingStatusChecks->count() > 50) { // Arbitrary threshold
            $issues[] = [
                'type' => 'info',
                'category' => 'maintenance',
                'message' => "{$pendingStatusChecks->count()} submissions are due for status checks",
                'data' => []
            ];
        }

        return $issues;
    }

    /**
     * Monitor clearinghouse account health
     */
    protected function monitorAccountHealth(): array
    {
        $issues = [];

        $accounts = ClearinghouseAccount::all();

        foreach ($accounts as $account) {
            // Check for authentication failures
            $authFailures = Cache::get("clearinghouse_auth_failures_{$account->id}", 0);
            if ($authFailures > 5) { // More than 5 auth failures in monitoring period
                $issues[] = [
                    'type' => 'critical',
                    'category' => 'accounts',
                    'message' => "Account {$account->name} has {$authFailures} recent authentication failures",
                    'data' => ['account_id' => $account->id, 'provider' => $account->provider]
                ];
            }

            // Check account usage patterns
            $recentSubmissions = ClearinghouseSubmission::where('clearinghouse_account_id', $account->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            if ($recentSubmissions > 1000) { // High volume account
                $issues[] = [
                    'type' => 'info',
                    'category' => 'usage',
                    'message' => "Account {$account->name} has high submission volume: {$recentSubmissions} submissions in 24 hours",
                    'data' => ['account_id' => $account->id, 'submissions_24h' => $recentSubmissions]
                ];
            }

            // Check for account-specific error patterns
            $accountErrors = ClearinghouseSubmission::where('clearinghouse_account_id', $account->id)
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subWeek())
                ->count();

            $accountTotal = ClearinghouseSubmission::where('clearinghouse_account_id', $account->id)
                ->where('created_at', '>=', now()->subWeek())
                ->count();

            if ($accountTotal > 0) {
                $errorRate = ($accountErrors / $accountTotal) * 100;
                if ($errorRate > 20) { // More than 20% error rate
                    $issues[] = [
                        'type' => 'warning',
                        'category' => 'accounts',
                        'message' => "Account {$account->name} has high error rate: {$errorRate}%",
                        'data' => ['account_id' => $account->id, 'error_rate' => $errorRate]
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Monitor system performance metrics
     */
    protected function monitorSystemPerformance(): array
    {
        $issues = [];

        // Check memory usage
        $memoryUsage = $this->getMemoryUsage();
        if ($memoryUsage > 80) { // Memory usage above 80%
            $issues[] = [
                'type' => 'warning',
                'category' => 'system',
                'message' => "High memory usage detected: {$memoryUsage}%",
                'data' => ['memory_usage' => $memoryUsage]
            ];
        }

        // Check disk space
        $diskUsage = $this->getDiskUsage();
        if ($diskUsage > 90) { // Disk usage above 90%
            $issues[] = [
                'type' => 'critical',
                'category' => 'system',
                'message' => "Critical disk usage: {$diskUsage}%",
                'data' => ['disk_usage' => $diskUsage]
            ];
        }

        // Check database connection pool
        $dbConnections = $this->getDatabaseConnections();
        if ($dbConnections > 80) { // Using more than 80% of available connections
            $issues[] = [
                'type' => 'warning',
                'category' => 'database',
                'message' => "High database connection usage: {$dbConnections}%",
                'data' => ['db_connections' => $dbConnections]
            ];
        }

        // Check for slow queries or performance issues
        $slowQueries = $this->checkForSlowQueries();
        if (!empty($slowQueries)) {
            $issues[] = [
                'type' => 'warning',
                'category' => 'database',
                'message' => "Detected slow database queries",
                'data' => ['slow_queries' => $slowQueries]
            ];
        }

        return $issues;
    }

    /**
     * Monitor queue health
     */
    protected function monitorQueueHealth(): array
    {
        $issues = [];

        // Check queue size
        $queueSize = $this->getQueueSize();
        if ($queueSize > 100) { // More than 100 jobs in queue
            $issues[] = [
                'type' => 'warning',
                'category' => 'queue',
                'message' => "Large queue backlog detected: {$queueSize} jobs",
                'data' => ['queue_size' => $queueSize]
            ];
        }

        // Check for failed jobs
        $failedJobs = $this->getFailedJobsCount();
        if ($failedJobs > 10) { // More than 10 failed jobs
            $issues[] = [
                'type' => 'critical',
                'category' => 'queue',
                'message' => "High number of failed jobs: {$failedJobs}",
                'data' => ['failed_jobs' => $failedJobs]
            ];
        }

        // Check queue worker status
        if (!$this->areQueueWorkersRunning()) {
            $issues[] = [
                'type' => 'critical',
                'category' => 'queue',
                'message' => "Queue workers are not running",
                'data' => []
            ];
        }

        return $issues;
    }

    /**
     * Report monitoring results
     */
    protected function reportMonitoringResults(array $issues): void
    {
        if (empty($issues)) {
            $this->info('✅ All systems operational - no issues detected');
            return;
        }

        $criticalCount = count(array_filter($issues, fn($issue) => $issue['type'] === 'critical'));
        $warningCount = count(array_filter($issues, fn($issue) => $issue['type'] === 'warning'));
        $infoCount = count(array_filter($issues, fn($issue) => $issue['type'] === 'info'));

        $this->warn("⚠️  Monitoring completed with issues:");
        $this->warn("   Critical: {$criticalCount}");
        $this->warn("   Warnings: {$warningCount}");
        $this->warn("   Info: {$infoCount}");

        if ($this->option('detailed')) {
            foreach ($issues as $issue) {
                $symbol = match($issue['type']) {
                    'critical' => '🔴',
                    'warning' => '🟡',
                    'info' => 'ℹ️'
                };

                $this->line("{$symbol} [{$issue['category']}] {$issue['message']}");

                if (!empty($issue['data'])) {
                    $this->line("   Data: " . json_encode($issue['data']));
                }
            }
        }
    }

    /**
     * Send alerts for issues
     */
    protected function sendAlerts(array $issues): void
    {
        $criticalIssues = array_filter($issues, fn($issue) => $issue['type'] === 'critical');
        $warningIssues = array_filter($issues, fn($issue) => $issue['type'] === 'warning');

        if (!empty($criticalIssues)) {
            $this->sendCriticalAlert($criticalIssues);
        }

        if (!empty($warningIssues)) {
            $this->sendWarningAlert($warningIssues);
        }

        $this->info('Alerts sent for detected issues');
    }

    /**
     * Send critical alert
     */
    protected function sendCriticalAlert(array $issues): void
    {
        Log::critical('CRITICAL: Clearinghouse monitoring detected critical issues', [
            'issues' => $issues,
            'timestamp' => now()
        ]);

        // Send email alert
        // Mail::to(config('clearinghouse.alert_emails'))->send(new CriticalAlertNotification($issues));

        // Send Slack notification if configured
        // $this->sendSlackAlert('critical', $issues);

        $this->error('🚨 CRITICAL ALERTS SENT - Immediate attention required!');
    }

    /**
     * Send warning alert
     */
    protected function sendWarningAlert(array $issues): void
    {
        Log::warning('WARNING: Clearinghouse monitoring detected warning issues', [
            'issues' => $issues,
            'timestamp' => now()
        ]);

        // Send email alert for warnings
        // Mail::to(config('clearinghouse.alert_emails'))->send(new WarningAlertNotification($issues));

        $this->warn('⚠️ WARNING ALERTS SENT');
    }

    // Helper methods for system monitoring
    protected function getMemoryUsage(): float
    {
        // Get PHP memory usage percentage
        $memoryLimit = ini_get('memory_limit');
        $memoryUsed = memory_get_peak_usage(true);

        if ($memoryLimit === '-1') {
            return 0; // Unlimited memory
        }

        $limitBytes = $this->convertToBytes($memoryLimit);
        return ($memoryUsed / $limitBytes) * 100;
    }

    protected function getDiskUsage(): float
    {
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');

        if ($diskTotal === false || $diskFree === false) {
            return 0;
        }

        $diskUsed = $diskTotal - $diskFree;
        return ($diskUsed / $diskTotal) * 100;
    }

    protected function getDatabaseConnections(): int
    {
        // This would need to be implemented based on your database setup
        // For MySQL, you could check SHOW PROCESSLIST
        return 0; // Placeholder
    }

    protected function checkForSlowQueries(): array
    {
        // Check for slow queries in the last monitoring period
        // This would need to be implemented based on your logging setup
        return []; // Placeholder
    }

    protected function getQueueSize(): int
    {
        // Get the number of jobs in the queue
        // This depends on your queue driver (database, redis, etc.)
        return 0; // Placeholder
    }

    protected function getFailedJobsCount(): int
    {
        // Get the number of failed jobs
        return 0; // Placeholder
    }

    protected function areQueueWorkersRunning(): bool
    {
        // Check if queue workers are running
        exec('pgrep -f "artisan queue:work"', $output);
        return !empty($output);
    }

    protected function convertToBytes(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        return match($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value
        };
    }
}
