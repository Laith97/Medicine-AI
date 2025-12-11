<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'description',
        'default_enabled',
        'default_channels',
        'icon',
        'color',
        'category',
    ];

    protected $casts = [
        'default_enabled' => 'boolean',
        'default_channels' => 'array',
    ];

    /**
     * Get the notifications for this type
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the notification preferences for this type
     */
    public function preferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * Scope for enabled types
     */
    public function scopeEnabled($query)
    {
        return $query->where('default_enabled', true);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for general category
     */
    public function scopeGeneral($query)
    {
        return $query->where('category', 'general');
    }

    /**
     * Scope for appointment category
     */
    public function scopeAppointment($query)
    {
        return $query->where('category', 'appointment');
    }

    /**
     * Scope for payment category
     */
    public function scopePayment($query)
    {
        return $query->where('category', 'payment');
    }

    /**
     * Scope for kiosk category
     */
    public function scopeKiosk($query)
    {
        return $query->where('category', 'kiosk');
    }

    /**
     * Check if type is enabled by default
     */
    public function isEnabledByDefault()
    {
        return $this->default_enabled;
    }

    /**
     * Get default channels as array
     */
    public function getDefaultChannels()
    {
        return $this->default_channels ?? ['database'];
    }

    /**
     * Check if channel is enabled by default
     */
    public function isChannelEnabledByDefault($channel)
    {
        $channels = $this->getDefaultChannels();
        return in_array($channel, $channels);
    }
}
