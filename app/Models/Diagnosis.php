<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Diagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'type',
        'diagnosis_text',
        'voice_transcript',
        'voice_file_path',
        'patient_data',
        'ai_response',
        'follow_up_count',
        'patient_notified',
        'patient_viewed_at',
        'patient_reviewed',
    ];

    protected $casts = [
        'patient_data' => 'array',
        'patient_notified' => 'boolean',
        'patient_reviewed' => 'boolean',
        'patient_viewed_at' => 'datetime',
    ];

    /**
     * Get the doctor who made the diagnosis
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the patient for this diagnosis
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the follow-up messages for this diagnosis
     */
    public function followUps()
    {
        return $this->hasMany(DiagnosisFollowUp::class);
    }

    /**
     * Check if patient can ask more follow-up questions
     */
    public function canAskFollowUp()
    {
        return $this->follow_up_count < 5;
    }

    /**
     * Increment follow-up count
     */
    public function incrementFollowUpCount()
    {
        $this->increment('follow_up_count');
    }

    /**
     * Mark as viewed by patient
     */
    public function markAsViewed()
    {
        if (!$this->patient_viewed_at) {
            $this->update(['patient_viewed_at' => now()]);
        }
    }

    /**
     * Mark as reviewed by patient
     */
    public function markAsReviewed()
    {
        $this->update(['patient_reviewed' => true]);
    }

    /**
     * Check if this is an AI diagnosis
     */
    public function isAiDiagnosis()
    {
        return $this->type === 'ai';
    }

    /**
     * Check if this is a manual diagnosis
     */
    public function isManualDiagnosis()
    {
        return $this->type === 'manual';
    }
}
