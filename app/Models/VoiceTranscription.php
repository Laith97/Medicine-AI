<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceTranscription extends Model
{
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'session_id',
        'raw_transcription',
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
}
