<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KioskCheckin extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'kiosk_session_id',
        'checkin_time',
        'verification_method',
        'verification_data',
    ];

    protected $casts = [
        'checkin_time' => 'datetime',
        'verification_data' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($checkin) {
            if (empty($checkin->checkin_time)) {
                $checkin->checkin_time = now();
            }
        });
    }

    /**
     * Get the appointment for this checkin
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the kiosk session for this checkin
     */
    public function kioskSession()
    {
        return $this->belongsTo(KioskSession::class, 'kiosk_session_id', 'session_id');
    }

    /**
     * Scope for QR code verifications
     */
    public function scopeQrCode($query)
    {
        return $query->where('verification_method', 'qr_code');
    }

    /**
     * Scope for ID card verifications
     */
    public function scopeIdCard($query)
    {
        return $query->where('verification_method', 'id_card');
    }

    /**
     * Scope for biometric verifications
     */
    public function scopeBiometric($query)
    {
        return $query->where('verification_method', 'biometric');
    }

    /**
     * Scope for manual verifications
     */
    public function scopeManual($query)
    {
        return $query->where('verification_method', 'manual');
    }

    /**
     * Get verification data value
     */
    public function getVerificationData($key, $default = null)
    {
        return data_get($this->verification_data, $key, $default);
    }

    /**
     * Set verification data value
     */
    public function setVerificationData($key, $value)
    {
        $data = $this->verification_data ?? [];
        data_set($data, $key, $value);
        $this->update(['verification_data' => $data]);
    }
}
