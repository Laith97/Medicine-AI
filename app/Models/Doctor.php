<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialty_id',
        'license_number',
        'phone',
        'bio',
        'profile_image',
        'languages',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'latitude',
        'longitude',
        'consultation_fee',
        'appointment_duration',
        'auto_approve_appointments',
        'allow_cancellation',
        'allow_rescheduling',
        'cancellation_hours',
        'average_rating',
        'total_reviews',
        'is_active',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'languages' => 'array',
        'consultation_fee' => 'integer',
        'appointment_duration' => 'integer',
        'cancellation_hours' => 'integer',
        'auto_approve_appointments' => 'boolean',
        'allow_cancellation' => 'boolean',
        'allow_rescheduling' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'average_rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user that owns the doctor profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the specialty of the doctor
     */
    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Get availability slots for the doctor
     */
    public function availabilitySlots()
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    /**
     * Get active availability slots
     */
    public function activeAvailabilitySlots()
    {
        return $this->hasMany(AvailabilitySlot::class)->where('is_active', true);
    }

    /**
     * Get appointments for the doctor
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get reviews for the doctor
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get approved reviews
     */
    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    /**
     * Get Google account
     */
    public function googleAccount()
    {
        return $this->hasOne(GoogleAccount::class);
    }

    /**
     * Scope for active doctors
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for verified doctors
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get consultation fee in dollars
     */
    public function getConsultationFeeDollarsAttribute()
    {
        return $this->consultation_fee / 100;
    }

    /**
     * Get available time slots for a specific date
     */
    public function getAvailableSlots($date)
    {
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));

        $availabilitySlots = $this->activeAvailabilitySlots()
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_from')
                      ->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', $date);
            })
            ->get();

        $slots = [];
        foreach ($availabilitySlots as $slot) {
            $startTime = Carbon::parse($date . ' ' . $slot->start_time);
            $endTime = Carbon::parse($date . ' ' . $slot->end_time);

            while ($startTime->lt($endTime)) {
                $slotEnd = $startTime->copy()->addMinutes($slot->slot_duration);

                if ($slotEnd->lte($endTime)) {
                    // Check if slot is already booked
                    $existingAppointments = $this->appointments()
                        ->where('appointment_date', $startTime)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->count();

                    if ($existingAppointments < $slot->max_bookings_per_slot) {
                        $slots[] = [
                            'start_time' => $startTime->format('H:i'),
                            'end_time' => $slotEnd->format('H:i'),
                            'datetime' => $startTime->toDateTimeString(),
                            'available' => true
                        ];
                    }
                }

                $startTime->addMinutes($slot->slot_duration);
            }
        }

        return collect($slots)->sortBy('start_time')->values();
    }

    /**
     * Update doctor's rating
     */
    public function updateRating()
    {
        $reviews = $this->approvedReviews();
        $this->total_reviews = $reviews->count();
        $this->average_rating = $reviews->avg('rating') ?: 0;
        $this->save();
    }

    /**
     * Check if doctor can be cancelled within hours
     */
    public function canCancelWithinHours($appointmentDate)
    {
        if (!$this->allow_cancellation) {
            return false;
        }

        $hoursUntilAppointment = Carbon::now()->diffInHours(Carbon::parse($appointmentDate));
        return $hoursUntilAppointment >= $this->cancellation_hours;
    }
}
