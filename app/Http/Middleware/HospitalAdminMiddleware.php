<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HospitalAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();



        // Check if admin is impersonating - allow access if so
        if (session()->has('impersonating_admin_id')) {
            return $next($request);
        }

        // Allow super admin to access hospital admin areas directly
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Regular hospital admin checks
        if (!$user->isHospitalAdmin()) {
            abort(403, 'Access denied. Hospital admin role required.');
        }

        if (!$user->hospital) {
            abort(403, 'Access denied. Hospital association required.');
        }
        return $next($request);
    }
}