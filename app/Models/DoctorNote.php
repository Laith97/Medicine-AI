<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'doctor_id', 'patient_id', 'appointment_id', 'title', 'note_text',
        'note_type', 'appointment_date', 'is_voice_note', 'audio_file_path',
        'transcript', 'audio_duration', 'is_private', 'tags', 'category',
        'follow_up_required', 'follow_up_date', 'shared_with_patient',
        'shared_at'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_voice_note' => 'boolean',
        'is_private' => 'boolean',
        'follow_up_required' => 'boolean',
        'shared_with_patient' => 'boolean',
        'follow_up_date' => 'datetime',
        'shared_at' => 'datetime',
        'audio_duration' => 'integer',
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
     * Check if this is a private note
     */
    public function isPrivate()
    {
        return $this->is_private;
    }

    /**
     * Check if this note is shared with patient
     */
    public function isSharedWithPatient()
    {
        return $this->shared_with_patient;
    }

    /**
     * Check if this note requires follow up
     */
    public function requiresFollowUp()
    {
        return $this->follow_up_required;
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
     * Scope for private notes
     */
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    /**
     * Scope for shared notes
     */
    public function scopeShared($query)
    {
        return $query->where('shared_with_patient', true);
    }

    /**
     * Scope for notes by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for notes requiring follow up
     */
    public function scopeRequiresFollowUp($query)
    {
        return $query->where('follow_up_required', true);
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('note_text', 'like', "%{$term}%")
              ->orWhere('transcript', 'like', "%{$term}%");
        });
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

    /**
     * Get truncated content attribute
     */
    public function getTruncatedContentAttribute()
    {
        $content = $this->isVoiceNote() && $this->transcript
            ? $this->transcript
            : $this->note_text;

        if (strlen($content) <= 200) {
            return $content;
        }

        return substr($content, 0, 197) . '...';
    }

    /**
     * Get word count attribute
     */
    public function getWordCountAttribute()
    {
        $content = $this->isVoiceNote() && $this->transcript
            ? $this->transcript
            : $this->note_text;

        return str_word_count(strip_tags($content));
    }

    /**
     * Get reading time attribute (in minutes)
     */
    public function getReadingTimeAttribute()
    {
        $wordCount = $this->word_count;
        $readingTime = ceil($wordCount / 200); // 200 words per minute
        return max(1, $readingTime); // Minimum 1 minute
    }

    /**
     * Share note with patient
     */
    public function shareWithPatient()
    {
        $this->update([
            'shared_with_patient' => true,
            'shared_at' => now(),
        ]);
    }

    /**
     * Unshare note with patient
     */
    public function unshareWithPatient()
    {
        $this->update([
            'shared_with_patient' => false,
            'shared_at' => null,
        ]);
    }

    /**
     * Mark note as private
     */
    public function markPrivate()
    {
        $this->update(['is_private' => true]);
    }

    /**
     * Mark note as public
     */
    public function markPublic()
    {
        $this->update(['is_private' => false]);
    }

    /**
     * Add tag to note
     */
    public function addTag($tag)
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    /**
     * Remove tag from note
     */
    public function removeTag($tag)
    {
        $tags = $this->tags ?? [];
        $tags = array_values(array_filter($tags, fn($t) => $t !== $tag));
        $this->update(['tags' => $tags]);
    }

    /**
     * Check if note has tag
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->tags ?? []);
    }

    /**
     * Set follow up for note
     */
    public function setFollowUp($date)
    {
        $this->update([
            'follow_up_required' => true,
            'follow_up_date' => $date,
        ]);
    }

    /**
     * Clear follow up for note
     */
    public function clearFollowUp()
    {
        $this->update([
            'follow_up_required' => false,
            'follow_up_date' => null,
        ]);
    }

    /**
     * Get formatted audio duration
     */
    public function getAudioDurationFormatted()
    {
        if (!$this->audio_duration) {
            return '0:00';
        }

        $minutes = floor($this->audio_duration / 60);
        $seconds = $this->audio_duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
