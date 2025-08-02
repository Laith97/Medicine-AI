<?php

namespace App\Http\Controllers;

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
        $users = User::with(['setting', 'patientAnalyses', 'monthlyInvoiceSetting', 'doctor'])
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
            'specialty_select' => ['nullable', 'string', 'max:255'],
            'custom_specialty' => ['nullable', 'string', 'max:255'],
            'specialty' => ['required', 'string', 'max:255'],
            'billing_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'monthly_cost_limit' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'subscription_period_months' => ['nullable', 'integer', 'in:1,3,6,12,24,36,-1'],
            'grace_period_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'warning_period_days' => ['nullable', 'integer', 'min:1', 'max:14'],
            'reminder_frequency_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];

        // Validate the request
        $request->validate($validationRules);

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

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'monthly_cost_limit' => $request->monthly_cost_limit ?? 0,
            'role' => 'doctor', // Set role to doctor for medical AI users
        ];

        // Create the user first
        $user = User::create($userData);

        // Create user settings with specialty
        $user->setting()->create([
            'specialty' => $specialty,
            'criterion' => 'CDC', // Default criterion
        ]);

        // Create doctor profile for doctor users
        if ($user->role === 'doctor') {
            // Get or create a default specialty for the doctor profile
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
        }

        // Create monthly invoice setting using provided amount or system defaults
        $monthlyAmount = $request->billing_amount;
        $subscriptionPeriod = $request->subscription_period_months ?? 1;
        $gracePeriod = $request->grace_period_days ?? SystemSetting::get('default_grace_period', 7);
        $warningPeriod = $request->warning_period_days ?? 3;
        $reminderFrequency = $request->reminder_frequency_days ?? 3;

        // Use system default if no amount provided
        if (!$monthlyAmount && SystemSetting::get('default_monthly_amount')) {
            $monthlyAmount = SystemSetting::get('default_monthly_amount');
        }

        if ($monthlyAmount && $monthlyAmount > 0) {
            $user->monthlyInvoiceSetting()->create([
                'billing_amount' => $monthlyAmount,
                'subscription_period_months' => $subscriptionPeriod,
                'is_active' => true,
                'grace_period_days' => $gracePeriod,
                'warning_period_days' => $warningPeriod,
                'reminder_frequency_days' => $reminderFrequency,
                'restricted_pages' => ['ask-ai', 'dashboard', 'cases'],
                'is_restricted' => false,
            ]);
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
            'billing_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'monthly_cost_limit' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'grace_period_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'reminder_frequency_days' => ['nullable', 'integer', 'min:1', 'max:30'],
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

        // Update or create monthly invoice setting
        if ($request->filled('billing_amount') && $request->billing_amount > 0) {
            $monthlyData = [
                'billing_amount' => $request->billing_amount,
                'is_active' => true,
                'grace_period_days' => $request->grace_period_days ?? 7,
                'reminder_frequency_days' => $request->reminder_frequency_days ?? 3,
                'restricted_pages' => ['ask-ai', 'dashboard', 'cases'],
            ];

            if ($user->monthlyInvoiceSetting) {
                $user->monthlyInvoiceSetting->update($monthlyData);
            } else {
                $monthlyData['is_restricted'] = false;
                $user->monthlyInvoiceSetting()->create($monthlyData);
            }
        } elseif ($user->monthlyInvoiceSetting) {
            // If monthly amount is removed, deactivate the setting
            $user->monthlyInvoiceSetting->update(['is_active' => false]);
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

        // Get users with their subscription and usage data
        $users = User::with(['activeSubscription', 'openaiUsages' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->withCount(['openaiUsages as total_requests' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->get()
        ->map(function ($user) use ($startDate, $endDate) {
            $usage = OpenAIUsage::getUserUsageStats($user->id, $startDate, $endDate);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'current_plan' => $user->current_plan,
                'subscription_active' => $user->subscription_active,
                'subscription_ends_at' => $user->subscription_ends_at,
                'stripe_customer_id' => $user->stripe_customer_id,
                'total_requests' => $usage['total_requests'],
                'total_tokens' => $usage['total_tokens'],
                'total_cost' => $usage['total_cost'],
                'monthly_usage' => $user->getMonthlyTokenUsage(),
                'token_limit' => $user->getPlanConfig()['token_limit'] ?? 0,
                'usage_percentage' => $this->calculateUsagePercentage($user),
                'subscription' => $user->activeSubscription,
            ];
        });

        // Calculate totals
        $totals = [
            'total_users' => $users->count(),
            'active_subscribers' => $users->where('subscription_active', true)->count(),
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

        $users = User::with(['activeSubscription'])
            ->get()
            ->map(function ($user) use ($startDate, $endDate) {
                $usage = OpenAIUsage::getUserUsageStats($user->id, $startDate, $endDate);
                return [
                    'User ID' => $user->id,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Current Plan' => ucfirst($user->current_plan),
                    'Subscription Active' => $user->subscription_active ? 'Yes' : 'No',
                    'Subscription Ends' => $user->subscription_ends_at ? $user->subscription_ends_at->format('Y-m-d') : 'N/A',
                    'Stripe Customer ID' => $user->stripe_customer_id ?? 'N/A',
                    'Total Requests' => $usage['total_requests'],
                    'Total Tokens' => number_format($usage['total_tokens']),
                    'Estimated Cost' => '$' . number_format($usage['total_cost'], 4),
                    'Monthly Token Usage' => number_format($user->getMonthlyTokenUsage()),
                    'Token Limit' => $user->getPlanConfig()['token_limit'] === -1 ? 'Unlimited' : number_format($user->getPlanConfig()['token_limit'] ?? 0),
                    'Usage Percentage' => number_format($this->calculateUsagePercentage($user), 2) . '%',
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
        ]);

        // Update pricing section visibility
        SystemSetting::set(
            'show_pricing_section',
            $request->has('show_pricing_section') ? '1' : '0',
            'boolean',
            'Show/hide the pricing information section on the home page'
        );

        // Update default monthly amount
        if ($request->filled('default_monthly_amount')) {
            SystemSetting::set(
                'default_monthly_amount',
                $request->default_monthly_amount,
                'decimal',
                'Default monthly amount for new user accounts'
            );
        }

        // Update default grace period
        if ($request->filled('default_grace_period')) {
            SystemSetting::set(
                'default_grace_period',
                $request->default_grace_period,
                'integer',
                'Default grace period in days for new user accounts'
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
            \Log::info('Manual reminders started by admin: ' . auth('admin')->user()->email);

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

        $users = $query->get()->filter(function($user) {
            return $user->isInGracePeriod();
        });

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
                    // Send immediately like contact form (bypass queue)
                    config(['queue.default' => 'sync']);
                    \Mail::to($user->email)->send(new \App\Mail\ManualReminderMail($user, $setting, 'grace_period'));
                    \Log::info('Grace period reminder sent successfully', [
                        'user_email' => $user->email
                    ]);
                } catch (\Exception $mailException) {
                    \Log::error('Failed to send grace period reminder email', [
                        'user_email' => $user->email,
                        'error' => $mailException->getMessage()
                    ]);
                    throw $mailException;
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

        $users = $allUsers->filter(function($user) {
            $isInWarning = $user->isInWarningPeriod();
            \Log::info('Checking user warning period status', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'isInWarningPeriod' => $isInWarning
            ]);
            return $isInWarning;
        });

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
                    // Send immediately like contact form (bypass queue)
                    config(['queue.default' => 'sync']);
                    Mail::to($user->email)->send(new \App\Mail\ManualReminderMail($user, $setting, 'warning_period'));
                    \Log::info('Warning period reminder sent successfully', [
                        'user_email' => $user->email
                    ]);
                } catch (\Exception $mailException) {
                    \Log::error('Failed to send warning period reminder email', [
                        'user_email' => $user->email,
                        'error' => $mailException->getMessage()
                    ]);
                    throw $mailException;
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

                        Mail::to($user->email)->send(new \App\Mail\ManualReminderMail($user, $fakeSetting, 'overdue'));
                        \Log::info('Overdue reminder sent successfully', [
                            'user_email' => $user->email,
                            'invoice_id' => $invoice->id
                        ]);
                    } catch (\Exception $mailException) {
                        \Log::error('Failed to send overdue reminder email', [
                            'user_email' => $user->email,
                            'invoice_id' => $invoice->id,
                            'error' => $mailException->getMessage()
                        ]);
                        throw $mailException;
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
}
