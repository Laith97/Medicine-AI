<?php

namespace App\Http\Middleware;

use App\Exceptions\WaitlistSecurityException;
use App\Exceptions\Handlers\WaitlistExceptionHandler;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WaitlistAccessControl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            Log::warning('Unauthorized waitlist access attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check user role permissions
        if (!$this->hasWaitlistAccess($user)) {
            $exception = new WaitlistSecurityException(
                'Insufficient permissions for waitlist access',
                [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'required_roles' => ['doctor', 'patient', 'hospital_admin'],
                ]
            );

            return WaitlistExceptionHandler::handleSecurityException($exception, $request);
        }

        // Rate limiting for waitlist operations
        if (!$this->checkRateLimit($request)) {
            $exception = new WaitlistSecurityException(
                'Too many waitlist requests. Please try again later.',
                [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                ]
            );

            return WaitlistExceptionHandler::handleSecurityException($exception, $request);
        }

        // Log access for audit trail
        Log::info('Waitlist access granted', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Check if user has waitlist access permissions
     */
    private function hasWaitlistAccess($user): bool
    {
        $allowedRoles = ['doctor', 'patient', 'hospital_admin'];

        return in_array($user->role, $allowedRoles);
    }

    /**
     * Check rate limiting for waitlist operations
     */
    private function checkRateLimit(Request $request): bool
    {
        $key = 'waitlist_requests_' . Auth::id() . '_' . $request->ip();
        $maxRequests = 100; // requests per minute
        $decayMinutes = 1;

        // Simple in-memory rate limiting (in production, use Redis or similar)
        $requests = cache()->get($key, 0);

        if ($requests >= $maxRequests) {
            return false;
        }

        cache()->put($key, $requests + 1, now()->addMinutes($decayMinutes));

        return true;
    }
}
