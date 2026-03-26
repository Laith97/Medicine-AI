<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RtmMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'rtm_session_id',
        'patient_id',
        'metric_type',
        'value',
        'unit',
        'notes',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'metadata' => 'array',
        'recorded_at' => 'datetime',
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
     * Metric type options.
     */
    public static function metricTypes(): array
    {
        return [
            'pain_level' => 'Pain Level (0-10)',
            'function_score' => 'Functional Score',
            'adherence' => 'Treatment Adherence %',
            'range_of_motion' => 'Range of Motion',
            'strength' => 'Strength Rating',
            'swelling' => 'Swelling Level',
            'temperature' => 'Temperature',
            'blood_pressure' => 'Blood Pressure',
            'weight' => 'Weight',
            'symptom_severity' => 'Symptom Severity',
            'sleep_quality' => 'Sleep Quality',
            'fatigue_level' => 'Fatigue Level',
        ];
    }

    /**
     * Trend change threshold constant.
     */
    private const TREND_THRESHOLD = 0.1;

    /**
     * Cached trend value.
     */
    protected ?string $cachedTrend = null;

    /**
     * Get trend (up/down/stable) compared to previous reading.
     * Result is cached for the lifetime of this model instance.
     */
    public function getTrendAttribute(): string
    {
        if ($this->cachedTrend !== null) {
            return $this->cachedTrend;
        }

        $previous = self::where('rtm_session_id', $this->rtm_session_id)
            ->where('metric_type', $this->metric_type)
            ->where('id', '<', $this->id)
            ->orderBy('recorded_at', 'desc')
            ->value('value');

        if ($previous === null) {
            return $this->cachedTrend = 'new';
        }

        $diff = $this->value - $previous;

        if (abs($diff) < self::TREND_THRESHOLD) {
            return $this->cachedTrend = 'stable';
        }

        return $this->cachedTrend = ($diff > 0) ? 'increasing' : 'decreasing';
    }
}
