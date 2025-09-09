<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelehealthAIInsights extends Model
{
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'emotion',
        'emotion_confidence',
        'attention_score',
        'eye_contact',
    ];

    protected $casts = [
        'emotion_confidence' => 'decimal:4',
        'attention_score' => 'decimal:4',
        'eye_contact' => 'decimal:4',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function scopeForAppointment($query, $appointmentId)
    {
        return $query->where('appointment_id', $appointmentId);
    }

    public function scopeWithEmotionData($query)
    {
        return $query->whereNotNull('emotion');
    }

    public function scopeWithEngagementData($query)
    {
        return $query->whereNotNull('attention_score');
    }
}
