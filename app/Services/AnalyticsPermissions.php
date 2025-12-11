<?php

namespace App\Services;

use App\Models\User;
use App\Models\AnalyticsRole;
use App\Models\AnalyticsAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AnalyticsPermissions
{
    /**
     * Check if user can access a specific dashboard
     */
    public function canAccessDashboard(User $user, string $dashboardName): bool
    {
        $role = $user->analyticsRole;
        if (!$role) {
            return false;
        }

        // Log the access attempt
        $this->logAccess($user, 'view_dashboard', 'dashboard', $dashboardName);

        return $role->canAccessDashboard($dashboardName);
    }

    /**
     * Get the access level for a specific dashboard
     */
    public function getDashboardAccessLevel(User $user, string $dashboardName): string
    {
        $role = $user->analyticsRole;
        if (!$role) {
            return 'none';
        }

        return $role->getDashboardAccessLevel($dashboardName);
    }

    /**
     * Get the data scope for a specific dashboard
     */
    public function getDataScope(User $user, string $dashboardName): string
    {
        $role = $user->analyticsRole;
        if (!$role) {
            return 'none';
        }

        return $role->getDashboardDataScope($dashboardName);
    }

    /**
     * Check if user can access a specific feature
     */
    public function canAccessFeature(User $user, string $featureName): bool
    {
        $role = $user->analyticsRole;
        if (!$role) {
            return false;
        }

        return $role->canAccessFeature($featureName);
    }

    /**
     * Check if user can view a specific KPI category
     */
    public function canViewKpi(User $user, string $kpiCategory): bool
    {
        $role = $user->analyticsRole;
        if (!$role) {
            return false;
        }

        // Log the KPI access
        $this->logAccess($user, 'view_kpi', 'kpi', $kpiCategory);

        return $role->canViewKpi($kpiCategory);
    }

    /**
     * Check if user can export a specific KPI category
     */
    public function canExportKpi(User $user, string $kpiCategory): bool
    {
        $role = $user->analyticsRole;
        if (!$role) {
            return false;
        }

        // Log the export attempt
        $this->logAccess($user, 'export_kpi', 'kpi', $kpiCategory);

        return $role->canExportKpi($kpiCategory);
    }

    /**
     * Apply data filtering based on user's scope
     */
    public function applyDataFilter(User $user, Builder $query, string $dashboardName = null): Builder
    {
        $scope = $dashboardName ? $this->getDataScope($user, $dashboardName) : 'personal';

        switch ($scope) {
            case 'personal':
                // Filter to user's own data (for doctors) or assigned patients (for staff)
                if ($user->isDoctor()) {
                    return $query->where('doctor_id', $user->id);
                } elseif ($user->isPatient()) {
                    return $query->where('patient_id', $user->id);
                }
                break;

            case 'team':
                // Filter to team/department data
                if ($user->hospital_id) {
                    return $query->where('hospital_id', $user->hospital_id);
                }
                break;

            case 'department':
                // Filter to department data (if department system exists)
                if ($user->hospital_id) {
                    return $query->where('hospital_id', $user->hospital_id);
                }
                break;

            case 'hospital':
                // Filter to hospital data
                if ($user->hospital_id) {
                    return $query->where('hospital_id', $user->hospital_id);
                }
                break;

            case 'system':
                // No filtering - full system access
                return $query;

            default:
                // No access
                return $query->whereRaw('1 = 0');
        }

        // Default to no access if no scope matches
        return $query->whereRaw('1 = 0');
    }

    /**
     * Get available dashboards for user
     */
    public function getAvailableDashboards(User $user): array
    {
        $role = $user->analyticsRole;
        if (!$role) {
            return [];
        }

        $dashboards = [
            'executive',
            'revenue',
            'patient_experience',
            'operations',
            'clinical'
        ];

        return array_filter($dashboards, function ($dashboard) use ($role) {
            return $role->canAccessDashboard($dashboard);
        });
    }

    /**
     * Get available features for user
     */
    public function getAvailableFeatures(User $user): array
    {
        $role = $user->analyticsRole;
        if (!$role) {
            return [];
        }

        $features = [
            'real_time_updates',
            'export_data',
            'custom_date_ranges',
            'drill_down_analysis',
            'alert_configuration',
            'dashboard_customization',
            'scheduled_reports',
            'api_access'
        ];

        return array_filter($features, function ($feature) use ($role) {
            return $role->canAccessFeature($feature);
        });
    }

    /**
     * Get available KPI categories for user
     */
    public function getAvailableKpiCategories(User $user): array
    {
        $role = $user->analyticsRole;
        if (!$role) {
            return [];
        }

        $categories = [
            'revenue_metrics',
            'patient_satisfaction',
            'operational_efficiency',
            'clinical_outcomes',
            'quality_measures',
            'compliance_metrics',
            'staff_performance',
            'system_performance'
        ];

        return array_filter($categories, function ($category) use ($role) {
            return $role->canViewKpi($category);
        });
    }

    /**
     * Assign analytics role to user
     */
    public function assignRole(User $user, AnalyticsRole $role): bool
    {
        $user->analytics_role_id = $role->role_id;
        return $user->save();
    }

    /**
     * Remove analytics role from user
     */
    public function removeRole(User $user): bool
    {
        $user->analytics_role_id = null;
        return $user->save();
    }

    /**
     * Log analytics access for audit purposes
     */
    private function logAccess(User $user, string $action, string $resourceType, string $resourceName, array $metadata = []): void
    {
        AnalyticsAuditLog::logAction(
            $user->id,
            $action,
            $resourceType,
            $resourceName,
            $metadata,
            request()->ip(),
            request()->userAgent()
        );
    }

    /**
     * Check if user has analytics access at all
     */
    public function hasAnalyticsAccess(User $user): bool
    {
        return $user->analyticsRole !== null;
    }

    /**
     * Get user's analytics role name
     */
    public function getUserRoleName(User $user): ?string
    {
        return $user->analyticsRole?->role_name;
    }

    /**
     * Get user's hierarchy level
     */
    public function getUserHierarchyLevel(User $user): int
    {
        return $user->analyticsRole?->hierarchy_level ?? 0;
    }
}
