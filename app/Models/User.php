<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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
        'hospital_id',
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

    /**
     * @property int $id
     * @property string|null $sub_user_role
     * @property int|null $parent_user_id
     */

    public function setting()
    {
        return $this->hasOne(Setting::class);
    }

    public function patientAnalyses()
    {
        return $this->hasMany(PatientAnalysis::class);
    }

    /**
     * Get the patient's data (latest record)
     */
    public function patientData()
    {
        return $this->hasOne(PatientData::class, 'assigned_patient_id')->latest();
    }

    // Doctor relationship
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    // Hospital relationship
    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    // Note: Department relationship removed since we simplified doctor management

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

    // Patient risk scores
    public function patientRiskScores()
    {
        return $this->hasMany(PatientRiskScore::class, 'patient_id');
    }

    // Get risk score for a specific appointment
    public function getRiskScoreForAppointment(Appointment $appointment)
    {
        return $this->patientRiskScores()->where('appointment_id', $appointment->id)->first();
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
    /** Check if user is a doctor */
    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    /**
     * Check if user is a patient
     */
    /** Check if user is a patient */
    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    /**
     * Check if user is a system admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a hospital admin
     */
    public function isHospitalAdmin()
    {
        return $this->role === 'hospital_admin';
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
    public function getMonthlyInvoices(int $month = null, int $year = null): \Illuminate\Database\Eloquent\Relations\HasMany
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
    if ($this->monthlyInvoiceSetting) {
        return $this->monthlyInvoiceSetting;
    }

    // Get pricing from system settings
    $defaultMonthly = SystemSetting::get('saas_professional_monthly', 30);
    $defaultYearly = SystemSetting::get('saas_professional_yearly', 300);

    return $this->monthlyInvoiceSetting()->create([
        'billing_amount' => $defaultMonthly, // Default to monthly billing
        'monthly_price' => $defaultMonthly,
        'yearly_price' => $defaultYearly,
        'grace_period_days' => 7,
        'reminder_frequency_days' => 3,
        'is_restricted' => false,
        'is_active' => true, // Set to active for subscription-ready state
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
    return (bool) $this->trial_used;
}

/**
 * Start trial for user
 */
public function startTrial(): void
{
    if ($this->hasUsedTrial()) {
        return; // User already used their trial
    }

    $trialDays = SystemSetting::get('trial_days', 14);

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
/** Get diagnoses made by this doctor */
public function doctorDiagnoses(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Diagnosis::class, 'doctor_id');
}

/**
 * Diagnoses received by this patient
 */
/** Get diagnoses received by this patient */
public function patientDiagnoses(): \Illuminate\Database\Eloquent\Relations\HasMany
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
/** Get patients assigned to this doctor */
public function assignedPatients(): \Illuminate\Database\Eloquent\Relations\HasMany
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

/**
 * Get all notifications for this user
 */
public function notifications()
{
    return $this->morphMany(\Illuminate\Notifications\DatabaseNotification::class, 'notifiable')
        ->orderBy('created_at', 'desc');
}

/**
 * Get unread notifications for this user
 */
public function unreadNotifications()
{
    return $this->notifications()->whereNull('read_at');
}

/**
 * Get read notifications for this user
 */
public function readNotifications()
{
    return $this->notifications()->whereNotNull('read_at');
}

/**
 * Get unread notification count
 */
public function unreadNotificationsCount()
{
    return $this->unreadNotifications()->count();
}

/**
 * Mark all notifications as read
 */
public function markAllNotificationsAsRead()
{
    $this->unreadNotifications()->update(['read_at' => now()]);
}

/**
 * Get notification preferences
 */
public function notificationPreferences()
{
    return $this->hasOne(\App\Models\NotificationPreference::class);
}

/**
 * Get or create notification preferences
 */
public function getOrCreateNotificationPreferences()
{
    if ($this->notificationPreferences) {
        return $this->notificationPreferences;
    }

    return $this->notificationPreferences()->create([
        'email_enabled' => true,
        'email_appointment_reminders' => true,
        'email_diagnosis_updates' => true,
        'email_review_requests' => true,
        'email_system_alerts' => true,
        'email_marketing' => false,
        'sms_enabled' => false,
        'sms_appointment_reminders' => false,
        'sms_urgent_alerts' => true,
        'in_app_enabled' => true,
        'in_app_sound' => true,
        'in_app_desktop' => true,
        'in_app_vibrate' => false,
        'frequency' => 'immediate',
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '08:00',
        'respect_quiet_hours' => true,
        'appointment_booked' => true,
        'appointment_reminder' => true,
        'diagnosis_submitted' => true,
        'review_submitted' => true,
        'voice_transcription_completed' => true,
        'system_alert' => true,
    ]);
}

/**
 * Check if user wants to receive notifications of a specific type
 */
public function wantsNotification(string $type): bool
{
    $preferences = $this->getOrCreateNotificationPreferences();

    switch ($type) {
        case 'appointment_booked':
            return $preferences->appointment_booked;
        case 'appointment_reminder':
            return $preferences->appointment_reminder;
        case 'diagnosis_submitted':
            return $preferences->diagnosis_submitted;
        case 'review_submitted':
            return $preferences->review_submitted;
        case 'voice_transcription_completed':
            return $preferences->voice_transcription_completed;
        case 'system_alert':
            return $preferences->system_alert;
        default:
            return true;
    }
}

/**
 * Check if user wants to receive notifications via a specific channel
 */
public function wantsNotificationChannel(string $channel): bool
{
    $preferences = $this->getOrCreateNotificationPreferences();

    switch ($channel) {
        case 'email':
            return $preferences->email_enabled;
        case 'sms':
            return $preferences->sms_enabled;
        case 'whatsapp':
            return $preferences->whatsapp_enabled;
        case 'in_app':
            return $preferences->in_app_enabled;
        default:
            return false;
    }
}

/**
 * Check if it's currently quiet hours for this user
 */
public function isQuietHours(): bool
{
    $preferences = $this->getOrCreateNotificationPreferences();

    if (!$preferences->respect_quiet_hours) {
        return false;
    }

    $now = now();
    $currentTime = $now->format('H:i');
    $startTime = $preferences->quiet_hours_start;
    $endTime = $preferences->quiet_hours_end;

    // Handle overnight quiet hours (e.g., 22:00 to 08:00)
    if ($startTime > $endTime) {
        return $currentTime >= $startTime || $currentTime <= $endTime;
    }

    return $currentTime >= $startTime && $currentTime <= $endTime;
}

/**
 * Get notification frequency setting
 */
public function getNotificationFrequency(): string
{
    return $this->getOrCreateNotificationPreferences()->frequency;
}

/**
 * Send notification if user wants to receive it
 */
public function notifyIfWants($instance, $type = null)
{
    // Try to get type from the notification's toArray method if available
    if (!$type && method_exists($instance, 'toArray')) {
        $data = $instance->toArray($this);
        $type = $data['type'] ?? 'general';
    }

    // Fallback to general if no type is provided
    $type = $type ?? 'general';

    if ($this->wantsNotification($type)) {
        $this->notify($instance);
    }
}


public function managedDoctors()
{
    if (!$this->isHospitalAdmin() || !$this->hospital_id) {
        return collect();
    }

    return $this->hospital->doctors();
}

/**
 * Check if this hospital admin can manage a specific doctor
 */
public function canManageDoctor(User $doctor): bool
{
    if (!$this->isHospitalAdmin() || !$doctor->isDoctor()) {
        return false;
    }

    return $doctor->hospital_id === $this->hospital_id;
}

/**
 * Get hospital admin statistics
 */
public function getHospitalAdminStatistics(): array
{
    if (!$this->isHospitalAdmin() || !$this->hospital) {
        return [];
    }

    return $this->hospital->getStatistics();
}

/**
 * Check if user is responsible for payments (hospital admin or standalone doctor)
 */
public function isPaymentResponsible(): bool
{
    // Hospital admins are always responsible for payments
    if ($this->isHospitalAdmin()) {
        return true;
    }

    // Standalone doctors (not associated with a hospital) are responsible for their own payments
    if ($this->isDoctor() && !$this->hospital_id) {
        return true;
    }

    // Doctors under a hospital are not responsible for payments
    return false;
}

/**
 * Get the user responsible for payments (for billing purposes)
 */
public function getPaymentResponsibleUser(): ?User
{
    // If this user is payment responsible, return self
    if ($this->isPaymentResponsible()) {
        return $this;
    }

    // If this is a doctor under a hospital, return the hospital admin
    if ($this->isDoctor() && $this->hospital_id && $this->hospital) {
        return $this->hospital->admin;
    }

    // For sub-users, get the payment responsible user of their parent
    if ($this->isSubUser() && $this->parentUser) {
        return $this->parentUser->getPaymentResponsibleUser();
    }

    return null;
}





}
