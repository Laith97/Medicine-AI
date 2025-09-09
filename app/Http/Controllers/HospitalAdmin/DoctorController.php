<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class DoctorController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            // Allow access if admin is impersonating
            if (session()->has('impersonating_admin_id')) {
                return $next($request);
            }

            // Allow super admin direct access
            if ($user->role === 'admin') {
                return $next($request);
            }

            if (!$user->isHospitalAdmin()) {
                abort(403, 'Access denied. Hospital admin role required.');
            }

            if (!$user->hospital) {
                abort(403, 'Access denied. Hospital association required.');
            }

            return $next($request);
        })->except(['returnToHospitalAdmin']);
    }

    /**
     * Display a listing of doctors under this hospital
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $hospital = $user->hospital;

        $query = $hospital->doctors()->with(['doctor.specialty']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by specialty
        if ($request->filled('specialty')) {
            $query->whereHas('doctor.specialty', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->specialty}%");
            });
        }

        // Filter by status - default to active only
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereHas('doctor', function ($q) {
                    $q->where('is_active', true);
                });
            } elseif ($request->status === 'inactive') {
                $query->whereHas('doctor', function ($q) {
                    $q->where('is_active', false);
                });
            } elseif ($request->status === 'all') {
                // Show all doctors regardless of status
            }
        } else {
            // Default: show only active doctors
            $query->whereHas('doctor', function ($q) {
                $q->where('is_active', true);
            });
        }

        $doctors = $query->paginate(15);

        return view('hospital-admin.doctors.index', compact('doctors', 'hospital'));
    }

    /**
     * Show the form for creating a new doctor
     */
    public function create()
    {
        $user = Auth::user();
        $hospital = $user->hospital;

        if (!$hospital) {
            return redirect()->route('hospital-admin.dashboard')
                ->with('error', 'No hospital associated with your account.');
        }

        return view('hospital-admin.doctors.create', compact('hospital'));
    }

    /**
     * Store a newly created doctor
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'specialty' => ['required', 'string', 'max:255'],
            'custom_specialty' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        // Determine the final specialty value
        $specialty = $request->specialty;
        if ($request->specialty === 'other' && $request->filled('custom_specialty')) {
            $specialty = $request->custom_specialty;
        }

        // Create the user account
        $doctor = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'doctor',
            'hospital_id' => $user->hospital_id,
        ]);

        // Ensure the role is set correctly
        if ($doctor->role !== 'doctor') {
            $doctor->update(['role' => 'doctor']);
        }

        // Create user settings with selected specialty
        $doctor->setting()->create([
            'specialty' => $specialty,
            'criterion' => 'CDC', // Default criterion
        ]);

        // Create doctor profile
        $doctor->doctor()->create([
            'specialty' => $specialty,
            'license_number' => 'TEMP-' . strtoupper(\Str::random(8)) . '-' . $doctor->id,
            'consultation_fee' => 5000, // Default $50.00 in cents
            'appointment_duration' => 30, // Default 30 minutes
            'auto_approve_appointments' => false,
            'allow_cancellation' => true,
            'allow_rescheduling' => true,
            'cancellation_hours' => 24, // Default 24 hours notice
            'is_active' => true,
            'is_verified' => true, // Hospital admin can verify doctors directly
        ]);

        // Trials disabled for homepage flow (only monthly/yearly). If trials are enabled (>0), you may call startTrial(); otherwise skip.
        // $doctor->startTrial();

        return redirect()->route('hospital-admin.doctors.index')
            ->with('success', 'Doctor created successfully with all standard features.');
    }

    /**
     * Display the specified doctor
     */
    public function show(User $doctor)
    {
        $user = Auth::user();

        if (!$user->canManageDoctor($doctor)) {
            abort(403, 'You cannot manage this doctor.');
        }

        $doctor->load(['doctor.specialty', 'subUsers']);

        // Get doctor statistics
        $statistics = [
            'total_appointments' => $doctor->doctor->appointments()->count(),
            'completed_appointments' => $doctor->doctor->appointments()->where('status', 'completed')->count(),
            'total_reviews' => $doctor->doctor->reviews()->count(),
            'average_rating' => $doctor->doctor->reviews()->avg('rating') ?: 0,
            'sub_users_count' => $doctor->subUsers()->count(),
        ];

        return view('hospital-admin.doctors.show', compact('doctor', 'statistics'));
    }

    /**
     * Show the form for editing the specified doctor
     */
    public function edit(User $doctor)
    {
        $user = Auth::user();

        if (!$user->canManageDoctor($doctor)) {
            abort(403, 'You cannot manage this doctor.');
        }

        $hospital = $user->hospital;
        $doctor->load('doctor');

        return view('hospital-admin.doctors.edit', compact('doctor', 'hospital'));
    }

    /**
     * Update the specified doctor
     */
    public function update(Request $request, User $doctor)
    {
        $user = Auth::user();

        if (!$user->canManageDoctor($doctor)) {
            abort(403, 'You cannot manage this doctor.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($doctor->id)],
            'phone' => ['required', 'string', 'max:20'],
            'specialty' => ['required', 'string', 'max:255'],
            'custom_specialty' => ['nullable', 'string', 'max:255'],
            'password' => 'nullable|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Determine the final specialty value
        $specialty = $request->specialty;
        if ($request->specialty === 'other' && $request->filled('custom_specialty')) {
            $specialty = $request->custom_specialty;
        }

        // Prepare update data
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        // Update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Update user information
        $doctor->update($updateData);

        // Update doctor profile
        $doctor->doctor->update([
            'specialty' => $specialty,
        ]);

        // Update user settings
        $doctor->setting()->update([
            'specialty' => $specialty,
        ]);

        return redirect()->route('hospital-admin.doctors.show', $doctor)
            ->with('success', 'Doctor updated successfully.');
    }

    /**
     * Toggle doctor active status
     */
    public function toggleStatus(User $doctor)
    {
        $user = Auth::user();

        if (!$user->canManageDoctor($doctor)) {
            abort(403, 'You cannot manage this doctor.');
        }

        $doctor->doctor->update([
            'is_active' => !$doctor->doctor->is_active
        ]);

        $status = $doctor->doctor->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Doctor {$status} successfully.");
    }

    /**
     * Remove the specified doctor
     */
    public function destroy(User $doctor)
    {
        $user = Auth::user();

        if (!$user->canManageDoctor($doctor)) {
            abort(403, 'You cannot manage this doctor.');
        }

        // Soft delete or deactivate instead of hard delete to preserve data integrity
        $doctor->doctor->update(['is_active' => false]);

        return redirect()->route('hospital-admin.doctors.index')
            ->with('success', 'Doctor deactivated successfully.');
    }

    /**
     * Show doctor statistics page
     */
    public function statistics()
    {
        $user = Auth::user();
        $hospital = $user->hospital;

        $doctors = $hospital->doctors()->with(['doctor.specialty'])->get();

        $statistics = [];
        foreach ($doctors as $doctor) {
            if ($doctor->doctor) {
                $statistics[] = [
                    'doctor' => $doctor,
                    'appointments_count' => $doctor->doctor->appointments()->count(),
                    'completed_appointments' => $doctor->doctor->appointments()->where('status', 'completed')->count(),
                    'reviews_count' => $doctor->doctor->reviews()->count(),
                    'average_rating' => $doctor->doctor->reviews()->avg('rating') ?: 0,
                    'monthly_appointments' => $doctor->doctor->appointments()
                        ->whereBetween('appointment_date', [now()->startOfMonth(), now()->endOfMonth()])
                        ->count(),
                ];
            }
        }

        return view('hospital-admin.doctors.statistics', compact('statistics', 'hospital'));
    }

    /**
     * Login as a doctor (impersonation)
     */
    public function loginAs(User $doctor)
    {
        $user = Auth::user();

        // Verify the hospital admin can manage this doctor
        if (!$user->canManageDoctor($doctor)) {
            abort(403, 'You cannot login as this doctor.');
        }

        // Verify the target is actually a doctor
        if (!$doctor->isDoctor()) {
            abort(403, 'Target user is not a doctor.');
        }

        // Check if we're in an admin impersonation session (chain impersonation)
        $isAdminImpersonating = session()->has('impersonating_admin_id');

        if ($isAdminImpersonating) {
            // Chain impersonation: Admin -> Hospital Admin -> Doctor
            // Keep the original admin session and add hospital admin info
            session([
                'impersonating_hospital_admin_id' => $user->id,
                'impersonating_hospital_admin_name' => $user->name,
                'impersonating_user_id' => $doctor->id, // Update to doctor
                'hospital_admin_impersonation_started_at' => now()->timestamp,
                'hospital_admin_impersonation_ip' => request()->ip(),
            ]);

            // Log hospital admin impersonation
            \App\Services\AuditLoggingService::logHospitalAdminImpersonation(
                $user->id,
                $doctor->id,
                [
                    'target_user_name' => $doctor->name,
                    'target_user_email' => $doctor->email,
                    'target_user_role' => $doctor->role,
                    'chain_impersonation' => true
                ]
            );

            \Log::info('Chain impersonation: Admin -> Hospital Admin -> Doctor', [
                'admin_id' => session('impersonating_admin_id'),
                'admin_name' => session('impersonating_admin_name'),
                'hospital_admin_id' => $user->id,
                'hospital_admin_name' => $user->name,
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->name,
                'hospital_id' => $user->hospital_id,
                'ip_address' => request()->ip(),
            ]);
        } else {
            // Direct hospital admin impersonation
            // Log hospital admin impersonation
            \App\Services\AuditLoggingService::logHospitalAdminImpersonation(
                $user->id,
                $doctor->id,
                [
                    'target_user_name' => $doctor->name,
                    'target_user_email' => $doctor->email,
                    'target_user_role' => $doctor->role,
                    'chain_impersonation' => false
                ]
            );

            session([
                'impersonating_hospital_admin_id' => $user->id,
                'impersonating_hospital_admin_name' => $user->name,
                'impersonation_started_at' => now()->timestamp,
                'impersonation_ip' => request()->ip(),
            ]);

            \Log::info('Hospital admin impersonation started', [
                'hospital_admin_id' => $user->id,
                'hospital_admin_name' => $user->name,
                'hospital_admin_email' => $user->email,
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->name,
                'doctor_email' => $doctor->email,
                'hospital_id' => $user->hospital_id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        // Login as the doctor
        Auth::login($doctor);

        return redirect()->route('doctor.dashboard')
            ->with('success', 'You are now logged in as Dr. ' . $doctor->name);
    }

    /**
     * Return to hospital admin from doctor impersonation
     */
    public function returnToHospitalAdmin()
    {
        // Check if we're in an impersonation session
        if (!session()->has('impersonating_hospital_admin_id')) {
            abort(403, 'No impersonation session found.');
        }

        // Check if this is a chain impersonation (Admin -> Hospital Admin -> Doctor)
        $isChainImpersonation = session()->has('impersonating_admin_id');

        if ($isChainImpersonation) {
            // Chain impersonation: return to hospital admin but keep admin session
            $sessionIp = session('hospital_admin_impersonation_ip');
            $sessionStarted = session('hospital_admin_impersonation_started_at');
        } else {
            // Direct hospital admin impersonation
            $sessionIp = session('impersonation_ip');
            $sessionStarted = session('impersonation_started_at');
        }

        $currentIp = request()->ip();

        // Check if IP changed (potential session hijacking)
        if ($sessionIp && $sessionIp !== $currentIp) {
            $this->clearImpersonationSession($isChainImpersonation);
            \Log::warning('Impersonation session IP mismatch detected', [
                'session_ip' => $sessionIp,
                'current_ip' => $currentIp,
                'user_id' => auth()->id(),
                'is_chain_impersonation' => $isChainImpersonation,
            ]);
            abort(403, 'Security violation: Session IP mismatch.');
        }

        // Check if session is too old (24 hours limit)
        if ($sessionStarted && (now()->timestamp - $sessionStarted) > 86400) {
            $this->clearImpersonationSession($isChainImpersonation);
            abort(403, 'Impersonation session expired.');
        }

        $hospitalAdminId = session('impersonating_hospital_admin_id');
        $hospitalAdmin = User::find($hospitalAdminId);

        if (!$hospitalAdmin || !$hospitalAdmin->isHospitalAdmin()) {
            $this->clearImpersonationSession($isChainImpersonation);
            \Log::warning('Invalid hospital admin session during return', [
                'hospital_admin_id' => $hospitalAdminId,
                'current_user_id' => auth()->id(),
                'current_user_role' => auth()->user()->role ?? 'unknown',
                'is_chain_impersonation' => $isChainImpersonation,
            ]);
            abort(403, 'Invalid hospital admin session.');
        }

        // Additional security: verify the current user is a doctor from the same hospital
        $currentUser = auth()->user();
        if (!$currentUser->isDoctor() || $currentUser->hospital_id !== $hospitalAdmin->hospital_id) {
            $this->clearImpersonationSession($isChainImpersonation);
            \Log::warning('Security violation: Invalid impersonation return attempt', [
                'hospital_admin_id' => $hospitalAdminId,
                'current_user_id' => $currentUser->id,
                'current_user_role' => $currentUser->role,
                'current_user_hospital_id' => $currentUser->hospital_id,
                'admin_hospital_id' => $hospitalAdmin->hospital_id,
                'is_chain_impersonation' => $isChainImpersonation,
            ]);

            // Log hospital admin impersonation ended
            \App\Services\AuditLoggingService::logHospitalAdminImpersonationEnded(
                $hospitalAdmin->id,
                [
                    'impersonated_user_id' => auth()->id(),
                    'impersonated_user_name' => auth()->user()->name,
                    'is_chain_impersonation' => $isChainImpersonation
                ]
            );
            abort(403, 'Security violation: Invalid impersonation context.');
        }

        // Log the end of impersonation
        \Log::info('Hospital admin impersonation ended', [
            'hospital_admin_id' => $hospitalAdmin->id,
            'hospital_admin_name' => $hospitalAdmin->name,
            'hospital_admin_email' => $hospitalAdmin->email,
            'doctor_id' => auth()->id(),
            'doctor_name' => auth()->user()->name,
            'doctor_email' => auth()->user()->email,
            'hospital_id' => $hospitalAdmin->hospital_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'is_chain_impersonation' => $isChainImpersonation,
        ]);

        if ($isChainImpersonation) {
            // Chain impersonation: update the impersonated user back to hospital admin
            session([
                'impersonating_user_id' => $hospitalAdmin->id,
            ]);

            // Clear only hospital admin specific session data
            session()->forget([
                'impersonating_hospital_admin_id',
                'impersonating_hospital_admin_name',
                'hospital_admin_impersonation_started_at',
                'hospital_admin_impersonation_ip'
            ]);
        } else {
            // Direct impersonation: clear all hospital admin session data
            session()->forget([
                'impersonating_hospital_admin_id',
                'impersonating_hospital_admin_name',
                'impersonation_started_at',
                'impersonation_ip'
            ]);
        }

        // Login back as hospital admin
        Auth::login($hospitalAdmin);

        return redirect()->route('hospital-admin.doctors.index')
            ->with('success', 'Returned to hospital admin dashboard.');
    }

    /**
     * Clear impersonation session data
     */
    private function clearImpersonationSession($isChainImpersonation = false)
    {
        if ($isChainImpersonation) {
            // Keep admin session, clear only hospital admin data
            session()->forget([
                'impersonating_hospital_admin_id',
                'impersonating_hospital_admin_name',
                'hospital_admin_impersonation_started_at',
                'hospital_admin_impersonation_ip'
            ]);
        } else {
            // Clear all impersonation data
            session()->forget([
                'impersonating_hospital_admin_id',
                'impersonating_hospital_admin_name',
                'impersonation_started_at',
                'impersonation_ip'
            ]);
        }
    }
}
