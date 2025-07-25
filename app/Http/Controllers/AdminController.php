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
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rules;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Display a listing of all users.
     */
    public function index()
    {
        $users = User::with(['setting', 'patientAnalyses'])
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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:patient,doctor'],
            'is_verified' => ['boolean'],
        ];

        // Add specialty validation only if user is a doctor
        if ($request->role === 'doctor') {
            $validationRules['specialty'] = ['required', 'string', 'max:255'];
        }

        $request->validate($validationRules);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified_at' => $request->boolean('is_verified', false) ? now() : null,
        ];

        // Add specialty only for doctors
        if ($request->role === 'doctor' && $request->specialty) {
            $userData['specialty'] = $request->specialty;
        }

        $user = User::create($userData);

        // Handle doctor-specific setup
        if ($request->role === 'doctor') {
            // Create user settings with selected specialty
            $user->setting()->create([
                'specialty' => $request->specialty,
                'criterion' => 'CDC', // Default criterion
            ]);

            // Find or create specialty
            $specialty = Specialty::firstOrCreate(
                ['name' => $request->specialty],
                ['slug' => Str::slug($request->specialty), 'is_active' => true]
            );

            // Create doctor profile
            $user->doctor()->create([
                'specialty_id' => $specialty->id,
                'license_number' => 'TEMP-' . strtoupper(Str::random(8)) . '-' . $user->id,
                'consultation_fee' => 5000, // Default $50.00 in cents
                'appointment_duration' => 30, // Default 30 minutes
                'auto_approve_appointments' => false,
                'allow_cancellation' => true,
                'allow_rescheduling' => true,
                'cancellation_hours' => 24, // Default 24 hours notice
                'is_verified' => $request->boolean('is_verified', false),
                'verified_at' => $request->boolean('is_verified', false) ? now() : null,
            ]);

            event(new Registered($user));
        }

        return redirect()->route('admin.users.index')
                        ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['setting', 'patientAnalyses']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load(['setting', 'doctor']);
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
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:patient,doctor'],
            'is_verified' => ['boolean'],
        ];

        // Add specialty validation only if user is a doctor
        if ($request->role === 'doctor') {
            $validationRules['specialty'] = ['required', 'string', 'max:255'];
        }

        $request->validate($validationRules);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'email_verified_at' => $request->boolean('is_verified', false) ? now() : null,
        ];

        // Add specialty only for doctors
        if ($request->role === 'doctor' && $request->specialty) {
            $userData['specialty'] = $request->specialty;
        }

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Handle doctor-specific setup when changing to doctor
        if ($request->role === 'doctor' && $user->role !== 'doctor') {
            // Create user settings if not exists
            if (!$user->setting) {
                $user->setting()->create([
                    'specialty' => $request->specialty,
                    'criterion' => 'CDC',
                ]);
            } else {
                $user->setting->update(['specialty' => $request->specialty]);
            }

            // Find or create specialty
            $specialty = Specialty::firstOrCreate(
                ['name' => $request->specialty],
                ['slug' => Str::slug($request->specialty), 'is_active' => true]
            );

            // Create doctor profile if not exists
            if (!$user->doctor) {
                $user->doctor()->create([
                    'specialty_id' => $specialty->id,
                    'license_number' => 'TEMP-' . strtoupper(Str::random(8)) . '-' . $user->id,
                    'consultation_fee' => 5000,
                    'appointment_duration' => 30,
                    'auto_approve_appointments' => false,
                    'allow_cancellation' => true,
                    'allow_rescheduling' => true,
                    'cancellation_hours' => 24,
                    'is_verified' => $request->boolean('is_verified', false),
                    'verified_at' => $request->boolean('is_verified', false) ? now() : null,
                ]);
            }
        }

        // Update existing doctor's specialty and verification
        if ($request->role === 'doctor' && $user->doctor) {
            $specialty = Specialty::firstOrCreate(
                ['name' => $request->specialty],
                ['slug' => Str::slug($request->specialty), 'is_active' => true]
            );
            $user->doctor->update([
                'specialty_id' => $specialty->id,
                'is_verified' => $request->boolean('is_verified', false),
                'verified_at' => $request->boolean('is_verified', false) ? now() : null,
            ]);
            if ($user->setting) {
                $user->setting->update(['specialty' => $request->specialty]);
            }
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
            'show_pricing_section' => 'boolean'
        ]);

        SystemSetting::set(
            'show_pricing_section',
            $request->has('show_pricing_section') ? '1' : '0',
            'boolean',
            'Show/hide the Choose Your Plan section on the home page'
        );

        return redirect()->back()->with('success', 'System settings updated successfully.');
    }
}
