<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kiosk extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'serial_number',
        'status',
        'configuration',
        'last_ping',
    ];

    protected $casts = [
        'configuration' => 'array',
        'last_ping' => 'datetime',
    ];

    /**
     * Get the sessions for this kiosk
     */
    public function sessions()
    {
        return $this->hasMany(KioskSession::class);
    }

    /**
     * Get the appointments for this kiosk
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Scope for active kiosks
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive kiosks
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Check if kiosk is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Update last ping timestamp
     */
    public function updateLastPing()
    {
        $this->update(['last_ping' => now()]);
    }

    /**
     * Check if kiosk is online (pinged within last 5 minutes)
     */
    public function isOnline()
    {
        return $this->last_ping && $this->last_ping->isAfter(now()->subMinutes(5));
    }
}
