<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyInvoiceSetting extends Model
{
    protected $fillable = [
        'user_id',
        'billing_amount',
        'subscription_period_months',
        'subscription_starts_at',
        'subscription_ends_at',
        'grace_period_days',
        'warning_period_days',
        'reminder_frequency_days',
        'is_restricted',
        'restricted_pages',
        'restriction_message',
        'last_reminder_sent_at',
        'is_active',
    ];

    protected $casts = [
        'billing_amount' => 'decimal:2',
        'subscription_period_months' => 'integer',
        'subscription_starts_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'grace_period_days' => 'integer',
        'warning_period_days' => 'integer',
        'reminder_frequency_days' => 'integer',
        'is_restricted' => 'boolean',
        'restricted_pages' => 'array',
        'last_reminder_sent_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the monthly invoice setting
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default restricted pages
     */
    public static function getDefaultRestrictedPages(): array
    {
        return [
            'ask-ai',
            'cases',
            'dashboard',
            'settings',
        ];
    }

    /**
     * Get all available pages that can be restricted
     */
    public static function getAvailablePages(): array
    {
        return [
            'ask-ai' => 'AI Assistant',
            'cases' => 'Patient Cases',
            'dashboard' => 'Dashboard',
            'settings' => 'Settings',
            'profile.edit' => 'Profile',
        ];
    }

    /**
     * Check if a specific page is restricted for this user
     */
    public function isPageRestricted(string $routeName): bool
    {
        if (!$this->is_restricted || !$this->restricted_pages) {
            return false;
        }

        return in_array($routeName, $this->restricted_pages);
    }

    /**
     * Get the restriction message or default
     */
    public function getRestrictionMessage(): string
    {
        return $this->restriction_message ?: 
            'Your access has been restricted due to unpaid invoices. Please pay your outstanding invoices to restore access.';
    }

    /**
     * Check if user should receive a reminder
     */
    public function shouldReceiveReminder(): bool
    {
        if (!$this->last_reminder_sent_at) {
            return true;
        }

        return $this->last_reminder_sent_at->addDays($this->reminder_frequency_days)->isPast();
    }

    /**
     * Update last reminder sent timestamp
     */
    public function markReminderSent(): void
    {
        $this->update(['last_reminder_sent_at' => now()]);
    }

    /**
     * Restrict user access
     */
    public function restrictAccess(array $pages = null): void
    {
        $this->update([
            'is_restricted' => true,
            'restricted_pages' => $pages ?: static::getDefaultRestrictedPages(),
        ]);
    }

    /**
     * Unrestrict user access
     */
    public function unrestrictAccess(): void
    {
        $this->update([
            'is_restricted' => false,
            'restricted_pages' => null,
        ]);
    }

    /**
     * Check if subscription has expired
     */
    public function isSubscriptionExpired(): bool
    {
        if (!$this->subscription_ends_at) {
            return false;
        }

        return $this->subscription_ends_at->isPast();
    }

    /**
     * Check if subscription is unlimited
     */
    public function isUnlimitedSubscription(): bool
    {
        return $this->subscription_period_months === -1;
    }

    /**
     * Get subscription period in human readable format
     */
    public function getSubscriptionPeriodText(): string
    {
        if ($this->isUnlimitedSubscription()) {
            return 'Unlimited';
        }

        $months = $this->subscription_period_months;
        
        return match($months) {
            1 => 'Monthly',
            3 => 'Quarterly',
            6 => 'Semi-Annual',
            12 => 'Annual',
            24 => 'Biennial',
            36 => 'Triennial',
            default => $months . ' Months'
        };
    }

    /**
     * Get billing frequency text (how often they pay)
     */
    public function getBillingFrequencyText(): string
    {
        if ($this->isUnlimitedSubscription()) {
            return 'One-time';
        }

        $months = $this->subscription_period_months;
        
        return match($months) {
            1 => 'Every month',
            3 => 'Every 3 months',
            6 => 'Every 6 months', 
            12 => 'Every year',
            24 => 'Every 2 years',
            36 => 'Every 3 years',
            default => "Every {$months} months"
        };
    }

    /**
     * Get amount display with proper period
     */
    public function getAmountWithPeriod(): string
    {
        if ($this->isUnlimitedSubscription()) {
            return '$' . number_format($this->billing_amount, 2) . ' (one-time)';
        }

        $months = $this->subscription_period_months;
        $suffix = match($months) {
            1 => '/month',
            3 => '/quarter',
            6 => '/6 months',
            12 => '/year',
            24 => '/2 years',
            36 => '/3 years',
            default => "/{$months} months"
        };

        return '$' . number_format($this->billing_amount, 2) . $suffix;
    }

    /**
     * Calculate subscription end date from start date
     */
    public function calculateSubscriptionEndDate(): ?\Carbon\Carbon
    {
        if (!$this->subscription_starts_at || $this->isUnlimitedSubscription()) {
            return null;
        }

        return $this->subscription_starts_at->copy()->addMonths($this->subscription_period_months);
    }

    /**
     * Start subscription
     */
    public function startSubscription(): void
    {
        $startDate = now();
        $endDate = $this->isUnlimitedSubscription() ? null : $startDate->copy()->addMonths($this->subscription_period_months);

        $this->update([
            'subscription_starts_at' => $startDate,
            'subscription_ends_at' => $endDate,
            'is_active' => true,
        ]);
    }

    /**
     * Get days remaining in subscription
     */
    public function getDaysRemaining(): ?int
    {
        if (!$this->subscription_ends_at || $this->isUnlimitedSubscription()) {
            return null;
        }

        return max(0, now()->diffInDays($this->subscription_ends_at, false));
    }

    /**
     * Get comprehensive subscription status
     */
    public function getSubscriptionStatus(): string
    {
        if (!$this->is_active) {
            return 'setup_pending';
        }

        if ($this->isUnlimitedSubscription()) {
            return 'unlimited';
        }

        if (!$this->subscription_starts_at) {
            return 'ready_to_subscribe';
        }

        if ($this->is_restricted) {
            return 'restricted';
        }

        if (!$this->isSubscriptionExpired()) {
            return 'active';
        }

        if ($this->isInGracePeriod()) {
            return 'grace_period';
        }

        if ($this->isInWarningPeriod()) {
            return 'warning_period';
        }

        return 'should_be_restricted';
    }

    /**
     * Check if subscription is in grace period
     */
    public function isInGracePeriod(): bool
    {
        if (!$this->isSubscriptionExpired() || $this->isUnlimitedSubscription()) {
            return false;
        }

        $gracePeriodEnd = $this->subscription_ends_at->copy()->addDays($this->grace_period_days);
        return now()->isBefore($gracePeriodEnd);
    }

    /**
     * Check if subscription is in warning period
     */
    public function isInWarningPeriod(): bool
    {
        if (!$this->isSubscriptionExpired() || $this->isUnlimitedSubscription()) {
            return false;
        }

        $gracePeriodEnd = $this->subscription_ends_at->copy()->addDays($this->grace_period_days);
        $warningPeriodEnd = $gracePeriodEnd->copy()->addDays($this->warning_period_days);
        
        return now()->isAfter($gracePeriodEnd) && now()->isBefore($warningPeriodEnd);
    }

    /**
     * Check if should be restricted (past warning period)
     */
    public function shouldBeRestricted(): bool
    {
        if ($this->isUnlimitedSubscription() || !$this->isSubscriptionExpired()) {
            return false;
        }

        $gracePeriodEnd = $this->subscription_ends_at->copy()->addDays($this->grace_period_days);
        $warningPeriodEnd = $gracePeriodEnd->copy()->addDays($this->warning_period_days);
        
        return now()->isAfter($warningPeriodEnd);
    }

    /**
     * Get grace period end date
     */
    public function getGracePeriodEndDate(): ?\Carbon\Carbon
    {
        if (!$this->subscription_ends_at || $this->isUnlimitedSubscription()) {
            return null;
        }

        return $this->subscription_ends_at->copy()->addDays($this->grace_period_days);
    }

    /**
     * Get warning period end date
     */
    public function getWarningPeriodEndDate(): ?\Carbon\Carbon
    {
        $gracePeriodEnd = $this->getGracePeriodEndDate();
        if (!$gracePeriodEnd) {
            return null;
        }

        return $gracePeriodEnd->copy()->addDays($this->warning_period_days);
    }

    /**
     * Get days remaining in current period
     */
    public function getDaysRemainingInCurrentPeriod(): int
    {
        $status = $this->getSubscriptionStatus();

        switch ($status) {
            case 'active':
                return $this->getDaysRemaining();
            
            case 'grace_period':
                $gracePeriodEnd = $this->getGracePeriodEndDate();
                return max(0, now()->diffInDays($gracePeriodEnd, false));
            
            case 'warning_period':
                $warningPeriodEnd = $this->getWarningPeriodEndDate();
                return max(0, now()->diffInDays($warningPeriodEnd, false));
            
            default:
                return 0;
        }
    }

    /**
     * Get human-readable status text
     */
    public function getStatusText(): string
    {
        return match($this->getSubscriptionStatus()) {
            'setup_pending' => 'Setup Pending',
            'ready_to_subscribe' => 'Ready to Subscribe',
            'active' => 'Active',
            'grace_period' => 'Expired (Grace Period)',
            'warning_period' => 'Final Warning',
            'restricted' => 'Restricted',
            'unlimited' => 'Unlimited Access',
            'should_be_restricted' => 'Should Be Restricted',
            default => 'Unknown'
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match($this->getSubscriptionStatus()) {
            'setup_pending' => '#6c757d',
            'ready_to_subscribe' => '#007bff',
            'active' => '#28a745',
            'grace_period' => '#ffc107',
            'warning_period' => '#fd7e14',
            'restricted' => '#dc3545',
            'unlimited' => '#20c997',
            'should_be_restricted' => '#dc3545',
            default => '#6c757d'
        };
    }

    /**
     * Get next action text for user
     */
    public function getNextActionText(): string
    {
        return match($this->getSubscriptionStatus()) {
            'setup_pending' => 'Contact support to activate your account',
            'ready_to_subscribe' => 'Start your subscription to unlock full access',
            'active' => 'Enjoy unlimited access to all features',
            'grace_period' => 'Renew your subscription to continue access',
            'warning_period' => 'Renew now to avoid account restriction',
            'restricted' => 'Pay outstanding invoices to restore access',
            'unlimited' => 'Enjoy unlimited lifetime access',
            'should_be_restricted' => 'Account should be restricted - contact admin',
            default => 'Contact support for assistance'
        };
    }
}