<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RtmSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'session_type',
        'status',
        'start_date',
        'end_date',
        'target_days',
        'monitoring_parameters',
        'clinical_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_days' => 'integer',
        'monitoring_parameters' => 'array',
    ];

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
     * Get the appointment if any.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the metrics for this session.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(RtmMetric::class);
    }

    /**
     * Get the alerts for this session.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(RtmAlert::class);
    }

    /**
     * Valid status values.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DISCHARGED = 'discharged';

    /**
     * Status transitions: current_status => allowed_next_statuses
     */
    private const STATUS_TRANSITIONS = [
        self::STATUS_ACTIVE => [self::STATUS_PAUSED, self::STATUS_COMPLETED, self::STATUS_DISCHARGED],
        self::STATUS_PAUSED => [self::STATUS_ACTIVE, self::STATUS_COMPLETED, self::STATUS_DISCHARGED],
        self::STATUS_COMPLETED => [],
        self::STATUS_DISCHARGED => [],
    ];

    /**
     * Check if session is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if transition to new status is valid.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowedTransitions);
    }

    /**
     * Get days remaining in monitoring period.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->isActive()) {
            return 0;
        }

        $endDate = $this->end_date ?? $this->start_date->addDays($this->target_days);
        $remaining = now()->diffInDays($endDate, false);

        return max(0, $remaining);
    }

    /**
     * Get average metric value for a type.
     */
    public function getAverageMetric(string $metricType): ?float
    {
        $avg = $this->metrics()
            ->where('metric_type', $metricType)
            ->avg('value');

        return $avg ? round($avg, 2) : null;
    }

    /**
     * Get latest metric value for a type.
     */
    public function getLatestMetric(string $metricType): ?RtmMetric
    {
        return $this->metrics()
            ->where('metric_type', $metricType)
            ->latest('recorded_at')
            ->first();
    }

    /**
     * Add a metric reading.
     */
    public function addMetric(string $type, float $value, ?string $unit = null, ?string $notes = null): RtmMetric
    {
        if (!in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PAUSED])) {
            throw new \InvalidArgumentException(
                "Cannot add metrics to session in '{$this->status}' status."
            );
        }

        return $this->metrics()->create([
            'metric_type' => $type,
            'value' => $value,
            'unit' => $unit,
            'notes' => $notes,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Pause the session.
     *
     * @throws \InvalidArgumentException
     */
    public function pause(): void
    {
        if (!$this->canTransitionTo(self::STATUS_PAUSED)) {
            throw new \InvalidArgumentException(
                "Cannot pause session in '{$this->status}' status."
            );
        }
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    /**
     * Resume the session.
     *
     * @throws \InvalidArgumentException
     */
    public function resume(): void
    {
        if (!$this->canTransitionTo(self::STATUS_ACTIVE)) {
            throw new \InvalidArgumentException(
                "Cannot resume session in '{$this->status}' status."
            );
        }
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Complete the session.
     *
     * @throws \InvalidArgumentException
     */
    public function complete(): void
    {
        if (!$this->canTransitionTo(self::STATUS_COMPLETED)) {
            throw new \InvalidArgumentException(
                "Cannot complete session in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'end_date' => now(),
        ]);
    }

    /**
     * Discharge patient from monitoring.
     *
     * @throws \InvalidArgumentException
     */
    public function discharge(): void
    {
        if (!$this->canTransitionTo(self::STATUS_DISCHARGED)) {
            throw new \InvalidArgumentException(
                "Cannot discharge session in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_DISCHARGED,
            'end_date' => now(),
        ]);
    }

    /**
     * Session type options.
     */
    public static function sessionTypes(): array
    {
        return [
            'initial' => 'Initial Assessment',
            'follow_up' => 'Follow-Up',
            'monitoring' => 'Continuous Monitoring',
        ];
    }

    /**
     * Check thresholds and create alert if breached.
     */
    public function checkThresholds(RtmMetric $metric): ?RtmAlert
    {
        $parameters = $this->monitoring_parameters ?? [];

        if (!isset($parameters['thresholds'][$metric->metric_type])) {
            return null;
        }

        $threshold = $parameters['thresholds'][$metric->metric_type];

        $shouldAlert = false;
        $severity = 'medium';

        if (isset($threshold['max']) && $metric->value > $threshold['max']) {
            $shouldAlert = true;
            $severity = $threshold['max_severity'] ?? 'high';
        }

        if (isset($threshold['min']) && $metric->value < $threshold['min']) {
            $shouldAlert = true;
            $severity = $threshold['min_severity'] ?? 'high';
        }

        if ($shouldAlert) {
            return $this->alerts()->create([
                'patient_id' => $this->patient_id,
                'doctor_id' => $this->doctor_id,
                'alert_type' => 'threshold_breach',
                'severity' => $severity,
                'metric_type' => $metric->metric_type,
                'trigger_value' => $metric->value,
                'threshold_value' => $threshold['max'] ?? $threshold['min'],
                'message' => "{$metric->metric_type} value ({$metric->value}) exceeded threshold",
                'triggered_at' => now(),
                'status' => 'active',
            ]);
        }

        return null;
    }
}
