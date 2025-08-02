<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'doctor_id', 'appointment_date', 'status',
        'appointment_type', 'duration', 'fee', 'notes', 'cancellation_reason',
        'cancelled_by', 'cancelled_at', 'confirmed_at', 'completed_at',
        'payment_status', 'payment_intent_id', 'meeting_link', 'meeting_id',
        'reminder_sent_at', 'follow_up_required', 'follow_up_date',
        'prescription_given', 'visit_number',
        'appointment_number',
        'appointment_end',
        'reason',
        'symptoms',
        'doctor_notes',
        'patient_notes',
        'consultation_fee',
        'reminder_sent',
        // Guest patient fields
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_date_of_birth',
        'guest_gender',
        'guest_address',
        'verification_token',
        'token_expires_at',
        'is_verified',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'appointment_end' => 'datetime',
        'consultation_fee' => 'integer',
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'follow_up_required' => 'boolean',
        'guest_date_of_birth' => 'date',
        'token_expires_at' => 'datetime',
        'is_verified' => 'boolean',
        'duration' => 'integer',
        'fee' => 'integer',
        'prescription_given' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'follow_up_date' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($appointment) {
            if (empty($appointment->appointment_number)) {
                $appointment->appointment_number = 'APT-' . strtoupper(uniqid());
            }
        });
    }

    /**
     * Get the doctor for this appointment
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the patient for this appointment
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the review for this appointment
     */
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Scope for upcoming appointments
     */
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>', now());
    }

    /**
     * Scope for past appointments
     */
    public function scopePast($query)
    {
        return $query->where('appointment_date', '<', now());
    }

    /**
     * Scope for today's appointments
     */
    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', today());
    }

    /**
     * Scope for specific status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for confirmed appointments
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope for pending appointments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed appointments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled appointments
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Check if appointment is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if appointment is confirmed
     */
    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if appointment is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if appointment is cancelled
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if appointment is today
     */
    public function isToday()
    {
        return $this->appointment_date->isToday();
    }

    /**
     * Check if appointment is upcoming
     */
    public function isUpcoming()
    {
        return $this->appointment_date->isFuture();
    }

    /**
     * Check if appointment is past
     */
    public function isPast()
    {
        return $this->appointment_date->isPast();
    }

    /**
     * Check if appointment can be cancelled
     */
    public function canBeCancelled()
    {
        if (in_array($this->status, ['cancelled', 'completed'])) {
            return false;
        }

        // Can't cancel past appointments
        if ($this->appointment_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if appointment can be rescheduled
     */
    public function canBeRescheduled()
    {
        if (in_array($this->status, ['cancelled', 'completed'])) {
            return false;
        }

        return true;
    }

    /**
     * Cancel the appointment
     */
    public function cancel($reason = null, $cancelledBy = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy,
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Confirm the appointment
     */
    public function confirm()
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Complete the appointment
     */
    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Reschedule the appointment
     */
    public function reschedule($newDate)
    {
        $this->update([
            'appointment_date' => $newDate,
            'status' => 'pending',
            'confirmed_at' => null,
        ]);
    }

    /**
     * Get duration in hours
     */
    public function getDurationInHours()
    {
        return $this->duration ? $this->duration / 60 : 0;
    }

    /**
     * Get end time based on duration
     */
    public function getEndTime()
    {
        if ($this->duration) {
            return $this->appointment_date->copy()->addMinutes($this->duration);
        }
        return $this->appointment_end;
    }

    /**
     * Check if appointment needs reminder
     */
    public function needsReminder()
    {
        $reminderSent = $this->reminder_sent || $this->reminder_sent_at;
        return !$reminderSent && $this->appointment_date->isFuture();
    }

    /**
     * Mark reminder as sent
     */
    public function markReminderSent()
    {
        $this->update([
            'reminder_sent' => true,
            'reminder_sent_at' => now(),
        ]);
    }

    /**
     * Mark as no show
     */
    public function markAsNoShow()
    {
        $this->update([
            'status' => 'no_show',
        ]);
    }

    /**
     * Get formatted appointment date
     */
    public function getFormattedDateAttribute()
    {
        return $this->appointment_date->format('M j, Y g:i A');
    }

    /**
     * Get formatted appointment time
     */
    public function getFormattedTimeAttribute()
    {
        return $this->appointment_date->format('g:i A');
    }

    /**
     * Get fee in dollars
     */
    public function getFeeDollarsAttribute()
    {
        return $this->fee ? $this->fee / 100 : 0;
    }

    /**
     * Get consultation fee in dollars
     */
    public function getConsultationFeeDollarsAttribute()
    {
        return $this->consultation_fee ? $this->consultation_fee / 100 : 0;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'confirmed' => 'primary',
            'cancelled' => 'danger',
            'completed' => 'success',
            'no_show' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Check if appointment is in the past
     */
    public function getIsPastAttribute()
    {
        return $this->appointment_date->isPast();
    }

    /**
     * Check if appointment is today
     */
    public function getIsTodayAttribute()
    {
        return $this->appointment_date->isToday();
    }

    /**
     * Check if appointment is upcoming
     */
    public function getIsUpcomingAttribute()
    {
        return $this->appointment_date->isFuture();
    }

    /**
     * Check if this is a guest appointment
     */
    public function isGuestAppointment()
    {
        return is_null($this->patient_id) && !empty($this->guest_email);
    }

    /**
     * Get patient name (registered or guest)
     */
    public function getPatientNameAttribute()
    {
        return $this->patient ? $this->patient->name : $this->guest_name;
    }

    /**
     * Get patient email (registered or guest)
     */
    public function getPatientEmailAttribute()
    {
        return $this->patient ? $this->patient->email : $this->guest_email;
    }

    /**
     * Get patient phone (registered or guest)
     */
    public function getPatientPhoneAttribute()
    {
        return $this->patient ? $this->patient->phone : $this->guest_phone;
    }

    /**
     * Generate verification token for guest appointments
     */
    public function generateVerificationToken()
    {
        $this->verification_token = bin2hex(random_bytes(32));
        $this->token_expires_at = now()->addHours(24);
        $this->save();

        return $this->verification_token;
    }

    /**
     * Verify guest appointment with token
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
     * Scope for guest appointments
     */
    public function scopeGuest($query)
    {
        return $query->whereNull('patient_id')->whereNotNull('guest_email');
    }

    /**
     * Scope for registered patient appointments
     */
    public function scopeRegistered($query)
    {
        return $query->whereNotNull('patient_id');
    }

    /**
     * Scope for appointments by guest email
     */
    public function scopeByGuestEmail($query, $email)
    {
        return $query->where('guest_email', $email);
    }
}
