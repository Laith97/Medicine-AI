<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'appointment_number',
        'appointment_date',
        'appointment_end',
        'status',
        'reason',
        'symptoms',
        'doctor_notes',
        'patient_notes',
        'consultation_fee',
        'appointment_type',
        'meeting_link',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'confirmed_at',
        'completed_at',
        'reminder_sent',
        'follow_up_required',
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
     * Check if appointment can be cancelled
     */
    public function canBeCancelled()
    {
        if (in_array($this->status, ['cancelled', 'completed'])) {
            return false;
        }

        return $this->doctor->canCancelWithinHours($this->appointment_date);
    }

    /**
     * Check if appointment can be rescheduled
     */
    public function canBeRescheduled()
    {
        if (!$this->doctor->allow_rescheduling) {
            return false;
        }

        if (in_array($this->status, ['cancelled', 'completed'])) {
            return false;
        }

        return $this->doctor->canCancelWithinHours($this->appointment_date);
    }

    /**
     * Cancel the appointment
     */
    public function cancel($cancelledBy, $reason = null)
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
        return $this->appointment_date->format('M d, Y');
    }

    /**
     * Get formatted appointment time
     */
    public function getFormattedTimeAttribute()
    {
        return $this->appointment_date->format('g:i A') . ' - ' . $this->appointment_end->format('g:i A');
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
            'pending' => 'yellow',
            'confirmed' => 'green',
            'cancelled' => 'red',
            'completed' => 'blue',
            'no_show' => 'gray',
            default => 'gray'
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
}
