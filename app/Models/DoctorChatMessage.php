<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorChatMessage extends Model
{
    protected $fillable = [
        'doctor_id',
        'session_id',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'message',
        'sender_type',
        'is_read',
        'metadata'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Get the doctor that owns the chat message
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for messages by session
     */
    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for visitor messages
     */
    public function scopeFromVisitor($query)
    {
        return $query->where('sender_type', 'visitor');
    }

    /**
     * Scope for bot messages
     */
    public function scopeFromBot($query)
    {
        return $query->where('sender_type', 'bot');
    }

    /**
     * Scope for doctor messages
     */
    public function scopeFromDoctor($query)
    {
        return $query->where('sender_type', 'doctor');
    }

    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Get conversation messages
     */
    public static function getConversation($doctorId, $sessionId)
    {
        return static::where('doctor_id', $doctorId)
                    ->where('session_id', $sessionId)
                    ->orderBy('created_at', 'asc')
                    ->get();
    }

    /**
     * Generate unique session ID
     */
    public static function generateSessionId()
    {
        return 'chat_' . uniqid() . '_' . time();
    }
}
