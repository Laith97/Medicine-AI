<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'appointment_id',
        'note_type',
        'note_text',
        'transcript',
        'audio_file_path',
        'appointment_date',
        'title',
        'tags',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the doctor who created this note
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the patient this note is about (nullable for general notes)
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the appointment this note is related to (nullable)
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Check if this is a voice note
     */
    public function isVoiceNote()
    {
        return $this->note_type === 'voice';
    }

    /**
     * Check if this is a text note
     */
    public function isTextNote()
    {
        return $this->note_type === 'text';
    }

    /**
     * Check if this is a general note (not patient-specific)
     */
    public function isGeneralNote()
    {
        return is_null($this->patient_id);
    }

    /**
     * Check if this is a patient-specific note
     */
    public function isPatientNote()
    {
        return !is_null($this->patient_id);
    }

    /**
     * Get the display title for the note
     */
    public function getDisplayTitle()
    {
        if ($this->title) {
            return $this->title;
        }

        if ($this->isPatientNote()) {
            return 'Note for ' . ($this->patient ? $this->patient->name : 'Unknown Patient');
        }

        return 'General Note';
    }

    /**
     * Get a short preview of the note content
     */
    public function getPreview($length = 100)
    {
        $content = $this->isVoiceNote() && $this->transcript
            ? $this->transcript
            : $this->note_text;

        return \Str::limit(strip_tags($content), $length);
    }

    /**
     * Scope for notes by a specific doctor
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope for notes about a specific patient
     */
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope for general notes (not patient-specific)
     */
    public function scopeGeneral($query)
    {
        return $query->whereNull('patient_id');
    }

    /**
     * Scope for patient-specific notes
     */
    public function scopePatientSpecific($query)
    {
        return $query->whereNotNull('patient_id');
    }

    /**
     * Scope for voice notes
     */
    public function scopeVoiceNotes($query)
    {
        return $query->where('note_type', 'voice');
    }

    /**
     * Scope for text notes
     */
    public function scopeTextNotes($query)
    {
        return $query->where('note_type', 'text');
    }

    /**
     * Scope for recent notes
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get formatted creation date
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('M j, Y g:i A');
    }

    /**
     * Get note type badge class for UI
     */
    public function getTypeBadgeClass()
    {
        return $this->isVoiceNote() ? 'bg-info' : 'bg-secondary';
    }

    /**
     * Get note type icon for UI
     */
    public function getTypeIcon()
    {
        return $this->isVoiceNote() ? 'fas fa-microphone' : 'fas fa-file-text';
    }
}
