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
        'age',
        'password',
        'role',
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
        'current_plan',
        'monthly_cost_limit',
        'subscription_ends_at',
        'subscription_active',
        'primary_doctor_id',
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
            'subscription_ends_at' => 'datetime',
            'subscription_active' => 'boolean',
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
        return $this->subscription_active &&
            $this->subscription_ends_at &&
            $this->subscription_ends_at->isFuture();
    }

    /**
     * Get the user's current plan configuration
     */
    public function getPlanConfig(): array
    {
        return config("stripe.plans.{$this->current_plan}", []);
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
     * Check if user has exceeded their token limit
     */
    public function hasExceededTokenLimit(): bool
    {
        $planConfig = $this->getPlanConfig();
        $tokenLimit = $planConfig['token_limit'] ?? 0;

        // Unlimited plan
        if ($tokenLimit === -1) {
            return false;
        }

        $monthlyUsage = $this->getMonthlyTokenUsage();
        return $monthlyUsage >= $tokenLimit;
    }

    /**
     * Get remaining tokens for current month
     */
    public function getRemainingTokens(): int
    {
        $planConfig = $this->getPlanConfig();
        $tokenLimit = $planConfig['token_limit'] ?? 0;

        // Unlimited plan
        if ($tokenLimit === -1) {
            return -1;
        }

        $monthlyUsage = $this->getMonthlyTokenUsage();
        return max(0, $tokenLimit - $monthlyUsage);
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
     * Get the estimated cost for this month
     */
    public function getMonthlyCostEstimate(): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $this->openaiUsages()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('cost_estimate');
    }

    /**
     * Check if user has exceeded their monthly cost limit
     */
    public function hasExceededCostLimit(): bool
    {
        if ($this->monthly_cost_limit <= 0) {
            return false; // No limit set
        }

        $monthlyCost = $this->getMonthlyCostEstimate();
        return $monthlyCost > $this->monthly_cost_limit;
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
     * Get remaining cost allowance for this month
     */
    public function getRemainingCostAllowance(): float
    {
        if ($this->monthly_cost_limit <= 0) {
            return -1; // Unlimited
        }

        $monthlyCost = $this->getMonthlyCostEstimate();
        return max(0, $this->monthly_cost_limit - $monthlyCost);
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
    $setting = $this->monthlyInvoiceSetting;
    return $setting && $setting->is_restricted;
}

/**
 * Check if a specific page is restricted for this user
 */
public function isPageRestricted(string $routeName): bool
{
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
 * Doctor notes relationship (for doctors)
 */
public function doctorNotes()
{
    return $this->hasMany(DoctorNote::class, 'doctor_id');
}

/**
 * Patient notes relationship (for patients - notes about them)
 */
public function patientNotes()
{
    return $this->hasMany(DoctorNote::class, 'patient_id');
}

/**
 * Diagnoses made by this doctor
 */
public function doctorDiagnoses()
{
    return $this->hasMany(Diagnosis::class, 'doctor_id');
}

/**
 * Diagnoses received by this patient
 */
public function patientDiagnoses()
{
    return $this->hasMany(Diagnosis::class, 'patient_id');
}

/**
 * Primary doctor relationship (for patients)
 */
public function primaryDoctor()
{
    return $this->belongsTo(User::class, 'primary_doctor_id');
}

/**
 * Patients assigned to this doctor (for doctors)
 */
public function assignedPatients()
{
    return $this->hasMany(User::class, 'primary_doctor_id')->where('role', 'patient');
}

/**
 * Follow-up questions asked by this patient
 */
public function diagnosisFollowUps()
{
    return $this->hasMany(DiagnosisFollowUp::class, 'patient_id');
}

}
