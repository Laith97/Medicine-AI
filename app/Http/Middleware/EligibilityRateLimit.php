<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EligibilityRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $user = $request->user();
        $key = "eligibility:{$type}:" . ($user ? $user->id : $request->ip());

        $limits = [
            'check' => [60, 1], // 60 requests per minute for individual checks
            'batch' => [10, 1], // 10 batch requests per minute
            'status' => [120, 1], // 120 status requests per minute
            'default' => [30, 1], // 30 requests per minute default
        ];

        $limit = $limits[$type] ?? $limits['default'];
        [$maxAttempts, $decayMinutes] = $limit;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Too many eligibility requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response;
    }
}
