<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorKioskConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'clinic_name',
        'clinic_address',
        'contact_phone',
        'kiosk_display_name',
        'primary_color',
        'secondary_color',
        'auto_approve_appointments',
        'require_payment_upfront',
        'voice_instructions_enabled',
        'high_contrast_mode',
        'kiosk_token',
        'is_active',
        'additional_settings',
    ];

    protected $casts = [
        'auto_approve_appointments' => 'boolean',
        'require_payment_upfront' => 'boolean',
        'voice_instructions_enabled' => 'boolean',
        'high_contrast_mode' => 'boolean',
        'is_active' => 'boolean',
        'additional_settings' => 'array',
    ];

    /**
     * Get the doctor that owns the kiosk config
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
