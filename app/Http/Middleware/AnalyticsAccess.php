<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\AnalyticsPermissions;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AnalyticsAccess
{
    protected AnalyticsPermissions $analyticsPermissions;

    public function __construct(AnalyticsPermissions $analyticsPermissions)
    {
        $this->analyticsPermissions = $analyticsPermissions;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission = null): SymfonyResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication required'
            ], 401);
        }

        // Check if user has analytics access at all
        if (!$this->analyticsPermissions->hasAnalyticsAccess($user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Analytics access not permitted'
            ], 403);
        }

        // If specific permission is required, check it
        if ($permission) {
            if (!$this->checkPermission($user, $permission, $request)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient permissions to access this resource',
                    'code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Check specific permission based on the permission string
     */
    protected function checkPermission($user, string $permission, Request $request): bool
    {
        // Parse permission string (e.g., "dashboard.executive.read", "kpi.revenue.view")
        $parts = explode('.', $permission);

        if (count($parts) < 2) {
            return false;
        }

        $resourceType = $parts[0];
        $resourceName = $parts[1];
        $action = $parts[2] ?? 'read';

        switch ($resourceType) {
            case 'dashboard':
                return $this->analyticsPermissions->canAccessDashboard($user, $resourceName);

            case 'kpi':
                if ($action === 'export') {
                    return $this->analyticsPermissions->canExportKpi($user, $resourceName);
                }
                return $this->analyticsPermissions->canViewKpi($user, $resourceName);

            case 'feature':
                return $this->analyticsPermissions->canAccessFeature($user, $resourceName);

            case 'api':
                // API access is a special feature permission
                return $this->analyticsPermissions->canAccessFeature($user, 'api_access');

            default:
                return false;
        }
    }
}
