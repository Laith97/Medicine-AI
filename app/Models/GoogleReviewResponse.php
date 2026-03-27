<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleReviewResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'doctor_id',
        'generated_response',
        'tone',
        'status',
        'approved_response',
        'posted_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Valid status values.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_POSTED = 'posted';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Valid status transitions.
     */
    private const STATUS_TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_APPROVED, self::STATUS_REJECTED],
        self::STATUS_APPROVED => [self::STATUS_POSTED, self::STATUS_REJECTED],
        self::STATUS_POSTED => [],
        self::STATUS_REJECTED => [],
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
     * Get the review.
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Get the doctor.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get who approved.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if response was posted.
     */
    public function wasPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /**
     * Approve the response.
     *
     * @throws \InvalidArgumentException
     */
    public function approve(User $user, ?string $response = null): void
    {
        if (!$this->canTransitionTo(self::STATUS_APPROVED)) {
            throw new \InvalidArgumentException(
                "Cannot approve response in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_response' => $response ?? $this->generated_response,
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);
    }

    /**
     * Reject the response.
     *
     * @throws \InvalidArgumentException
     */
    public function reject(): void
    {
        if (!$this->canTransitionTo(self::STATUS_REJECTED)) {
            throw new \InvalidArgumentException(
                "Cannot reject response in '{$this->status}' status."
            );
        }
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    /**
     * Mark as posted to Google.
     *
     * @throws \InvalidArgumentException
     */
    public function markAsPosted(): void
    {
        if (!$this->canTransitionTo(self::STATUS_POSTED)) {
            throw new \InvalidArgumentException(
                "Cannot mark as posted in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_POSTED,
            'posted_at' => now(),
        ]);
    }

    /**
     * Scope for pending review.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for approved.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Tone options.
     */
    public static function tones(): array
    {
        return [
            'professional' => 'Professional',
            'friendly' => 'Friendly',
            'empathetic' => 'Empathetic',
            'formal' => 'Formal',
        ];
    }
}
