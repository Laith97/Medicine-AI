<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class RealtimePerformanceMonitoringService
{
    protected const CACHE_KEY_METRICS = 'realtime:performance:metrics';
    protected const CACHE_KEY_ANALYTICS = 'realtime:performance:analytics';
    protected const CACHE_KEY_ALERTS = 'realtime:performance:alerts';
    protected const METRICS_TTL = 3600; // 1 hour
    protected const ANALYTICS_TTL = 86400; // 24 hours

    // Performance thresholds
    protected const BROADCAST_LATENCY_THRESHOLD = 1000; // ms
    protected const CONNECTION_POOL_UTILIZATION_THRESHOLD = 0.8; // 80%
    protected const COMPRESSION_RATIO_THRESHOLD = 0.7; // 70% size reduction
    protected const ERROR_RATE_THRESHOLD = 0.05; // 5%

    protected array $metrics = [
        'broadcasts' => [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'compressed' => 0,
            'avg_latency' => 0,
            'avg_compression_ratio' => 0,
        ],
        'connections' => [
            'active' => 0,
            'pool_utilization' => 0,
            'created' => 0,
            'reused' => 0,
        ],
        'cache' => [
            'hits' => 0,
            'misses' => 0,
            'hit_rate' => 0,
        ],
        'errors' => [
            'total' => 0,
            'by_type' => [],
            'error_rate' => 0,
        ]
    ];

    /**
     * Record broadcast performance metrics
     */
    public function recordBroadcastMetrics(array $data): void
    {
        $metrics = Cache::get(self::CACHE_KEY_METRICS, $this->metrics);

        $metrics['broadcasts']['total']++;

        if (isset($data['success']) && $data['success']) {
            $metrics['broadcasts']['successful']++;
        } else {
            $metrics['broadcasts']['failed']++;
        }

        if (isset($data['compressed']) && $data['compressed']) {
            $metrics['broadcasts']['compressed']++;
        }

        if (isset($data['latency'])) {
            $currentAvg = $metrics['broadcasts']['avg_latency'];
            $count = $metrics['broadcasts']['total'];
            $metrics['broadcasts']['avg_latency'] = (($currentAvg * ($count - 1)) + $data['latency']) / $count;
        }

        if (isset($data['compression_ratio'])) {
            $currentAvg = $metrics['broadcasts']['avg_compression_ratio'];
            $compressedCount = $metrics['broadcasts']['compressed'];
            if ($compressedCount > 0) {
                $metrics['broadcasts']['avg_compression_ratio'] =
                    (($currentAvg * ($compressedCount - 1)) + $data['compression_ratio']) / $compressedCount;
            }
        }

        // Update error rate
        $total = $metrics['broadcasts']['total'];
        $failed = $metrics['broadcasts']['failed'];
        $metrics['broadcasts']['error_rate'] = $total > 0 ? $failed / $total : 0;

        Cache::put(self::CACHE_KEY_METRICS, $metrics, self::METRICS_TTL);

        $this->checkThresholds($metrics);
    }

    /**
     * Record connection pool metrics
     */
    public function recordConnectionMetrics(array $data): void
    {
        $metrics = Cache::get(self::CACHE_KEY_METRICS, $this->metrics);

        if (isset($data['active_connections'])) {
            $metrics['connections']['active'] = $data['active_connections'];
        }

        if (isset($data['pool_utilization'])) {
            $metrics['connections']['pool_utilization'] = $data['pool_utilization'];
        }

        if (isset($data['created'])) {
            $metrics['connections']['created'] += $data['created'];
        }

        if (isset($data['reused'])) {
            $metrics['connections']['reused'] += $data['reused'];
        }

        Cache::put(self::CACHE_KEY_METRICS, $metrics, self::METRICS_TTL);
    }

    /**
     * Record cache performance metrics
     */
    public function recordCacheMetrics(bool $hit): void
    {
        $metrics = Cache::get(self::CACHE_KEY_METRICS, $this->metrics);

        if ($hit) {
            $metrics['cache']['hits']++;
        } else {
            $metrics['cache']['misses']++;
        }

        $total = $metrics['cache']['hits'] + $metrics['cache']['misses'];
        $metrics['cache']['hit_rate'] = $total > 0 ? $metrics['cache']['hits'] / $total : 0;

        Cache::put(self::CACHE_KEY_METRICS, $metrics, self::METRICS_TTL);
    }

    /**
     * Record error metrics
     */
    public function recordError(string $type, string $message, array $context = []): void
    {
        $metrics = Cache::get(self::CACHE_KEY_METRICS, $this->metrics);

        $metrics['errors']['total']++;

        if (!isset($metrics['errors']['by_type'][$type])) {
            $metrics['errors']['by_type'][$type] = 0;
        }
        $metrics['errors']['by_type'][$type]++;

        $totalBroadcasts = $metrics['broadcasts']['total'];
        $metrics['errors']['error_rate'] = $totalBroadcasts > 0 ? $metrics['errors']['total'] / $totalBroadcasts : 0;

        Cache::put(self::CACHE_KEY_METRICS, $metrics, self::METRICS_TTL);

        Log::warning('Realtime performance error recorded', [
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'error_rate' => $metrics['errors']['error_rate']
        ]);

        $this->checkThresholds($metrics);
    }

    /**
     * Check performance thresholds and create alerts
     */
    protected function checkThresholds(array $metrics): void
    {
        $alerts = Cache::get(self::CACHE_KEY_ALERTS, []);

        // Check broadcast latency
        if ($metrics['broadcasts']['avg_latency'] > self::BROADCAST_LATENCY_THRESHOLD) {
            $alerts[] = [
                'type' => 'high_latency',
                'message' => 'Broadcast latency exceeds threshold',
                'value' => $metrics['broadcasts']['avg_latency'],
                'threshold' => self::BROADCAST_LATENCY_THRESHOLD,
                'timestamp' => now()
            ];
        }

        // Check connection pool utilization
        if ($metrics['connections']['pool_utilization'] > self::CONNECTION_POOL_UTILIZATION_THRESHOLD) {
            $alerts[] = [
                'type' => 'high_connection_utilization',
                'message' => 'Connection pool utilization is high',
                'value' => $metrics['connections']['pool_utilization'],
                'threshold' => self::CONNECTION_POOL_UTILIZATION_THRESHOLD,
                'timestamp' => now()
            ];
        }

        // Check error rate
        if ($metrics['broadcasts']['error_rate'] > self::ERROR_RATE_THRESHOLD) {
            $alerts[] = [
                'type' => 'high_error_rate',
                'message' => 'Broadcast error rate exceeds threshold',
                'value' => $metrics['broadcasts']['error_rate'],
                'threshold' => self::ERROR_RATE_THRESHOLD,
                'timestamp' => now()
            ];
        }

        // Keep only recent alerts (last 100)
        if (count($alerts) > 100) {
            $alerts = array_slice($alerts, -100);
        }

        Cache::put(self::CACHE_KEY_ALERTS, $alerts, self::METRICS_TTL);
    }

    /**
     * Get current performance metrics
     */
    public function getMetrics(): array
    {
        $metrics = Cache::get(self::CACHE_KEY_METRICS, $this->metrics);
        $alerts = Cache::get(self::CACHE_KEY_ALERTS, []);

        return [
            'metrics' => $metrics,
            'alerts' => array_slice($alerts, -10), // Last 10 alerts
            'timestamp' => now(),
            'uptime' => $this->getUptime()
        ];
    }

    /**
     * Get performance analytics over time
     */
    public function getAnalytics(int $hours = 24): array
    {
        $analytics = Cache::get(self::CACHE_KEY_ANALYTICS, []);
        $startTime = now()->subHours($hours);

        // Filter analytics for the specified time period
        $filteredAnalytics = array_filter($analytics, function ($entry) use ($startTime) {
            return isset($entry['timestamp']) && Carbon::parse($entry['timestamp'])->gte($startTime);
        });

        return [
            'period_hours' => $hours,
            'data_points' => count($filteredAnalytics),
            'analytics' => array_values($filteredAnalytics),
            'summary' => $this->summarizeAnalytics($filteredAnalytics)
        ];
    }

    /**
     * Summarize analytics data
     */
    protected function summarizeAnalytics(array $analytics): array
    {
        if (empty($analytics)) {
            return [];
        }

        $summary = [
            'avg_broadcast_latency' => 0,
            'avg_compression_ratio' => 0,
            'total_broadcasts' => 0,
            'total_errors' => 0,
            'peak_connections' => 0,
            'cache_hit_rate' => 0
        ];

        $latencySum = 0;
        $compressionSum = 0;
        $compressionCount = 0;
        $cacheHits = 0;
        $cacheTotal = 0;

        foreach ($analytics as $entry) {
            if (isset($entry['broadcast_latency'])) {
                $latencySum += $entry['broadcast_latency'];
                $summary['total_broadcasts']++;
            }

            if (isset($entry['compression_ratio'])) {
                $compressionSum += $entry['compression_ratio'];
                $compressionCount++;
            }

            if (isset($entry['error'])) {
                $summary['total_errors']++;
            }

            if (isset($entry['active_connections']) &&
                $entry['active_connections'] > $summary['peak_connections']) {
                $summary['peak_connections'] = $entry['active_connections'];
            }

            if (isset($entry['cache_hit'])) {
                $cacheTotal++;
                if ($entry['cache_hit']) {
                    $cacheHits++;
                }
            }
        }

        if ($summary['total_broadcasts'] > 0) {
            $summary['avg_broadcast_latency'] = $latencySum / $summary['total_broadcasts'];
        }

        if ($compressionCount > 0) {
            $summary['avg_compression_ratio'] = $compressionSum / $compressionCount;
        }

        if ($cacheTotal > 0) {
            $summary['cache_hit_rate'] = $cacheHits / $cacheTotal;
        }

        return $summary;
    }

    /**
     * Store analytics snapshot
     */
    public function storeAnalyticsSnapshot(): void
    {
        $analytics = Cache::get(self::CACHE_KEY_ANALYTICS, []);
        $metrics = $this->getMetrics();

        $snapshot = [
            'timestamp' => now(),
            'broadcast_latency' => $metrics['metrics']['broadcasts']['avg_latency'],
            'compression_ratio' => $metrics['metrics']['broadcasts']['avg_compression_ratio'],
            'active_connections' => $metrics['metrics']['connections']['active'],
            'error_rate' => $metrics['metrics']['broadcasts']['error_rate'],
            'cache_hit_rate' => $metrics['metrics']['cache']['hit_rate']
        ];

        $analytics[] = $snapshot;

        // Keep only last 1000 snapshots
        if (count($analytics) > 1000) {
            $analytics = array_slice($analytics, -1000);
        }

        Cache::put(self::CACHE_KEY_ANALYTICS, $analytics, self::ANALYTICS_TTL);
    }

    /**
     * Get system uptime (simplified)
     */
    protected function getUptime(): int
    {
        // In a real implementation, this would track actual service uptime
        return Cache::get('realtime_service_start_time', time()) - time();
    }

    /**
     * Clear all performance data
     */
    public function clearMetrics(): void
    {
        Cache::forget(self::CACHE_KEY_METRICS);
        Cache::forget(self::CACHE_KEY_ANALYTICS);
        Cache::forget(self::CACHE_KEY_ALERTS);

        Log::info('Realtime performance metrics cleared');
    }

    /**
     * Get performance health status
     */
    public function getHealthStatus(): array
    {
        $metrics = $this->getMetrics();

        $status = 'healthy';
        $issues = [];

        if ($metrics['metrics']['broadcasts']['avg_latency'] > self::BROADCAST_LATENCY_THRESHOLD) {
            $status = 'degraded';
            $issues[] = 'High broadcast latency';
        }

        if ($metrics['metrics']['connections']['pool_utilization'] > self::CONNECTION_POOL_UTILIZATION_THRESHOLD) {
            $status = 'warning';
            $issues[] = 'High connection pool utilization';
        }

        if ($metrics['metrics']['broadcasts']['error_rate'] > self::ERROR_RATE_THRESHOLD) {
            $status = 'critical';
            $issues[] = 'High error rate';
        }

        return [
            'status' => $status,
            'issues' => $issues,
            'metrics' => $metrics,
            'last_checked' => now()
        ];
    }
}
