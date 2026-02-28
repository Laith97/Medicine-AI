<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientSummary extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'summary',
        'raw_data',
        'last_visit_date',
        'total_visits',
        'is_ai_generated'
    ];

    protected $casts = [
        'last_visit_date' => 'datetime',
        'is_ai_generated' => 'boolean',
        'total_visits' => 'integer'
    ];

    /**
     * Get the patient that owns the summary.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientAnalysis::class, 'patient_id');
    }

    /**
     * Get the doctor that owns the summary.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Scope a query to only include summaries for a specific patient.
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope a query to only include summaries for a specific doctor.
     */
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope a query to only include AI-generated summaries.
     */
    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }
}
