<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HepProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'doctor_id',
        'patient_id',
        'diagnosis_id',
        'appointment_id',
        'duration_weeks',
        'frequency_per_week',
        'goals',
        'precautions',
        'personalization_metadata',
        'status',
        'template_id',
    ];

    protected $casts = [
        'duration_weeks' => 'integer',
        'frequency_per_week' => 'integer',
        'goals' => 'array',
        'precautions' => 'array',
        'personalization_metadata' => 'array',
    ];

    /**
     * Get the doctor who created this program
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Get the patient for this program
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the diagnosis this program is based on
     */
    public function diagnosis()
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * Get the appointment this program is related to
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the template this program was created from
     */
    public function template()
    {
        return $this->belongsTo(HepProgramTemplate::class, 'template_id');
    }

    /**
     * Get the HEP exercises in this program
     */
    public function hepExercises()
    {
        return $this->hasMany(HepExercise::class);
    }

    /**
     * Get the HEP assignments for this program
     */
    public function hepAssignments()
    {
        return $this->hasMany(HepAssignment::class);
    }

    /**
     * Scope for active programs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed programs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for programs by doctor
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope for programs by patient
     */
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope for programs by diagnosis
     */
    public function scopeByDiagnosis($query, $diagnosisId)
    {
        return $query->where('diagnosis_id', $diagnosisId);
    }

    /**
     * Check if program is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if program is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Mark program as completed
     */
    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark program as paused
     */
    public function markAsPaused()
    {
        $this->update(['status' => 'paused']);
    }

    /**
     * Resume program
     */
    public function resume()
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Get program status options
     */
    public static function getStatusOptions()
    {
        return ['active', 'completed', 'paused'];
    }

    /**
     * Calculate total program duration in days
     */
    public function getTotalDurationDays()
    {
        return $this->duration_weeks * 7;
    }

    /**
     * Get exercises for a specific week
     */
    public function getExercisesForWeek($weekNumber)
    {
        return $this->hepExercises()
            ->where('week_number', $weekNumber)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get personalization summary
     */
    public function getPersonalizationSummary()
    {
        if (!$this->personalization_metadata) {
            return null;
        }

        return [
            'personalized_at' => $this->personalization_metadata['personalized_at'] ?? null,
            'customizations_applied' => $this->personalization_metadata['customizations_applied'] ?? [],
            'patient_conditions' => $this->personalization_metadata['patient_profile']['medical_conditions'] ?? [],
            'functional_limitations' => $this->personalization_metadata['patient_profile']['functional_limitations'] ?? [],
            'treatment_goals' => $this->personalization_metadata['patient_profile']['treatment_goals'] ?? [],
        ];
    }

    /**
     * Check if program was personalized
     */
    public function isPersonalized()
    {
        return !empty($this->personalization_metadata);
    }
}
