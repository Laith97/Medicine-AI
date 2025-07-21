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
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_anonymous' => 'boolean',
        'is_approved' => 'boolean',
        'posted_to_google' => 'boolean',
        'google_posted_at' => 'datetime',
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

        return $this->patient->name;
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
}
