<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TreatmentOptimizationRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'ai_session_id',
        'recommended_medications',
        'alternative_medications',
        'dosage_adjustments',
        'timing_optimizations',
        'outcome_predictions',
        'risk_assessment',
        'adherence_factors',
        'effectiveness_score',
        'safety_score',
        'cost_efficiency_score',
        'validated_by_doctor',
        'validated_at',
        'implemented'
    ];

    protected $casts = [
        'recommended_medications' => 'array',
        'alternative_medications' => 'array',
        'dosage_adjustments' => 'array',
        'timing_optimizations' => 'array',
        'outcome_predictions' => 'array',
        'risk_assessment' => 'array',
        'adherence_factors' => 'array',
        'effectiveness_score' => 'decimal:2',
        'safety_score' => 'decimal:2',
        'cost_efficiency_score' => 'decimal:2',
        'validated_by_doctor' => 'boolean',
        'validated_at' => 'datetime',
        'implemented' => 'boolean'
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    // Scopes for filtering
    public function scopeByEffectiveness($query, $minScore)
    {
        return $query->where('effectiveness_score', '>=', $minScore);
    }

    public function scopeValidated($query)
    {
        return $query->where('validated_by_doctor', true);
    }

    public function scopeByCondition($query, $conditionCode)
    {
        return $query->whereHas('appointment.diagnosis', function($q) use ($conditionCode) {
            $q->where('diagnosis_code', $conditionCode);
        });
    }
}
