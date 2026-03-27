<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EligibilityAccessControl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'error' => 'Authentication required'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user has required role/permission for eligibility operations
        if (!$this->userHasEligibilityPermission($user, $permission)) {
            return response()->json([
                'error' => 'Insufficient permissions for eligibility operations'
            ], Response::HTTP_FORBIDDEN);
        }

        // Log access attempt
        \Illuminate\Support\Facades\Log::info('Eligibility access granted', [
            'user_id' => $user->id,
            'permission' => $permission,
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
            'ip_address' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Check if user has required permission for eligibility operations
     */
    protected function userHasEligibilityPermission($user, ?string $permission): bool
    {
        // Basic role-based access control
        // In a real application, this would check against a proper ACL system

        $userRoles = $user->roles ?? []; // Assuming user has roles relationship
        $userPermissions = $user->permissions ?? []; // Assuming user has permissions

        // Define eligibility permissions
        $eligibilityPermissions = [
            'eligibility.view' => ['admin', 'doctor', 'nurse', 'medical_staff'],
            'eligibility.check' => ['admin', 'doctor', 'nurse', 'medical_staff'],
            'eligibility.batch' => ['admin', 'doctor'],
            'eligibility.manage' => ['admin'],
        ];

        if ($permission && isset($eligibilityPermissions[$permission])) {
            $allowedRoles = $eligibilityPermissions[$permission];

            // Check if user has any of the allowed roles
            if (is_array($userRoles)) {
                foreach ($userRoles as $role) {
                    if (in_array($role, $allowedRoles)) {
                        return true;
                    }
                }
            }

            // Check direct permissions
            if (is_array($userPermissions) && in_array($permission, $userPermissions)) {
                return true;
            }

            return false;
        }

        // Default: deny access if permission is not explicitly defined
        // This is more secure - explicit permissions are required
        return false;
    }
}
