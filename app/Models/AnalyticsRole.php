<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsRole extends Model
{
    use HasFactory;

    protected $table = 'analytics_roles';
    protected $primaryKey = 'role_id';

    protected $fillable = [
        'role_name',
        'role_description',
        'hierarchy_level',
    ];

    protected $casts = [
        'hierarchy_level' => 'integer',
    ];

    /**
     * Dashboard permissions for this role
     */
    public function dashboardPermissions(): HasMany
    {
        return $this->hasMany(DashboardPermission::class, 'role_id', 'role_id');
    }

    /**
     * Feature permissions for this role
     */
    public function featurePermissions(): HasMany
    {
        return $this->hasMany(FeaturePermission::class, 'role_id', 'role_id');
    }

    /**
     * KPI permissions for this role
     */
    public function kpiPermissions(): HasMany
    {
        return $this->hasMany(KpiPermission::class, 'role_id', 'role_id');
    }

    /**
     * Users with this analytics role
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'analytics_role_id', 'role_id');
    }

    /**
     * Check if this role has access to a specific dashboard
     */
    public function canAccessDashboard(string $dashboardName): bool
    {
        $permission = $this->dashboardPermissions()->where('dashboard_name', $dashboardName)->first();
        return $permission && $permission->access_level !== 'none';
    }

    /**
     * Get the access level for a specific dashboard
     */
    public function getDashboardAccessLevel(string $dashboardName): string
    {
        $permission = $this->dashboardPermissions()->where('dashboard_name', $dashboardName)->first();
        return $permission ? $permission->access_level : 'none';
    }

    /**
     * Get the data scope for a specific dashboard
     */
    public function getDashboardDataScope(string $dashboardName): string
    {
        $permission = $this->dashboardPermissions()->where('dashboard_name', $dashboardName)->first();
        return $permission ? $permission->data_scope : 'none';
    }

    /**
     * Check if this role can access a specific feature
     */
    public function canAccessFeature(string $featureName): bool
    {
        $permission = $this->featurePermissions()->where('feature_name', $featureName)->first();
        return $permission ? $permission->can_access : false;
    }

    /**
     * Check if this role can view a specific KPI category
     */
    public function canViewKpi(string $kpiCategory): bool
    {
        $permission = $this->kpiPermissions()->where('kpi_category', $kpiCategory)->first();
        return $permission ? $permission->can_view : false;
    }

    /**
     * Check if this role can export a specific KPI category
     */
    public function canExportKpi(string $kpiCategory): bool
    {
        $permission = $this->kpiPermissions()->where('kpi_category', $kpiCategory)->first();
        return $permission ? $permission->can_export : false;
    }

    /**
     * Scope for roles by hierarchy level
     */
    public function scopeByHierarchyLevel($query, int $level)
    {
        return $query->where('hierarchy_level', $level);
    }

    /**
     * Scope for roles with higher or equal hierarchy
     */
    public function scopeHigherOrEqualHierarchy($query, int $level)
    {
        return $query->where('hierarchy_level', '>=', $level);
    }
}
