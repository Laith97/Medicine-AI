<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'thread_id',
        'sender_type',
        'sender_id',
        'body',
        'ai_suggestion_id',
        'is_sent',
    ];

    protected $casts = [
        'is_sent' => 'boolean',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function aiSuggestion(): BelongsTo
    {
        return $this->belongsTo(AiMessageSuggestion::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function scopeByPatient($query)
    {
        return $query->where('sender_type', 'patient');
    }

    public function scopeByDoctor($query)
    {
        return $query->where('sender_type', 'doctor');
    }
}
