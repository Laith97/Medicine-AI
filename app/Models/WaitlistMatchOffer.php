<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistMatchOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'waitlist_id',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'availability_slot_id',
        'match_score',
        'status',
        'sent_at',
        'expires_at',
        'responded_at',
        'patient_response',
        'decline_reason',
    ];

    protected $casts = [
        'match_score' => 'decimal:4',
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the waitlist.
     */
    public function waitlist(): BelongsTo
    {
        return $this->belongsTo(Waitlist::class);
    }

    /**
     * Get the patient.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the doctor.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the appointment.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the availability slot.
     */
    public function availabilitySlot(): BelongsTo
    {
        return $this->belongsTo(AvailabilitySlot::class);
    }

    /**
     * Valid status values.
     */
    public const STATUS_PENDING = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_BOOKED = 'booked';

    /**
     * Valid status transitions.
     */
    private const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_ACCEPTED, self::STATUS_DECLINED, self::STATUS_EXPIRED],
        self::STATUS_ACCEPTED => [self::STATUS_BOOKED],
        self::STATUS_DECLINED => [],
        self::STATUS_EXPIRED => [],
        self::STATUS_BOOKED => [],
    ];

    /**
     * Check if transition to new status is valid.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = self::STATUS_TRANSITIONS[$this->status] ?? [];
        return in_array($newStatus, $allowedTransitions);
    }

    /**
     * Mark as sent.
     *
     * @throws \InvalidArgumentException
     */
    public function markAsSent(): void
    {
        if (!$this->canTransitionTo(self::STATUS_PENDING)) {
            throw new \InvalidArgumentException(
                "Cannot mark as sent in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_PENDING,
            'sent_at' => now(),
        ]);
    }

    /**
     * Accept the offer.
     *
     * @throws \InvalidArgumentException
     */
    public function accept(): void
    {
        if (!$this->canTransitionTo(self::STATUS_ACCEPTED)) {
            throw new \InvalidArgumentException(
                "Cannot accept offer in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'patient_response' => 'accept',
            'responded_at' => now(),
        ]);
    }

    /**
     * Decline the offer.
     *
     * @throws \InvalidArgumentException
     */
    public function decline(?string $reason = null): void
    {
        if (!$this->canTransitionTo(self::STATUS_DECLINED)) {
            throw new \InvalidArgumentException(
                "Cannot decline offer in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_DECLINED,
            'patient_response' => 'decline',
            'decline_reason' => $reason,
            'responded_at' => now(),
        ]);
    }

    /**
     * Mark as expired.
     *
     * @throws \InvalidArgumentException
     */
    public function expire(): void
    {
        if (!$this->canTransitionTo(self::STATUS_EXPIRED)) {
            throw new \InvalidArgumentException(
                "Cannot expire offer in '{$this->status}' status."
            );
        }
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Mark as booked (appointment created).
     *
     * @throws \InvalidArgumentException
     */
    public function markAsBooked(): void
    {
        if (!$this->canTransitionTo(self::STATUS_BOOKED)) {
            throw new \InvalidArgumentException(
                "Cannot mark as booked in '{$this->status}' status."
            );
        }
        $this->update(['status' => self::STATUS_BOOKED]);
    }

    /**
     * Check if offer is still valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Scope for pending offers.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for valid offers.
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
