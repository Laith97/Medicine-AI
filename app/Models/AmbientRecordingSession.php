<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AmbientRecordingSession extends Model
{
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'appointment_id',
        'session_uuid',
        'status',
        'started_at',
        'paused_at',
        'completed_at',
        'audio_duration',
        'audio_file_path',
        'transcription',
        'ai_analysis',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AmbientRecordingChunk::class, 'session_id');
    }

    public function insights(): HasMany
    {
        return $this->hasMany(RealTimeInsight::class, 'session_id');
    }
}
