<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Appointment;
use App\Models\Diagnosis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientManagementController extends Controller
{
    public function index(Request $request)
    {
        $doctor = Auth::user()->doctor;

        // Use unified method to get all patients (assigned + appointments with this doctor)
        $query = Auth::user()->getDoctorPatients();

        // Apply search filter after getting results
        if ($request->filled('search')) {
            $search = $request->search;
            $query = $query->filter(function($patient) use ($search) {
                return stripos($patient->name, $search) !== false ||
                       stripos($patient->email, $search) !== false ||
                       ($patient->phone && stripos($patient->phone, $search) !== false);
            });
        }

        // Paginate results
        $patients = new \Illuminate\Pagination\LengthAwarePaginator(
            $query->forPage($request->page ?? 1, 15),
            $query->count(),
            15,
            $request->page ?? 1,
            ['path' => url()->current()]
        );

        // Calculate stats
        $totalPatients = $query->count();
        $totalVisits = 0;
        $activePatients = 0;

        foreach ($query as $patient) {
            $visitCount = $patient->appointments ? $patient->appointments->count() : 0;
            $totalVisits += $visitCount;
            $lastVisit = $patient->appointments && $patient->appointments->first()
                ? $patient->appointments->first()->appointment_date
                : null;
            if ($lastVisit && $lastVisit->isCurrentMonth()) {
                $activePatients++;
            }
        }

        $stats = [
            'total_patients' => $totalPatients,
            'total_visits' => $totalVisits,
            'active_patients' => $activePatients,
        ];

        return view('doctor.patients.index', compact('patients', 'stats'));
    }
    
    public function show($id)
    {
        $patient = User::where('role', 'patient')->findOrFail($id);
        $doctor = Auth::user()->doctor;
        
        // Get patient's history with this doctor
        $appointments = Appointment::where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->latest()
            ->get();
        
        $diagnoses = Diagnosis::where('patient_id', $patient->id)
            ->where('doctor_id', Auth::id())
            ->latest()
            ->get();
        
        // Check access
        $hasAccess = $patient->primary_doctor_id == Auth::id() || $appointments->isNotEmpty();
        if (!$hasAccess) {
            abort(403, 'Unauthorized access to patient.');
        }
        
        return view('doctor.patients.show', compact('patient', 'appointments', 'diagnoses'));
    }
    
    public function create()
    {
        return view('doctor.patients.create');
    }

    public function store(Request $request)
    {
        // Ensure user is a doctor
        if (!Auth::user()->doctor) {
            abort(403, 'Only doctors can create patients.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'age' => ['required', 'integer', 'min:1', 'max:150'],
            'gender' => ['required', 'in:male,female,other'],
        ]);

        // Generate a secure temporary password
        $temporaryPassword = \Illuminate\Support\Str::random(16);

        // Convert age to date_of_birth
        $dateOfBirth = now()->subYears($validated['age'])->format('Y-m-d');

        $patient = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => \Illuminate\Support\Facades\Hash::make($temporaryPassword),
            'role' => 'patient',
            'gender' => $validated['gender'],
            'date_of_birth' => $dateOfBirth,
            'primary_doctor_id' => Auth::id(),
            'email_verified_at' => now(),
        ]);

        // Send welcome notification if contact info is available
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationSent = $notificationService->sendPatientWelcome($patient, $temporaryPassword);

        if (!$notificationSent) {
            // Log warning and show message to doctor
            Log::warning('Patient created but welcome notification failed', [
                'patient_id' => $patient->id,
                'doctor_id' => Auth::id(),
            ]);

            return redirect()->route('doctor.patients.show', $patient->id)
                ->with('warning', 'Patient created successfully, but login credentials could not be sent automatically. Please contact the patient with their credentials.')
                ->with('temp_password', $temporaryPassword);
        }

        return redirect()->route('doctor.patients.show', $patient->id)
            ->with('success', 'Patient created successfully! Login credentials have been sent to the patient.');
    }
    
    public function edit($id)
    {
        $patient = User::where('role', 'patient')->findOrFail($id);
        return view('doctor.patients.edit', compact('patient'));
    }
    
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'age' => 'required|integer|min:1|max:150',
            'gender' => 'required|in:male,female,other',
        ]);
        
        $patient = User::where('role', 'patient')->findOrFail($id);
        
        // Convert age to date_of_birth
        $validated['date_of_birth'] = now()->subYears($validated['age'])->format('Y-m-d');
        unset($validated['age']);
        
        $patient->update($validated);
        
        return redirect()->route('doctor.patients.show', $id)
            ->with('success', 'Patient updated successfully!');
    }
    
    public function destroy($id)
    {
        $patient = User::where('role', 'patient')->findOrFail($id);
        $patient->delete();
        
        return redirect()->route('doctor.patients.index')
            ->with('success', 'Patient deleted successfully!');
    }
}
