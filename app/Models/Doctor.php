<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Doctor extends Model
{
    use HasFactory;

    /**
     * @property int $id
     * @property int $user_id
     * @property int|null $specialty_id
     * @property string|null $license_number
     * @property string|null $phone
     * @property string|null $bio
     * @property string|null $profile_image
     * @property array|null $languages
     * @property string|null $address
     * @property string|null $city
     * @property string|null $state
     * @property string|null $zip_code
     * @property string|null $country
     * @property string|null $latitude
     * @property string|null $longitude
     * @property int $consultation_fee
     * @property int $appointment_duration
     * @property bool $auto_approve_appointments
     * @property bool $allow_cancellation
     * @property bool $allow_rescheduling
     * @property int $cancellation_hours
     * @property float $average_rating
     * @property int $total_reviews
     * @property bool $is_active
     * @property bool $is_verified
     * @property \Carbon\Carbon|null $verified_at
     * @property array|null $appointment_type_preferences
     * @property bool $ai_chat_enabled
     * @property array|null $ai_chat_settings
     */

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
        'appointment_type_preferences',
        'ai_chat_enabled',
        'ai_chat_settings',
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
        'appointment_type_preferences' => 'array',
        'ai_chat_enabled' => 'boolean',
        'ai_chat_settings' => 'array',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'appointment_type_preferences' => '{"in_person": true, "video_call": false, "phone_call": false}',
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
     * Get landing page
     */
    public function landingPage()
    {
        return $this->hasOne(DoctorLandingPage::class);
    }

    /**
     * Get blog posts
     */
    public function blogPosts()
    {
        return $this->hasMany(DoctorBlogPost::class);
    }

    /**
     * Get published blog posts
     */
    public function publishedBlogPosts()
    {
        return $this->hasMany(DoctorBlogPost::class)->published();
    }

    /**
     * Get chat sessions
     */
    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Get active chat sessions
     */
    public function activeChatSessions()
    {
        return $this->hasMany(ChatSession::class)->active();
    }

    /**
     * Get chat sessions with unread messages
     */
    public function unreadChatSessions()
    {
        return $this->hasMany(ChatSession::class)->withUnreadMessages();
    }

    /**
     * Get landing page visits
     */
    public function landingPageVisits()
    {
        return $this->hasMany(LandingPageVisit::class);
    }

    /**
     * Get public reviews for landing page
     */
    public function publicReviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->verified();
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
     * Get full address (method version)
     */
    public function getFullAddress()
    {
        return $this->getFullAddressAttribute();
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

    /**
     * Get enabled appointment types
     */
    public function getEnabledAppointmentTypes()
    {
        $preferences = $this->appointment_type_preferences ?? [
            'in_person' => true,
            'video_call' => false,
            'phone_call' => false
        ];

        return array_keys(array_filter($preferences));
    }

    /**
     * Check if an appointment type is enabled
     */
    public function isAppointmentTypeEnabled($type)
    {
        $preferences = $this->appointment_type_preferences ?? [
            'in_person' => true,
            'video_call' => false,
            'phone_call' => false
        ];

        return $preferences[$type] ?? false;
    }

    /**
     * Get appointment type preferences with defaults
     */
    public function getAppointmentTypePreferences()
    {
        return $this->appointment_type_preferences ?? [
            'in_person' => true,
            'video_call' => false,
            'phone_call' => false
        ];
    }

    /**
     * Update appointment type preferences
     */
    public function updateAppointmentTypePreferences($preferences)
    {
        $this->appointment_type_preferences = $preferences;
        $this->save();
    }
}
