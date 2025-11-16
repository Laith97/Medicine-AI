<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\HepProgram;
use App\Models\HepAssignment;
use App\Models\Diagnosis;
use App\Models\User;
use App\Models\Exercise;
use App\Services\HEPGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $diagnoses = Diagnosis::where('doctor_id', $doctor->id)
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get doctor's patients
        $patients = User::whereHas('patientAppointments', function ($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id);
        })->distinct()->get();

        // Get exercise categories for filtering
        $exerciseCategories = Exercise::select('category')->distinct()->pluck('category');

        // Check if diagnosis is pre-selected
        $selectedDiagnosis = null;
        if ($request->has('diagnosis_id')) {
            $selectedDiagnosis = Diagnosis::where('id', $request->diagnosis_id)
                ->where('doctor_id', $doctor->id)
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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
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
            'exercises.*.frequency' => 'nullable|string',
            'exercises.*.notes' => 'nullable|string',
        ]);

        $doctor = Auth::user()->doctor;

        // Verify diagnosis belongs to doctor
        $diagnosis = Diagnosis::where('id', $request->diagnosis_id)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        try {
            // Create HEP program
            $program = HepProgram::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $diagnosis->patient_id,
                'diagnosis_id' => $diagnosis->id,
                'title' => $request->title,
                'description' => $request->description,
                'goals' => $request->goals,
                'duration_weeks' => $request->duration_weeks,
                'status' => 'draft',
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
                    'frequency' => $exerciseData['frequency'],
                    'notes' => $exerciseData['notes'],
                ]);
            }

            return redirect()->route('doctor.hep.show', $program)
                ->with('success', 'HEP program created successfully.');

        } catch (\Exception $e) {
            Log::error('HEP program creation failed', [
                'error' => $e->getMessage(),
                'doctor_id' => $doctor->id,
                'diagnosis_id' => $request->diagnosis_id,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create HEP program. Please try again.');
        }
    }

    /**
     * Display the specified HEP program
     */
    public function show(HepProgram $program): View
    {
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

        return view('doctor.hep.show', compact('program', 'exercisesByWeek', 'assignment', 'progressStats'));
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
            'status' => 'required|in:draft,active,completed,cancelled',
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
            'exercises.*.week_number' => 'required|integer|min:1',
            'exercises.*.sets' => 'nullable|integer|min:1',
            'exercises.*.reps' => 'nullable|integer|min:1',
            'exercises.*.duration_seconds' => 'nullable|integer|min:1',
            'exercises.*.frequency' => 'nullable|string',
            'exercises.*.notes' => 'nullable|string',
        ]);

        try {
            // Update program
            $program->update([
                'title' => $request->title,
                'description' => $request->description,
                'goals' => $request->goals,
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
    public function assign(Request $request, HepProgram $program): RedirectResponse
    {
        $this->authorize('update', $program);

        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $doctor = Auth::user()->doctor;

        // Verify patient belongs to doctor
        $patient = User::where('id', $request->patient_id)
            ->whereHas('patientAppointments', function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })
            ->firstOrFail();

        try {
            // Check if already assigned
            $existingAssignment = HepAssignment::where('hep_program_id', $program->id)
                ->where('patient_id', $patient->id)
                ->first();

            if ($existingAssignment) {
                return redirect()->back()
                    ->with('error', 'This program is already assigned to the selected patient.');
            }

            // Create assignment
            HepAssignment::create([
                'hep_program_id' => $program->id,
                'patient_id' => $patient->id,
                'assigned_by' => $doctor->user_id,
                'assigned_at' => now(),
                'notes' => $request->notes,
            ]);

            // Update program status to active
            $program->update(['status' => 'active']);

            return redirect()->route('doctor.hep.show', $program)
                ->with('success', 'HEP program assigned to patient successfully.');

        } catch (\Exception $e) {
            Log::error('HEP assignment failed', [
                'error' => $e->getMessage(),
                'program_id' => $program->id,
                'patient_id' => $request->patient_id,
            ]);

            return redirect()->back()
                ->with('error', 'Failed to assign HEP program. Please try again.');
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
            $program = $this->hepGenerator->generateProgram($diagnosis, [
                'additional_context' => $request->additional_context,
            ]);

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
}
