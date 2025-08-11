<?php

namespace App\Http\Controllers;

use App\Mail\ManualReminderMail;
use App\Services\EmailService;
use App\Models\User;
use App\Models\Setting;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\OpenAIUsage;
use App\Models\Subscription;
use App\Models\PatientAnalysis;
use App\Models\SystemSetting;
use App\Models\MonthlyInvoiceSetting;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;

use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Display a listing of all users.
     */
    public function index()
    {
        $users = User::with(['setting', 'patientAnalyses', 'monthlyInvoiceSetting', 'doctor', 'hospital'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validationRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{6,14}$/', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:doctor,hospital_admin,patient'],
            'monthly_price' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'yearly_price' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'monthly_cost_limit' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'grace_period_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'warning_period_days' => ['nullable', 'integer', 'min:1', 'max:14'],
            'reminder_frequency_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ];

        // Add role-specific validation rules
        if ($request->role === 'doctor') {
            $validationRules['specialty_select'] = ['nullable', 'string', 'max:255'];
            $validationRules['custom_specialty'] = ['nullable', 'string', 'max:255'];
            $validationRules['specialty'] = ['required', 'string', 'max:255'];
        }

        // Hospital admin will manage their own hospital info after creation
        // No hospital validation needed during user creation

        // Custom validation messages
        $messages = [];

        // Validate the request
        $request->validate($validationRules, $messages);

        // For hospital admin, assign to first available hospital or create a default one
        $hospitalId = null;
        if ($request->role === 'hospital_admin') {
            $hospital = \App\Models\Hospital::where('is_active', true)->first();
            if (!$hospital) {
                // Create a default hospital if none exists
                $hospital = \App\Models\Hospital::create([
                    'name' => 'Default Medical Center',
                    'address' => 'Medical District',
                    'city' => 'Healthcare City',
                    'state' => 'Medical State',
                    'zip_code' => '12345',
                    'phone' => '+1234567890',
                    'email' => 'info@defaultmedical.com',
                    'is_active' => true,
                ]);
            }
            $hospitalId = $hospital->id;
        }

        // Process specialty field for doctors
        $specialty = null;
        if ($request->role === 'doctor') {
            $specialty = $request->specialty;
            if ($request->specialty_select === 'other' && $request->filled('custom_specialty')) {
                $specialty = trim($request->custom_specialty);
            } elseif ($request->filled('specialty_select') && $request->specialty_select !== 'other') {
                $specialty = $request->specialty_select;
            }

            // Ensure we have a valid specialty for doctors
            if (empty($specialty)) {
                return back()->withErrors(['specialty' => 'Please select a medical specialty.'])->withInput();
            }
        }

        // Calculate trial end date
        $trialDays = (int) ($request->trial_days ?? 7);
        $trialEndsAt = $trialDays > 0 ? now()->addDays($trialDays) : null;

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'monthly_cost_limit' => $request->monthly_cost_limit ?? 0,
            'role' => $request->role,
            'hospital_id' => $hospitalId,
            'trial_ends_at' => $trialEndsAt,
            'trial_used' => $trialDays > 0, // Mark trial as used if trial period is set
        ];

        // Create the user first
        $user = User::create($userData);

        // Create role-specific profiles and settings
        if ($user->role === 'doctor') {
            // Create user settings with specialty
            $user->setting()->create([
                'specialty' => $specialty,
                'criterion' => 'CDC', // Default criterion
            ]);

            // Create doctor profile for doctor users
            $doctorSpecialty = Specialty::where('name', $specialty)->first();
            if (!$doctorSpecialty) {
                $doctorSpecialty = Specialty::first(); // Use first available specialty
            }

            if ($doctorSpecialty) {
                $user->doctor()->create([
                    'specialty_id' => $doctorSpecialty->id,
                    'license_number' => 'LIC' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                    'bio' => 'Medical professional using AI-assisted diagnosis system.',
                    'consultation_fee' => 5000, // $50.00 in cents
                    'appointment_duration' => 30,
                    'is_active' => true,
                    'is_verified' => true
                ]);
            }
        } elseif ($user->role === 'hospital_admin') {
            // Create basic settings for hospital admin (no specialty needed)
            $user->setting()->create([
                'specialty' => 'Hospital Administration',
                'criterion' => 'CDC', // Default criterion
            ]);
        } elseif ($user->role === 'patient') {
            // Create basic settings for patient
            $user->setting()->create([
                'specialty' => 'Patient',
                'criterion' => 'CDC', // Default criterion
            ]);
        }

        // Create user-specific pricing and monthly invoice setting
        if ($request->monthly_price || $request->yearly_price || $request->billing_amount) {
            // Create user-specific monthly invoice setting
            $setting = $user->getOrCreateMonthlyInvoiceSetting();

            // Prepare update data with defaults
            $updateData = [
                'grace_period_days' => (int) ($request->grace_period_days ?? 7),
                'warning_period_days' => (int) ($request->warning_period_days ?? 3),
                'reminder_frequency_days' => (int) ($request->reminder_frequency_days ?? 3),
                'subscription_period_months' => $request->subscription_period_months ?? 1,
                'is_active' => true,
                'is_restricted' => false,
                'restricted_pages' => ['ask-ai', 'dashboard', 'cases'],
            ];

            // Set billing amount (support both billing_amount and monthly_price)
            if ($request->billing_amount) {
                $updateData['billing_amount'] = $request->billing_amount;
            }

            // Set user-specific pricing
            if ($request->monthly_price) {
                $updateData['monthly_price'] = $request->monthly_price;
            }

            if ($request->yearly_price) {
                $updateData['yearly_price'] = $request->yearly_price;
            }

            $setting->update($updateData);
        }

        return redirect()->route('admin.users.index')
                        ->with('success', 'User created successfully with monthly invoice settings.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['setting', 'patientAnalyses', 'monthlyInvoiceSetting']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load(['setting', 'monthlyInvoiceSetting']);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validationRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{6,14}$/', 'unique:users,phone,'.$user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'specialty_select' => ['nullable', 'string', 'max:255'],
            'custom_specialty' => ['nullable', 'string', 'max:255'],
            'specialty' => ['required', 'string', 'max:255'],
            'monthly_price' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'yearly_price' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'monthly_cost_limit' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'grace_period_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'reminder_frequency_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'is_verified' => ['nullable', 'boolean'],
        ];

        // Validate the request
        $request->validate($validationRules);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'monthly_cost_limit' => $request->monthly_cost_limit ?? 0,
            'role' => 'doctor', // Ensure role is set to doctor for medical AI users
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        // Handle email verification status
        if ($request->has('is_verified')) {
            $userData['email_verified_at'] = $request->boolean('is_verified') ? now() : null;
        }

        // Process specialty field based on form input
        $specialty = $request->specialty;
        if ($request->specialty_select === 'other' && $request->filled('custom_specialty')) {
            $specialty = trim($request->custom_specialty);
        } elseif ($request->filled('specialty_select') && $request->specialty_select !== 'other') {
            $specialty = $request->specialty_select;
        }

        // Ensure we have a valid specialty
        if (empty($specialty)) {
            return back()->withErrors(['specialty' => 'Please select a medical specialty.'])->withInput();
        }

        $user->update($userData);

        // Update user settings
        if ($user->setting) {
            $user->setting->update([
                'specialty' => $specialty,
            ]);
        } else {
            $user->setting()->create([
                'specialty' => $specialty,
                'criterion' => 'CDC',
            ]);
        }

        // Update user-specific pricing (NOT system-wide)
        if ($request->monthly_price || $request->yearly_price) {
            // Get or create user-specific monthly invoice setting
            $setting = $user->monthlyInvoiceSetting ?? $user->getOrCreateMonthlyInvoiceSetting();

            $updateData = [
                'grace_period_days' => (int) ($request->grace_period_days ?? 7),
                'reminder_frequency_days' => (int) ($request->reminder_frequency_days ?? 3),
            ];

            // Update user-specific pricing
            if ($request->monthly_price) {
                $updateData['monthly_price'] = $request->monthly_price;
            }

            if ($request->yearly_price) {
                $updateData['yearly_price'] = $request->yearly_price;
            }

            $setting->update($updateData);
        }

        return redirect()->route('admin.users.index')
                        ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                            ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
                        ->with('success', 'User deleted successfully.');
    }



    /**
     * Show admin dashboard with statistics.
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'admin_users' => \App\Models\Admin::count(),
            'regular_users' => User::count(), // All users in the users table are regular users now
            'recent_users' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $recentUsers = User::latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }

    /**
     * Display patient analyses for a specific user.
     */
    public function userPatientAnalyses(User $user)
    {
        $patientAnalyses = $user->patientAnalyses()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Group patients by patient_key to count visits
        $patientGroups = [];
        $patientVisits = [];

        foreach ($user->patientAnalyses as $record) {
            // If patient_key is not set, use the name-age-gender combination
            $key = $record->patient_key ?? ($record->name . '-' . $record->age . '-' . $record->gender);

            if (!isset($patientGroups[$key])) {
                $patientGroups[$key] = $record; // Store the most recent record
                $patientVisits[$key] = ['count' => 0, 'patient' => $record];
            }

            $patientVisits[$key]['count']++;
        }

        // Add visit count to each record
        foreach ($patientAnalyses as $analysis) {
            $key = $analysis->patient_key ?? ($analysis->name . '-' . $analysis->age . '-' . $analysis->gender);
            $analysis->total_visits = $patientVisits[$key]['count'] ?? 1;
        }

        return view('admin.users.patient-analyses', compact('user', 'patientAnalyses'));
    }

    /**
     * Display billing dashboard with user subscriptions and usage
     */
    public function billing(Request $request)
    {
        $dateRange = $request->get('date_range', 'current_month');

        // Calculate date range
        switch ($dateRange) {
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'last_3_months':
                $startDate = Carbon::now()->subMonths(3)->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
            case 'current_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            default: // current_month
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
        }

        // Get users with their monthly invoice settings and usage data
        $users = User::with(['monthlyInvoiceSetting', 'openaiUsages' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->withCount(['openaiUsages as total_requests' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->get()
        ->map(function ($user) use ($startDate, $endDate) {
            $usage = OpenAIUsage::getUserUsageStats($user->id, $startDate, $endDate);
            $setting = $user->monthlyInvoiceSetting;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'stripe_customer_id' => $user->stripe_customer_id,
                'total_requests' => $usage['total_requests'],
                'total_tokens' => $usage['total_tokens'],
                'total_cost' => $usage['total_cost'],
                'monthly_usage' => $user->getMonthlyTokenUsage(),
                'monthly_cost_limit' => $user->monthly_cost_limit,
                'cost_usage_percentage' => $user->getCostUsagePercentage(),
                // Monthly Invoice Settings Data
                'monthly_price' => $setting->monthly_price ?? 0,
                'yearly_price' => $setting->yearly_price ?? 0,
                'billing_amount' => $setting->billing_amount ?? 0,
                'subscription_status' => $setting ? $setting->getSubscriptionStatus() : 'setup_pending',
                'subscription_starts_at' => $setting->subscription_starts_at ?? null,
                'subscription_ends_at' => $setting->subscription_ends_at ?? null,
                'is_active' => $setting->is_active ?? false,
                'is_restricted' => $setting->is_restricted ?? false,
                'grace_period_days' => $setting->grace_period_days ?? 0,
                'warning_period_days' => $setting->warning_period_days ?? 0,
                'days_remaining' => $setting ? $setting->getDaysRemaining() : null,
                'setting' => $setting,
            ];
        });

        // Calculate totals
        $totals = [
            'total_users' => $users->count(),
            'active_subscribers' => $users->where('is_active', true)->count(),
            'restricted_users' => $users->where('is_restricted', true)->count(),
            'total_requests' => $users->sum('total_requests'),
            'total_tokens' => $users->sum('total_tokens'),
            'total_cost' => $users->sum('total_cost'),
            'total_revenue' => $this->calculateTotalRevenue($startDate, $endDate),
        ];

        return view('admin.billing.index', compact('users', 'totals', 'dateRange', 'startDate', 'endDate'));
    }

    /**
     * Export billing data as CSV
     */
    public function exportBilling(Request $request)
    {
        $dateRange = $request->get('date_range', 'current_month');

        // Calculate date range (same logic as billing method)
        switch ($dateRange) {
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'last_3_months':
                $startDate = Carbon::now()->subMonths(3)->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
            case 'current_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
        }

        $users = User::with(['monthlyInvoiceSetting'])
            ->get()
            ->map(function ($user) use ($startDate, $endDate) {
                $usage = OpenAIUsage::getUserUsageStats($user->id, $startDate, $endDate);
                $setting = $user->monthlyInvoiceSetting;
                return [
                    'User ID' => $user->id,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Monthly Price' => $setting ? '$' . number_format($setting->monthly_price, 2) : 'N/A',
                    'Yearly Price' => $setting ? '$' . number_format($setting->yearly_price, 2) : 'N/A',
                    'Current Billing' => $setting && $setting->billing_amount > 0 ? '$' . number_format($setting->billing_amount, 2) : 'Not chosen',
                    'Subscription Status' => $setting ? $setting->getSubscriptionStatus() : 'setup_pending',
                    'Subscription Ends' => $user->getSubscriptionEndDate() ? $user->getSubscriptionEndDate()->format('Y-m-d') : 'N/A',
                    'Stripe Customer ID' => $user->stripe_customer_id ?? 'N/A',
                    'Total Requests' => $usage['total_requests'],
                    'Total Tokens' => number_format($usage['total_tokens']),
                    'Estimated Cost' => '$' . number_format($usage['total_cost'], 4),
                    'Monthly Token Usage' => number_format($user->getMonthlyTokenUsage()),
                    'Cost Limit' => $user->monthly_cost_limit ? '$' . number_format($user->monthly_cost_limit, 2) : 'Unlimited',
                    'Cost Usage Percentage' => number_format($user->getCostUsagePercentage(), 2) . '%',
                ];
            });

        $filename = 'billing_report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            if ($users->isNotEmpty()) {
                fputcsv($file, array_keys($users->first()));
            }

            // Add data rows
            foreach ($users as $user) {
                fputcsv($file, $user);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Display usage analytics dashboard
     */
    public function usageAnalytics(Request $request)
    {
        $period = $request->get('period', '30_days');

        // Calculate date range
        switch ($period) {
            case '7_days':
                $startDate = Carbon::now()->subDays(7);
                break;
            case '90_days':
                $startDate = Carbon::now()->subDays(90);
                break;
            case '1_year':
                $startDate = Carbon::now()->subYear();
                break;
            default: // 30_days
                $startDate = Carbon::now()->subDays(30);
        }

        $endDate = Carbon::now();

        // Daily usage statistics
        $dailyUsage = OpenAIUsage::selectRaw('DATE(created_at) as date, COUNT(*) as requests, SUM(total_tokens) as tokens, SUM(cost_estimate) as cost')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Usage by request type
        $usageByType = OpenAIUsage::selectRaw('request_type, COUNT(*) as requests, SUM(total_tokens) as tokens, SUM(cost_estimate) as cost')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('request_type')
            ->get();

        // Top users by usage
        $topUsers = User::withCount(['openaiUsages as total_requests' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->with(['openaiUsages' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                  ->selectRaw('user_id, SUM(total_tokens) as total_tokens, SUM(cost_estimate) as total_cost')
                  ->groupBy('user_id');
        }])
        ->having('total_requests', '>', 0)
        ->orderBy('total_requests', 'desc')
        ->limit(10)
        ->get();

        // Model usage statistics
        $modelUsage = OpenAIUsage::selectRaw('model_used, COUNT(*) as requests, SUM(total_tokens) as tokens, AVG(total_tokens) as avg_tokens')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('model_used')
            ->orderBy('requests', 'desc')
            ->get();

        return view('admin.analytics.usage', compact(
            'dailyUsage',
            'usageByType',
            'topUsers',
            'modelUsage',
            'period',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Calculate usage percentage for a user
     */
    private function calculateUsagePercentage($user): float
    {
        $planConfig = $user->getPlanConfig();
        $tokenLimit = $planConfig['token_limit'] ?? 0;

        if ($tokenLimit === -1) {
            return 0; // Unlimited plan
        }

        if ($tokenLimit === 0) {
            return 100; // Free plan with no usage allowed
        }

        $monthlyUsage = $user->getMonthlyTokenUsage();
        return ($monthlyUsage / $tokenLimit) * 100;
    }

    /**
     * Calculate total revenue for a date range
     */
    private function calculateTotalRevenue($startDate, $endDate): float
    {
        return Subscription::where('status', 'active')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Display system settings page
     */
    public function systemSettings()
    {
        $settings = SystemSetting::all()->keyBy('key');
        return view('admin.system-settings', compact('settings'));
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(Request $request)
    {
        $request->validate([
            'show_pricing_section' => 'boolean',
            'default_monthly_amount' => 'nullable|numeric|min:0|max:9999.99',
            'default_grace_period' => 'nullable|integer|min:1|max:30',
            'trial_days' => 'nullable|integer|min:1|max:365',
        ]);

        // Update pricing section visibility
        SystemSetting::set(
            'show_pricing_section',
            $request->has('show_pricing_section') ? '1' : '0',
            'boolean',
            'Show/hide the pricing information section on the home page'
        );

        // Note: default_monthly_amount setting is deprecated - now using per-user pricing

        // Update default grace period
        if ($request->filled('default_grace_period')) {
            SystemSetting::set(
                'default_grace_period',
                $request->default_grace_period,
                'integer',
                'Default grace period in days for new user accounts'
            );
        }

        // Update trial days
        if ($request->filled('trial_days')) {
            SystemSetting::set(
                'trial_days',
                $request->trial_days,
                'integer',
                'Number of free trial days for new users'
            );
        }

        return redirect()->back()->with('success', 'System settings updated successfully.');
    }

    /**
     * Display SMS settings page with country-based provider management
     */
    public function smsSettings()
    {
        $smsService = app(\App\Services\SmsService::class);
        $providers = $smsService->getAvailableProviders();
        $activeProvidersWithCountries = $smsService->getActiveProvidersWithCountries();
        $allCountries = \App\Models\SmsProviderCountry::getAllCountries();
        $unassignedCountries = \App\Models\SmsProviderCountry::getUnassignedCountries();

        return view('admin.sms-settings', compact(
            'providers',
            'activeProvidersWithCountries',
            'allCountries',
            'unassignedCountries'
        ));
    }

    /**
     * Assign countries to SMS provider
     */
    public function assignCountriesToProvider(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:twilio,plivo,messagebird,unifonic,smsgatewayhub,log',
            'countries' => 'required|array|min:1',
            'countries.*.code' => 'required|string|size:2',
            'countries.*.name' => 'required|string|max:255'
        ]);

        $smsService = app(\App\Services\SmsService::class);

        if ($smsService->assignCountriesToProvider($request->provider, $request->countries)) {
            $countryNames = collect($request->countries)->pluck('name')->join(', ');
            return redirect()->back()->with('success',
                "Successfully assigned {$countryNames} to {$request->provider} provider."
            );
        }

        return redirect()->back()->with('error', 'Failed to assign countries to provider');
    }

    /**
     * Remove country assignments from provider
     */
    public function removeProviderCountryAssignments(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:twilio,plivo,messagebird,unifonic,smsgatewayhub,log'
        ]);

        $smsService = app(\App\Services\SmsService::class);

        if ($smsService->removeProviderCountryAssignments($request->provider)) {
            return redirect()->back()->with('success',
                "Successfully removed all country assignments from {$request->provider} provider."
            );
        }

        return redirect()->back()->with('error', 'Failed to remove country assignments');
    }

    /**
     * Send test SMS with country-based routing
     */
    public function sendTestSms(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string|max:20'
        ]);

        $smsService = app(\App\Services\SmsService::class);
        $result = $smsService->send($request->test_phone, 'Test SMS from MedcuraAI - Country-based routing is working!');

        if ($result['success']) {
            return redirect()->back()->with('success', 'Test SMS sent successfully! ' . $result['message']);
        }

        return redirect()->back()->with('error', 'Failed to send test SMS: ' . $result['message']);
    }

    /**
     * Show the manual reminders form
     */
    public function showSendRemindersForm()
    {
        try {
            // Get all users with active monthly invoice settings
            $allUsers = User::whereHas('monthlyInvoiceSetting', function($query) {
                $query->where('is_active', true);
            })->with(['monthlyInvoiceSetting', 'stripeInvoices' => function($query) {
                $query->where('status', 'open');
            }])->get();

            // Categorize users by their current status
            $gracePeriodUsers = $allUsers->filter(function($user) {
                return $user->isInGracePeriod();
            });

            $warningPeriodUsers = $allUsers->filter(function($user) {
                return $user->isInWarningPeriod();
            });

            $overdueUsers = $allUsers->filter(function($user) {
                return $user->stripeInvoices->where('status', 'open')->where('due_date', '<', now())->count() > 0;
            });

            // Get all users for "All Types" option (users with active billing)
            $allEligibleUsers = $allUsers;

            // Ensure collections are not null
            $gracePeriodUsers = $gracePeriodUsers ?? collect();
            $warningPeriodUsers = $warningPeriodUsers ?? collect();
            $overdueUsers = $overdueUsers ?? collect();
            $allEligibleUsers = $allEligibleUsers ?? collect();

            return view('admin.send-reminders', compact(
                'gracePeriodUsers',
                'warningPeriodUsers',
                'overdueUsers',
                'allEligibleUsers'
            ));

        } catch (\Exception $e) {
            \Log::error('Error in showSendRemindersForm: ' . $e->getMessage());
            return redirect()->route('admin.dashboard')
                ->with('error', 'Unable to load reminders form. Please try again.');
        }
    }

    /**
     * Send manual reminders
     */
    public function sendManualReminders(Request $request)
    {
        try {
            // Log the request data for debugging
            \Log::info('Manual reminders request data:', $request->all());
            \Log::info('Manual reminders started by admin: ' . auth()->user()->email);

            // Log mail configuration for debugging
            \Log::info('Mail configuration check:', [
                'mail_mailer' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_from' => config('mail.from.address')
            ]);

            $request->validate([
                'reminder_type' => 'required|in:grace_period,warning_period,overdue,all',
                'user_ids' => 'nullable|array',
                'user_ids.*' => 'exists:users,id',
                'force_send' => 'boolean'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Manual reminders validation failed:', $e->errors());
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Please check your input and try again.');
        }

        $results = [
            'grace_reminders_sent' => 0,
            'warning_reminders_sent' => 0,
            'overdue_reminders_sent' => 0,
            'errors' => []
        ];

        try {
            // Safely get user_ids as array
            $userIds = $request->input('user_ids');
            if ($userIds && !is_array($userIds)) {
                $userIds = [$userIds];
            }

            switch ($request->reminder_type) {
                case 'grace_period':
                    $graceResults = $this->sendGracePeriodReminders($userIds, $request->boolean('force_send'));
                    $results = [
                        'grace_reminders_sent' => $graceResults['grace_reminders_sent'],
                        'warning_reminders_sent' => 0,
                        'overdue_reminders_sent' => 0,
                        'errors' => $graceResults['errors']
                    ];
                    break;

                case 'warning_period':
                    $warningResults = $this->sendWarningPeriodReminders($userIds, $request->boolean('force_send'));
                    $results = [
                        'grace_reminders_sent' => 0,
                        'warning_reminders_sent' => $warningResults['warning_reminders_sent'],
                        'overdue_reminders_sent' => 0,
                        'errors' => $warningResults['errors']
                    ];
                    break;

                case 'overdue':
                    $overdueResults = $this->sendOverdueReminders($userIds, $request->boolean('force_send'));
                    $results = [
                        'grace_reminders_sent' => 0,
                        'warning_reminders_sent' => 0,
                        'overdue_reminders_sent' => $overdueResults['overdue_reminders_sent'],
                        'errors' => $overdueResults['errors']
                    ];
                    break;

                case 'all':
                    $graceResults = $this->sendGracePeriodReminders(null, $request->boolean('force_send'));
                    $warningResults = $this->sendWarningPeriodReminders(null, $request->boolean('force_send'));
                    $overdueResults = $this->sendOverdueReminders(null, $request->boolean('force_send'));

                    $results = [
                        'grace_reminders_sent' => $graceResults['grace_reminders_sent'],
                        'warning_reminders_sent' => $warningResults['warning_reminders_sent'],
                        'overdue_reminders_sent' => $overdueResults['overdue_reminders_sent'],
                        'errors' => array_merge($graceResults['errors'], $warningResults['errors'], $overdueResults['errors'])
                    ];
                    break;
            }

            $totalSent = $results['grace_reminders_sent'] + $results['warning_reminders_sent'] + $results['overdue_reminders_sent'];
            $message = "Successfully sent {$totalSent} reminder(s). ";

            if ($results['grace_reminders_sent'] > 0) {
                $message .= "{$results['grace_reminders_sent']} grace period, ";
            }
            if ($results['warning_reminders_sent'] > 0) {
                $message .= "{$results['warning_reminders_sent']} warning period, ";
            }
            if ($results['overdue_reminders_sent'] > 0) {
                $message .= "{$results['overdue_reminders_sent']} overdue, ";
            }

            $message = rtrim($message, ', ') . '.';

            if (count($results['errors']) > 0) {
                $message .= ' ' . count($results['errors']) . ' error(s) occurred.';
                // Store detailed errors in session for debugging
                session()->flash('detailed_errors', $results['errors']);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send reminders: ' . $e->getMessage());
        }
    }

    /**
     * Send grace period reminders
     */
    private function sendGracePeriodReminders($userIds = null, $forceSend = false)
    {
        $results = ['grace_reminders_sent' => 0, 'errors' => []];

        \Log::info('Starting sendGracePeriodReminders', [
            'userIds' => $userIds,
            'forceSend' => $forceSend
        ]);

        $query = User::whereHas('monthlyInvoiceSetting', function($query) {
            $query->where('is_active', true);
        })->with('monthlyInvoiceSetting');

        if ($userIds && is_array($userIds) && count($userIds) > 0) {
            $query->whereIn('id', $userIds);
        }

        $users = $query->get();

        // Only filter by grace period status if not forcing send
        if (!$forceSend) {
            $users = $users->filter(function($user) {
                return $user->isInGracePeriod();
            });
        }

        \Log::info('Found users in grace period', [
            'total_users' => $users->count(),
            'user_emails' => $users->pluck('email')->toArray()
        ]);

        foreach ($users as $user) {
            try {
                $setting = $user->monthlyInvoiceSetting;

                // Check if we should send reminder (unless forced)
                if (!$forceSend) {
                    if ($setting->last_reminder_sent_at &&
                        !$setting->last_reminder_sent_at->addDays($setting->reminder_frequency_days)->isPast()) {
                        \Log::info('Skipping reminder - too soon since last reminder', [
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'last_reminder_sent_at' => $setting->last_reminder_sent_at,
                            'reminder_frequency_days' => $setting->reminder_frequency_days,
                            'next_reminder_allowed_at' => $setting->last_reminder_sent_at->addDays($setting->reminder_frequency_days)
                        ]);
                        continue; // Skip - too soon since last reminder
                    }
                }

                // Send email directly like contact form
                \Log::info('Sending grace period reminder', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->name
                ]);

                try {
                    \Log::info('About to send grace period reminder email and SMS', [
                        'user_email' => $user->email,
                        'user_phone' => $user->phone,
                        'timestamp' => now()->format('Y-m-d H:i:s.u')
                    ]);

                    // Send Email
                    $emailService = new EmailService();
                    $emailService->sendEmail(
                        $user->email,
                        'MedCura AI - Payment Reminder',
                        'emails.reminders.grace-period-simple',
                        [
                            'userName' => $user->name,
                            'userEmail' => $user->email,
                            'billingAmount' => $setting->billing_amount ?? 0,
                            'gracePeriodDays' => $setting->grace_period_days ?? 7,
                            'subscriptionEndsAt' => $setting->subscription_ends_at,
                            'reminderType' => 'grace_period',
                        ]
                    );

                    // Send SMS if user has phone number
                    if ($user->phone) {
                        try {
                            $smsService = new \App\Services\SmsService();
                            $daysRemaining = $setting->getDaysRemainingInCurrentPeriod();
                            $renewalUrl = route('subscription.manage');

                            $smsMessage = "🔔 MedCura AI: Your subscription expired but you're in grace period. {$daysRemaining} days remaining. Renew now: {$renewalUrl}";

                            $smsResult = $smsService->send($user->phone, $smsMessage);

                            if ($smsResult['success']) {
                                \Log::info('Grace period reminder SMS sent successfully', [
                                    'user_phone' => $user->phone,
                                    'provider' => $smsResult['data']['provider'] ?? 'unknown'
                                ]);
                            } else {
                                \Log::warning('Failed to send grace period reminder SMS', [
                                    'user_phone' => $user->phone,
                                    'error' => $smsResult['message']
                                ]);
                            }
                        } catch (\Exception $smsException) {
                            \Log::warning('SMS service error for grace period reminder', [
                                'user_phone' => $user->phone,
                                'error' => $smsException->getMessage()
                            ]);
                        }
                    }

                    \Log::info('Grace period reminder sent successfully', [
                        'user_email' => $user->email,
                        'user_phone' => $user->phone,
                        'timestamp' => now()->format('Y-m-d H:i:s.u')
                    ]);
                } catch (\Exception $mailException) {
                    \Log::error('Failed to send grace period reminder email to ' . $user->email . ': ' . $mailException->getMessage());
                    $results['errors'][] = "User {$user->id} ({$user->name}): " . $mailException->getMessage();
                    continue; // Continue to next user instead of throwing exception
                }

                // Update timestamp
                $setting->update(['last_reminder_sent_at' => now()]);

                $results['grace_reminders_sent']++;

            } catch (\Exception $e) {
                \Log::error('Failed to send grace period reminder', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $results['errors'][] = "User {$user->id} ({$user->name}): " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Send warning period reminders
     */
    private function sendWarningPeriodReminders($userIds = null, $forceSend = false)
    {
        $results = ['warning_reminders_sent' => 0, 'errors' => []];

        \Log::info('sendWarningPeriodReminders called', [
            'userIds' => $userIds,
            'forceSend' => $forceSend
        ]);

        $query = User::whereHas('monthlyInvoiceSetting', function($query) {
            $query->where('is_active', true);
        })->with('monthlyInvoiceSetting');

        if ($userIds && is_array($userIds) && count($userIds) > 0) {
            $query->whereIn('id', $userIds);
            \Log::info('Filtering by user IDs', ['userIds' => $userIds]);
        }

        $allUsers = $query->get();
        \Log::info('Users with monthly invoice settings found', [
            'count' => $allUsers->count(),
            'user_ids' => $allUsers->pluck('id')->toArray()
        ]);

        // Only filter by warning period status if not forcing send
        if (!$forceSend) {
            $users = $allUsers->filter(function($user) {
                $isInWarning = $user->isInWarningPeriod();
                \Log::info('Checking user warning period status', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'isInWarningPeriod' => $isInWarning
                ]);
                return $isInWarning;
            });
        } else {
            $users = $allUsers;
            \Log::info('Force send enabled - including all selected users', [
                'user_count' => $users->count(),
                'user_ids' => $users->pluck('id')->toArray()
            ]);
        }

        \Log::info('Users in warning period after filtering', [
            'count' => $users->count(),
            'user_ids' => $users->pluck('id')->toArray()
        ]);

        foreach ($users as $user) {
            try {
                $setting = $user->monthlyInvoiceSetting;

                // Check if we should send reminder (unless forced)
                if (!$forceSend) {
                    \Log::info('Checking reminder frequency', [
                        'user_id' => $user->id,
                        'last_reminder_sent_at' => $setting->last_reminder_sent_at ? $setting->last_reminder_sent_at->format('Y-m-d H:i:s') : null,
                        'reminder_frequency_days' => $setting->reminder_frequency_days,
                        'next_reminder_allowed_at' => $setting->last_reminder_sent_at ? $setting->last_reminder_sent_at->addDays($setting->reminder_frequency_days)->format('Y-m-d H:i:s') : null,
                        'is_past' => $setting->last_reminder_sent_at ? $setting->last_reminder_sent_at->addDays($setting->reminder_frequency_days)->isPast() : true
                    ]);

                    if ($setting->last_reminder_sent_at &&
                        !$setting->last_reminder_sent_at->addDays($setting->reminder_frequency_days)->isPast()) {
                        \Log::info('Skipping reminder - too soon since last reminder', [
                            'user_id' => $user->id,
                            'user_email' => $user->email
                        ]);
                        continue; // Skip - too soon since last reminder
                    }
                }

                // Send email directly like contact form
                \Log::info('Sending warning period reminder', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->name
                ]);

                try {
                    // Send Email
                    $emailService = new EmailService();
                    $emailService->sendEmail(
                        $user->email,
                        'MedCura AI - Payment Due',
                        'emails.reminders.warning-period',
                        [
                            'userName' => $user->name,
                            'userEmail' => $user->email,
                            'billingAmount' => $setting->billing_amount ?? 0,
                            'gracePeriodDays' => $setting->grace_period_days ?? 7,
                            'subscriptionEndsAt' => $setting->subscription_ends_at,
                            'reminderType' => 'warning_period',
                        ]
                    );

                    // Send SMS if user has phone number
                    if ($user->phone) {
                        try {
                            $smsService = new \App\Services\SmsService();
                            $daysRemaining = $setting->getDaysRemainingInCurrentPeriod();
                            $renewalUrl = route('subscription.manage');

                            $smsMessage = "🚨 URGENT - MedCura AI: FINAL WARNING! Your account will be RESTRICTED in {$daysRemaining} days. Renew immediately: {$renewalUrl}";

                            $smsResult = $smsService->send($user->phone, $smsMessage);

                            if ($smsResult['success']) {
                                \Log::info('Warning period reminder SMS sent successfully', [
                                    'user_phone' => $user->phone,
                                    'provider' => $smsResult['data']['provider'] ?? 'unknown'
                                ]);
                            } else {
                                \Log::warning('Failed to send warning period reminder SMS', [
                                    'user_phone' => $user->phone,
                                    'error' => $smsResult['message']
                                ]);
                            }
                        } catch (\Exception $smsException) {
                            \Log::warning('SMS service error for warning period reminder', [
                                'user_phone' => $user->phone,
                                'error' => $smsException->getMessage()
                            ]);
                        }
                    }

                    \Log::info('Warning period reminder sent successfully', [
                        'user_email' => $user->email,
                        'user_phone' => $user->phone
                    ]);
                } catch (\Exception $mailException) {
                    \Log::error('Failed to send warning period reminder email to ' . $user->email . ': ' . $mailException->getMessage());
                    $results['errors'][] = "User {$user->id} ({$user->name}): " . $mailException->getMessage();
                    continue; // Continue to next user instead of throwing exception
                }

                // Update timestamp
                $setting->update(['last_reminder_sent_at' => now()]);

                $results['warning_reminders_sent']++;

            } catch (\Exception $e) {
                $results['errors'][] = "User {$user->id} ({$user->name}): " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Send overdue reminders
     */
    private function sendOverdueReminders($userIds = null, $forceSend = false)
    {
        $results = ['overdue_reminders_sent' => 0, 'errors' => []];

        $query = User::whereHas('stripeInvoices', function($query) {
            $query->where('status', 'open')
                  ->where('due_date', '<', now());
        })->with(['stripeInvoices' => function($query) {
            $query->where('status', 'open')
                  ->where('due_date', '<', now());
        }]);

        if ($userIds && is_array($userIds) && count($userIds) > 0) {
            $query->whereIn('id', $userIds);
        }

        $users = $query->get();

        foreach ($users as $user) {
            foreach ($user->stripeInvoices as $invoice) {
                try {
                    // Check if we should send reminder (unless forced)
                    if (!$forceSend && !$invoice->needsReminder()) {
                        continue; // Skip - doesn't need reminder yet
                    }

                    // Send email directly like contact form
                    \Log::info('Sending overdue reminder', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'user_name' => $user->name,
                        'invoice_id' => $invoice->id
                    ]);

                    try {
                        // For overdue, we need to pass the invoice data differently
                        // Create a fake setting for the email template
                        $fakeSetting = new \App\Models\MonthlyInvoiceSetting([
                            'billing_amount' => $invoice->amount_due / 100, // Convert from cents
                            'subscription_period_months' => 1,
                            'subscription_starts_at' => $invoice->created_at,
                            'subscription_ends_at' => $invoice->due_date,
                            'grace_period_days' => 7,
                            'warning_period_days' => 3,
                            'is_active' => true,
                        ]);

                        // Send Email
                        $emailService = new EmailService();
                        $emailService->sendEmail(
                            $user->email,
                            'MedCura AI - Account Update Needed',
                            'emails.reminders.overdue-simple',
                            [
                                'userName' => $user->name,
                                'userEmail' => $user->email,
                                'billingAmount' => $invoice->amount_due / 100,
                                'gracePeriodDays' => 7,
                                'subscriptionEndsAt' => $invoice->due_date,
                                'reminderType' => 'overdue',
                            ]
                        );

                        // Send SMS if user has phone number
                        if ($user->phone) {
                            try {
                                $smsService = new \App\Services\SmsService();
                                $amount = number_format($invoice->amount_due / 100, 2);
                                $renewalUrl = route('subscription.manage');

                                $smsMessage = "⚠️ MedCura AI: Your invoice of \${$amount} is overdue. Update your payment method to avoid service interruption: {$renewalUrl}";

                                $smsResult = $smsService->send($user->phone, $smsMessage);

                                if ($smsResult['success']) {
                                    \Log::info('Overdue reminder SMS sent successfully', [
                                        'user_phone' => $user->phone,
                                        'invoice_id' => $invoice->id,
                                        'provider' => $smsResult['data']['provider'] ?? 'unknown'
                                    ]);
                                } else {
                                    \Log::warning('Failed to send overdue reminder SMS', [
                                        'user_phone' => $user->phone,
                                        'invoice_id' => $invoice->id,
                                        'error' => $smsResult['message']
                                    ]);
                                }
                            } catch (\Exception $smsException) {
                                \Log::warning('SMS service error for overdue reminder', [
                                    'user_phone' => $user->phone,
                                    'invoice_id' => $invoice->id,
                                    'error' => $smsException->getMessage()
                                ]);
                            }
                        }

                        \Log::info('Overdue reminder sent successfully', [
                            'user_email' => $user->email,
                            'user_phone' => $user->phone,
                            'invoice_id' => $invoice->id
                        ]);
                    } catch (\Exception $mailException) {
                        \Log::error('Failed to send overdue reminder email to ' . $user->email . ': ' . $mailException->getMessage());
                        $results['errors'][] = "Invoice {$invoice->id} for user {$user->id} ({$user->name}): " . $mailException->getMessage();
                        continue; // Continue to next invoice instead of throwing exception
                    }

                    // Update invoice reminder tracking
                    $invoice->markReminderSent();

                    $results['overdue_reminders_sent']++;

                } catch (\Exception $e) {
                    $results['errors'][] = "Invoice {$invoice->id} for user {$user->id} ({$user->name}): " . $e->getMessage();
                }
            }
        }

        return $results;
    }

    /**
     * Toggle doctor account status (activate/deactivate)
     */
    public function toggleDoctorStatus(User $user)
    {
        try {
            // Check if user has a doctor profile
            if (!$user->doctor) {
                return redirect()->back()->with('error', 'This user does not have a doctor profile.');
            }

            // Toggle the is_active status
            $newStatus = !$user->doctor->is_active;
            $user->doctor->update(['is_active' => $newStatus]);

            $statusText = $newStatus ? 'activated' : 'deactivated';
            $message = "Doctor account for {$user->name} has been {$statusText} successfully.";

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Error toggling doctor status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update doctor status. Please try again.');
        }
    }

    /**
     * Manage hospital admin and their hospital
     */
    public function manageHospitalAdmin(User $user)
    {
        if ($user->role !== 'hospital_admin') {
            return redirect()->back()->with('error', 'This user is not a hospital admin.');
        }

        return view('admin.hospital-admins.manage', compact('user'));
    }

    /**
     * Create hospital for hospital admin
     */
    public function createHospitalForAdmin(Request $request, User $user)
    {
        if ($user->role !== 'hospital_admin') {
            return redirect()->back()->with('error', 'This user is not a hospital admin.');
        }

        if ($user->hospital) {
            return redirect()->back()->with('error', 'This hospital admin already has a hospital.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
        ]);

        try {
            $hospital = \App\Models\Hospital::create([
                'name' => $request->name,
                'slug' => \Illuminate\Support\Str::slug($request->name),
                'email' => $request->email,
                'phone' => $request->phone,
                'website' => $request->website,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'is_active' => true,
            ]);

            $user->update(['hospital_id' => $hospital->id]);

            return redirect()->back()->with('success', 'Hospital created successfully and assigned to hospital admin.');
        } catch (\Exception $e) {
            \Log::error('Error creating hospital for admin: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create hospital. Please try again.');
        }
    }

    /**
     * Update hospital for hospital admin
     */
    public function updateHospitalForAdmin(Request $request, User $user)
    {
        if ($user->role !== 'hospital_admin' || !$user->hospital) {
            return redirect()->back()->with('error', 'Invalid hospital admin or no hospital found.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        try {
            $user->hospital->update([
                'name' => $request->name,
                'slug' => \Illuminate\Support\Str::slug($request->name),
                'email' => $request->email,
                'phone' => $request->phone,
                'website' => $request->website,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return redirect()->back()->with('success', 'Hospital information updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating hospital for admin: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update hospital. Please try again.');
        }
    }

    /**
     * Manage doctors for hospital admin
     */
    public function manageHospitalDoctors(User $user)
    {
        if ($user->role !== 'hospital_admin' || !$user->hospital) {
            return redirect()->back()->with('error', 'Invalid hospital admin or no hospital found.');
        }

        $doctors = $user->hospital->doctors()->with(['doctor.specialty'])->paginate(15);

        return view('admin.hospital-admins.doctors', compact('user', 'doctors'));
    }

    /**
     * Toggle doctor status for hospital admin's doctors
     */
    public function toggleHospitalDoctorStatus(User $user, User $doctor)
    {
        if ($user->role !== 'hospital_admin' || !$user->hospital) {
            return redirect()->back()->with('error', 'Invalid hospital admin or no hospital found.');
        }

        if ($doctor->hospital_id !== $user->hospital_id) {
            return redirect()->back()->with('error', 'This doctor does not belong to this hospital.');
        }

        if (!$doctor->doctor) {
            return redirect()->back()->with('error', 'This user does not have a doctor profile.');
        }

        try {
            $newStatus = !$doctor->doctor->is_active;
            $doctor->doctor->update(['is_active' => $newStatus]);

            $statusText = $newStatus ? 'activated' : 'deactivated';
            $message = "Doctor {$doctor->name} has been {$statusText} successfully.";

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error toggling hospital doctor status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update doctor status. Please try again.');
        }
    }

    /**
     * Login as another user (Admin impersonation)
     */
    public function loginAs(User $user)
    {
        // Check if admin is authenticated using admin guard
        if (!auth('admin')->check()) {
            return redirect()->route('admin.login')->with('error', 'You must be logged in as admin.');
        }

        $admin = auth('admin')->user();

        // Additional check to ensure admin object exists and has required properties
        if (!$admin || !isset($admin->id) || !isset($admin->name)) {
            return redirect()->route('admin.login')->with('error', 'Admin session is invalid. Please login again.');
        }

        // Validate that the target user is either hospital_admin or doctor
        if (!in_array($user->role, ['hospital_admin', 'doctor'])) {
            return redirect()->back()->with('error', 'You can only login as Hospital Admins or Doctors.');
        }

        // Clear ALL existing impersonation sessions to prevent conflicts
        // This is CRITICAL for direct admin impersonation to work correctly
        session()->forget([
            'impersonating_hospital_admin_id',
            'impersonating_hospital_admin_name',
            'hospital_admin_impersonation_started_at',
            'hospital_admin_impersonation_ip',
            'impersonation_started_at',
            'impersonation_ip',
            // Clear any existing admin impersonation to start fresh
            'impersonating_user_id',
            'admin_impersonation_started_at',
            'admin_impersonation_ip'
        ]);

        // Force session save to ensure cleanup is applied immediately
        session()->save();

        // Store admin and user session info for impersonation
        session([
            'impersonating_admin_id' => $admin->id,
            'impersonating_admin_name' => $admin->name,
            'impersonating_user_id' => $user->id,
            'admin_impersonation_started_at' => now()->timestamp,
            'admin_impersonation_ip' => request()->ip(),
        ]);

        // CRITICAL: Actually log the user into the web guard
        // This ensures they pass the 'auth' middleware on protected routes
        auth('web')->login($user);

        // Log admin impersonation
        \App\Services\AuditLoggingService::logAdminImpersonation(
            $admin->id,
            $user->id,
            [
                'target_user_name' => $user->name,
                'target_user_email' => $user->email,
                'target_user_role' => $user->role
            ]
        );

        // Log the impersonation for security audit
        \Log::info('Admin impersonation started', [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'target_user_role' => $user->role,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'web_auth_check' => auth('web')->check(),
            'web_auth_user_id' => auth('web')->id(),
        ]);

        // Redirect to appropriate dashboard - admin sees exactly what the user sees
        if ($user->role === 'hospital_admin') {
            return redirect()->route('hospital-admin.dashboard')
                ->with('success', "You are now logged in as Hospital Admin: {$user->name}");
        } elseif ($user->role === 'doctor') {
            return redirect()->route('dashboard')
                ->with('success', "You are now logged in as Doctor: {$user->name}");
        }

        return redirect()->route('dashboard');
    }

    /**
     * Return to admin from user impersonation
     */
    public function returnToAdmin()
    {
        // CRITICAL DEBUG: Log that method is being called
        \Log::emergency('🚨 returnToAdmin method called!', [
            'timestamp' => now()->toDateTimeString(),
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
        ]);

        // Log the attempt for debugging
        \Log::info('Return to admin attempt', [
            'session_data' => [
                'impersonating_admin_id' => session('impersonating_admin_id'),
                'impersonating_admin_name' => session('impersonating_admin_name'),
                'impersonating_user_id' => session('impersonating_user_id'),
                'admin_impersonation_started_at' => session('admin_impersonation_started_at'),
                'admin_impersonation_ip' => session('admin_impersonation_ip'),
            ],
            'current_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'web_auth_check' => auth('web')->check(),
            'web_auth_user_id' => auth('web')->id(),
            'admin_auth_check' => auth('admin')->check(),
            'admin_auth_user_id' => auth('admin')->id(),
        ]);

        // Validate admin impersonation session
        $adminId = session('impersonating_admin_id');
        $adminName = session('impersonating_admin_name');
        $userId = session('impersonating_user_id');
        $startedAt = session('admin_impersonation_started_at');
        $sessionIp = session('admin_impersonation_ip');

        if (!$adminId || !$adminName || !$userId || !$startedAt) {
            \Log::warning('Invalid admin impersonation session - missing data', [
                'adminId' => $adminId,
                'adminName' => $adminName,
                'userId' => $userId,
                'startedAt' => $startedAt,
            ]);
            $this->clearImpersonationSession();
            return redirect()->route('admin.login')->with('error', 'Invalid admin impersonation session.');
        }

        // Security checks - Allow IP changes for now (can be restrictive in some environments)
        if ($sessionIp && $sessionIp !== request()->ip()) {
            \Log::warning('Admin impersonation IP mismatch', [
                'session_ip' => $sessionIp,
                'current_ip' => request()->ip(),
                'admin_id' => $adminId,
            ]);
            // Don't fail on IP mismatch for now, just log it
            // $this->clearImpersonationSession();
            // return redirect()->route('admin.login')->with('error', 'Security violation: IP address mismatch.');
        }

        // Check session expiry (24 hours)
        if ((now()->timestamp - $startedAt) > 86400) {
            \Log::warning('Admin impersonation session expired', [
                'started_at' => $startedAt,
                'current_time' => now()->timestamp,
                'duration' => now()->timestamp - $startedAt,
            ]);
            $this->clearImpersonationSession();
            return redirect()->route('admin.login')->with('error', 'Admin impersonation session expired.');
        }

        // Find the admin user
        $admin = \App\Models\Admin::find($adminId);
        if (!$admin) {
            \Log::error('Admin not found during return from impersonation', [
                'admin_id' => $adminId,
            ]);
            $this->clearImpersonationSession();
            return redirect()->route('admin.login')->with('error', 'Invalid admin user.');
        }

        // Get impersonated user for logging
        $user = \App\Models\User::find($userId);
        $userName = $user ? $user->name : 'Unknown';

        // Log the end of impersonation
        \Log::info('Admin impersonation ended successfully', [
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'impersonated_user_id' => $userId,
            'impersonated_user_name' => $userName,
            'duration_seconds' => now()->timestamp - $startedAt,
            'ip_address' => request()->ip(),
        ]);

        // Clear impersonation session
        $this->clearImpersonationSession();

        // Login back as admin (like Hospital Admin does)
        auth()->login($admin);

        // Log admin impersonation ended
        \App\Services\AuditLoggingService::logAdminImpersonationEnded(
            $admin->id,
            [
                'impersonated_user_id' => $userId,
                'impersonated_user_name' => $userName
            ]
        );

        // Log the successful return
        \Log::info('Admin successfully returned from impersonation', [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'auth_check' => auth()->check(),
            'auth_user_id' => auth()->id(),
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', "Welcome back, {$adminName}! You have returned from impersonation.");
    }

    /**
     * Clear impersonation session
     */
    private function clearImpersonationSession(): void
    {
        session()->forget([
            'impersonating_user_id',
            'impersonating_admin_id',
            'impersonating_admin_name',
            'admin_impersonation_started_at',
            'admin_impersonation_ip'
        ]);
    }
}
