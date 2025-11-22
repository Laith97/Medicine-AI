<?php

namespace App\Services;

use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WaitlistPerformanceMonitor
{
    /**
     * Monitor waitlist operation performance
     */
    public function recordOperation(string $operation, float $duration, array $metadata = []): void
    {
        // Store in cache for quick access
        $key = "waitlist_perf_{$operation}_" . date('Y-m-d-H');
        $current = Cache::get($key, [
            'count' => 0,
            'total_duration' => 0,
            'max_duration' => 0,
            'min_duration' => PHP_FLOAT_MAX,
        ]);

        $current['count']++;
        $current['total_duration'] += $duration;
        $current['max_duration'] = max($current['max_duration'], $duration);
        $current['min_duration'] = min($current['min_duration'], $duration);

        Cache::put($key, $current, now()->addHours(24));

        // Log slow operations
        if ($duration > 1000) { // More than 1 second
            Log::warning('Slow waitlist operation detected', [
                'operation' => $operation,
                'duration_ms' => $duration,
                'metadata' => $metadata,
            ]);
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(string $timeframe = '1h'): array
    {
        $metrics = [
            'operations' => $this->getOperationMetrics($timeframe),
            'database' => $this->getDatabaseMetrics(),
            'system' => $this->getSystemMetrics(),
            'alerts' => $this->getPerformanceAlerts(),
        ];

        return $metrics;
    }

    /**
     * Get operation-specific metrics
     */
    private function getOperationMetrics(string $timeframe): array
    {
        $hours = match($timeframe) {
            '1h' => 1,
            '6h' => 6,
            '24h' => 24,
            '7d' => 168,
            default => 1,
        };

        $operations = [
            'add_to_waitlist',
            'remove_from_waitlist',
            'offer_slot',
            'accept_offer',
            'decline_offer',
            'get_statistics',
            'find_available_slots',
        ];

        $metrics = [];
        foreach ($operations as $operation) {
            $key = "waitlist_perf_{$operation}_" . date('Y-m-d-H');
            $data = Cache::get($key, ['count' => 0, 'total_duration' => 0, 'max_duration' => 0, 'min_duration' => PHP_FLOAT_MAX]);

            if ($data['count'] > 0) {
                $metrics[$operation] = [
                    'count' => $data['count'],
                    'avg_duration' => round($data['total_duration'] / $data['count'], 2),
                    'max_duration' => round($data['max_duration'], 2),
                    'min_duration' => $data['min_duration'] < PHP_FLOAT_MAX ? round($data['min_duration'], 2) : 0,
                ];
            }
        }

        return $metrics;
    }

    /**
     * Get database performance metrics
     */
    private function getDatabaseMetrics(): array
    {
        $startTime = microtime(true);

        // Test basic waitlist queries
        $waitlistCount = Waitlist::count();
        $activeWaitlists = Waitlist::active()->count();
        $pendingOffers = WaitlistEntry::offered()->count();

        $queryTime = (microtime(true) - $startTime) * 1000;

        // Get slow query count (if available)
        $slowQueries = 0; // Would need database-specific monitoring

        return [
            'query_time_ms' => round($queryTime, 2),
            'total_waitlists' => $waitlistCount,
            'active_waitlists' => $activeWaitlists,
            'pending_offers' => $pendingOffers,
            'slow_queries' => $slowQueries,
        ];
    }

    /**
     * Get system resource metrics
     */
    private function getSystemMetrics(): array
    {
        return [
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
            ],
            'cache' => [
                'hits' => Cache::store()->getStore() instanceof \Illuminate\Cache\RedisStore ? 'N/A' : 'N/A', // Would need Redis metrics
                'misses' => 'N/A',
                'hit_rate' => 'N/A',
            ],
        ];
    }

    /**
     * Get performance alerts
     */
    private function getPerformanceAlerts(): array
    {
        $alerts = [];

        // Check for slow operations in the last hour
        $operations = ['add_to_waitlist', 'offer_slot', 'get_statistics'];
        foreach ($operations as $operation) {
            $key = "waitlist_perf_{$operation}_" . date('Y-m-d-H');
            $data = Cache::get($key, ['max_duration' => 0]);

            if ($data['max_duration'] > 2000) { // More than 2 seconds
                $alerts[] = [
                    'type' => 'slow_operation',
                    'operation' => $operation,
                    'max_duration_ms' => round($data['max_duration'], 2),
                    'severity' => 'high',
                ];
            }
        }

        // Check database performance
        $dbMetrics = $this->getDatabaseMetrics();
        if ($dbMetrics['query_time_ms'] > 500) {
            $alerts[] = [
                'type' => 'slow_database',
                'query_time_ms' => $dbMetrics['query_time_ms'],
                'severity' => 'medium',
            ];
        }

        return $alerts;
    }

    /**
     * Monitor waitlist health
     */
    public function getHealthStatus(): array
    {
        $issues = [];

        // Check database connectivity
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $issues[] = 'Database connection failed';
        }

        // Check cache connectivity
        try {
            Cache::store()->get('health_check');
        } catch (\Exception $e) {
            $issues[] = 'Cache connection failed';
        }

        // Check for stuck entries (offered but expired)
        $stuckEntries = WaitlistEntry::where('status', 'offered')
            ->where('response_deadline', '<', now())
            ->count();

        if ($stuckEntries > 0) {
            $issues[] = "{$stuckEntries} stuck waitlist entries need cleanup";
        }

        return [
            'status' => empty($issues) ? 'healthy' : 'degraded',
            'issues' => $issues,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Clean up old performance data
     */
    public function cleanupOldData(): void
    {
        // Remove performance data older than 7 days
        $cutoff = now()->subDays(7);
        $pattern = 'waitlist_perf_*';

        // This would need a cache driver that supports pattern deletion
        // For now, we'll just log that cleanup should be performed
        Log::info('Waitlist performance data cleanup recommended', [
            'cutoff_date' => $cutoff->format('Y-m-d'),
        ]);
    }
}
