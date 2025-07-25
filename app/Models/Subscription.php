<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'plan_name',
        'billing_cycle',
        'status',
        'amount',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'trial_ends_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the subscription is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    /**
     * Check if the subscription is past due.
     */
    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    /**
     * Get the plan configuration.
     */
    public function getPlanConfig(): array
    {
        return config("stripe.plans.{$this->plan_name}", []);
    }

    /**
     * Get the token limit for this subscription.
     */
    public function getTokenLimit(): int
    {
        $config = $this->getPlanConfig();
        return $config['token_limit'] ?? 0;
    }
}
