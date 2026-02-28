<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'event_category',
        'user_id',
        'user_role',
        'resource_id',
        'resource_type',
        'action_performed',
        'event_data',
        'compliance_context',
        'severity_level',
        'ip_address',
        'user_agent',
        'session_id',
        'request_id',
        'event_timestamp',
    ];

    protected $casts = [
        'event_data' => 'array',
        'compliance_context' => 'array',
        'event_timestamp' => 'datetime',
    ];

    /**
     * Get the user that performed this compliance event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by event type.
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope for filtering by event category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('event_category', $category);
    }

    /**
     * Scope for filtering by severity level.
     */
    public function scopeWithSeverity($query, string $severity)
    {
        return $query->where('severity_level', $severity);
    }

    /**
     * Scope for filtering by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by resource.
     */
    public function scopeForResource($query, string $resourceType, int $resourceId)
    {
        return $query->where('resource_type', $resourceType)
                    ->where('resource_id', $resourceId);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_timestamp', [$startDate, $endDate]);
    }

    /**
     * Get formatted event data for display.
     */
    public function getFormattedEventData(): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'event_category' => $this->event_category,
            'user' => $this->user?->name ?? 'System',
            'user_role' => $this->user_role,
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_id,
            'action_performed' => $this->action_performed,
            'severity_level' => $this->severity_level,
            'ip_address' => $this->ip_address,
            'event_timestamp' => $this->event_timestamp->toISOString(),
            'event_data' => $this->event_data,
            'compliance_context' => $this->compliance_context,
        ];
    }

    /**
     * Check if this event represents a compliance violation.
     */
    public function isViolation(): bool
    {
        return in_array($this->event_type, [
            'compliance_violation',
            'hipaa_violation',
            'data_privacy_breach',
            'unauthorized_access',
            'retention_policy_violation',
        ]);
    }

    /**
     * Check if this event requires immediate attention.
     */
    public function requiresAttention(): bool
    {
        return in_array($this->severity_level, ['high', 'critical']);
    }
}
