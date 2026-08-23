<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRiskScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'no_show_risk',
        'hospitalization_risk',
        'prediction_method',
        'confidence',
        'model_version',
        'feature_snapshot',
    ];

    protected $casts = [
        'no_show_risk' => 'decimal:3',
        'hospitalization_risk' => 'decimal:3',
        'confidence' => 'decimal:2',
        'feature_snapshot' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the patient that owns the risk score.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the maximum risk score between no-show and hospitalization.
     */
    public function getMaxRiskAttribute(): float
    {
        return max($this->no_show_risk ?? 0, $this->hospitalization_risk ?? 0);
    }

    /**
     * Get risk level based on the maximum risk score.
     */
    public function getRiskLevelAttribute(): string
    {
        $maxRisk = $this->getMaxRiskAttribute();

        if ($maxRisk >= 0.7) {
            return 'high';
        } elseif ($maxRisk >= 0.3) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get risk level as a human-readable string.
     */
    public function getRiskLevelLabelAttribute(): string
    {
        return match($this->getRiskLevelAttribute()) {
            'low' => 'Low Risk',
            'medium' => 'Medium Risk',
            'high' => 'High Risk',
            default => 'Unknown'
        };
    }

    /**
     * Scope for high risk patients.
     */
    public function scopeHighRisk($query)
    {
        return $query->whereRaw('GREATEST(COALESCE(no_show_risk, 0), COALESCE(hospitalization_risk, 0)) >= 0.7');
    }

    /**
     * Scope for medium risk patients.
     */
    public function scopeMediumRisk($query)
    {
        return $query->whereRaw('GREATEST(COALESCE(no_show_risk, 0), COALESCE(hospitalization_risk, 0)) >= 0.3')
                    ->whereRaw('GREATEST(COALESCE(no_show_risk, 0), COALESCE(hospitalization_risk, 0)) < 0.7');
    }

    /**
     * Scope for low risk patients.
     */
    public function scopeLowRisk($query)
    {
        return $query->whereRaw('GREATEST(COALESCE(no_show_risk, 0), COALESCE(hospitalization_risk, 0)) < 0.3');
    }

    /**
     * Check if the risk score indicates high risk.
     */
    public function isHighRisk(): bool
    {
        return $this->getMaxRiskAttribute() >= 0.7;
    }

    /**
     * Check if the risk score indicates medium risk.
     */
    public function isMediumRisk(): bool
    {
        $maxRisk = $this->getMaxRiskAttribute();
        return $maxRisk >= 0.3 && $maxRisk < 0.7;
    }

    /**
     * Check if the risk score indicates low risk.
     */
    public function isLowRisk(): bool
    {
        return $this->getMaxRiskAttribute() < 0.3;
    }
}
