<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDoctor
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
        
        // Log middleware execution for debugging
        \Log::info('Doctor middleware check', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'is_doctor' => $user->isDoctor(),
            'has_doctor_profile' => $user->doctor ? true : false,
            'doctor_is_active' => $user->doctor ? $user->doctor->is_active : null,
            'request_url' => $request->url()
        ]);

        if (!$user->isDoctor() || !$user->doctor) {
            abort(403, 'Access denied. Doctor profile required.');
        }

        // Check if doctor account is active
        if (!$user->doctor->is_active) {
            \Log::warning('Inactive doctor attempted access', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'doctor_id' => $user->doctor->id,
                'is_active' => $user->doctor->is_active
            ]);
            
            // Log out the user to prevent session caching issues
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            abort(403, 'Access denied. Your doctor account has been deactivated. Please contact support.');
        }

        return $next($request);
    }
}
