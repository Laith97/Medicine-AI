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
        'risk_score',
        'risk_level',
        'factors',
        'model_version',
        'calculated_at',
    ];

    protected $casts = [
        'risk_score' => 'decimal:4',
        'factors' => 'array',
        'calculated_at' => 'datetime',
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
     * Scope for high risk patients.
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', 'high');
    }

    /**
     * Scope for medium risk patients.
     */
    public function scopeMediumRisk($query)
    {
        return $query->where('risk_level', 'medium');
    }

    /**
     * Scope for low risk patients.
     */
    public function scopeLowRisk($query)
    {
        return $query->where('risk_level', 'low');
    }

    /**
     * Get risk level as a human-readable string.
     */
    public function getRiskLevelLabelAttribute(): string
    {
        return match($this->risk_level) {
            'low' => 'Low Risk',
            'medium' => 'Medium Risk',
            'high' => 'High Risk',
            default => 'Unknown'
        };
    }

    /**
     * Check if the risk score indicates high risk.
     */
    public function isHighRisk(): bool
    {
        return $this->risk_level === 'high';
    }

    /**
     * Check if the risk score indicates medium risk.
     */
    public function isMediumRisk(): bool
    {
        return $this->risk_level === 'medium';
    }

    /**
     * Check if the risk score indicates low risk.
     */
    public function isLowRisk(): bool
    {
        return $this->risk_level === 'low';
    }
}
