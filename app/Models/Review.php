<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'appointment_id',
        'rating',
        'comment',
        'is_anonymous',
        'is_approved',
        'posted_to_google',
        'google_review_id',
        'google_posted_at',
        'source',
        // Guest reviewer fields
        'guest_name',
        'guest_email',
        'verification_token',
        'token_expires_at',
        'is_verified',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_anonymous' => 'boolean',
        'is_approved' => 'boolean',
        'posted_to_google' => 'boolean',
        'google_posted_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            // Update doctor's rating when a new review is created
            $review->doctor->updateRating();
        });

        static::updated(function ($review) {
            // Update doctor's rating when a review is updated
            if ($review->isDirty(['rating', 'is_approved'])) {
                $review->doctor->updateRating();
            }
        });

        static::deleted(function ($review) {
            // Update doctor's rating when a review is deleted
            $review->doctor->updateRating();
        });
    }

    /**
     * Get the doctor for this review
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the patient for this review
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the appointment for this review
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Scope for approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for pending reviews
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope for MedCura reviews
     */
    public function scopeMedcura($query)
    {
        return $query->where('source', 'medcura');
    }

    /**
     * Scope for Google reviews
     */
    public function scopeGoogle($query)
    {
        return $query->where('source', 'google');
    }

    /**
     * Scope for specific rating
     */
    public function scopeRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Get star rating as HTML
     */
    public function getStarsHtmlAttribute()
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $stars .= '<i class="fas fa-star text-yellow-400"></i>';
            } else {
                $stars .= '<i class="far fa-star text-gray-300"></i>';
            }
        }
        return $stars;
    }

    /**
     * Get patient display name
     */
    public function getPatientDisplayNameAttribute()
    {
        if ($this->is_anonymous) {
            return 'Anonymous Patient';
        }

        return $this->patient ? $this->patient->name : $this->guest_name;
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('M d, Y');
    }

    /**
     * Get rating text
     */
    public function getRatingTextAttribute()
    {
        return match($this->rating) {
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Very Good',
            5 => 'Excellent',
            default => 'Not Rated'
        };
    }

    /**
     * Approve the review
     */
    public function approve()
    {
        $this->update(['is_approved' => true]);
    }

    /**
     * Reject the review
     */
    public function reject()
    {
        $this->update(['is_approved' => false]);
    }

    /**
     * Mark as posted to Google
     */
    public function markAsPostedToGoogle($googleReviewId = null)
    {
        $this->update([
            'posted_to_google' => true,
            'google_review_id' => $googleReviewId,
            'google_posted_at' => now(),
        ]);
    }

    /**
     * Check if this is a guest review
     */
    public function isGuestReview()
    {
        return is_null($this->patient_id) && !empty($this->guest_email);
    }

    /**
     * Generate verification token for guest reviews
     */
    public function generateVerificationToken()
    {
        $this->verification_token = bin2hex(random_bytes(32));
        $this->token_expires_at = now()->addHours(24);
        $this->save();

        return $this->verification_token;
    }

    /**
     * Verify guest review with token
     */
    public function verifyWithToken($token)
    {
        if ($this->verification_token === $token &&
            $this->token_expires_at &&
            $this->token_expires_at->isFuture()) {

            $this->is_verified = true;
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Scope for guest reviews
     */
    public function scopeGuest($query)
    {
        return $query->whereNull('patient_id')->whereNotNull('guest_email');
    }

    /**
     * Scope for registered patient reviews
     */
    public function scopeRegistered($query)
    {
        return $query->whereNotNull('patient_id');
    }

    /**
     * Scope for verified reviews (both registered and verified guest reviews)
     */
    public function scopeVerified($query)
    {
        return $query->where(function($q) {
            $q->whereNotNull('patient_id')
              ->orWhere('is_verified', true);
        });
    }
}
