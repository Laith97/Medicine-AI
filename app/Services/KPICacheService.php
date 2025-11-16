<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class KPICacheService
{
    protected int $defaultTtl = 3600; // 1 hour
    protected int $realtimeTtl = 300; // 5 minutes for real-time data
    protected int $historicalTtl = 86400; // 24 hours for historical data

    /**
     * Cache KPI data with appropriate TTL based on data type
     */
    public function cacheKPI(string $kpiName, array $data, int $hospitalKey = 1): bool
    {
        $cacheKey = $this->getKPICacheKey($kpiName, $hospitalKey);

        $cacheData = [
            'value' => $data['value'] ?? null,
            'change' => $data['change'] ?? null,
            'change_percentage' => $data['change_percentage'] ?? null,
            'trend' => $data['trend'] ?? null,
            'timestamp' => $data['timestamp'] ?? now()->toISOString(),
            'period' => $data['period'] ?? 'latest',
            'cached_at' => now(),
            'expires_at' => now()->addSeconds($this->getCacheTtl($data['period'] ?? 'latest'))
        ];

        return Cache::put($cacheKey, $cacheData, $this->getCacheTtl($data['period'] ?? 'latest'));
    }

    /**
     * Get cached KPI data
     */
    public function getCachedKPI(string $kpiName, int $hospitalKey = 1): ?array
    {
        $cacheKey = $this->getKPICacheKey($kpiName, $hospitalKey);
        return Cache::get($cacheKey);
    }

    /**
     * Cache multiple KPIs at once
     */
    public function cacheMultipleKPIs(array $kpiData, int $hospitalKey = 1): array
    {
        $results = [];

        foreach ($kpiData as $kpiName => $data) {
            $results[$kpiName] = $this->cacheKPI($kpiName, $data, $hospitalKey);
        }

        return $results;
    }

    /**
     * Get multiple cached KPIs
     */
    public function getMultipleCachedKPIs(array $kpiNames, int $hospitalKey = 1): array
    {
        $results = [];

        foreach ($kpiNames as $kpiName) {
            $results[$kpiName] = $this->getCachedKPI($kpiName, $hospitalKey);
        }

        return array_filter($results); // Remove null values
    }

    /**
     * Cache dashboard data
     */
    public function cacheDashboard(string $dashboardId, array $data, int $hospitalKey = 1): bool
    {
        $cacheKey = $this->getDashboardCacheKey($dashboardId, $hospitalKey);

        $cacheData = [
            'dashboard_id' => $dashboardId,
            'data' => $data,
            'hospital_key' => $hospitalKey,
            'cached_at' => now(),
            'expires_at' => now()->addSeconds($this->realtimeTtl)
        ];

        return Cache::put($cacheKey, $cacheData, $this->realtimeTtl);
    }

    /**
     * Get cached dashboard data
     */
    public function getCachedDashboard(string $dashboardId, int $hospitalKey = 1): ?array
    {
        $cacheKey = $this->getDashboardCacheKey($dashboardId, $hospitalKey);
        return Cache::get($cacheKey);
    }

    /**
     * Cache KPI time series data
     */
    public function cacheKPITimeSeries(string $kpiName, array $timeSeriesData, string $period = 'daily', int $hospitalKey = 1): bool
    {
        $cacheKey = $this->getTimeSeriesCacheKey($kpiName, $period, $hospitalKey);

        $cacheData = [
            'kpi_name' => $kpiName,
            'period' => $period,
            'data' => $timeSeriesData,
            'hospital_key' => $hospitalKey,
            'cached_at' => now(),
            'expires_at' => now()->addSeconds($this->historicalTtl)
        ];

        return Cache::put($cacheKey, $cacheData, $this->historicalTtl);
    }

    /**
     * Get cached KPI time series data
     */
    public function getCachedKPITimeSeries(string $kpiName, string $period = 'daily', int $hospitalKey = 1): ?array
    {
        $cacheKey = $this->getTimeSeriesCacheKey($kpiName, $period, $hospitalKey);
        return Cache::get($cacheKey);
    }

    /**
     * Invalidate KPI cache
     */
    public function invalidateKPI(string $kpiName, int $hospitalKey = 1): bool
    {
        $cacheKey = $this->getKPICacheKey($kpiName, $hospitalKey);
        return Cache::forget($cacheKey);
    }

    /**
     * Invalidate dashboard cache
     */
    public function invalidateDashboard(string $dashboardId, int $hospitalKey = 1): bool
    {
        $cacheKey = $this->getDashboardCacheKey($dashboardId, $hospitalKey);
        return Cache::forget($cacheKey);
    }

    /**
     * Invalidate all caches for a hospital
     */
    public function invalidateHospitalCache(int $hospitalKey): int
    {
        $pattern = "kpi:{$hospitalKey}:*";
        $keys = $this->getKeysByPattern($pattern);

        $invalidated = 0;
        foreach ($keys as $key) {
            if (Cache::forget($key)) {
                $invalidated++;
            }
        }

        // Also invalidate dashboard caches
        $dashboardPattern = "dashboard:{$hospitalKey}:*";
        $dashboardKeys = $this->getKeysByPattern($dashboardPattern);

        foreach ($dashboardKeys as $key) {
            if (Cache::forget($key)) {
                $invalidated++;
            }
        }

        return $invalidated;
    }

    /**
     * Warm up cache with frequently accessed KPIs
     */
    public function warmupCache(array $kpiNames, int $hospitalKey = 1): array
    {
        $results = [];

        foreach ($kpiNames as $kpiName) {
            $cached = $this->getCachedKPI($kpiName, $hospitalKey);

            if (!$cached) {
                // If not cached, mark as needing refresh
                $results[$kpiName] = false;
            } else {
                $results[$kpiName] = true;
            }
        }

        return $results;
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [
            'total_cached_kpis' => 0,
            'total_cached_dashboards' => 0,
            'cache_hit_ratio' => 0,
            'memory_usage' => 0,
            'uptime' => 0
        ];

        try {
            // Get Redis info if using Redis
            if (Cache::store()->getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Redis::connection();
                $info = $redis->info();

                $stats['memory_usage'] = $info['used_memory'] ?? 0;
                $stats['uptime'] = $info['uptime_in_seconds'] ?? 0;
            }

            // Count cached items (approximate)
            $kpiKeys = $this->getKeysByPattern('kpi:*:*:*');
            $dashboardKeys = $this->getKeysByPattern('dashboard:*:*:*');

            $stats['total_cached_kpis'] = count($kpiKeys);
            $stats['total_cached_dashboards'] = count($dashboardKeys);

        } catch (\Exception $e) {
            // If Redis is not available, return basic stats
        }

        return $stats;
    }

    /**
     * Get cache key for KPI
     */
    protected function getKPICacheKey(string $kpiName, int $hospitalKey): string
    {
        return "kpi:{$hospitalKey}:{$kpiName}:latest";
    }

    /**
     * Get cache key for dashboard
     */
    protected function getDashboardCacheKey(string $dashboardId, int $hospitalKey): string
    {
        return "dashboard:{$hospitalKey}:{$dashboardId}:data";
    }

    /**
     * Get cache key for time series data
     */
    protected function getTimeSeriesCacheKey(string $kpiName, string $period, int $hospitalKey): string
    {
        return "timeseries:{$hospitalKey}:{$kpiName}:{$period}";
    }

    /**
     * Get appropriate TTL based on data period
     */
    protected function getCacheTtl(string $period): int
    {
        return match($period) {
            'realtime' => $this->realtimeTtl,
            'latest' => $this->defaultTtl,
            'daily', 'weekly', 'monthly' => $this->historicalTtl,
            default => $this->defaultTtl
        };
    }

    /**
     * Get keys by pattern (Redis specific)
     */
    protected function getKeysByPattern(string $pattern): array
    {
        try {
            if (Cache::store()->getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Redis::connection();
                return $redis->keys($pattern);
            }
        } catch (\Exception $e) {
            // Fallback if Redis is not available
        }

        return [];
    }

    /**
     * Check if cache is healthy
     */
    public function isHealthy(): bool
    {
        try {
            // Try to set and get a test value
            $testKey = 'cache_health_check_' . time();
            $testValue = 'ok';

            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);

            // Clean up
            Cache::forget($testKey);

            return $retrieved === $testValue;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear all KPI-related caches
     */
    public function clearAll(): int
    {
        $patterns = [
            'kpi:*',
            'dashboard:*',
            'timeseries:*',
            'alerts:*'
        ];

        $cleared = 0;

        foreach ($patterns as $pattern) {
            $keys = $this->getKeysByPattern($pattern);
            foreach ($keys as $key) {
                if (Cache::forget($key)) {
                    $cleared++;
                }
            }
        }

        return $cleared;
    }
}
