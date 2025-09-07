<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationCacheService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'notifications:';

    /**
     * Get cached notification data
     */
    public function getCachedNotifications(int $userId, string $type = 'all', int $limit = 15): ?array
    {
        $cacheKey = $this->generateCacheKey($userId, $type, $limit);

        $cached = Cache::get($cacheKey);

        if ($cached) {
            Log::info('Notification cache hit', [
                'user_id' => $userId,
                'type' => $type,
                'cache_key' => $cacheKey
            ]);
        }

        return $cached;
    }

    /**
     * Cache notification data
     */
    public function cacheNotifications(int $userId, string $type, int $limit, array $data): void
    {
        $cacheKey = $this->generateCacheKey($userId, $type, $limit);

        Cache::put($cacheKey, $data, self::CACHE_TTL);

        Log::info('Notification data cached', [
            'user_id' => $userId,
            'type' => $type,
            'cache_key' => $cacheKey,
            'ttl' => self::CACHE_TTL
        ]);
    }

    /**
     * Invalidate user notification cache
     */
    public function invalidateUserCache(int $userId): void
    {
        $pattern = self::CACHE_PREFIX . $userId . ':*';

        // Since Laravel Cache doesn't have pattern deletion, we'll use tags
        Cache::tags(['notifications', 'user:' . $userId])->flush();

        Log::info('User notification cache invalidated', [
            'user_id' => $userId
        ]);
    }

    /**
     * Invalidate specific notification cache
     */
    public function invalidateSpecificCache(int $userId, string $type = 'all', int $limit = 15): void
    {
        $cacheKey = $this->generateCacheKey($userId, $type, $limit);
        Cache::forget($cacheKey);

        Log::info('Specific notification cache invalidated', [
            'user_id' => $userId,
            'type' => $type,
            'cache_key' => $cacheKey
        ]);
    }

    /**
     * Generate cache key
     */
    private function generateCacheKey(int $userId, string $type, int $limit): string
    {
        return self::CACHE_PREFIX . $userId . ':' . $type . ':' . $limit;
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        // This would require a cache backend that supports stats
        // For now, return basic info
        return [
            'cache_ttl' => self::CACHE_TTL,
            'cache_prefix' => self::CACHE_PREFIX,
            'cache_driver' => config('cache.default'),
        ];
    }

    /**
     * Clear all notification caches
     */
    public function clearAllCaches(): void
    {
        Cache::tags(['notifications'])->flush();
        Log::info('All notification caches cleared');
    }
}
