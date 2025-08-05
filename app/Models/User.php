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
        'monthly_cost_limit',
        'trial_ends_at',
        'trial_used',
        'subscription_ends_at',
        'subscription_active',
        'primary_doctor_id',
        'parent_user_id',
        'sub_user_role',
        'is_sub_user',
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
            'is_sub_user' => 'boolean',
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
     * Check if user is a sub-user
     */
    public function isSubUser()
    {
        return $this->is_sub_user;
    }

    /**
     * Check if user is a main user (not a sub-user)
     */
    public function isMainUser()
    {
        return !$this->is_sub_user;
    }

    /**
     * Parent user relationship (for sub-users)
     */
    public function parentUser()
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    /**
     * Sub-users relationship (for main users)
     */
    public function subUsers()
    {
        return $this->hasMany(User::class, 'parent_user_id')->where('is_sub_user', true);
    }

    /**
     * Permissions relationship
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
                    ->withPivot('granted_by')
                    ->withTimestamps();
    }

    /**
     * User permissions pivot records
     */
    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permissionName): bool
    {
        // Main users (non-sub-users) have all permissions except restricted ones
        if ($this->isMainUser()) {
            // Check if it's a restricted permission
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission && $permission->is_restricted) {
                // Only doctors can access restricted permissions
                return $this->isDoctor();
            }
            return true;
        }

        // Sub-users only have explicitly granted permissions
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Check if user can access a specific route
     */
    public function canAccessRoute(string $routeName): bool
    {
        // Main users can access all routes based on their role
        if ($this->isMainUser()) {
            // Check for restricted routes
            $restrictedPermissions = Permission::where('is_restricted', true)->get();
            foreach ($restrictedPermissions as $permission) {
                if ($permission->matchesRoute($routeName)) {
                    return $this->isDoctor();
                }
            }
            return true;
        }

        // Sub-users need explicit permission
        $permissions = $this->permissions;
        foreach ($permissions as $permission) {
            if ($permission->matchesRoute($routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Grant permission to this user
     */
    public function grantPermission(Permission $permission, User $grantedBy): bool
    {
        // Can't grant restricted permissions to sub-users
        if ($this->isSubUser() && $permission->is_restricted) {
            return false;
        }

        // Can't grant permission if already exists
        if ($this->hasPermission($permission->name)) {
            return false;
        }

        $this->permissions()->attach($permission->id, [
            'granted_by' => $grantedBy->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Revoke permission from this user
     */
    public function revokePermission(Permission $permission): bool
    {
        return $this->permissions()->detach($permission->id) > 0;
    }

    /**
     * Get available permissions for this user (for UI display)
     */
    public function getAvailablePermissions()
    {
        if ($this->isMainUser()) {
            // Main users can see all non-restricted permissions for granting to sub-users
            return Permission::getAvailableForSubUsers();
        }

        // Sub-users can only see their granted permissions
        return $this->permissions;
    }

    /**
     * Get the effective role for permission checking
     */
    public function getEffectiveRole(): string
    {
        if ($this->isSubUser()) {
            return $this->sub_user_role ?? 'sub_user';
        }

        return $this->role;
    }

    /**
     * Get the effective doctor profile (for sub-users, returns parent's doctor profile)
     */
    public function getEffectiveDoctor()
    {
        if ($this->isSubUser()) {
            return $this->parentUser ? $this->parentUser->doctor : null;
        }

        return $this->doctor;
    }

    /**
     * Get the effective doctor user (for sub-users, returns parent user)
     */
    public function getEffectiveDoctorUser()
    {
        if ($this->isSubUser()) {
            return $this->parentUser;
        }

        return $this;
    }

    /**
     * Get assigned patients (works for both main users and sub-users)
     */
    public function getEffectiveAssignedPatients()
    {
        if ($this->isSubUser()) {
            return $this->parentUser ? $this->parentUser->assignedPatients() : collect();
        }

        return $this->assignedPatients();
    }

    /**
     * Get effective doctor appointments (for sub-users, returns parent's doctor appointments)
     */
    public function getEffectiveDoctorAppointments()
    {
        $doctor = $this->getEffectiveDoctor();
        return $doctor ? $doctor->appointments() : collect();
    }

    /**
     * Get effective doctor reviews (for sub-users, returns parent's doctor reviews)
     */
    public function getEffectiveDoctorReviews()
    {
        $doctor = $this->getEffectiveDoctor();
        return $doctor ? $doctor->reviews() : collect();
    }

    /**
     * Check if user (or their parent) has an active doctor profile
     */
    public function hasActiveDoctorProfile(): bool
    {
        $doctor = $this->getEffectiveDoctor();
        return $doctor && $doctor->is_active;
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

/**
 * AI assistant results created by this doctor
 */
public function doctorAiAssistantResults()
{
    return $this->hasMany(AiAssistantResult::class, 'doctor_id');
}

/**
 * AI assistant results for this patient
 */
public function patientAiAssistantResults()
{
    return $this->hasMany(AiAssistantResult::class, 'patient_id');
}

}
