<?php

namespace App\Services;

use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use App\Models\WaitlistPatientPreference;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WaitlistCacheService
{
    protected int $defaultTtl = 3600; // 1 hour
    protected int $analyticsTtl = 1800; // 30 minutes
    protected int $preferencesTtl = 7200; // 2 hours

    /**
     * Get cached waitlist statistics for a doctor
     */
    public function getWaitlistStatistics(int $doctorId): ?array
    {
        $cacheKey = "waitlist_stats_doctor_{$doctorId}";

        return Cache::get($cacheKey);
    }

    /**
     * Cache waitlist statistics for a doctor
     */
    public function setWaitlistStatistics(int $doctorId, array $stats): void
    {
        $cacheKey = "waitlist_stats_doctor_{$doctorId}";

        Cache::put($cacheKey, $stats, $this->analyticsTtl);

        Log::debug('Waitlist statistics cached', [
            'doctor_id' => $doctorId,
            'cache_key' => $cacheKey,
            'ttl' => $this->analyticsTtl,
        ]);
    }

    /**
     * Get cached available slots for a doctor
     */
    public function getAvailableSlots(int $doctorId, int $daysAhead = 30): ?array
    {
        $cacheKey = "waitlist_slots_doctor_{$doctorId}_{$daysAhead}";

        return Cache::get($cacheKey);
    }

    /**
     * Cache available slots for a doctor
     */
    public function setAvailableSlots(int $doctorId, int $daysAhead, array $slots): void
    {
        $cacheKey = "waitlist_slots_doctor_{$doctorId}_{$daysAhead}";

        Cache::put($cacheKey, $slots, $this->defaultTtl);

        Log::debug('Available slots cached', [
            'doctor_id' => $doctorId,
            'days_ahead' => $daysAhead,
            'cache_key' => $cacheKey,
            'slots_count' => count($slots),
        ]);
    }

    /**
     * Get cached patient preferences
     */
    public function getPatientPreferences(int $patientId, int $doctorId): ?array
    {
        $cacheKey = "waitlist_preferences_{$patientId}_{$doctorId}";

        return Cache::get($cacheKey);
    }

    /**
     * Cache patient preferences
     */
    public function setPatientPreferences(int $patientId, int $doctorId, array $preferences): void
    {
        $cacheKey = "waitlist_preferences_{$patientId}_{$doctorId}";

        Cache::put($cacheKey, $preferences, $this->preferencesTtl);

        Log::debug('Patient preferences cached', [
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Get cached waitlist position
     */
    public function getWaitlistPosition(int $waitlistId): ?array
    {
        $cacheKey = "waitlist_position_{$waitlistId}";

        return Cache::get($cacheKey);
    }

    /**
     * Cache waitlist position
     */
    public function setWaitlistPosition(int $waitlistId, array $position): void
    {
        $cacheKey = "waitlist_position_{$waitlistId}";

        Cache::put($cacheKey, $position, $this->defaultTtl);

        Log::debug('Waitlist position cached', [
            'waitlist_id' => $waitlistId,
            'cache_key' => $cacheKey,
            'position' => $position['position'],
        ]);
    }

    /**
     * Get cached analytics data
     */
    public function getAnalyticsData(int $doctorId, string $timeframe): ?array
    {
        $cacheKey = "waitlist_analytics_{$doctorId}_{$timeframe}";

        return Cache::get($cacheKey);
    }

    /**
     * Cache analytics data
     */
    public function setAnalyticsData(int $doctorId, string $timeframe, array $data): void
    {
        $cacheKey = "waitlist_analytics_{$doctorId}_{$timeframe}";

        Cache::put($cacheKey, $data, $this->analyticsTtl);

        Log::debug('Analytics data cached', [
            'doctor_id' => $doctorId,
            'timeframe' => $timeframe,
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Invalidate cache for a specific doctor
     */
    public function invalidateDoctorCache(int $doctorId): void
    {
        $patterns = [
            "waitlist_stats_doctor_{$doctorId}",
            "waitlist_slots_doctor_{$doctorId}_*",
            "waitlist_analytics_{$doctorId}_*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        Log::info('Doctor cache invalidated', ['doctor_id' => $doctorId]);
    }

    /**
     * Invalidate cache for a specific patient
     */
    public function invalidatePatientCache(int $patientId): void
    {
        // Find all doctor preferences for this patient and invalidate
        $preferences = WaitlistPatientPreference::where('patient_id', $patientId)->get();

        foreach ($preferences as $preference) {
            $cacheKey = "waitlist_preferences_{$patientId}_{$preference->doctor_id}";
            Cache::forget($cacheKey);
        }

        Log::info('Patient cache invalidated', ['patient_id' => $patientId]);
    }

    /**
     * Invalidate cache for a specific waitlist
     */
    public function invalidateWaitlistCache(int $waitlistId): void
    {
        $cacheKey = "waitlist_position_{$waitlistId}";
        Cache::forget($cacheKey);

        // Also invalidate doctor's cache
        $waitlist = Waitlist::find($waitlistId);
        if ($waitlist) {
            $this->invalidateDoctorCache($waitlist->doctor_id);
        }

        Log::info('Waitlist cache invalidated', ['waitlist_id' => $waitlistId]);
    }

    /**
     * Clear all waitlist-related cache
     */
    public function clearAllCache(): void
    {
        // This is a simplified implementation
        // In production, you might want to use cache tags or a more sophisticated approach
        Cache::flush();

        Log::info('All waitlist cache cleared');
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        // This would require a cache driver that supports statistics
        // For now, return basic info
        return [
            'default_ttl' => $this->defaultTtl,
            'analytics_ttl' => $this->analyticsTtl,
            'preferences_ttl' => $this->preferencesTtl,
            'cache_driver' => config('cache.default'),
        ];
    }

    /**
     * Warm up cache for a doctor
     */
    public function warmUpDoctorCache(int $doctorId): void
    {
        $waitlistService = app(WaitlistService::class);

        // Pre-cache statistics
        $stats = $waitlistService->getWaitlistStatistics($doctorId);
        $this->setWaitlistStatistics($doctorId, $stats);

        // Pre-cache available slots
        $slots = $waitlistService->findAvailableSlots($doctorId, 14);
        $this->setAvailableSlots($doctorId, 14, $slots);

        Log::info('Doctor cache warmed up', ['doctor_id' => $doctorId]);
    }

    /**
     * Check if cache is enabled and working
     */
    public function isCacheHealthy(): bool
    {
        try {
            $testKey = 'waitlist_cache_health_check_' . time();
            Cache::put($testKey, 'test', 10);
            $result = Cache::get($testKey);
            Cache::forget($testKey);

            return $result === 'test';
        } catch (\Exception $e) {
            Log::error('Cache health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get cached data with automatic refresh if expired
     */
    public function getWithAutoRefresh(string $key, callable $callback, int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($key, $ttl, function () use ($callback, $key) {
            Log::debug('Cache miss, refreshing data', ['key' => $key]);
            return $callback();
        });
    }
}
