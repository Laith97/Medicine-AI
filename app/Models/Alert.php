<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_id',
        'alert_rule_id',
        'title',
        'message',
        'severity',
        'status',
        'event_type',
        'model_type',
        'model_id',
        'event_data',
        'context_data',
        'priority_score',
        'escalation_history',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'notification_history',
        'next_escalation_at',
        'escalation_level',
        'metadata',
    ];

    protected $casts = [
        'event_data' => 'array',
        'context_data' => 'array',
        'escalation_history' => 'array',
        'notification_history' => 'array',
        'priority_score' => 'decimal:2',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'next_escalation_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($alert) {
            if (empty($alert->alert_id)) {
                $alert->alert_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the alert rule that created this alert
     */
    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class);
    }

    /**
     * Get the user who acknowledged this alert
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get the user who resolved this alert
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the related model (polymorphic)
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for active alerts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for acknowledged alerts
     */
    public function scopeAcknowledged($query)
    {
        return $query->where('status', 'acknowledged');
    }

    /**
     * Scope for resolved alerts
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope for escalated alerts
     */
    public function scopeEscalated($query)
    {
        return $query->where('status', 'escalated');
    }

    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope by event type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope for alerts requiring escalation
     */
    public function scopeRequiringEscalation($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('next_escalation_at')
            ->where('next_escalation_at', '<=', now());
    }

    /**
     * Scope ordered by priority score
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority_score', 'desc')
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Check if alert can be acknowledged
     */
    public function canBeAcknowledged(): bool
    {
        return in_array($this->status, ['active', 'escalated']);
    }

    /**
     * Check if alert can be resolved
     */
    public function canBeResolved(): bool
    {
        return in_array($this->status, ['active', 'acknowledged', 'escalated']);
    }

    /**
     * Acknowledge the alert
     */
    public function acknowledge(User $user, ?string $notes = null): bool
    {
        if (!$this->canBeAcknowledged()) {
            return false;
        }

        $this->status = 'acknowledged';
        $this->acknowledged_at = now();
        $this->acknowledged_by = $user->id;

        if ($notes) {
            $this->resolution_notes = $notes;
        }

        // Clear escalation timer
        $this->next_escalation_at = null;

        return $this->save();
    }

    /**
     * Resolve the alert
     */
    public function resolve(User $user, ?string $notes = null): bool
    {
        if (!$this->canBeResolved()) {
            return false;
        }

        $this->status = 'resolved';
        $this->resolved_at = now();
        $this->resolved_by = $user->id;

        if ($notes) {
            $this->resolution_notes = $notes;
        }

        // Clear escalation timer
        $this->next_escalation_at = null;

        return $this->save();
    }

    /**
     * Escalate the alert
     */
    public function escalate(): bool
    {
        $this->status = 'escalated';
        $this->escalation_level += 1;

        // Add to escalation history
        $history = $this->escalation_history ?? [];
        $history[] = [
            'level' => $this->escalation_level,
            'escalated_at' => now()->toISOString(),
            'previous_status' => $this->getOriginal('status'),
        ];
        $this->escalation_history = $history;

        // Set next escalation if configured
        $escalationRules = $this->alertRule->getEscalationRules($this->severity);
        if (isset($escalationRules[$this->escalation_level])) {
            $nextLevel = $escalationRules[$this->escalation_level];
            if (isset($nextLevel['delay_minutes'])) {
                $this->next_escalation_at = now()->addMinutes($nextLevel['delay_minutes']);
            }
        }

        return $this->save();
    }

    /**
     * Add notification to history
     */
    public function addNotificationHistory(string $channel, array $details): void
    {
        $history = $this->notification_history ?? [];
        $history[] = [
            'channel' => $channel,
            'sent_at' => now()->toISOString(),
            'details' => $details,
        ];
        $this->notification_history = $history;
        $this->save();
    }

    /**
     * Get severity color for UI
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'secondary',
            'info' => 'light',
            default => 'secondary',
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'active' => 'primary',
            'acknowledged' => 'warning',
            'resolved' => 'success',
            'escalated' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Check if alert is overdue for escalation
     */
    public function isOverdueForEscalation(): bool
    {
        return $this->status === 'active' &&
               $this->next_escalation_at &&
               $this->next_escalation_at->isPast();
    }

    /**
     * Get time until next escalation
     */
    public function getTimeUntilEscalation(): ?int
    {
        if (!$this->next_escalation_at) {
            return null;
        }

        return now()->diffInMinutes($this->next_escalation_at, false);
    }
}
