<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class KioskSession extends Model
{
    use HasFactory;

    protected $primaryKey = 'session_id';

    protected $fillable = [
        'session_id',
        'kiosk_id',
        'start_time',
        'end_time',
        'status',
        'session_data',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'session_data' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->start_time)) {
                $session->start_time = now();
            }
        });
    }

    /**
     * Get the kiosk for this session
     */
    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class);
    }

    /**
     * Get the checkins for this session
     */
    public function checkins()
    {
        return $this->hasMany(KioskCheckin::class, 'kiosk_session_id', 'session_id');
    }

    /**
     * Get the payments for this session
     */
    public function payments()
    {
        return $this->hasMany(KioskPayment::class, 'kiosk_session_id', 'session_id');
    }

    /**
     * Get the appointments associated with this session through check-ins
     */
    public function appointments()
    {
        return $this->hasManyThrough(
            Appointment::class,
            KioskCheckin::class,
            'kiosk_session_id', // Foreign key on kiosk_checkins table
            'id',               // Foreign key on appointments table
            'session_id',       // Local key on kiosk_sessions table
            'appointment_id'    // Local key on kiosk_checkins table
        );
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for abandoned sessions
     */
    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned');
    }

    /**
     * Check if session is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if session is expired (no activity for 30 minutes)
     */
    public function isExpired()
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->start_time->isBefore(now()->subMinutes(30));
    }

    /**
     * End the session
     */
    public function end($status = 'completed')
    {
        $this->update([
            'status' => $status,
            'end_time' => now(),
        ]);
    }

    /**
     * Get session duration in minutes
     */
    public function getDurationInMinutes()
    {
        $endTime = $this->end_time ?? now();
        return $this->start_time->diffInMinutes($endTime);
    }

    /**
     * Get session data value
     */
    public function getSessionData($key, $default = null)
    {
        return data_get($this->session_data, $key, $default);
    }

    /**
     * Set session data value
     */
    public function setSessionData($key, $value)
    {
        $data = $this->session_data ?? [];
        data_set($data, $key, $value);
        $this->update(['session_data' => $data]);
    }
}
