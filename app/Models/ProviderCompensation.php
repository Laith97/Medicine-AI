<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderCompensation extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'compensation_plan_id',
        'appointment_id',
        'claim_id',
        'compensation_type',
        'amount',
        'hours_worked',
        'base_amount',
        'commission_rate',
        'description',
        'pay_period_start',
        'pay_period_end',
        'status',
        'paid_at',
        'payroll_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the doctor that owns the compensation.
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
     * Get the related appointment if any.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the related claim if any.
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
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
                "Cannot approve compensation in '{$this->status}' status."
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
                "Cannot mark compensation as paid in '{$this->status}' status."
            );
        }
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'payroll_reference' => $payrollReference,
        ]);
    }

    /**
     * Scope for pending compensations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for a specific pay period.
     */
    public function scopeForPayPeriod($query, $start, $end)
    {
        return $query->where('pay_period_start', '>=', $start)
            ->where('pay_period_end', '<=', $end);
    }
}
