<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class KioskRateLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '10', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            Log::warning('Kiosk rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'route' => $request->route() ? $request->route()->getName() : 'unknown',
                'session_id' => session('kiosk_session_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please wait before trying again.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response;
    }

    /**
     * Resolve request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $sessionId = session('kiosk_session_id') ?? 'no_session';
        $ip = $request->ip();

        // Use combination of IP and session for rate limiting
        return sha1($ip . '|' . $sessionId . '|' . $request->route()->getName());
    }
}
