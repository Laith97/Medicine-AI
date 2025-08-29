<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class NotificationPerformanceMonitor
{
    private const METRICS_CACHE_KEY = 'notification_performance_metrics';
    private const METRICS_TTL = 3600; // 1 hour

    private array $metrics = [
        'requests' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'compression_savings' => 0,
        'memory_usage' => [],
        'response_times' => [],
        'errors' => [],
        'broadcast_success' => 0,
        'broadcast_failures' => 0,
    ];

    /**
     * Record a notification request
     */
    public function recordRequest(string $type = 'api', array $metadata = []): void
    {
        $this->metrics['requests']++;

        Log::info('Notification request recorded', [
            'type' => $type,
            'total_requests' => $this->metrics['requests'],
            'metadata' => $metadata
        ]);
    }

    /**
     * Record cache performance
     */
    public function recordCacheHit(): void
    {
        $this->metrics['cache_hits']++;
    }

    /**
     * Record cache miss
     */
    public function recordCacheMiss(): void
    {
        $this->metrics['cache_misses']++;
    }

    /**
     * Record compression savings
     */
    public function recordCompressionSaving(int $originalSize, int $compressedSize): void
    {
        $saving = $originalSize - $compressedSize;
        $this->metrics['compression_savings'] += $saving;

        $ratio = $originalSize > 0 ? round(($saving / $originalSize) * 100, 2) : 0;

        Log::info('Compression saving recorded', [
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'saving_bytes' => $saving,
            'compression_ratio' => $ratio . '%',
            'total_savings' => $this->metrics['compression_savings']
        ]);
    }

    /**
     * Record memory usage
     */
    public function recordMemoryUsage(): void
    {
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        $this->metrics['memory_usage'][] = [
            'current' => $usage,
            'peak' => $peak,
            'timestamp' => now()->toISOString()
        ];

        // Keep only last 100 memory readings
        if (count($this->metrics['memory_usage']) > 100) {
            array_shift($this->metrics['memory_usage']);
        }

        Log::debug('Memory usage recorded', [
            'current_mb' => round($usage / 1024 / 1024, 2),
            'peak_mb' => round($peak / 1024 / 1024, 2)
        ]);
    }

    /**
     * Record response time
     */
    public function recordResponseTime(float $startTime, string $endpoint = 'unknown'): void
    {
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        $this->metrics['response_times'][] = [
            'time_ms' => $responseTime,
            'endpoint' => $endpoint,
            'timestamp' => now()->toISOString()
        ];

        // Keep only last 100 response times
        if (count($this->metrics['response_times']) > 100) {
            array_shift($this->metrics['response_times']);
        }

        Log::info('Response time recorded', [
            'endpoint' => $endpoint,
            'response_time_ms' => round($responseTime, 2)
        ]);
    }

    /**
     * Record broadcast success
     */
    public function recordBroadcastSuccess(string $event = 'unknown'): void
    {
        $this->metrics['broadcast_success']++;

        Log::info('Broadcast success recorded', [
            'event' => $event,
            'total_success' => $this->metrics['broadcast_success']
        ]);
    }

    /**
     * Record broadcast failure
     */
    public function recordBroadcastFailure(string $event = 'unknown', string $error = ''): void
    {
        $this->metrics['broadcast_failures']++;

        $this->metrics['errors'][] = [
            'type' => 'broadcast_failure',
            'event' => $event,
            'error' => $error,
            'timestamp' => now()->toISOString()
        ];

        // Keep only last 50 errors
        if (count($this->metrics['errors']) > 50) {
            array_shift($this->metrics['errors']);
        }

        Log::warning('Broadcast failure recorded', [
            'event' => $event,
            'error' => $error,
            'total_failures' => $this->metrics['broadcast_failures']
        ]);
    }

    /**
     * Record general error
     */
    public function recordError(string $type, string $message, array $context = []): void
    {
        $this->metrics['errors'][] = [
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toISOString()
        ];

        // Keep only last 50 errors
        if (count($this->metrics['errors']) > 50) {
            array_shift($this->metrics['errors']);
        }

        Log::error('Notification error recorded', [
            'type' => $type,
            'message' => $message,
            'context' => $context
        ]);
    }

    /**
     * Get performance metrics
     */
    public function getMetrics(): array
    {
        $this->loadPersistedMetrics();

        $metrics = $this->metrics;

        // Calculate derived metrics
        $totalCacheRequests = $metrics['cache_hits'] + $metrics['cache_misses'];
        $cacheHitRate = $totalCacheRequests > 0 ? round(($metrics['cache_hits'] / $totalCacheRequests) * 100, 2) : 0;

        $totalBroadcasts = $metrics['broadcast_success'] + $metrics['broadcast_failures'];
        $broadcastSuccessRate = $totalBroadcasts > 0 ? round(($metrics['broadcast_success'] / $totalBroadcasts) * 100, 2) : 0;

        // Calculate average response time
        $avgResponseTime = 0;
        if (!empty($metrics['response_times'])) {
            $totalTime = array_sum(array_column($metrics['response_times'], 'time_ms'));
            $avgResponseTime = round($totalTime / count($metrics['response_times']), 2);
        }

        // Calculate memory usage statistics
        $memoryStats = $this->calculateMemoryStats();

        return [
            'summary' => [
                'total_requests' => $metrics['requests'],
                'cache_hit_rate' => $cacheHitRate . '%',
                'broadcast_success_rate' => $broadcastSuccessRate . '%',
                'average_response_time_ms' => $avgResponseTime,
                'total_compression_savings_bytes' => $metrics['compression_savings'],
                'total_errors' => count($metrics['errors']),
            ],
            'cache' => [
                'hits' => $metrics['cache_hits'],
                'misses' => $metrics['cache_misses'],
                'hit_rate' => $cacheHitRate . '%',
            ],
            'broadcast' => [
                'success' => $metrics['broadcast_success'],
                'failures' => $metrics['broadcast_failures'],
                'success_rate' => $broadcastSuccessRate . '%',
            ],
            'performance' => [
                'response_times' => $metrics['response_times'],
                'average_response_time_ms' => $avgResponseTime,
                'memory_stats' => $memoryStats,
            ],
            'compression' => [
                'total_savings_bytes' => $metrics['compression_savings'],
                'savings_mb' => round($metrics['compression_savings'] / 1024 / 1024, 2),
            ],
            'errors' => $metrics['errors'],
            'last_updated' => now()->toISOString(),
        ];
    }

    /**
     * Calculate memory usage statistics
     */
    private function calculateMemoryStats(): array
    {
        if (empty($this->metrics['memory_usage'])) {
            return [
                'current_avg_mb' => 0,
                'peak_avg_mb' => 0,
                'current_max_mb' => 0,
                'peak_max_mb' => 0,
            ];
        }

        $currentUsages = array_column($this->metrics['memory_usage'], 'current');
        $peakUsages = array_column($this->metrics['memory_usage'], 'peak');

        return [
            'current_avg_mb' => round(array_sum($currentUsages) / count($currentUsages) / 1024 / 1024, 2),
            'peak_avg_mb' => round(array_sum($peakUsages) / count($peakUsages) / 1024 / 1024, 2),
            'current_max_mb' => round(max($currentUsages) / 1024 / 1024, 2),
            'peak_max_mb' => round(max($peakUsages) / 1024 / 1024, 2),
        ];
    }

    /**
     * Persist metrics to cache
     */
    public function persistMetrics(): void
    {
        Cache::put(self::METRICS_CACHE_KEY, $this->metrics, self::METRICS_TTL);

        Log::info('Performance metrics persisted to cache');
    }

    /**
     * Load persisted metrics from cache
     */
    private function loadPersistedMetrics(): void
    {
        $persisted = Cache::get(self::METRICS_CACHE_KEY, []);

        if (!empty($persisted)) {
            // Merge persisted metrics with current metrics
            foreach ($persisted as $key => $value) {
                if (isset($this->metrics[$key])) {
                    if (is_array($value) && is_array($this->metrics[$key])) {
                        $this->metrics[$key] = array_merge($value, $this->metrics[$key]);
                    } elseif (is_numeric($value)) {
                        $this->metrics[$key] += $value;
                    }
                }
            }
        }
    }

    /**
     * Reset all metrics
     */
    public function resetMetrics(): void
    {
        $this->metrics = [
            'requests' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'compression_savings' => 0,
            'memory_usage' => [],
            'response_times' => [],
            'errors' => [],
            'broadcast_success' => 0,
            'broadcast_failures' => 0,
        ];

        Cache::forget(self::METRICS_CACHE_KEY);

        Log::info('Performance metrics reset');
    }

    /**
     * Get health status based on metrics
     */
    public function getHealthStatus(): array
    {
        $metrics = $this->getMetrics();

        $issues = [];

        // Check cache hit rate
        $cacheHitRate = (float) str_replace('%', '', $metrics['summary']['cache_hit_rate']);
        if ($cacheHitRate < 50) {
            $issues[] = 'Low cache hit rate: ' . $cacheHitRate . '%';
        }

        // Check broadcast success rate
        $broadcastSuccessRate = (float) str_replace('%', '', $metrics['summary']['broadcast_success_rate']);
        if ($broadcastSuccessRate < 90) {
            $issues[] = 'Low broadcast success rate: ' . $broadcastSuccessRate . '%';
        }

        // Check average response time
        if ($metrics['summary']['average_response_time_ms'] > 1000) {
            $issues[] = 'High average response time: ' . $metrics['summary']['average_response_time_ms'] . 'ms';
        }

        // Check error count
        if ($metrics['summary']['total_errors'] > 10) {
            $issues[] = 'High error count: ' . $metrics['summary']['total_errors'];
        }

        $status = empty($issues) ? 'healthy' : (count($issues) > 2 ? 'critical' : 'warning');

        return [
            'status' => $status,
            'issues' => $issues,
            'metrics' => $metrics['summary'],
        ];
    }
}
