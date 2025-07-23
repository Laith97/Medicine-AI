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
    public function create(): View
    {
        return view('auth.register');
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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'specialty' => ['required', 'string', 'max:255'],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'doctor', // Set role as doctor for this registration
        ];

        $user = User::create($userData);

        // Ensure the role is set correctly
        if ($user->role !== 'doctor') {
            $user->update(['role' => 'doctor']);
        }

        // Create user settings with selected specialty
        $user->setting()->create([
            'specialty' => $request->specialty,
            'criterion' => 'CDC', // Default criterion
        ]);

        // Find or create specialty
        $specialty = Specialty::firstOrCreate(
            ['name' => $request->specialty],
            ['slug' => \Str::slug($request->specialty), 'is_active' => true]
        );

        // Create doctor profile
        $user->doctor()->create([
            'specialty_id' => $specialty->id,
            'license_number' => 'TEMP-' . strtoupper(Str::random(8)) . '-' . $user->id, // Temporary license number
            'consultation_fee' => 5000, // Default $50.00 in cents
            'appointment_duration' => 30, // Default 30 minutes
            'auto_approve_appointments' => false,
            'allow_cancellation' => true,
            'allow_rescheduling' => true,
            'cancellation_hours' => 24, // Default 24 hours notice
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('doctor.dashboard', absolute: false));
    }
}
