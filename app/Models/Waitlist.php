<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Waitlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'service_type',
        'priority_level',
        'preferred_time_slots',
        'preferred_days',
        'max_wait_days',
        'notification_channels',
        'status',
    ];

    protected $casts = [
        'preferred_time_slots' => 'array',
        'preferred_days' => 'array',
        'notification_channels' => 'array',
        'max_wait_days' => 'integer',
    ];

    /**
     * Get the patient that owns the waitlist
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the doctor that owns the waitlist
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the waitlist entries for the waitlist
     */
    public function entries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }

    /**
     * Get the patient preferences for the waitlist
     */
    public function patientPreferences(): HasMany
    {
        return $this->hasMany(WaitlistPatientPreference::class, 'patient_id', 'patient_id');
    }

    /**
     * Scope for active waitlists
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for waitlists by doctor
     */
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope for waitlists by patient
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Check if waitlist is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if waitlist is paused
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Check if waitlist is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if waitlist is fulfilled
     */
    public function isFulfilled(): bool
    {
        return $this->status === 'fulfilled';
    }

    /**
     * Pause the waitlist
     */
    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    /**
     * Resume the waitlist
     */
    public function resume(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Cancel the waitlist
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Fulfill the waitlist
     */
    public function fulfill(): void
    {
        $this->update(['status' => 'fulfilled']);
    }
}
