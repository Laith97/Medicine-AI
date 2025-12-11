<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * OnDeckAppointment Model
 *
 * Represents appointments in the real-time queue/deck system for tracking
 * appointment status, queue position, and estimated wait times.
 *
 * Key Features:
 * - Real-time queue position tracking
 * - Status workflow management (waiting, ready, in-progress, completed, no-show)
 * - Risk scoring for patient prioritization
 * - Estimated wait time calculations
 * - Integration with appointment broadcasting system
 *
 * @property int $id Unique identifier
 * @property int $appointment_id Associated appointment ID
 * @property int $doctor_id Doctor ID
 * @property int $patient_id Patient ID
 * @property string $status Current status (waiting, ready, in-progress, completed, no-show)
 * @property int $position Queue position (1-based)
 * @property int|null $estimated_wait_minutes Estimated wait time in minutes
 * @property float|null $risk_score Risk assessment score (0-1)
 * @property string|null $risk_factors JSON-encoded risk factors
 * @property \Carbon\Carbon $created_at Creation timestamp
 * @property \Carbon\Carbon $updated_at Last update timestamp
 * @property \Carbon\Carbon|null $deleted_at Soft delete timestamp
 *
 * Relationships:
 * @property-read \App\Models\Appointment $appointment Associated appointment
 * @property-read \App\Models\Doctor $doctor Associated doctor
 * @property-read \App\Models\User $patient Associated patient
 */
class OnDeckAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'status',
        'position',
        'estimated_wait_minutes',
        'risk_score',
        'risk_factors',
    ];

    protected $casts = [
        'position' => 'integer',
        'estimated_wait_minutes' => 'integer',
        'risk_score' => 'float',
        'risk_factors' => 'array',
        'appointment_id' => 'integer',
        'doctor_id' => 'integer',
        'patient_id' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-assign position when creating
        static::creating(function ($onDeckAppointment) {
            if (is_null($onDeckAppointment->position)) {
                $onDeckAppointment->position = static::getNextPosition($onDeckAppointment->doctor_id);
            }
        });

        // Update positions when deleting or changing doctor
        static::deleting(function ($onDeckAppointment) {
            static::reorderPositions($onDeckAppointment->doctor_id, $onDeckAppointment->position);
        });

        static::updating(function ($onDeckAppointment) {
            // If position changed, reorder positions
            if ($onDeckAppointment->isDirty('position') || $onDeckAppointment->isDirty('doctor_id')) {
                $oldDoctorId = $onDeckAppointment->getOriginal('doctor_id');
                $oldPosition = $onDeckAppointment->getOriginal('position');
                $newDoctorId = $onDeckAppointment->doctor_id;
                $newPosition = $onDeckAppointment->position;

                // Remove from old position
                static::reorderPositions($oldDoctorId, $oldPosition);

                // Add to new position
                if ($oldDoctorId !== $newDoctorId) {
                    static::insertAtPosition($newDoctorId, $newPosition);
                }
            }
        });
    }

    /**
     * Get the appointment for this on-deck entry
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the doctor for this on-deck entry
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the patient for this on-deck entry
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Scope for appointments by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for appointments by doctor
     */
    public function scopeDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope for appointments by position
     */
    public function scopePosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope for waiting appointments
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    /**
     * Scope for ready appointments
     */
    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    /**
     * Scope for in-progress appointments
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in-progress');
    }

    /**
     * Check if status is waiting
     */
    public function isWaiting()
    {
        return $this->status === 'waiting';
    }

    /**
     * Check if status is ready
     */
    public function isReady()
    {
        return $this->status === 'ready';
    }

    /**
     * Check if status is in progress
     */
    public function isInProgress()
    {
        return $this->status === 'in-progress';
    }

    /**
     * Check if status is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if status is no-show
     */
    public function isNoShow()
    {
        return $this->status === 'no-show';
    }

    /**
     * Mark as ready
     */
    public function markAsReady()
    {
        return $this->update(['status' => 'ready']);
    }

    /**
     * Mark as in progress
     */
    public function markAsInProgress()
    {
        return $this->update(['status' => 'in-progress']);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted()
    {
        return $this->update(['status' => 'completed']);
    }

    /**
     * Mark as no-show
     */
    public function markAsNoShow()
    {
        return $this->update(['status' => 'no-show']);
    }

    /**
     * Move to next position in queue
     */
    public function moveToNextPosition()
    {
        $nextPosition = $this->position + 1;

        // Check if position is available
        $existingOnDeck = static::where('doctor_id', $this->doctor_id)
            ->where('position', $nextPosition)
            ->first();

        if ($existingOnDeck) {
            // Shift existing appointment back
            $existingOnDeck->position = $nextPosition + 1;
            $existingOnDeck->save();
        }

        $this->position = $nextPosition;
        return $this->save();
    }

    /**
     * Calculate estimated wait time based on position and average appointment duration
     */
    public function calculateEstimatedWaitTime()
    {
        // Get average appointment duration for this doctor
        $avgDuration = Appointment::where('doctor_id', $this->doctor_id)
            ->where('status', 'completed')
            ->avg('duration') ?? 30; // Default to 30 minutes

        // Calculate wait time: (current position - 1) * average duration
        $waitTime = max(0, ($this->position - 1) * $avgDuration);

        $this->estimated_wait_minutes = $waitTime;
        return $this->save();
    }

    /**
     * Set risk score and factors
     */
    public function setRiskScore($score, array $factors = [])
    {
        $this->risk_score = $score;
        $this->risk_factors = $factors;
        return $this->save();
    }

    /**
     * Get risk factors as array
     */
    public function getRiskFactorsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    /**
     * Set risk factors from array
     */
    public function setRiskFactorsAttribute($value)
    {
        $this->attributes['risk_factors'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Get next available position for a doctor
     */
    protected static function getNextPosition($doctorId)
    {
        $maxPosition = static::where('doctor_id', $doctorId)
            ->max('position');

        return ($maxPosition ?? 0) + 1;
    }

    /**
     * Reorder positions after deletion
     */
    protected static function reorderPositions($doctorId, $deletedPosition)
    {
        static::where('doctor_id', $doctorId)
            ->where('position', '>', $deletedPosition)
            ->decrement('position');
    }

    /**
     * Insert at specific position and shift others back
     */
    protected static function insertAtPosition($doctorId, $position)
    {
        // Shift positions back for existing appointments at or after this position
        static::where('doctor_id', $doctorId)
            ->where('position', '>=', $position)
            ->increment('position');
    }

    /**
     * Get queue statistics for a doctor
     */
    public static function getQueueStats($doctorId)
    {
        return [
            'total_waiting' => static::where('doctor_id', $doctorId)->waiting()->count(),
            'total_ready' => static::where('doctor_id', $doctorId)->ready()->count(),
            'total_in_progress' => static::where('doctor_id', $doctorId)->inProgress()->count(),
            'average_wait_time' => static::where('doctor_id', $doctorId)
                ->whereNotNull('estimated_wait_minutes')
                ->avg('estimated_wait_minutes') ?? 0,
            'next_appointment_id' => static::where('doctor_id', $doctorId)
                ->waiting()
                ->orderBy('position')
                ->first()?->appointment_id,
        ];
    }

    /**
     * Get estimated completion time
     */
    public function getEstimatedCompletionTime()
    {
        if (!$this->estimated_wait_minutes) {
            $this->calculateEstimatedWaitTime();
        }

        return now()->addMinutes($this->estimated_wait_minutes ?? 0);
    }

    /**
     * Check if this appointment is next in queue
     */
    public function isNextInQueue()
    {
        $nextInQueue = static::where('doctor_id', $this->doctor_id)
            ->waiting()
            ->orderBy('position')
            ->first();

        return $nextInQueue && $nextInQueue->id === $this->id;
    }
}
