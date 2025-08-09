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
        
        // Allow access if admin is impersonating
        if (session()->has('impersonating_admin_id')) {
            return $next($request);
        }
        
        // Log middleware execution for debugging
        \Log::info('Doctor middleware check', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'is_sub_user_field' => $user->is_sub_user,
            'parent_user_id' => $user->parent_user_id,
            'sub_user_role' => $user->sub_user_role,
            'isSubUser_method' => $user->isSubUser(),
            'isDoctor_method' => $user->isDoctor(),
            'has_doctor_profile' => $user->doctor ? true : false,
            'doctor_is_active' => $user->doctor ? $user->doctor->is_active : null,
            'parent_user_exists' => $user->parentUser ? true : false,
            'parent_user_email' => $user->parentUser ? $user->parentUser->email : null,
            'parent_has_doctor_profile' => ($user->parentUser && $user->parentUser->doctor) ? true : false,
            'request_url' => $request->url()
        ]);

        // Handle sub-users - they inherit access from their parent doctor
        if ($user->isSubUser()) {
            $parentUser = $user->parentUser;
            if (!$parentUser || !$parentUser->isDoctor() || !$parentUser->doctor) {
                abort(403, 'Access denied. Parent doctor profile required.');
            }
            
            // Check if parent doctor account is active
            if (!$parentUser->doctor->is_active) {
                \Log::warning('Sub-user attempted access with inactive parent doctor', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'parent_user_id' => $parentUser->id,
                    'parent_email' => $parentUser->email,
                    'parent_doctor_id' => $parentUser->doctor->id,
                    'parent_is_active' => $parentUser->doctor->is_active
                ]);
                
                abort(403, 'Access denied. The parent doctor account has been deactivated. Please contact support.');
            }
        } else {
            // Handle main users (doctors)
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
        }

        return $next($request);
    }
}
