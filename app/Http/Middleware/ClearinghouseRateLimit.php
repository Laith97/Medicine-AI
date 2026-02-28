<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ClearinghouseRateLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $provider = 'default'): Response
    {
        $accountId = $request->route('account_id') ?? $request->input('account_id');

        if (!$accountId) {
            return $next($request);
        }

        $limits = $this->getRateLimits($provider);
        $cacheKey = "clearinghouse_rate_limit:{$provider}:{$accountId}";

        // Get current request count
        $currentCount = Cache::get($cacheKey, 0);

        // Check if limit exceeded
        if ($currentCount >= $limits['max_requests']) {
            Log::warning('Clearinghouse rate limit exceeded', [
                'provider' => $provider,
                'account_id' => $accountId,
                'current_count' => $currentCount,
                'max_requests' => $limits['max_requests']
            ]);

            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests to clearinghouse API. Please try again later.',
                'retry_after' => $this->getRetryAfter($cacheKey),
                'limit' => $limits['max_requests'],
                'remaining' => 0,
                'reset_time' => $this->getResetTime($cacheKey)
            ], 429, [
                'X-RateLimit-Limit' => $limits['max_requests'],
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => $this->getResetTime($cacheKey),
                'Retry-After' => $this->getRetryAfter($cacheKey)
            ]);
        }

        // Increment counter
        Cache::put($cacheKey, $currentCount + 1, $limits['window_minutes']);

        // Add rate limit headers to response
        $response = $next($request);

        $remaining = max(0, $limits['max_requests'] - $currentCount - 1);

        $response->headers->set('X-RateLimit-Limit', $limits['max_requests']);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', $this->getResetTime($cacheKey));

        return $response;
    }

    /**
     * Get rate limits for a provider
     */
    protected function getRateLimits(string $provider): array
    {
        $defaultLimits = [
            'max_requests' => 100, // requests per window
            'window_minutes' => 60, // time window in minutes
        ];

        $providerLimits = [
            'availity' => [
                'max_requests' => 50, // Availity has stricter limits
                'window_minutes' => 60,
            ],
            'change_healthcare' => [
                'max_requests' => 100,
                'window_minutes' => 60,
            ],
            'trizetto' => [
                'max_requests' => 75,
                'window_minutes' => 60,
            ],
            'default' => $defaultLimits,
        ];

        return $providerLimits[$provider] ?? $defaultLimits;
    }

    /**
     * Get reset time for rate limit
     */
    protected function getResetTime(string $cacheKey): int
    {
        $limits = $this->getRateLimits(explode(':', $cacheKey)[1] ?? 'default');
        return time() + ($limits['window_minutes'] * 60);
    }

    /**
     * Get retry after seconds
     */
    protected function getRetryAfter(string $cacheKey): int
    {
        $limits = $this->getRateLimits(explode(':', $cacheKey)[1] ?? 'default');
        return $limits['window_minutes'] * 60;
    }

    /**
     * Check if rate limit is exceeded without incrementing
     */
    public function checkLimit(string $provider, int $accountId): array
    {
        $limits = $this->getRateLimits($provider);
        $cacheKey = "clearinghouse_rate_limit:{$provider}:{$accountId}";
        $currentCount = Cache::get($cacheKey, 0);

        return [
            'exceeded' => $currentCount >= $limits['max_requests'],
            'current' => $currentCount,
            'limit' => $limits['max_requests'],
            'remaining' => max(0, $limits['max_requests'] - $currentCount),
            'reset_time' => $this->getResetTime($cacheKey),
            'retry_after' => $this->getRetryAfter($cacheKey),
        ];
    }

    /**
     * Reset rate limit counter for testing or manual intervention
     */
    public function resetLimit(string $provider, int $accountId): void
    {
        $cacheKey = "clearinghouse_rate_limit:{$provider}:{$accountId}";
        Cache::forget($cacheKey);

        Log::info('Rate limit reset', [
            'provider' => $provider,
            'account_id' => $accountId
        ]);
    }

    /**
     * Get rate limit status for monitoring
     */
    public function getStatus(string $provider, int $accountId): array
    {
        $limits = $this->getRateLimits($provider);
        $cacheKey = "clearinghouse_rate_limit:{$provider}:{$accountId}";
        $currentCount = Cache::get($cacheKey, 0);

        return [
            'provider' => $provider,
            'account_id' => $accountId,
            'current_requests' => $currentCount,
            'max_requests' => $limits['max_requests'],
            'window_minutes' => $limits['window_minutes'],
            'remaining_requests' => max(0, $limits['max_requests'] - $currentCount),
            'reset_time' => $this->getResetTime($cacheKey),
            'utilization_percentage' => $limits['max_requests'] > 0
                ? round(($currentCount / $limits['max_requests']) * 100, 2)
                : 0,
        ];
    }
}
