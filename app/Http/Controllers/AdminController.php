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
use Illuminate\Validation\Rules;

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
            'is_admin' => ['boolean'],
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
            'is_admin' => $request->boolean('is_admin', false),
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
            'is_admin' => ['boolean'],
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
            'is_admin' => $request->boolean('is_admin', false),
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
     * Toggle admin status for a user.
     */
    public function toggleAdmin(User $user)
    {
        // Prevent admin from removing their own admin privileges
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                            ->with('error', 'You cannot modify your own admin status.');
        }

        if ($user->isAdmin()) {
            $user->removeAdmin();
            $message = 'Admin privileges removed from ' . $user->name;
        } else {
            $user->makeAdmin();
            $message = 'Admin privileges granted to ' . $user->name;
        }

        return redirect()->route('admin.users.index')
                        ->with('success', $message);
    }

    /**
     * Show admin dashboard with statistics.
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'admin_users' => User::where('is_admin', true)->count(),
            'regular_users' => User::where('is_admin', false)->count(),
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
}
