<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsAuditLog extends Model
{
    use HasFactory;

    protected $table = 'analytics_audit_log';

    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_name',
        'metadata',
        'ip_address',
        'user_agent',
        'accessed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'accessed_at' => 'datetime',
    ];

    /**
     * The user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by resource type
     */
    public function scopeByResourceType($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope for filtering by resource name
     */
    public function scopeByResourceName($query, string $resourceName)
    {
        return $query->where('resource_name', $resourceName);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('accessed_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent entries
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('accessed_at', '>=', now()->subDays($days));
    }

    /**
     * Get available actions
     */
    public static function getAvailableActions(): array
    {
        return [
            'view_dashboard',
            'export_data',
            'modify_permissions',
            'view_kpi',
            'export_kpi',
            'access_api',
            'customize_dashboard',
            'configure_alerts',
        ];
    }

    /**
     * Get available resource types
     */
    public static function getAvailableResourceTypes(): array
    {
        return [
            'dashboard',
            'kpi',
            'report',
            'api_endpoint',
            'permission',
            'alert',
        ];
    }

    /**
     * Log an analytics action
     */
    public static function logAction(
        int $userId,
        string $action,
        string $resourceType,
        string $resourceName,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_name' => $resourceName,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'accessed_at' => now(),
        ]);
    }
}
