<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\AppointmentBroadcastService;

/**
 * Appointment Model
 *
 * Represents medical appointments in the MedcuraAI system. Supports both
 * registered patients and guest appointments with comprehensive status
 * tracking, payment processing, and real-time updates.
 *
 * Key Features:
 * - Multi-status appointment lifecycle (pending, confirmed, completed, cancelled, no_show)
 * - Guest appointment support with email verification
 * - Real-time status broadcasting
 * - Payment processing integration
 * - Reminder system
 * - Kiosk integration
 * - Optimistic locking with versioning
 *
 * @property int $id Unique identifier for the appointment
 * @property int|null $patient_id ID of the registered patient (null for guest appointments)
 * @property int $doctor_id ID of the doctor
 * @property \Carbon\Carbon $appointment_date Scheduled date and time
 * @property string $status Current status (pending, confirmed, completed, cancelled, no_show)
 * @property string|null $appointment_type Type of appointment
 * @property int|null $duration Duration in minutes
 * @property int|null $fee Appointment fee in cents
 * @property string|null $notes Additional notes
 * @property string|null $cancellation_reason Reason for cancellation
 * @property int|null $cancelled_by ID of user who cancelled
 * @property \Carbon\Carbon|null $cancelled_at Cancellation timestamp
 * @property \Carbon\Carbon|null $confirmed_at Confirmation timestamp
 * @property \Carbon\Carbon|null $completed_at Completion timestamp
 * @property string|null $payment_status Payment status
 * @property string|null $payment_intent_id Stripe payment intent ID
 * @property string|null $meeting_link Video meeting link
 * @property string|null $meeting_id Meeting identifier
 * @property \Carbon\Carbon|null $reminder_sent_at Reminder sent timestamp
 * @property bool $follow_up_required Whether follow-up is required
 * @property \Carbon\Carbon|null $follow_up_date Follow-up date
 * @property bool $prescription_given Whether prescription was given
 * @property int|null $visit_number Visit number for patient
 * @property string $appointment_number Unique appointment number
 * @property \Carbon\Carbon|null $appointment_end End time (calculated or stored)
 * @property string|null $reason Reason for visit
 * @property string|null $symptoms Patient symptoms
 * @property string|null $doctor_notes Doctor's notes
 * @property string|null $patient_notes Patient's notes
 * @property int|null $consultation_fee Consultation fee in cents
 * @property bool $reminder_sent Whether reminder was sent
 * @property int|null $kiosk_id Associated kiosk ID
 * @property int $version Version number for optimistic locking
 * @property string|null $guest_name Guest patient name
 * @property string|null $guest_email Guest patient email
 * @property string|null $guest_phone Guest patient phone
 * @property \Carbon\Carbon|null $guest_date_of_birth Guest patient date of birth
 * @property string|null $guest_gender Guest patient gender
 * @property string|null $guest_address Guest patient address
 * @property string|null $verification_token Email verification token
 * @property \Carbon\Carbon|null $token_expires_at Token expiration time
 * @property bool $is_verified Whether guest appointment is verified
 * @property \Carbon\Carbon $created_at Creation timestamp
 * @property \Carbon\Carbon $updated_at Last update timestamp
 *
 * Relationships:
 * @property-read \App\Models\Doctor $doctor Associated doctor
 * @property-read \App\Models\User|null $patient Associated registered patient
 * @property-read \App\Models\Review|null $review Appointment review
 * @property-read \Illuminate\Database\Eloquent\Collection $prescriptions Appointment prescriptions
 * @property-read \App\Models\Kiosk|null $kiosk Associated kiosk
 */
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
        'kiosk_id',
        'version',
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

        // Observe status changes for broadcasting and version increment
        static::updating(function ($appointment) {
            if ($appointment->isDirty()) {
                // Increment version for optimistic locking
                $appointment->version = $appointment->version + 1;

                // NOTE: Status change broadcasting is handled by AppointmentObserver::updated()
                // to avoid duplicate broadcasts from both model callbacks and observer.
                // The model-level broadcast in methods like cancel(), confirm(), complete()
                // has been removed to prevent duplicates.
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
     * Get the prescriptions for this appointment
     */
    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get the diagnosis for this appointment
     */
    public function diagnosis()
    {
        return $this->hasOne(Diagnosis::class);
    }

    /**
     * Get all diagnoses for this appointment
     */
    public function diagnoses()
    {
        return $this->hasMany(Diagnosis::class)->orderBy('created_at', 'desc');
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
        if (in_array($this->status, ['cancelled', 'completed', 'no_show'])) {
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
        if (in_array($this->status, ['cancelled', 'completed', 'no_show'])) {
            return false;
        }

        return true;
    }

    /**
     * Cancel the appointment
     *
     * Updates the appointment status to cancelled, records the cancellation
     * details, and broadcasts the status change. Fires additional events
     * if the appointment was previously confirmed.
     *
     * @param string|null $reason Optional reason for cancellation
     * @param int|null $cancelledBy ID of the user who cancelled the appointment
     * @return void
     * @throws \Exception If concurrent update is detected
     */
    public function cancel($reason = null, $cancelledBy = null)
    {
        DB::transaction(function () use ($reason, $cancelledBy) {
            $wasConfirmed = $this->isConfirmed();
            $oldStatus = $this->status;

            $result = $this->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy,
                'cancellation_reason' => $reason,
            ]);

            if (!$result) {
                throw new \Exception('Concurrent update detected - appointment was modified by another process');
            }

            // NOTE: Status change broadcasting is handled by AppointmentObserver::updated()
            // to avoid duplicate broadcasts from model methods
        });
    }

    /**
     * Confirm the appointment
     *
     * Updates the appointment status to confirmed and broadcasts the status change.
     * This method handles the transition from pending to confirmed status.
     *
     * @return void
     * @throws \Exception If concurrent update is detected
     */
    public function confirm()
    {
        DB::transaction(function () {
            $oldStatus = $this->status;

            $result = $this->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            if (!$result) {
                throw new \Exception('Concurrent update detected - appointment was modified by another process');
            }

            // Auto-assign primary doctor to patient if not already assigned
            if ($this->patient && !$this->patient->primary_doctor_id) {
                $this->patient->update(['primary_doctor_id' => $this->doctor->user_id]);
            }

            // NOTE: Status change broadcasting is handled by AppointmentObserver::updated()
        });
    }

    /**
     * Complete the appointment
     *
     * Marks the appointment as completed, records the completion time,
     * broadcasts the status change, and fires completion events for
     * slot availability monitoring.
     *
     * @return void
     * @throws \Exception If concurrent update is detected
     */
    public function complete()
    {
        DB::transaction(function () {
            $oldStatus = $this->status;

            $result = $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            if (!$result) {
                throw new \Exception('Concurrent update detected - appointment was modified by another process');
            }

            // NOTE: Status change broadcasting is handled by AppointmentObserver::updated()
            // Fire completion event for slot availability monitoring
            event(new \App\Events\AppointmentCompletedEvent($this));
        });
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

        // If appointment_end is missing or appears corrupted (far in the future),
        // fall back to using the doctor's appointment_duration
        if (!$this->appointment_end || $this->appointment_end->gt(now()->addDays(2))) {
            if ($this->doctor && $this->doctor->appointment_duration) {
                return $this->appointment_date->copy()->addMinutes($this->doctor->appointment_duration);
            }
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
        DB::transaction(function () {
            $oldStatus = $this->status;

            $result = $this->update([
                'status' => 'no_show',
            ]);

            if (!$result) {
                throw new \Exception('Concurrent update detected - appointment was modified by another process');
            }

            // NOTE: Status change broadcasting is handled by AppointmentObserver::updated()
        });
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

    /**
     * Get the kiosk for this appointment
     */
    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class);
    }

    /**
     * Get the kiosk checkins for this appointment
     */
    public function kioskCheckins()
    {
        return $this->hasMany(KioskCheckin::class);
    }

    /**
     * Get the kiosk payments for this appointment
     */
    public function kioskPayments()
    {
        return $this->hasMany(KioskPayment::class);
    }

    /**
     * Confirm the appointment (alias for confirm method for backward compatibility)
     *
     * @return void
     * @throws \Exception If concurrent update is detected
     */
    public function confirmAppointment()
    {
        return $this->confirm();
    }

    /**
     * Cancel the appointment (alias for cancel method for backward compatibility)
     *
     * @param string|null $reason Optional reason for cancellation
     * @param int|null $cancelledBy ID of the user who cancelled the appointment
     * @return void
     * @throws \Exception If concurrent update is detected
     */
    public function cancelAppointment($reason = null, $cancelledBy = null)
    {
        return $this->cancel($reason, $cancelledBy);
    }
}
