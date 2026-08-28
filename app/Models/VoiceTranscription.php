<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VoiceTranscription extends Model
{
    use HasFactory;
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'diagnosis_id',
        'session_id',
        'raw_transcription',
        'audio_file',
        'audio_format',
        'audio_duration',
        'audio_file_size',
        'extracted_data',
        'ai_analysis',
        'structured_chart',
        'is_confirmed',
        'is_final',
        'status',
        'session_started_at',
        'session_ended_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'structured_chart' => 'array',
        'is_confirmed' => 'boolean',
        'is_final' => 'boolean',
        'session_started_at' => 'datetime',
        'session_ended_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Get the public URL for the audio file.
     * Handles both legacy absolute paths and correct relative paths.
     */
    public function getAudioUrlAttribute(): ?string
    {
        if (empty($this->audio_file)) {
            return null;
        }

        $path = $this->audio_file;

        // Handle legacy absolute paths (e.g., /home/laith/.../storage/app/public/audio/...)
        if (str_starts_with($path, '/') || str_contains($path, 'storage/app/public')) {
            // Extract relative part after app/public/
            if (str_contains($path, 'app/public/')) {
                $path = substr($path, strpos($path, 'app/public/') + strlen('app/public/'));
            } else {
                // Fallback: try to extract after /audio/
                $pos = strpos($path, 'audio/');
                if ($pos !== false) {
                    $path = substr($path, $pos);
                }
            }
            $path = ltrim($path, '/');
        }

        // Also handle case where path mistakenly starts with storage/
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Get the relative storage path for the audio file.
     */
    public function getAudioRelativePathAttribute(): ?string
    {
        if (empty($this->audio_file)) {
            return null;
        }
        $path = $this->audio_file;
        if (str_contains($path, 'app/public/')) {
            $path = substr($path, strpos($path, 'app/public/') + strlen('app/public/'));
        }
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }
        return ltrim($path, '/');
    }
}
