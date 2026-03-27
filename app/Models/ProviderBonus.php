<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderBonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'compensation_plan_id',
        'bonus_type',
        'amount',
        'reason',
        'status',
        'earned_date',
        'paid_at',
        'payroll_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'earned_date' => 'date',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the doctor that owns the bonus.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the compensation plan.
     */
    public function compensationPlan(): BelongsTo
    {
        return $this->belongsTo(CompensationPlan::class);
    }

    /**
     * Valid status values.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Valid status transitions.
     */
    private const STATUS_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_APPROVED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED => [self::STATUS_PAID, self::STATUS_CANCELLED],
        self::STATUS_PAID => [],
        self::STATUS_CANCELLED => [],
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
     * Mark as approved.
     *
     * @throws \InvalidArgumentException
     */
    public function approve(): void
    {
        if (!$this->canTransitionTo(self::STATUS_APPROVED)) {
            throw new \InvalidArgumentException(
                "Cannot approve bonus in '{$this->status}' status."
            );
        }
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Mark as paid.
     *
     * @throws \InvalidArgumentException
     */
    public function markAsPaid(?string $payrollReference = null): void
    {
        if (!$this->canTransitionTo(self::STATUS_PAID)) {
            throw new \InvalidArgumentException(
                "Cannot mark bonus as paid in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'payroll_reference' => $payrollReference,
        ]);
    }

    /**
     * Scope for pending bonuses.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for a specific bonus type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('bonus_type', $type);
    }

    /**
     * Bonus type options.
     */
    public static function bonusTypes(): array
    {
        return [
            'performance' => 'Performance Bonus',
            'retention' => 'Retention Bonus',
            'referral' => 'Referral Bonus',
            'holiday' => 'Holiday Bonus',
            'sign_on' => 'Sign-On Bonus',
            'quality' => 'Quality Bonus',
            'productivity' => 'Productivity Bonus',
        ];
    }
}
