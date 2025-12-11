<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class RealtimePerformanceMonitor
{
    protected string $cachePrefix = 'realtime_metrics';
    protected int $retentionHours = 24;

    /**
     * Record KPI update performance metrics
     */
    public function recordKPIUpdateMetrics(string $kpiName, int $hospitalKey, array $metrics): void
    {
        $key = "{$this->cachePrefix}:kpi_updates:{$hospitalKey}:{$kpiName}";

        $metricData = [
            'timestamp' => now(),
            'kpi_name' => $kpiName,
            'hospital_key' => $hospitalKey,
            'calculation_time_ms' => $metrics['calculation_time_ms'] ?? null,
            'cache_time_ms' => $metrics['cache_time_ms'] ?? null,
            'broadcast_time_ms' => $metrics['broadcast_time_ms'] ?? null,
            'total_time_ms' => $metrics['total_time_ms'] ?? null,
            'cache_hit' => $metrics['cache_hit'] ?? false,
            'broadcast_success' => $metrics['broadcast_success'] ?? true,
            'error' => $metrics['error'] ?? null,
        ];

        // Store in time-series cache
        $this->storeTimeSeriesMetric($key, $metricData);

        // Update aggregate metrics
        $this->updateAggregateMetrics('kpi_updates', $hospitalKey, $metricData);
    }

    /**
     * Record WebSocket broadcast metrics
     */
    public function recordBroadcastMetrics(array $channels, string $event, array $metrics): void
    {
        $key = "{$this->cachePrefix}:broadcasts";

        $metricData = [
            'timestamp' => now(),
            'channels_count' => count($channels),
            'event' => $event,
            'data_size_bytes' => $metrics['data_size_bytes'] ?? null,
            'broadcast_time_ms' => $metrics['broadcast_time_ms'] ?? null,
            'success' => $metrics['success'] ?? true,
            'error' => $metrics['error'] ?? null,
            'active_connections' => $metrics['active_connections'] ?? null,
        ];

        $this->storeTimeSeriesMetric($key, $metricData);
        $this->updateAggregateMetrics('broadcasts', null, $metricData);
    }

    /**
     * Record alert processing metrics
     */
    public function recordAlertMetrics(array $alertData, int $hospitalKey, array $metrics): void
    {
        $key = "{$this->cachePrefix}:alerts:{$hospitalKey}";

        $metricData = [
            'timestamp' => now(),
            'alert_id' => $alertData['alert_id'] ?? null,
            'kpi_name' => $alertData['kpi_name'] ?? null,
            'alert_level' => $alertData['alert_level'] ?? null,
            'processing_time_ms' => $metrics['processing_time_ms'] ?? null,
            'notification_time_ms' => $metrics['notification_time_ms'] ?? null,
            'recipients_count' => $metrics['recipients_count'] ?? null,
            'success' => $metrics['success'] ?? true,
            'error' => $metrics['error'] ?? null,
        ];

        $this->storeTimeSeriesMetric($key, $metricData);
        $this->updateAggregateMetrics('alerts', $hospitalKey, $metricData);
    }

    /**
     * Record cache performance metrics
     */
    public function recordCacheMetrics(string $operation, string $key, array $metrics): void
    {
        $cacheKey = "{$this->cachePrefix}:cache_performance";

        $metricData = [
            'timestamp' => now(),
            'operation' => $operation,
            'cache_key' => $key,
            'hit' => $metrics['hit'] ?? false,
            'response_time_ms' => $metrics['response_time_ms'] ?? null,
            'data_size_bytes' => $metrics['data_size_bytes'] ?? null,
            'ttl_seconds' => $metrics['ttl_seconds'] ?? null,
        ];

        $this->storeTimeSeriesMetric($cacheKey, $metricData);
        $this->updateAggregateMetrics('cache', null, $metricData);
    }

    /**
     * Get performance dashboard data
     */
    public function getPerformanceDashboard(int $hours = 24): array
    {
        $startTime = now()->subHours($hours);

        return [
            'summary' => $this->getPerformanceSummary($startTime),
            'kpi_updates' => $this->getKPIPerformanceMetrics($startTime),
            'broadcasts' => $this->getBroadcastPerformanceMetrics($startTime),
            'alerts' => $this->getAlertPerformanceMetrics($startTime),
            'cache' => $this->getCachePerformanceMetrics($startTime),
            'system_health' => $this->getSystemHealthMetrics(),
            'time_range' => [
                'start' => $startTime,
                'end' => now(),
                'hours' => $hours
            ]
        ];
    }

    /**
     * Get performance summary
     */
    protected function getPerformanceSummary(Carbon $startTime): array
    {
        $summary = [
            'total_kpi_updates' => 0,
            'total_broadcasts' => 0,
            'total_alerts' => 0,
            'avg_kpi_update_time' => 0,
            'avg_broadcast_time' => 0,
            'avg_alert_time' => 0,
            'cache_hit_rate' => 0,
            'error_rate' => 0,
        ];

        // Aggregate from stored metrics
        $metrics = Cache::get('realtime_aggregate_metrics', []);

        if (!empty($metrics)) {
            $summary = array_merge($summary, $metrics);
        }

        return $summary;
    }

    /**
     * Get KPI performance metrics
     */
    protected function getKPIPerformanceMetrics(Carbon $startTime): array
    {
        $metrics = $this->getTimeSeriesMetrics("{$this->cachePrefix}:kpi_updates:*", $startTime);

        return [
            'total_updates' => count($metrics),
            'avg_calculation_time' => $this->calculateAverage($metrics, 'calculation_time_ms'),
            'avg_cache_time' => $this->calculateAverage($metrics, 'cache_time_ms'),
            'avg_broadcast_time' => $this->calculateAverage($metrics, 'broadcast_time_ms'),
            'cache_hit_rate' => $this->calculatePercentage($metrics, 'cache_hit'),
            'error_rate' => $this->calculatePercentage($metrics, 'error', true),
            'updates_by_kpi' => $this->groupByField($metrics, 'kpi_name'),
            'updates_by_hour' => $this->groupByHour($metrics),
        ];
    }

    /**
     * Get broadcast performance metrics
     */
    protected function getBroadcastPerformanceMetrics(Carbon $startTime): array
    {
        $metrics = $this->getTimeSeriesMetrics("{$this->cachePrefix}:broadcasts", $startTime);

        return [
            'total_broadcasts' => count($metrics),
            'avg_broadcast_time' => $this->calculateAverage($metrics, 'broadcast_time_ms'),
            'avg_data_size' => $this->calculateAverage($metrics, 'data_size_bytes'),
            'success_rate' => $this->calculatePercentage($metrics, 'success'),
            'broadcasts_by_event' => $this->groupByField($metrics, 'event'),
            'broadcasts_by_hour' => $this->groupByHour($metrics),
        ];
    }

    /**
     * Get alert performance metrics
     */
    protected function getAlertPerformanceMetrics(Carbon $startTime): array
    {
        $metrics = $this->getTimeSeriesMetrics("{$this->cachePrefix}:alerts:*", $startTime);

        return [
            'total_alerts' => count($metrics),
            'avg_processing_time' => $this->calculateAverage($metrics, 'processing_time_ms'),
            'avg_notification_time' => $this->calculateAverage($metrics, 'notification_time_ms'),
            'success_rate' => $this->calculatePercentage($metrics, 'success'),
            'alerts_by_level' => $this->groupByField($metrics, 'alert_level'),
            'alerts_by_kpi' => $this->groupByField($metrics, 'kpi_name'),
            'alerts_by_hour' => $this->groupByHour($metrics),
        ];
    }

    /**
     * Get cache performance metrics
     */
    protected function getCachePerformanceMetrics(Carbon $startTime): array
    {
        $metrics = $this->getTimeSeriesMetrics("{$this->cachePrefix}:cache_performance", $startTime);

        return [
            'total_operations' => count($metrics),
            'avg_response_time' => $this->calculateAverage($metrics, 'response_time_ms'),
            'hit_rate' => $this->calculatePercentage($metrics, 'hit'),
            'operations_by_type' => $this->groupByField($metrics, 'operation'),
            'operations_by_hour' => $this->groupByHour($metrics),
        ];
    }

    /**
     * Get system health metrics
     */
    protected function getSystemHealthMetrics(): array
    {
        $streamingService = app(RealtimeStreamingService::class);
        $cacheService = app(KPICacheService::class);

        return [
            'streaming_service' => $streamingService->healthCheck(),
            'cache_service' => [
                'healthy' => $cacheService->isHealthy(),
                'stats' => $cacheService->getCacheStats()
            ],
            'queue_health' => $this->checkQueueHealth(),
            'memory_usage' => $this->getMemoryUsage(),
            'timestamp' => now()
        ];
    }

    /**
     * Store time-series metric
     */
    protected function storeTimeSeriesMetric(string $key, array $data): void
    {
        $existing = Cache::get($key, []);
        $existing[] = $data;

        // Keep only recent metrics (last 24 hours)
        $cutoff = now()->subHours($this->retentionHours);
        $existing = array_filter($existing, function ($metric) use ($cutoff) {
            return Carbon::parse($metric['timestamp'])->greaterThan($cutoff);
        });

        // Limit to last 1000 entries per key to prevent memory issues
        if (count($existing) > 1000) {
            $existing = array_slice($existing, -1000);
        }

        Cache::put($key, array_values($existing), $this->retentionHours * 3600);
    }

    /**
     * Update aggregate metrics
     */
    protected function updateAggregateMetrics(string $type, ?int $hospitalKey, array $data): void
    {
        $key = 'realtime_aggregate_metrics';
        $aggregates = Cache::get($key, []);

        $hospitalSuffix = $hospitalKey ? "_{$hospitalKey}" : '';

        if (!isset($aggregates[$type . $hospitalSuffix])) {
            $aggregates[$type . $hospitalSuffix] = [
                'count' => 0,
                'avg_times' => [],
                'success_count' => 0,
                'error_count' => 0,
            ];
        }

        $agg = &$aggregates[$type . $hospitalSuffix];
        $agg['count']++;

        // Track timing metrics
        $timeFields = ['calculation_time_ms', 'cache_time_ms', 'broadcast_time_ms', 'processing_time_ms', 'response_time_ms'];
        foreach ($timeFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                $agg['avg_times'][$field][] = $data[$field];
                // Keep only last 100 measurements
                if (count($agg['avg_times'][$field]) > 100) {
                    array_shift($agg['avg_times'][$field]);
                }
            }
        }

        // Track success/error rates
        if (isset($data['success'])) {
            if ($data['success']) {
                $agg['success_count']++;
            } else {
                $agg['error_count']++;
            }
        }

        Cache::put($key, $aggregates, $this->retentionHours * 3600);
    }

    /**
     * Get time-series metrics from cache
     */
    protected function getTimeSeriesMetrics(string $pattern, Carbon $startTime): array
    {
        // This is a simplified version - in production you'd need Redis SCAN or similar
        // For now, we'll get individual keys that match the pattern
        $metrics = [];

        try {
            if (Cache::store()->getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = \Illuminate\Support\Facades\Redis::connection();
                $keys = $redis->keys($pattern);

                foreach ($keys as $key) {
                    $data = Cache::get($key, []);
                    foreach ($data as $metric) {
                        if (Carbon::parse($metric['timestamp'])->greaterThanOrEqualTo($startTime)) {
                            $metrics[] = $metric;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error retrieving time-series metrics', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }

        return $metrics;
    }

    /**
     * Calculate average of field in metrics array
     */
    protected function calculateAverage(array $metrics, string $field): float
    {
        $values = array_filter(array_column($metrics, $field), function ($value) {
            return $value !== null;
        });

        return empty($values) ? 0 : array_sum($values) / count($values);
    }

    /**
     * Calculate percentage of true values for boolean field
     */
    protected function calculatePercentage(array $metrics, string $field, bool $invert = false): float
    {
        if (empty($metrics)) return 0;

        $trueCount = count(array_filter($metrics, function ($metric) use ($field, $invert) {
            $value = $metric[$field] ?? false;
            return $invert ? !$value : $value;
        }));

        return ($trueCount / count($metrics)) * 100;
    }

    /**
     * Group metrics by field value
     */
    protected function groupByField(array $metrics, string $field): array
    {
        $grouped = [];
        foreach ($metrics as $metric) {
            $key = $metric[$field] ?? 'unknown';
            if (!isset($grouped[$key])) {
                $grouped[$key] = 0;
            }
            $grouped[$key]++;
        }
        return $grouped;
    }

    /**
     * Group metrics by hour
     */
    protected function groupByHour(array $metrics): array
    {
        $grouped = [];
        foreach ($metrics as $metric) {
            $hour = Carbon::parse($metric['timestamp'])->format('Y-m-d H:00');
            if (!isset($grouped[$hour])) {
                $grouped[$hour] = 0;
            }
            $grouped[$hour]++;
        }
        ksort($grouped);
        return $grouped;
    }

    /**
     * Check queue health
     */
    protected function checkQueueHealth(): array
    {
        try {
            // Check if queues are processing
            $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
            $pendingJobs = \Illuminate\Support\Facades\DB::table('jobs')->count();

            return [
                'failed_jobs' => $failedJobs,
                'pending_jobs' => $pendingJobs,
                'healthy' => $failedJobs < 10 && $pendingJobs < 1000, // Arbitrary thresholds
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'healthy' => false
            ];
        }
    }

    /**
     * Get memory usage
     */
    protected function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Clear old metrics
     */
    public function cleanupOldMetrics(int $daysOld = 7): int
    {
        $cutoff = now()->subDays($daysOld);
        $cleaned = 0;

        try {
            if (Cache::store()->getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = \Illuminate\Support\Facades\Redis::connection();
                $keys = $redis->keys("{$this->cachePrefix}:*");

                foreach ($keys as $key) {
                    $data = Cache::get($key, []);
                    $originalCount = count($data);

                    $data = array_filter($data, function ($metric) use ($cutoff) {
                        return Carbon::parse($metric['timestamp'])->greaterThan($cutoff);
                    });

                    if (count($data) !== $originalCount) {
                        Cache::put($key, array_values($data), $this->retentionHours * 3600);
                        $cleaned += ($originalCount - count($data));
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error cleaning up old metrics', [
                'error' => $e->getMessage()
            ]);
        }

        return $cleaned;
    }
}
