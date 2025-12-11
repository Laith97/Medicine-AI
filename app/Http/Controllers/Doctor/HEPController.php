<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\HepProgram;
use App\Models\HepAssignment;
use App\Models\Diagnosis;
use App\Models\User;
use App\Models\Exercise;
use App\Models\Appointment;
use App\Services\HEPGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class HEPController extends Controller
{
    protected $hepGenerator;

    public function __construct(HEPGenerator $hepGenerator)
    {
        $this->hepGenerator = $hepGenerator;
    }

    /**
     * Display the HEP programs dashboard
     */
    public function index(): View
    {
        $doctor = Auth::user()->doctor;

        $programs = HepProgram::where('doctor_id', $doctor->id)
            ->with(['patient', 'diagnosis', 'hepExercises.exercise', 'hepAssignments'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total_programs' => HepProgram::where('doctor_id', $doctor->id)->count(),
            'active_programs' => HepProgram::where('doctor_id', $doctor->id)->where('status', 'active')->count(),
            'assigned_programs' => HepAssignment::whereHas('hepProgram', function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })->count(),
            'completed_programs' => HepProgram::where('doctor_id', $doctor->id)->where('status', 'completed')->count(),
        ];

        return view('doctor.hep.index', compact('programs', 'stats'));
    }

    /**
     * Show the form for creating a new HEP program
     */
    public function create(Request $request): View
    {
        $doctor = Auth::user()->doctor;

        // Get recent diagnoses for the doctor
        $diagnoses = Diagnosis::where('doctor_id', Auth::user()->id)
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get doctor's patients
        $patients = User::whereHas('appointments', function ($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id);
        })->distinct()->get();

        // Get exercise categories for filtering
        $exerciseCategories = Exercise::select('category')->distinct()->pluck('category');

        // Check if diagnosis is pre-selected
        $selectedDiagnosis = null;
        if ($request->has('diagnosis_id')) {
            $selectedDiagnosis = Diagnosis::where('id', $request->diagnosis_id)
                ->where('doctor_id', Auth::user()->id)
                ->with('patient')
                ->first();
        }

        return view('doctor.hep.create', compact(
            'diagnoses',
            'patients',
            'exerciseCategories',
            'selectedDiagnosis'
        ));
    }

    /**
     * Store a newly created HEP program
     */
    public function store(Request $request)
    {
        // Handle both AJAX and regular form submissions
        $isAjax = $request->ajax() || $request->wantsJson();

        $request->validate([
            'creation_method' => 'required|in:manual,ai',
            'title' => 'required_if:creation_method,manual|string|max:255',
            'diagnosis_id' => 'required|exists:diagnoses,id',
            'duration_weeks' => 'required|integer|min:1|max:52',
            'description' => 'nullable|string',
            'goals' => 'nullable|string',
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
            'exercises.*.week_number' => 'required|integer|min:1',
            'exercises.*.sets' => 'nullable|integer|min:1',
            'exercises.*.reps' => 'nullable|integer|min:1',
            'exercises.*.duration_seconds' => 'nullable|integer|min:1',
            'exercises.*.rest_seconds' => 'nullable|integer|min:0',
            'exercises.*.frequency' => 'nullable|string',
            'exercises.*.notes' => 'nullable|string',
        ]);

        $doctor = Auth::user()->doctor;

        // Verify diagnosis belongs to doctor
        $diagnosis = Diagnosis::where('id', $request->diagnosis_id)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        // Create or get appointment for this HEP program
        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $diagnosis->patient_id,
            'appointment_date' => now(),
            'appointment_end' => now(),
            'status' => 'completed',
            'appointment_type' => 'in_person',
            'reason' => 'HEP Program: ' . ($request->title ?? 'Untitled'),
        ]);

        try {
            // Create HEP program
            $program = HepProgram::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $diagnosis->patient_id,
                'diagnosis_id' => $diagnosis->id,
                'title' => $request->title,
                'description' => $request->description,
                'goals' => $request->goals ? array_filter(array_map('trim', explode("\n", $request->goals))) : [],
                'duration_weeks' => $request->duration_weeks,
                'status' => $request->status ?? 'active',
                'appointment_id' => $appointment->id,
                'frequency_per_week' => 3,
                'precautions' => $request->precautions ?? 'No specific precautions',
            ]);

            // Create HEP exercises
            foreach ($request->exercises as $exerciseData) {
                $program->hepExercises()->create([
                    'exercise_id' => $exerciseData['exercise_id'],
                    'week_number' => $exerciseData['week_number'],
                    'order' => $exerciseData['order'] ?? 0,
                    'sets' => $exerciseData['sets'],
                    'reps' => $exerciseData['reps'],
                    'duration_seconds' => $exerciseData['duration_seconds'],
                    'rest_seconds' => $exerciseData['rest_seconds'] ?? 30,
                    'frequency' => $exerciseData['frequency'],
                    'notes' => $exerciseData['notes'],
                ]);
            }

            // Return appropriate response based on request type
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => 'HEP program created successfully.',
                    'redirect_url' => route('doctor.hep.index')
                ]);
            } else {
                return redirect()->route('doctor.hep.index')
                    ->with('success', 'HEP program created successfully.');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('HEP program validation failed', [
                'errors' => $e->errors(),
                'doctor_id' => $doctor->id ?? null,
                'diagnosis_id' => $request->diagnosis_id,
            ]);

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            } else {
                return redirect()->back()
                    ->withInput()
                    ->withErrors($e->errors());
            }

        } catch (\Exception $e) {
            Log::error('HEP program creation failed', [
                'error' => $e->getMessage(),
                'doctor_id' => $doctor->id ?? null,
                'diagnosis_id' => $request->diagnosis_id,
            ]);

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create HEP program. Please try again.'
                ], 500);
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to create HEP program. Please try again.');
            }
        }
    }

    /**
     * Display the specified HEP program
     */
    public function show(HepProgram $program): View
    {
        $user = Auth::user();
        Log::info('HEP show method called', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'program_id' => $program->id,
            'program_doctor_id' => $program->doctor_id,
            'program_patient_id' => $program->patient_id,
            'program_status' => $program->status,
        ]);

        $this->authorize('view', $program);

        $program->load([
            'patient',
            'diagnosis',
            'doctor.user',
            'hepExercises.exercise',
            'hepAssignments.patient',
            'hepAssignments.hepProgress.hepExercise.exercise'
        ]);

        // Group exercises by week
        $exercisesByWeek = $program->hepExercises->groupBy('week_number');

        // Get assignment progress if assigned
        $assignment = $program->hepAssignments()->with('hepProgress')->first();
        $progressStats = null;

        if ($assignment) {
            $totalExercises = $program->hepExercises()->count();
            $currentWeek = min(now()->diffInWeeks($assignment->assigned_at) + 1, $program->duration_weeks);

            $completedExercises = $assignment->hepProgress()
                ->whereHas('hepExercise', function ($query) use ($currentWeek) {
                    $query->where('week_number', '<=', $currentWeek);
                })
                ->distinct('hep_exercise_id')
                ->count('hep_exercise_id');

            $progressStats = [
                'total_exercises' => $totalExercises,
                'completed_exercises' => $completedExercises,
                'completion_percentage' => $totalExercises > 0 ? round(($completedExercises / $totalExercises) * 100, 1) : 0,
                'current_week' => $currentWeek,
            ];
        }

        // Get patients who have appointments with this doctor for assignment
        $doctor = Auth::user()->doctor;
        $patients = User::where('role', 'patient')->whereHas('appointments', function ($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id);
        })->whereNotIn('id', function($query) use ($doctor) {
            $query->select('patient_id')
                  ->from('hep_assignments')
                  ->join('hep_programs', 'hep_assignments.hep_program_id', '=', 'hep_programs.id')
                  ->where('hep_programs.doctor_id', $doctor->id);
        })->distinct()->select('id', 'name', 'email')->get();

        return view('doctor.hep.show', compact('program', 'exercisesByWeek', 'assignment', 'progressStats', 'patients'));
    }

    /**
     * Show the form for editing the specified HEP program
     */
    public function edit(HepProgram $program): View
    {
        $this->authorize('update', $program);

        $program->load(['hepExercises.exercise', 'diagnosis.patient']);

        // Get exercise categories for filtering
        $exerciseCategories = Exercise::select('category')->distinct()->pluck('category');

        return view('doctor.hep.edit', compact('program', 'exerciseCategories'));
    }

    /**
     * Update the specified HEP program
     */
    public function update(Request $request, HepProgram $program): RedirectResponse
    {
        $this->authorize('update', $program);

        $request->validate([
            'title' => 'required|string|max:255',
            'duration_weeks' => 'required|integer|min:1|max:52',
            'description' => 'nullable|string',
            'goals' => 'nullable|string',
            'status' => 'required|in:active,completed,paused',
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
            'exercises.*.week_number' => 'required|integer|min:1',
            'exercises.*.sets' => 'nullable|integer|min:1',
            'exercises.*.reps' => 'nullable|integer|min:1',
            'exercises.*.duration_seconds' => 'nullable|min:1',
            'exercises.*.rest_seconds' => 'nullable|integer|min:0',
            'exercises.*.frequency' => 'nullable|string',
            'exercises.*.notes' => 'nullable|string',
        ]);

        try {
            // Update program
            $program->update([
                'title' => $request->title,
                'description' => $request->description,
                'goals' => $request->goals ? array_filter(array_map('trim', explode("\n", $request->goals))) : [],
                'duration_weeks' => $request->duration_weeks,
                'status' => $request->status,
            ]);

            // Delete existing exercises and recreate
            $program->hepExercises()->delete();

            // Create updated exercises
            foreach ($request->exercises as $exerciseData) {
                $program->hepExercises()->create([
                    'exercise_id' => $exerciseData['exercise_id'],
                    'week_number' => $exerciseData['week_number'],
                    'order' => $exerciseData['order'] ?? 0,
                    'sets' => $exerciseData['sets'],
                    'reps' => $exerciseData['reps'],
                    'duration_seconds' => $exerciseData['duration_seconds'],
                    'rest_seconds' => $exerciseData['rest_seconds'] ?? 30,
                    'frequency' => $exerciseData['frequency'],
                    'notes' => $exerciseData['notes'],
                ]);
            }

            return redirect()->route('doctor.hep.show', $program)
                ->with('success', 'HEP program updated successfully.');

        } catch (\Exception $e) {
            Log::error('HEP program update failed', [
                'error' => $e->getMessage(),
                'program_id' => $program->id,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update HEP program. Please try again.');
        }
    }

    /**
     * Remove the specified HEP program
     */
    public function destroy(HepProgram $program): RedirectResponse
    {
        $this->authorize('delete', $program);

        try {
            $program->delete();

            return redirect()->route('doctor.hep.index')
                ->with('success', 'HEP program deleted successfully.');

        } catch (\Exception $e) {
            Log::error('HEP program deletion failed', [
                'error' => $e->getMessage(),
                'program_id' => $program->id,
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete HEP program. Please try again.');
        }
    }

    /**
     * Assign HEP program to patient
     */
    public function assign(Request $request, HepProgram $program)
    {
        // Check authorization and return appropriate response for AJAX requests
        try {
            $this->authorize('update', $program);
        } catch (\Exception $e) {
            Log::error('HEP assignment authorization failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'program_id' => $program->id,
                'is_ajax' => $request->ajax(),
                'wants_json' => $request->wantsJson(),
            ]);

            // Check multiple indicators for AJAX request
            $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->acceptsJson();

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to assign this program.',
                ], 403);
            }

            throw $e; // Re-throw for non-AJAX requests
        }

        Log::info('HEP assign method called', [
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'accepts_json' => $request->acceptsJson(),
            'content_type' => $request->header('Content-Type'),
            'accept_header' => $request->header('Accept'),
        ]);

        // Validate request - handle validation errors properly for AJAX requests
        try {
            $request->validate([
                'patient_id' => 'required',
                'notes' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('HEP assignment validation failed', [
                'errors' => $e->errors(),
                'is_ajax' => $request->ajax(),
                'wants_json' => $request->wantsJson(),
            ]);

            // Check multiple indicators for AJAX request
            $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->acceptsJson();

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }

            // For non-AJAX requests, let Laravel handle validation error redirect
            throw $e;
        }

        $doctor = Auth::user()->doctor;

        Log::info('Starting patient verification', [
            'request_patient_id' => $request->patient_id,
            'doctor_id' => $doctor->id,
            'auth_user_id' => Auth::id(),
        ]);

        // Verify patient belongs to doctor
        try {
            $patient = User::where('id', $request->patient_id)
                ->where('role', 'patient')
                ->whereHas('appointments', function ($query) use ($doctor) {
                    $query->where('doctor_id', $doctor->id);
                })
                ->first();

            Log::info('Patient verification result', [
                'patient_found' => $patient ? true : false,
                'patient_id' => $request->patient_id,
                'doctor_id' => $doctor->id,
            ]);

            if (!$patient) {
                $errorMessage = 'The selected patient is not associated with you.';

                Log::warning('Patient verification failed - patient not associated with doctor', [
                    'patient_id' => $request->patient_id,
                    'doctor_id' => $doctor->id,
                    'auth_user_id' => Auth::id(),
                ]);

                // Check multiple indicators for AJAX request
                $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->acceptsJson();

                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => ['patient_id' => [$errorMessage]],
                    ], 422);
                }

                return back()->withErrors(['patient_id' => $errorMessage]);
            }
        } catch (\Exception $e) {
            Log::error('Patient verification failed', [
                'error' => $e->getMessage(),
                'patient_id' => $request->patient_id,
                'doctor_id' => $doctor->id,
                'auth_user_id' => Auth::id(),
                'is_ajax' => $request->ajax(),
                'wants_json' => $request->wantsJson(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = 'Unable to verify patient. Please try again.';

            // Check multiple indicators for AJAX request
            $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->acceptsJson();

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => ['patient_id' => [$errorMessage]],
                ], 500);
            }

            return back()->withErrors(['patient_id' => $errorMessage]);
        }

        try {
            // Check if already assigned
            $existingAssignment = HepAssignment::where('hep_program_id', $program->id)
                ->where('patient_id', $patient->id)
                ->first();

            if ($existingAssignment) {
                $message = 'This program is already assigned to the selected patient.';

                // Check multiple indicators for AJAX request
                $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->acceptsJson();

                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 409);
                }

                return redirect()->back()->with('error', $message);
            }

            // Calculate due_date based on program duration
            $assignedAt = now();
            $dueDate = $assignedAt->copy()->addWeeks($program->duration_weeks)->toDateString();

            Log::info('HEP assignment due_date calculation', [
                'program_id' => $program->id,
                'duration_weeks' => $program->duration_weeks,
                'assigned_at' => $assignedAt->toDateTimeString(),
                'calculated_due_date' => $dueDate,
            ]);

            // Log the IDs to debug the issue
            Log::info('HEP assignment user IDs', [
                'auth_user_id' => Auth::user()->id,
                'auth_user_exists' => User::find(Auth::user()->id) ? true : false,
                'doctor_user_id' => $doctor->user_id,
                'doctor_user_exists' => User::find($doctor->user_id) ? true : false,
                'patient_id' => $request->patient_id,
                'patient_exists' => User::find($request->patient_id) ? true : false,
                'program_id' => $program->id,
                'program_exists' => HepProgram::find($program->id) ? true : false,
                'doctor_id' => $doctor->id,
            ]);

            // Create assignment - Use Auth::user()->id which is the authenticated user ID
            HepAssignment::create([
                'hep_program_id' => $program->id,
                'patient_id' => $patient->id,
                'assigned_by' => Auth::user()->id,  // Use the authenticated user's ID
                'assigned_at' => $assignedAt,
                'due_date' => $dueDate,
                'patient_notes' => $request->notes,
            ]);

            // Update program status to active
            $program->update(['status' => 'active']);

            $successMessage = 'HEP program assigned to patient successfully.';
            $redirectUrl = route('doctor.hep.show', $program);

            // Always return JSON for assignment requests
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'redirect_url' => $redirectUrl,
                'program' => $program->fresh(['patient', 'hepAssignments']),
            ])
            ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            Log::error('HEP assignment failed', [
                'error' => $e->getMessage(),
                'program_id' => $program->id,
                'patient_id' => $request->patient_id,
                'is_ajax' => $request->ajax(),
                'wants_json' => $request->wantsJson(),
                'auth_user_id' => Auth::user()->id ?? null,
                'doctor_user_id' => $doctor->user_id ?? null,
                'doctor_id' => $doctor->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = 'Failed to assign HEP program. Please try again.';

            // Check multiple indicators for AJAX request
            $isAjax = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->acceptsJson();

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Show progress for assigned HEP program
     */
    public function progress(HepProgram $program): View
    {
        $this->authorize('view', $program);

        $assignment = $program->hepAssignments()
            ->with(['patient', 'hepProgress.hepExercise.exercise'])
            ->firstOrFail();

        // Group progress by week
        $progressByWeek = $assignment->hepProgress
            ->groupBy(function ($progress) {
                return $progress->hepExercise->week_number;
            })
            ->sortKeys();

        return view('doctor.hep.progress', compact('program', 'assignment', 'progressByWeek'));
    }

    /**
     * Generate HEP program using AI
     */
    public function generateAI(Request $request): JsonResponse
    {
        $request->validate([
            'diagnosis_id' => 'required|exists:diagnoses,id',
            'additional_context' => 'nullable|string',
        ]);

        $doctor = Auth::user()->doctor;

        // Verify diagnosis belongs to doctor
        $diagnosis = Diagnosis::where('id', $request->diagnosis_id)
            ->where('doctor_id', $doctor->id)
            ->with('patient')
            ->firstOrFail();

        try {
            // Generate HEP program using AI
            // FIXED: Pass the correct parameters to match the method signature
            $program = $this->hepGenerator->generateProgram(
                $diagnosis,                  // Diagnosis model
                $diagnosis->patient,         // Patient User model
                Auth::user(),                // Doctor User model
                [
                    'additional_context' => $request->additional_context,
                ]
            );

            return response()->json([
                'success' => true,
                'program' => $program->load(['hepExercises.exercise', 'patient', 'diagnosis']),
                'message' => 'HEP program generated successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('AI HEP generation failed', [
                'error' => $e->getMessage(),
                'diagnosis_id' => $request->diagnosis_id,
                'doctor_id' => $doctor->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate HEP program. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get patients for HEP assignment (AJAX)
     */
    public function getPatients(Request $request): JsonResponse
    {
        $doctor = Auth::user()->doctor;
        $search = $request->input('search');

        $patients = User::where('role', 'patient')
            ->whereHas('appointments', function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
            })
            ->distinct()
            ->select('id', 'name', 'email')
            ->limit(50)
            ->get();

        return response()->json(['patients' => $patients]);
    }
}
