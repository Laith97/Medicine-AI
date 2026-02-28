<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class RoleRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Redirect based on user role
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'hospital_admin') {
            return redirect()->route('hospital-admin.dashboard');
        }

        if ($user->role === 'doctor') {
            // For doctors, continue to the dashboard
            return $next($request);
        }

        if ($user->role === 'patient') {
            // For patients, redirect to doctors search page
            return redirect()->route('doctors.index');
        }

        // For any other role, redirect to home
        return redirect()->route('doctors.index');
    }
}
