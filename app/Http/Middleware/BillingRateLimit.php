<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class BillingRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $user = $request->user();
        $key = "billing:{$type}:" . ($user ? $user->id : $request->ip());

        $limits = [
            'suggestions' => [20, 1], // 20 code suggestions per minute
            'prediction' => [30, 1], // 30 denial predictions per minute
            'analysis' => [15, 1], // 15 underpayment analyses per minute
            'default' => [30, 1], // 30 requests per minute default
        ];

        $limit = $limits[$type] ?? $limits['default'];
        [$maxAttempts, $decayMinutes] = $limit;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Too many billing requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response;
    }
}
