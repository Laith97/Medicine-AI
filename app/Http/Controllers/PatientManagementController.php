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

        // Apply gender filter
        if ($request->filled('gender')) {
            $gender = $request->gender;
            $query = $query->where('gender', $gender);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query = $query->where('is_active', $isActive);
        }

        // Apply sorting
        switch ($request->get('sort')) {
            case 'newest':
                $query = $query->sortByDesc('created_at');
                break;
            case 'oldest':
                $query = $query->sortBy('created_at');
                break;
            default:
                $query = $query->sortBy('name');
                break;
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
        $activePatients = $query->where('is_active', true)->count();

        foreach ($query as $patient) {
            $visitCount = $patient->appointments ? $patient->appointments->count() : 0;
            $totalVisits += $visitCount;
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
        
        // Get patient's history with this doctor - limit 50 for speed, View Full Details should be instant
        $appointments = Appointment::where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->latest()
            ->limit(50)
            ->get();
        
        $diagnoses = Diagnosis::where('patient_id', $patient->id)
            ->where('doctor_id', Auth::id())
            ->latest()
            ->limit(50)
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
