<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'state',
        'zip_code',
        'emergency_contact_name',
        'emergency_contact_phone',
        'email_verified_at',
        'stripe_customer_id',
        'monthly_cost_limit',
        'trial_ends_at',
        'trial_used',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'monthly_cost_limit' => 'decimal:2',
            'trial_ends_at' => 'datetime',
            'trial_used' => 'boolean',
        ];
    }

    public function setting()
    {
        return $this->hasOne(Setting::class);
    }

    public function patientAnalyses()
    {
        return $this->hasMany(PatientAnalysis::class);
    }

    // Doctor relationship
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    // Patient appointments
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    // Patient reviews
    public function reviews()
    {
        return $this->hasMany(Review::class, 'patient_id');
    }



    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    public function openaiUsages()
    {
        return $this->hasMany(OpenAIUsage::class);
    }

public function stripeInvoices()
{
    return $this->hasMany(StripeInvoice::class);
}

public function monthlyInvoiceSetting()
{
    return $this->hasOne(MonthlyInvoiceSetting::class);
}

/**
 * Get fresh monthly invoice setting (no caching)
 */
public function getFreshMonthlyInvoiceSetting()
{
    return MonthlyInvoiceSetting::where('user_id', $this->id)->first();
}



    /**
     * Check if user is a doctor
     */
    public function isDoctor()
    {
        return $this->role === 'doctor';
    }

    /**
     * Check if user is a patient
     */
    public function isPatient()
    {
        return $this->role === 'patient';
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code
        ]);

        return implode(', ', $parts);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Check if user has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->monthlyInvoiceSetting && 
               $this->monthlyInvoiceSetting->isActiveSubscription();
    }

    /**
     * Get the user's current plan configuration (deprecated - use monthlyInvoiceSetting)
     */
    public function getPlanConfig(): array
    {
        // Return default config for backward compatibility
        return [
            'name' => 'Custom Plan',
            'token_limit' => -1, // Unlimited
            'monthly_cost_limit' => $this->monthly_cost_limit ?? 100,
        ];
    }

    /**
     * Get the user's monthly token usage
     */
    public function getMonthlyTokenUsage(): int
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $this->openaiUsages()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_tokens');
    }

    /**
     * Check if user has exceeded their cost limit (replaces token limit)
     */
    public function hasExceededTokenLimit(): bool
    {
        return $this->hasExceededCostLimit();
    }

    /**
     * Check if user has exceeded their monthly cost limit
     */
    public function hasExceededCostLimit(): bool
    {
        if (!$this->monthly_cost_limit || $this->monthly_cost_limit <= 0) {
            return false; // No limit set
        }

        $monthlyCost = $this->getMonthlyCost();
        return $monthlyCost >= $this->monthly_cost_limit;
    }

    /**
     * Get remaining tokens for current month (deprecated - use cost limits)
     */
    public function getRemainingTokens(): int
    {
        // Return unlimited for backward compatibility
        return -1;
    }

    /**
     * Get remaining cost allowance for current month
     */
    public function getRemainingCostAllowance(): float
    {
        if (!$this->monthly_cost_limit || $this->monthly_cost_limit <= 0) {
            return -1; // Unlimited
        }

        $monthlyCost = $this->getMonthlyCost();
        return max(0, $this->monthly_cost_limit - $monthlyCost);
    }

    /**
     * Get the number of requests made this month
     */
    public function getMonthlyRequestCount(): int
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $this->openaiUsages()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();
    }

    /**
     * Get the actual cost for this month
     */
    public function getMonthlyCost(): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $this->openaiUsages()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('cost_estimate');
    }

    /**
     * Get the estimated cost for this month (alias for getMonthlyCost)
     */
    public function getMonthlyCostEstimate(): float
    {
        return $this->getMonthlyCost();
    }



    /**
     * Get the excess cost over the limit
     */
    public function getExcessCost(): float
    {
        if ($this->monthly_cost_limit <= 0) {
            return 0; // No limit set
        }

        $monthlyCost = $this->getMonthlyCostEstimate();
        return max(0, $monthlyCost - $this->monthly_cost_limit);
    }



    /**
     * Get cost usage percentage
     */
    public function getCostUsagePercentage(): float
    {
        if ($this->monthly_cost_limit <= 0) {
            return 0; // No limit set
        }

        $monthlyCost = $this->getMonthlyCostEstimate();
        return min(100, ($monthlyCost / $this->monthly_cost_limit) * 100);
    }

    /**
     * Get total unpaid invoice amount
     */
    public function getTotalUnpaidAmount(): float
    {
        return $this->stripeInvoices()
            ->unpaid()
            ->sum('amount_due') - $this->stripeInvoices()
            ->unpaid()
            ->sum('amount_paid');
    }

    /**
     * Get total paid invoice amount
     */
    public function getTotalPaidAmount(): float
    {
        return $this->stripeInvoices()
            ->paid()
            ->sum('amount_paid');
    }

    /**
     * Get last paid invoice
     */
    public function getLastPaidInvoice()
    {
        return $this->stripeInvoices()
            ->paid()
            ->latest('paid_at')
            ->first();
    }

    /**
     * Get next due invoice
     */
    public function getNextDueInvoice()
    {
        return $this->stripeInvoices()
            ->unpaid()
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->first();
    }

    /**
     * Check if user has overdue invoices
     */
    public function hasOverdueInvoices(): bool
    {
        return $this->stripeInvoices()
            ->overdue()
            ->exists();
    }

/**
 * Get overdue invoices count
 */
public function getOverdueInvoicesCount(): int
{
    return $this->stripeInvoices()
        ->overdue()
        ->count();
}

/**
 * Get monthly invoices for a specific month/year
 */
public function getMonthlyInvoices(int $month = null, int $year = null)
{
    $month = $month ?: now()->month;
    $year = $year ?: now()->year;
    
    return $this->stripeInvoices()
        ->where('invoice_type', 'monthly')
        ->where('invoice_month', $month)
        ->where('invoice_year', $year);
}

/**
 * Check if user has unpaid monthly invoices
 */
public function hasUnpaidMonthlyInvoices(): bool
{
    return $this->stripeInvoices()
        ->where('invoice_type', 'monthly')
        ->unpaid()
        ->exists();
}

/**
 * Get total unpaid monthly invoice amount
 */
public function getTotalUnpaidMonthlyAmount(): float
{
    return $this->stripeInvoices()
        ->where('invoice_type', 'monthly')
        ->unpaid()
        ->sum('amount_due') - $this->stripeInvoices()
        ->where('invoice_type', 'monthly')
        ->unpaid()
        ->sum('amount_paid');
}

/**
 * Check if user is currently restricted
 */
public function isRestricted(): bool
{
    // If user is in trial period, they are not restricted
    if ($this->isInTrialPeriod()) {
        return false;
    }
    
    $setting = $this->monthlyInvoiceSetting;
    return $setting && $setting->is_restricted;
}

/**
 * Check if a specific page is restricted for this user
 */
public function isPageRestricted(string $routeName): bool
{
    // If user is in trial period, no pages are restricted
    if ($this->isInTrialPeriod()) {
        return false;
    }
    
    $setting = $this->monthlyInvoiceSetting;
    return $setting && $setting->isPageRestricted($routeName);
}

/**
 * Get the user's restriction message
 */
public function getRestrictionMessage(): string
{
    $setting = $this->monthlyInvoiceSetting;
    return $setting ? $setting->getRestrictionMessage() : '';
}

/**
 * Get or create monthly invoice setting
 */
public function getOrCreateMonthlyInvoiceSetting(): MonthlyInvoiceSetting
{
    return $this->monthlyInvoiceSetting ?: $this->monthlyInvoiceSetting()->create([
        'billing_amount' => 0,
        'monthly_price' => 0,
        'yearly_price' => 0,
        'grace_period_days' => 7,
        'reminder_frequency_days' => 3,
        'is_restricted' => false,
        'is_active' => false,
    ]);
}

/**
 * Check if user is in grace period
 */
public function isInGracePeriod(): bool
{
    $setting = $this->monthlyInvoiceSetting;
    return $setting && $setting->isInGracePeriod();
}

/**
 * Check if user is in warning period
 */
public function isInWarningPeriod(): bool
{
    $setting = $this->monthlyInvoiceSetting;
    return $setting && $setting->isInWarningPeriod();
}

/**
 * Get subscription status
 */
public function getSubscriptionStatus(): string
{
    $setting = $this->monthlyInvoiceSetting;
    return $setting ? $setting->getSubscriptionStatus() : 'setup_pending';
}

/**
 * Get days remaining in current subscription period
 */
public function getDaysRemainingInCurrentPeriod(): int
{
    $setting = $this->monthlyInvoiceSetting;
    return $setting ? $setting->getDaysRemainingInCurrentPeriod() : 0;
}

/**
 * Get subscription end date
 */
public function getSubscriptionEndDate(): ?\Carbon\Carbon
{
    $setting = $this->monthlyInvoiceSetting;
    return $setting ? $setting->subscription_ends_at : null;
}

/**
 * Check if user is in trial period
 */
public function isInTrialPeriod(): bool
{
    return $this->trial_ends_at && $this->trial_ends_at->isFuture();
}

/**
 * Check if user has used their trial
 */
public function hasUsedTrial(): bool
{
    return $this->trial_used;
}

/**
 * Start trial for user
 */
public function startTrial(): void
{
    if ($this->hasUsedTrial()) {
        return; // User already used their trial
    }
    
    $trialDays = SystemSetting::get('trial_days', 7);
    
    $this->update([
        'trial_ends_at' => now()->addDays($trialDays),
        'trial_used' => true,
    ]);
}

/**
 * Get trial days remaining
 */
public function getTrialDaysRemaining(): int
{
    if (!$this->isInTrialPeriod()) {
        return 0;
    }
    
    return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
}

/**
 * Get trial status
 */
public function getTrialStatus(): string
{
    if (!$this->hasUsedTrial()) {
        return 'not_started';
    }
    
    if ($this->isInTrialPeriod()) {
        return 'active';
    }
    
    return 'expired';
}

}
