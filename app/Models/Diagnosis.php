<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Diagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'diagnosis_text',
        'voice_transcripts',
        'voice_files',
        'patient_data',
        'follow_up_count',
        'patient_notified',
        'patient_viewed_at',
        'patient_reviewed',
        'patient_key',
    ];

    protected $casts = [
        'patient_data' => 'array',
        'voice_files' => 'array',
        'voice_transcripts' => 'array',
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
     * Get the AI assistant results linked to this diagnosis
     */
    public function aiAssistantResults()
    {
        return $this->hasMany(AiAssistantResult::class);
    }

    /**
     * Check if patient can ask more follow-up questions
     */
    public function canAskFollowUp()
    {
        return $this->follow_up_count < 5;
    }

    /**
     * Increment follow-up count atomically if under limit
     *
     * @return bool True if increment was successful, false if limit reached
     */
    public function incrementFollowUpCount(): bool
    {
        // Use atomic update with WHERE clause to prevent race conditions
        $affected = static::where('id', $this->id)
            ->where('follow_up_count', '<', 5)
            ->increment('follow_up_count');

        return $affected > 0;
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
     * Check if this diagnosis has AI assistant results
     */
    public function hasAiAssistantResults()
    {
        return $this->aiAssistantResults()->exists();
    }

    /**
     * Generate a unique patient key based on patient name, age, gender and doctor_id
     */
    public static function generatePatientKey($name, $age, $gender, $doctorId)
    {
        return md5($name . '-' . $age . '-' . $gender . '-' . $doctorId);
    }
}
