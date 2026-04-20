<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessageSuggestion extends Model
{
    protected $fillable = [
        'thread_id',
        'doctor_id',
        'suggested_reply',
        'status',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
