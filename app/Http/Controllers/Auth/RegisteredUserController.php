<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        // Get dynamic pricing from system settings
        $professionalMonthly = \App\Models\SystemSetting::get('saas_professional_monthly', 30);
        $professionalYearly = \App\Models\SystemSetting::get('saas_professional_yearly', 300);

        
        // Define the same pricing plans as in the home page
        $pricingPlans = [
            'free' => [
                'name' => 'Free',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'description' => 'Perfect for getting started',
                'features' => ['5 AI consultations per month', 'Basic patient management', 'Email support', 'Standard security'],
            ],
            'professional' => [
                'name' => 'Professional', 
                'price_monthly' => $professionalMonthly,
                'price_yearly' => $professionalYearly,
                'description' => 'Most popular for growing practices',
                'features' => ['Unlimited AI consultations', 'Advanced patient management', 'Voice assistant & transcription', 'Professional landing page', 'Priority email support', 'Export capabilities', 'Basic analytics'],
            ],

        ];
        
        // Get selected plan from URL parameter
        $selectedPlan = $request->get('plan', 'professional'); // Default to professional
        $selectedBilling = $request->get('billing', 'monthly'); // Default to monthly
        
        return view('auth.register', compact('pricingPlans', 'selectedPlan', 'selectedBilling'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'specialty' => ['required', 'string', 'max:255'],
            'custom_specialty' => ['nullable', 'string', 'max:255'],
            'selected_plan' => ['required', 'string', 'in:free,professional'],
            'selected_billing' => ['required', 'string', 'in:monthly,yearly'],
        ]);

        // Determine the final specialty value
        $specialty = $request->specialty;

        // If specialty is empty but custom_specialty is provided, use custom_specialty
        if (empty($specialty) && !empty($request->custom_specialty)) {
            $specialty = trim($request->custom_specialty);
        }

        // Validate that we have a specialty
        if (empty($specialty)) {
            return back()->withErrors(['specialty' => 'Please select or enter your medical specialty.'])->withInput();
        }

        // Get dynamic pricing from system settings
        $professionalMonthly = \App\Models\SystemSetting::get('saas_professional_monthly', 30);
        $professionalYearly = \App\Models\SystemSetting::get('saas_professional_yearly', 300);

        
        // Define pricing plans (same as in create method)
        $pricingPlans = [
            'free' => ['price_monthly' => 0, 'price_yearly' => 0],
            'professional' => ['price_monthly' => $professionalMonthly, 'price_yearly' => $professionalYearly],

        ];

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'doctor', // Set role as doctor for this registration
        ]);

        // Ensure the role is set correctly
        if ($user->role !== 'doctor') {
            $user->update(['role' => 'doctor']);
        }

        // Create user settings with selected specialty
        $user->setting()->create([
            'specialty' => $specialty,
            'criterion' => 'CDC', // Default criterion
        ]);

        // Find or create specialty
        $specialtyModel = Specialty::firstOrCreate(
            ['name' => $request->specialty],
            ['slug' => \Str::slug($request->specialty), 'is_active' => true]
        );

        // Create doctor profile
        $user->doctor()->create([
            'specialty_id' => $specialtyModel->id,
            'license_number' => 'TEMP-' . strtoupper(Str::random(8)) . '-' . $user->id, // Temporary license number
            'consultation_fee' => 5000, // Default $50.00 in cents
            'appointment_duration' => 30, // Default 30 minutes
            'auto_approve_appointments' => false,
            'allow_cancellation' => true,
            'allow_rescheduling' => true,
            'cancellation_hours' => 24, // Default 24 hours notice
        ]);

        // Start free trial by default using admin-configured trial days
        $user->startTrial();

        // Do not collect plan choice here; user can subscribe any time during/after trial
        // Ensure monthly invoice setting exists for later subscription flow (uses defaults)
        $user->getOrCreateMonthlyInvoiceSetting();

        event(new Registered($user));

        Auth::login($user);

        // ALL users go to dashboard with trial notification
        return redirect(route('doctor.dashboard', absolute: false))
            ->with('success', 'Welcome! Your free trial has started - enjoy full access to all features!');
    }
}
