<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PatientTreatmentResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'medication_name',
        'dosage',
        'duration',
        'start_date',
        'end_date',
        'outcome',
        'effectiveness_score',
        'side_effects',
        'adherence_rate',
        'notes'
    ];

    protected $casts = [
        'side_effects' => 'array',
        'effectiveness_score' => 'decimal:2',
        'adherence_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function scopeByOutcome($query, $outcome)
    {
        return $query->where('outcome', $outcome);
    }

    public function scopeByMedication($query, $medicationName)
    {
        return $query->where('medication_name', $medicationName);
    }

    public function scopeEffective($query)
    {
        return $query->where('outcome', 'effective');
    }

    public function scopeIneffective($query)
    {
        return $query->where('outcome', 'ineffective');
    }
}
