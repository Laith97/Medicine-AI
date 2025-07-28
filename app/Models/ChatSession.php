<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'doctor_id',
        'visitor_name',
        'visitor_email',
        'visitor_ip',
        'visitor_user_agent',
        'is_active',
        'last_activity_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->session_id)) {
                $session->session_id = static::generateSessionId();
            }

            $session->last_activity_at = now();
        });
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function unreadMessages()
    {
        return $this->hasMany(ChatMessage::class)
                    ->where('sender_type', 'visitor')
                    ->where('is_read', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeWithUnreadMessages($query)
    {
        return $query->whereHas('unreadMessages');
    }

    public function updateActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function markMessagesAsRead()
    {
        $this->messages()
             ->where('sender_type', 'visitor')
             ->where('is_read', false)
             ->update(['is_read' => true]);
    }

    public function getHasUnreadMessagesAttribute()
    {
        return $this->unreadMessages()->exists();
    }

    public function getLastMessageAttribute()
    {
        $latestMessage = $this->latestMessage;
        return $latestMessage ? $latestMessage->message : 'No messages yet';
    }

    public function getDisplayNameAttribute()
    {
        return $this->visitor_name ?: 'Anonymous Visitor';
    }

    public static function generateSessionId()
    {
        do {
            $sessionId = 'chat_' . uniqid() . '_' . random_int(1000, 9999);
        } while (static::where('session_id', $sessionId)->exists());

        return $sessionId;
    }

    public static function findOrCreateForVisitor($doctorId, $visitorData)
    {
        // Try to find existing active session for this visitor
        $session = static::where('doctor_id', $doctorId)
                         ->where('visitor_ip', $visitorData['ip'])
                         ->where('is_active', true)
                         ->where('last_activity_at', '>', now()->subHours(24))
                         ->first();

        if (!$session) {
            $session = static::create([
                'doctor_id' => $doctorId,
                'visitor_name' => $visitorData['name'] ?? null,
                'visitor_email' => $visitorData['email'] ?? null,
                'visitor_ip' => $visitorData['ip'],
                'visitor_user_agent' => $visitorData['user_agent'] ?? null,
            ]);
        } else {
            // Update visitor info if provided
            $updateData = [];
            if (!empty($visitorData['name']) && empty($session->visitor_name)) {
                $updateData['visitor_name'] = $visitorData['name'];
            }
            if (!empty($visitorData['email']) && empty($session->visitor_email)) {
                $updateData['visitor_email'] = $visitorData['email'];
            }

            if (!empty($updateData)) {
                $session->update($updateData);
            }
        }

        return $session;
    }
}
