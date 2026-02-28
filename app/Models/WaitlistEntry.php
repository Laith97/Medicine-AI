<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'waitlist_id',
        'appointment_id',
        'slot_date',
        'slot_time',
        'offered_at',
        'response_deadline',
        'status',
    ];

    protected $casts = [
        'slot_date' => 'date',
        'slot_time' => 'datetime',
        'offered_at' => 'datetime',
        'response_deadline' => 'datetime',
    ];

    /**
     * Get the waitlist that owns the entry
     */
    public function waitlist(): BelongsTo
    {
        return $this->belongsTo(Waitlist::class);
    }

    /**
     * Get the appointment that owns the entry
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Scope for pending entries
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for offered entries
     */
    public function scopeOffered($query)
    {
        return $query->where('status', 'offered');
    }

    /**
     * Scope for accepted entries
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for declined entries
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    /**
     * Scope for expired entries
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for entries with expired response deadline
     */
    public function scopeExpiredDeadline($query)
    {
        return $query->where('response_deadline', '<', now())
                    ->where('status', 'offered');
    }

    /**
     * Check if entry is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if entry is offered
     */
    public function isOffered(): bool
    {
        return $this->status === 'offered';
    }

    /**
     * Check if entry is accepted
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if entry is declined
     */
    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    /**
     * Check if entry is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Check if response deadline has passed
     */
    public function isResponseDeadlinePassed(): bool
    {
        return $this->response_deadline && $this->response_deadline->isPast();
    }

    /**
     * Mark entry as offered
     */
    public function markAsOffered($deadline = null): void
    {
        $this->update([
            'status' => 'offered',
            'offered_at' => now(),
            'response_deadline' => $deadline ?? now()->addHours(24),
        ]);
    }

    /**
     * Accept the entry
     */
    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
    }

    /**
     * Decline the entry
     */
    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }

    /**
     * Expire the entry
     */
    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Get formatted slot date and time
     */
    public function getFormattedSlotAttribute(): string
    {
        return $this->slot_date->format('Y-m-d') . ' ' . $this->slot_time->format('H:i:s');
    }
}
