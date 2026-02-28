<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistPatientPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'preferred_times',
        'preferred_days',
        'service_priorities',
        'notification_settings',
        'auto_accept_threshold',
        'max_travel_distance',
        'preferred_location_lat',
        'preferred_location_lng',
        'emergency_contact',
        'special_requirements',
    ];

    protected $casts = [
        'preferred_times' => 'array',
        'preferred_days' => 'array',
        'service_priorities' => 'array',
        'notification_settings' => 'array',
        'auto_accept_threshold' => 'integer',
        'max_travel_distance' => 'decimal:2',
        'preferred_location_lat' => 'decimal:8',
        'preferred_location_lng' => 'decimal:8',
    ];

    /**
     * Get the patient that owns the preference
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the doctor that owns the preference
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Scope for preferences by patient
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope for preferences by doctor
     */
    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Check if email notifications are enabled
     */
    public function hasEmailNotifications(): bool
    {
        return $this->notification_settings['email'] ?? false;
    }

    /**
     * Check if SMS notifications are enabled
     */
    public function hasSmsNotifications(): bool
    {
        return $this->notification_settings['sms'] ?? false;
    }

    /**
     * Check if push notifications are enabled
     */
    public function hasPushNotifications(): bool
    {
        return $this->notification_settings['push'] ?? false;
    }

    /**
     * Get priority for a specific service type
     */
    public function getServicePriority(string $serviceType): string
    {
        return $this->service_priorities[$serviceType] ?? 'medium';
    }

    /**
     * Check if preferred time matches
     */
    public function matchesPreferredTime(string $timeSlot): bool
    {
        if (empty($this->preferred_times)) {
            return true;
        }

        // Simple time matching logic - can be enhanced
        $hour = date('H', strtotime($timeSlot));

        foreach ($this->preferred_times as $preferredTime) {
            switch ($preferredTime) {
                case 'morning':
                    if ($hour >= 6 && $hour < 12) return true;
                    break;
                case 'afternoon':
                    if ($hour >= 12 && $hour < 17) return true;
                    break;
                case 'evening':
                    if ($hour >= 17 && $hour < 22) return true;
                    break;
            }
        }

        return false;
    }

    /**
     * Check if preferred day matches
     */
    public function matchesPreferredDay(string $dayOfWeek): bool
    {
        if (empty($this->preferred_days)) {
            return true;
        }

        return in_array(strtolower($dayOfWeek), array_map('strtolower', $this->preferred_days));
    }

    /**
     * Check if slot should be auto-accepted based on threshold
     */
    public function shouldAutoAccept(int $daysUntilSlot): bool
    {
        return $daysUntilSlot <= $this->auto_accept_threshold;
    }
}
