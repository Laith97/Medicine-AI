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
        'password',
        'is_admin',
        'stripe_customer_id',
        'current_plan',
        'subscription_ends_at',
        'subscription_active',
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
            'is_admin' => 'boolean',
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

/**
 * Check if user is admin
 */
public function isAdmin()
{
    return $this->is_admin;
}

/**
 * Make user admin
 */
public function makeAdmin()
{
    $this->update(['is_admin' => true]);
}

/**
 * Remove admin privileges
 */
public function removeAdmin()
{
    $this->update(['is_admin' => false]);
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

}
