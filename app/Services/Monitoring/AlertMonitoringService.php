<?php

namespace App\Services\Monitoring;

use App\Models\Alert;
use App\Models\AlertRule;
use App\Services\AlertNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AlertMonitoringService
{
    protected MetricsService $metricsService;
    protected AlertNotificationService $notificationService;

    public function __construct(
        MetricsService $metricsService,
        AlertNotificationService $notificationService
    ) {
        $this->metricsService = $metricsService;
        $this->notificationService = $notificationService;
    }

    /**
     * Check all active alert rules and create alerts if conditions are met
     */
    public function checkAllAlertRules(): void
    {
        $activeRules = AlertRule::active()->get();

        foreach ($activeRules as $rule) {
            try {
                $this->checkAlertRule($rule);
            } catch (\Exception $e) {
                Log::error('Failed to check alert rule', [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check a specific alert rule
     */
    public function checkAlertRule(AlertRule $rule): void
    {
        // Check if rule is in cooldown
        if ($rule->isInCooldown()) {
            return;
        }

        // Get monitoring data based on rule type
        $monitoringData = $this->getMonitoringDataForRule($rule);

        if (empty($monitoringData)) {
            return;
        }

        // Check if conditions match
        if (!$rule->matchesConditions($monitoringData)) {
            return;
        }

        // Determine severity
        $severity = $rule->getSeverityLevel($monitoringData);

        // Check if similar alert already exists and is active
        $existingAlert = $this->findExistingAlert($rule, $monitoringData);
        if ($existingAlert) {
            // Update existing alert if severity increased
            if ($this->shouldEscalateAlert($existingAlert, $severity)) {
                $existingAlert->escalate();
                $this->notificationService->sendAlertNotifications($existingAlert, 'escalation');
            }
            return;
        }

        // Create new alert
        $this->createAlert($rule, $monitoringData, $severity);
    }

    /**
     * Get monitoring data for a specific rule
     */
    protected function getMonitoringDataForRule(AlertRule $rule): array
    {
        return match ($rule->event_type) {
            'system_health' => $this->getSystemHealthData(),
            'performance' => $this->getPerformanceData(),
            'queue_health' => $this->getQueueHealthData(),
            'resource_usage' => $this->getResourceUsageData(),
            'external_service' => $this->getExternalServiceData(),
            'security' => $this->getSecurityData(),
            default => []
        };
    }

    /**
     * Get system health monitoring data
     */
    protected function getSystemHealthData(): array
    {
        $health = $this->metricsService->healthCheck();

        return [
            'database_status' => $health['checks']['database']['status'] ?? 'unknown',
            'cache_status' => $health['checks']['cache']['status'] ?? 'unknown',
            'redis_status' => $health['checks']['redis']['status'] ?? 'unknown',
            'queue_status' => $health['checks']['queue']['status'] ?? 'unknown',
            'storage_status' => $health['checks']['storage']['status'] ?? 'unknown',
            'overall_status' => $health['status'],
        ];
    }

    /**
     * Get performance monitoring data
     */
    protected function getPerformanceData(): array
    {
        // Get data from middleware
        $middlewareClass = \App\Http\Middleware\MetricsCollectionMiddleware::class;

        $responseTimeStats = $middlewareClass::getHistogramStats('http_request_duration_seconds');

        return [
            'response_time_p50' => $responseTimeStats['p50'] ?? 0,
            'response_time_p95' => $responseTimeStats['p95'] ?? 0,
            'response_time_p99' => $responseTimeStats['p99'] ?? 0,
            'error_rate' => $this->calculateErrorRate(),
            'active_users' => $middlewareClass::getCounterValue('active_users_total'),
        ];
    }

    /**
     * Get queue health monitoring data
     */
    protected function getQueueHealthData(): array
    {
        try {
            $queueStats = Cache::get('queue_health_stats', []);

            return [
                'total_jobs' => $queueStats['total_jobs'] ?? 0,
                'failed_jobs' => $queueStats['failed_jobs'] ?? 0,
                'failure_rate' => $queueStats['failure_rate'] ?? 0,
                'processing_time_avg' => $queueStats['processing_time_avg'] ?? 0,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get resource usage monitoring data
     */
    protected function getResourceUsageData(): array
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
        $maxMemory = ini_get('memory_limit');

        if ($maxMemory !== '-1') {
            $maxMemoryBytes = $this->convertToBytes($maxMemory);
            $memoryUsagePercent = ($memoryUsage / ($maxMemoryBytes / 1024 / 1024)) * 100;
        } else {
            $memoryUsagePercent = 0;
        }

        $diskFree = disk_free_space(storage_path());
        $diskTotal = disk_total_space(storage_path());
        $diskUsagePercent = $diskTotal > 0 ? (($diskTotal - $diskFree) / $diskTotal) * 100 : 0;

        return [
            'memory_usage_mb' => round($memoryUsage, 2),
            'memory_usage_percent' => round($memoryUsagePercent, 2),
            'disk_usage_percent' => round($diskUsagePercent, 2),
            'cpu_usage' => sys_getloadavg()[0] ?? 0,
        ];
    }

    /**
     * Get external service monitoring data
     */
    protected function getExternalServiceData(): array
    {
        $health = $this->metricsService->healthCheck();

        $services = [];
        if (isset($health['checks']['external_services']['services'])) {
            foreach ($health['checks']['external_services']['services'] as $serviceName => $serviceData) {
                $services["{$serviceName}_status"] = $serviceData['status'];
            }
        }

        return $services;
    }

    /**
     * Get security monitoring data
     */
    protected function getSecurityData(): array
    {
        // Get security metrics from cache
        $failedLogins = Cache::get('security_failed_logins', []);
        $suspiciousActivities = Cache::get('security_suspicious_activities', 0);

        return [
            'failed_login_attempts' => count($failedLogins),
            'suspicious_activities' => $suspiciousActivities,
        ];
    }

    /**
     * Calculate current error rate
     */
    protected function calculateErrorRate(): float
    {
        $middlewareClass = \App\Http\Middleware\MetricsCollectionMiddleware::class;

        $totalRequests = 0;
        $errorRequests = 0;

        // Sum up all request counters
        $counters = ['http_requests_total{method="GET",status="200"}', 'http_requests_total{method="GET",status="500"}'];
        foreach ($counters as $counter) {
            $count = $middlewareClass::getCounterValue($counter);
            $totalRequests += $count;
            if (strpos($counter, 'status="500"') !== false) {
                $errorRequests += $count;
            }
        }

        return $totalRequests > 0 ? ($errorRequests / $totalRequests) : 0;
    }

    /**
     * Find existing active alert for the same rule and conditions
     */
    protected function findExistingAlert(AlertRule $rule, array $data): ?Alert
    {
        return Alert::active()
            ->where('alert_rule_id', $rule->id)
            ->where('event_type', $rule->event_type)
            ->first();
    }

    /**
     * Check if alert should be escalated
     */
    protected function shouldEscalateAlert(Alert $alert, string $newSeverity): bool
    {
        $severityLevels = ['info' => 1, 'low' => 2, 'medium' => 3, 'high' => 4, 'critical' => 5];
        $currentLevel = $severityLevels[$alert->severity] ?? 0;
        $newLevel = $severityLevels[$newSeverity] ?? 0;

        return $newLevel > $currentLevel;
    }

    /**
     * Create a new alert
     */
    protected function createAlert(AlertRule $rule, array $data, string $severity): Alert
    {
        $alert = Alert::create([
            'alert_rule_id' => $rule->id,
            'title' => $rule->name,
            'message' => $this->generateAlertMessage($rule, $data),
            'severity' => $severity,
            'status' => 'active',
            'event_type' => $rule->event_type,
            'event_data' => $data,
            'context_data' => [
                'rule_conditions' => $rule->conditions,
                'monitoring_timestamp' => now()->toISOString(),
            ],
            'priority_score' => $this->calculatePriorityScore($rule, $severity),
        ]);

        // Send initial notifications
        $this->notificationService->sendAlertNotifications($alert, 'initial');

        Log::info('Alert created', [
            'alert_id' => $alert->id,
            'rule_name' => $rule->name,
            'severity' => $severity,
        ]);

        return $alert;
    }

    /**
     * Generate alert message based on rule and data
     */
    protected function generateAlertMessage(AlertRule $rule, array $data): string
    {
        $messages = [
            'system_health' => 'System health check failed',
            'performance' => 'Performance threshold exceeded',
            'queue_health' => 'Queue processing issues detected',
            'resource_usage' => 'Resource usage is high',
            'external_service' => 'External service is down',
            'security' => 'Security incident detected',
        ];

        $baseMessage = $messages[$rule->event_type] ?? 'Alert condition met';

        // Add specific details based on data
        $details = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $details[] = "{$key}: {$value}";
            }
        }

        return $baseMessage . ' - ' . implode(', ', $details);
    }

    /**
     * Calculate priority score for alert
     */
    protected function calculatePriorityScore(AlertRule $rule, string $severity): float
    {
        $severityScores = ['info' => 1, 'low' => 2, 'medium' => 3, 'high' => 4, 'critical' => 5];
        $severityScore = $severityScores[$severity] ?? 3;

        return ($rule->priority / 100) * $severityScore;
    }

    /**
     * Process alert escalations
     */
    public function processAlertEscalations(): void
    {
        $alertsToEscalate = Alert::requiringEscalation()->get();

        foreach ($alertsToEscalate as $alert) {
            try {
                $alert->escalate();
                $this->notificationService->sendAlertNotifications($alert, 'escalation');

                Log::info('Alert escalated', [
                    'alert_id' => $alert->id,
                    'new_level' => $alert->escalation_level,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to escalate alert', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Auto-resolve alerts based on rules
     */
    public function autoResolveAlerts(): void
    {
        $activeAlerts = Alert::active()->with('alertRule')->get();

        foreach ($activeAlerts as $alert) {
            try {
                if ($this->shouldAutoResolveAlert($alert)) {
                    $alert->resolve(null, 'Auto-resolved by monitoring system');
                    $this->notificationService->sendAlertNotifications($alert, 'resolved');

                    Log::info('Alert auto-resolved', [
                        'alert_id' => $alert->id,
                        'reason' => 'conditions no longer met',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to auto-resolve alert', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if alert should be auto-resolved
     */
    protected function shouldAutoResolveAlert(Alert $alert): bool
    {
        $rule = $alert->alertRule;

        // Check if rule has auto-resolve metadata
        if (!isset($rule->metadata['auto_resolve']) || !$rule->metadata['auto_resolve']) {
            return false;
        }

        // Get current monitoring data
        $currentData = $this->getMonitoringDataForRule($rule);

        // Check if conditions are no longer met
        return !$rule->matchesConditions($currentData);
    }

    /**
     * Convert size string to bytes
     */
    protected function convertToBytes(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        switch ($unit) {
            case 'g': return $value * 1024 * 1024 * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'k': return $value * 1024;
            default: return $value;
        }
    }
}
