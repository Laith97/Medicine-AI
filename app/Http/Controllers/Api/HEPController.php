<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateHEPRequest;
use App\Http\Requests\UpdateHEPProgressRequest;
use App\Http\Requests\ManageHEPAssignmentRequest;
use App\Jobs\GenerateHEPProgram;
use App\Models\HepProgram;
use App\Models\HepAssignment;
use App\Models\HepProgress;
use App\Services\HEPGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HEPController extends Controller
{
    protected $hepGenerator;

    public function __construct(HEPGenerator $hepGenerator)
    {
        $this->hepGenerator = $hepGenerator;
    }

    /**
     * Generate a HEP program using AI
     */
    public function generate(GenerateHEPRequest $request): JsonResponse
    {
        try {
            $diagnosis = \App\Models\Diagnosis::findOrFail($request->diagnosis_id);
            $patient = \App\Models\User::findOrFail($request->patient_id);
            $doctor = $request->user();

            if ($request->boolean('use_background_processing', true)) {
                // Use background processing
                GenerateHEPProgram::dispatch(
                    $diagnosis,
                    $patient,
                    $doctor,
                    $request->additional_context ?? []
                );

                return response()->json([
                    'message' => 'HEP program generation started. You will be notified when complete.',
                    'status' => 'processing',
                ], 202);
            } else {
                // Generate synchronously
                $program = $this->hepGenerator->generateProgram(
                    $diagnosis,
                    $patient,
                    $doctor,
                    $request->additional_context ?? []
                );

                return response()->json([
                    'message' => 'HEP program generated successfully',
                    'program' => $program->load(['hepExercises.exercise', 'patient', 'doctor']),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('HEP generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'diagnosis_id' => $request->diagnosis_id,
                'patient_id' => $request->patient_id,
            ]);

            return response()->json([
                'message' => 'Failed to generate HEP program',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all HEP programs for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = HepProgram::with(['patient', 'diagnosis', 'hepExercises.exercise']);

        if ($user->hasRole('doctor')) {
            $query->where('doctor_id', $user->id);
        } elseif ($user->hasRole('patient')) {
            $query->where('patient_id', $user->id);
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        $programs = $query->orderBy('created_at', 'desc')
                          ->paginate($request->get('per_page', 15));

        return response()->json($programs);
    }

    /**
     * Get a specific HEP program
     */
    public function show(HepProgram $program): JsonResponse
    {
        $this->authorize('view', $program);

        $program->load([
            'patient',
            'doctor',
            'diagnosis',
            'hepExercises.exercise',
            'hepAssignments.patient',
            'hepAssignments.hepProgress'
        ]);

        return response()->json($program);
    }

    /**
     * Update HEP program status
     */
    public function update(Request $request, HepProgram $program): JsonResponse
    {
        $this->authorize('update', $program);

        $request->validate([
            'status' => 'required|in:active,completed,paused',
        ]);

        $program->update($request->only(['status']));

        return response()->json([
            'message' => 'HEP program updated successfully',
            'program' => $program,
        ]);
    }

    /**
     * Delete a HEP program
     */
    public function destroy(HepProgram $program): JsonResponse
    {
        $this->authorize('delete', $program);

        $program->delete();

        return response()->json([
            'message' => 'HEP program deleted successfully',
        ]);
    }

    /**
     * Generate compliance document for a HEP program
     */
    public function generateComplianceDocument(HepProgram $program): JsonResponse
    {
        $this->authorize('view', $program);

        try {
            $document = $this->hepGenerator->generateComplianceDocument($program);

            return response()->json([
                'document' => $document,
                'program_id' => $program->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Compliance document generation failed', [
                'error' => $e->getMessage(),
                'program_id' => $program->id,
            ]);

            return response()->json([
                'message' => 'Failed to generate compliance document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create HEP assignment
     */
    public function createAssignment(ManageHEPAssignmentRequest $request): JsonResponse
    {
        try {
            $assignment = HepAssignment::create([
                'hep_program_id' => $request->hep_program_id,
                'patient_id' => $request->patient_id,
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
                'due_date' => $request->due_date ?? now()->addWeeks(4),
                'completion_status' => 'pending',
                'patient_notes' => $request->patient_notes,
                'clinician_feedback' => $request->clinician_feedback,
            ]);

            $assignment->load(['hepProgram', 'patient', 'assignedBy']);

            return response()->json([
                'message' => 'HEP assignment created successfully',
                'assignment' => $assignment,
            ], 201);

        } catch (\Exception $e) {
            Log::error('HEP assignment creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'program_id' => $request->hep_program_id,
                'patient_id' => $request->patient_id,
            ]);

            return response()->json([
                'message' => 'Failed to create HEP assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update HEP assignment
     */
    public function updateAssignment(ManageHEPAssignmentRequest $request, HepAssignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);

        $assignment->update($request->validated());

        $assignment->load(['hepProgram', 'patient', 'assignedBy']);

        return response()->json([
            'message' => 'HEP assignment updated successfully',
            'assignment' => $assignment,
        ]);
    }

    /**
     * Get HEP assignments
     */
    public function getAssignments(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = HepAssignment::with(['hepProgram', 'patient', 'assignedBy']);

        if ($user->hasRole('doctor')) {
            $query->where('assigned_by', $user->id);
        } elseif ($user->hasRole('patient')) {
            $query->where('patient_id', $user->id);
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('completion_status', $request->status);
        }

        if ($request->has('program_id')) {
            $query->where('hep_program_id', $request->program_id);
        }

        $assignments = $query->orderBy('created_at', 'desc')
                             ->paginate($request->get('per_page', 15));

        return response()->json($assignments);
    }

    /**
     * Update patient progress
     */
    public function updateProgress(UpdateHEPProgressRequest $request, HepAssignment $assignment): JsonResponse
    {
        try {
            $progressRecords = [];

            foreach ($request->progress_data as $progressData) {
                $progressRecords[] = HepProgress::updateOrCreate(
                    [
                        'hep_assignment_id' => $assignment->id,
                        'hep_exercise_id' => $progressData['hep_exercise_id'],
                        'date' => $progressData['date'],
                    ],
                    [
                        'completed_sets' => $progressData['completed_sets'] ?? null,
                        'completed_reps' => $progressData['completed_reps'] ?? null,
                        'duration_completed' => $progressData['duration_completed'] ?? null,
                        'pain_level' => $progressData['pain_level'] ?? null,
                        'difficulty_rating' => $progressData['difficulty_rating'] ?? null,
                        'notes' => $progressData['notes'] ?? null,
                    ]
                );
            }

            return response()->json([
                'message' => 'Progress updated successfully',
                'progress_records' => $progressRecords,
            ]);

        } catch (\Exception $e) {
            Log::error('Progress update failed', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignment->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Failed to update progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get progress for an assignment
     */
    public function getProgress(HepAssignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $progress = $assignment->hepProgress()
            ->with('hepExercise.exercise')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'assignment' => $assignment->load('hepProgram'),
            'progress' => $progress,
            'summary' => [
                'total_sessions' => $progress->unique('date')->count(),
                'average_pain_level' => $progress->avg('pain_level'),
                'average_difficulty' => $progress->avg('difficulty_rating'),
                'completion_percentage' => $assignment->getProgressPercentage(),
            ],
        ]);
    }

    /**
     * Get available exercises
     */
    public function getExercises(Request $request): JsonResponse
    {
        $query = \App\Models\Exercise::query();

        // Apply filters
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('difficulty_level')) {
            $query->where('difficulty_level', $request->difficulty_level);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $exercises = $query->orderBy('name')->paginate(20);

        return response()->json($exercises);
    }
}
