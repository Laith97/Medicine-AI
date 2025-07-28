<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_session_id',
        'message',
        'sender_type',
        'is_read',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'metadata' => 'array',
    ];

    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeFromVisitor($query)
    {
        return $query->where('sender_type', 'visitor');
    }

    public function scopeFromDoctor($query)
    {
        return $query->where('sender_type', 'doctor');
    }

    public function scopeFromBot($query)
    {
        return $query->where('sender_type', 'bot');
    }

    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update(['is_read' => true]);
        }
    }

    public function getFormattedTimeAttribute()
    {
        return $this->created_at->format('g:i A');
    }

    public function getIsFromVisitorAttribute()
    {
        return $this->sender_type === 'visitor';
    }

    public function getIsFromDoctorAttribute()
    {
        return $this->sender_type === 'doctor';
    }

    public function getIsFromBotAttribute()
    {
        return $this->sender_type === 'bot';
    }

    public static function createVisitorMessage($sessionId, $message, $metadata = [])
    {
        return static::create([
            'chat_session_id' => $sessionId,
            'message' => $message,
            'sender_type' => 'visitor',
            'metadata' => $metadata,
        ]);
    }

    public static function createDoctorMessage($sessionId, $message, $metadata = [])
    {
        return static::create([
            'chat_session_id' => $sessionId,
            'message' => $message,
            'sender_type' => 'doctor',
            'is_read' => true, // Doctor messages are automatically read
            'metadata' => $metadata,
        ]);
    }

    public static function createBotMessage($sessionId, $message, $metadata = [])
    {
        return static::create([
            'chat_session_id' => $sessionId,
            'message' => $message,
            'sender_type' => 'bot',
            'is_read' => true, // Bot messages are automatically read
            'metadata' => $metadata,
        ]);
    }
}
