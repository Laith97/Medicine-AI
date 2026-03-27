<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AdminImpersonation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {

        
        // Only run impersonation checks if there's an active impersonation session
        if (session()->has('impersonating_user_id') && session()->has('impersonating_admin_id')) {
            $userId = session('impersonating_user_id');
            $adminId = session('impersonating_admin_id');
            
            // Validate the impersonation session
            if (!$this->validateImpersonationSession($adminId, $userId)) {
                // Invalid session, clear it and logout
                $this->clearImpersonationSession();
                auth('web')->logout();
                return redirect()->route('admin.login')->with('error', 'Invalid impersonation session.');
            }
            
            // Verify the currently authenticated user matches the impersonation session
            $currentUser = auth('web')->user();
            if (!$currentUser || $currentUser->id != $userId) {
                // User mismatch - clear impersonation and require re-authentication
                // This prevents session manipulation attacks
                $this->clearImpersonationSession();
                auth('web')->logout();
                return redirect()->route('admin.login')->with('error', 'Session mismatch. Please re-authenticate.');
            }
        }

        return $next($request);
    }

    /**
     * Validate impersonation session
     */
    private function validateImpersonationSession($adminId, $userId): bool
    {
        // Check if admin still exists
        $admin = \App\Models\Admin::find($adminId);
        if (!$admin) {
            return false;
        }

        // Check if user still exists
        $user = User::find($userId);
        if (!$user || !in_array($user->role, ['hospital_admin', 'doctor'])) {
            return false;
        }

        // Check session expiry (24 hours)
        $startedAt = session('admin_impersonation_started_at');
        if (!$startedAt || (now()->timestamp - $startedAt) > 86400) {
            return false;
        }

        // Check IP address
        $sessionIp = session('admin_impersonation_ip');
        if ($sessionIp !== request()->ip()) {
            return false;
        }

        return true;
    }

    /**
     * Clear impersonation session
     */
    private function clearImpersonationSession(): void
    {
        session()->forget([
            'impersonating_user_id',
            'impersonating_admin_id', 
            'impersonating_admin_name',
            'admin_impersonation_started_at',
            'admin_impersonation_ip'
        ]);
    }
}