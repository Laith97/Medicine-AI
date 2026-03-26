<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtmAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'rtm_session_id',
        'patient_id',
        'doctor_id',
        'alert_type',
        'severity',
        'metric_type',
        'trigger_value',
        'threshold_value',
        'message',
        'recommended_action',
        'status',
        'triggered_at',
        'acknowledged_at',
        'resolved_at',
        'acknowledged_by',
    ];

    protected $casts = [
        'trigger_value' => 'decimal:2',
        'threshold_value' => 'decimal:2',
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the RTM session.
     */
    public function rtmSession(): BelongsTo
    {
        return $this->belongsTo(RtmSession::class);
    }

    /**
     * Get the patient.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the doctor.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get who acknowledged the alert.
     */
    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Valid status values.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    /**
     * Valid status transitions.
     */
    private const STATUS_TRANSITIONS = [
        self::STATUS_ACTIVE => [self::STATUS_ACKNOWLEDGED, self::STATUS_DISMISSED],
        self::STATUS_ACKNOWLEDGED => [self::STATUS_RESOLVED, self::STATUS_DISMISSED],
        self::STATUS_RESOLVED => [],
        self::STATUS_DISMISSED => [],
    ];

    /**
     * Check if transition to new status is valid.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowedTransitions);
    }

    /**
     * Acknowledge the alert.
     *
     * @throws \InvalidArgumentException
     */
    public function acknowledge(User $user): void
    {
        if (!$this->canTransitionTo(self::STATUS_ACKNOWLEDGED)) {
            throw new \InvalidArgumentException(
                "Cannot acknowledge alert in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);
    }

    /**
     * Resolve the alert.
     *
     * @throws \InvalidArgumentException
     */
    public function resolve(): void
    {
        if (!$this->canTransitionTo(self::STATUS_RESOLVED)) {
            throw new \InvalidArgumentException(
                "Cannot resolve alert in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Dismiss the alert.
     *
     * @throws \InvalidArgumentException
     */
    public function dismiss(): void
    {
        if (!$this->canTransitionTo(self::STATUS_DISMISSED)) {
            throw new \InvalidArgumentException(
                "Cannot dismiss alert in '{$this->status}' status."
            );
        }
        $this->update(['status' => self::STATUS_DISMISSED]);
    }

    /**
     * Check if alert is critical.
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Check if alert is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope for active alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for critical alerts.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope for unacknowledged alerts (active or acknowledged but not yet resolved).
     */
    public function scopeUnacknowledged($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_ACKNOWLEDGED]);
    }

    /**
     * Alert type options.
     */
    public static function alertTypes(): array
    {
        return [
            'threshold_breach' => 'Threshold Breach',
            'pattern_change' => 'Pattern Change',
            'adherence_drop' => 'Adherence Drop',
            'deterioration' => 'Clinical Deterioration',
        ];
    }

    /**
     * Severity options.
     */
    public static function severityLevels(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
    }
}
