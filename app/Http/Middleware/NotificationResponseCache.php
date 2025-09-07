<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationCacheService;
use Symfony\Component\HttpFoundation\Response;

class NotificationResponseCache
{
    private NotificationCacheService $cacheService;

    public function __construct(NotificationCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'all', int $limit = 15): Response
    {
        // Only cache for authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        $userId = Auth::id();

        // Try to get cached response
        $cachedResponse = $this->cacheService->getCachedNotifications($userId, $type, $limit);

        if ($cachedResponse) {
            return response()->json($cachedResponse);
        }

        // Get fresh response
        $response = $next($request);

        // Cache the response if it's successful
        if ($response->getStatusCode() === 200) {
            $responseData = json_decode($response->getContent(), true);
            if ($responseData) {
                $this->cacheService->cacheNotifications($userId, $type, $limit, $responseData);
            }
        }

        return $response;
    }
}
