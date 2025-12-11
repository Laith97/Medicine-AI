<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\HepAssignment;
use App\Models\HepProgress;
use App\Models\HepExercise;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\HEPSafetyService;

class HEPController extends Controller
{
    protected $safetyService;

    public function __construct(HEPSafetyService $safetyService)
    {
        $this->safetyService = $safetyService;
    }

    /**
     * Display the patient HEP dashboard
     */
    public function dashboard(): View
    {
        $patient = Auth::user();

        // Get active HEP assignments
        $activeAssignments = HepAssignment::where('patient_id', $patient->id)
            ->where('completion_status', '!=', 'completed')
            ->with(['hepProgram.doctor.user', 'hepProgram.diagnosis', 'hepProgress' => function($query) {
                $query->orderBy('date', 'desc')->limit(10);
            }])
            ->orderBy('assigned_at', 'desc')
            ->get();

        // Get completed assignments for history
        $completedAssignments = HepAssignment::where('patient_id', $patient->id)
            ->where('completion_status', 'completed')
            ->with(['hepProgram.doctor.user', 'hepProgram.diagnosis'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate overall progress stats
        $totalPrograms = $activeAssignments->count() + $completedAssignments->count();
        $completedPrograms = $completedAssignments->count();
        $overallCompletionRate = $totalPrograms > 0 ? round(($completedPrograms / $totalPrograms) * 100, 1) : 0;

        // Get today's progress
        $today = Carbon::today();
        $todayProgress = HepProgress::whereHas('hepAssignment', function($query) use ($patient) {
            $query->where('patient_id', $patient->id);
        })
        ->where('date', $today)
        ->with(['hepExercise.exercise', 'hepAssignment.hepProgram'])
        ->get();

        // Get upcoming exercises (next 3 days)
        $upcomingExercises = $this->getUpcomingExercises($patient->id);

        return view('patient.hep.dashboard', compact(
            'activeAssignments',
            'completedAssignments',
            'overallCompletionRate',
            'todayProgress',
            'upcomingExercises'
        ));
    }

    /**
     * Display a specific HEP program for the patient
     */
    public function show(HepAssignment $assignment): View
    {
        $this->authorize('view', $assignment);

        $assignment->load([
            'hepProgram.doctor.user',
            'hepProgram.diagnosis',
            'hepProgram.hepExercises.exercise',
            'hepProgress.hepExercise.exercise'
        ]);

        // Group exercises by week
        $exercisesByWeek = $assignment->hepProgram->hepExercises->groupBy('week_number');

        // Get current week
        $currentWeek = min(
            now()->diffInWeeks($assignment->assigned_at) + 1,
            $assignment->hepProgram->duration_weeks
        );

        // Get progress for current week
        $currentWeekProgress = $assignment->hepProgress()
            ->whereHas('hepExercise', function($query) use ($currentWeek) {
                $query->where('week_number', $currentWeek);
            })
            ->with('hepExercise.exercise')
            ->get()
            ->keyBy('hep_exercise_id');

        // Calculate week completion
        $weekExercises = $exercisesByWeek->get($currentWeek, collect());
        $completedExercises = $currentWeekProgress->count();
        $weekCompletionRate = $weekExercises->count() > 0 ?
            round(($completedExercises / $weekExercises->count()) * 100, 1) : 0;

        return view('patient.hep.show', compact(
            'assignment',
            'exercisesByWeek',
            'currentWeek',
            'currentWeekProgress',
            'weekCompletionRate'
        ));
    }

    /**
     * Show exercise details with multimedia support
     */
    public function showExercise(HepAssignment $assignment, HepExercise $exercise): View
    {
        $this->authorize('view', $assignment);

        // Verify exercise belongs to the assignment's program
        if ($exercise->hep_program_id !== $assignment->hep_program_id) {
            abort(404);
        }

        $exercise->load('exercise');
        $patient = Auth::user();

        // Check contraindications for this exercise
        $safetyIssues = $this->safetyService->checkContraindications($patient, $exercise);
        $isBlocked = !empty(array_filter($safetyIssues, function($issue) {
            return in_array($issue['severity'], ['high', 'critical']);
        }));

        // Get patient's progress for this exercise
        $progress = HepProgress::where('hep_assignment_id', $assignment->id)
            ->where('hep_exercise_id', $exercise->id)
            ->orderBy('date', 'desc')
            ->get();

        return view('patient.hep.exercise', compact('assignment', 'exercise', 'progress', 'safetyIssues', 'isBlocked'));
    }

    /**
     * Log exercise completion with pain and difficulty reporting
     */
    public function logProgress(Request $request, HepAssignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);

        $request->validate([
            'hep_exercise_id' => 'required|exists:hep_exercises,id',
            'date' => 'required|date',
            'completed_sets' => 'nullable|integer|min:0',
            'completed_reps' => 'nullable|integer|min:0',
            'duration_completed' => 'nullable|integer|min:0',
            'pain_level' => 'nullable|integer|min:0|max:10',
            'difficulty_rating' => 'nullable|integer|min:1|max:5',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Verify exercise belongs to assignment's program
            $exercise = HepExercise::where('id', $request->hep_exercise_id)
                ->where('hep_program_id', $assignment->hep_program_id)
                ->firstOrFail();

            $patient = Auth::user();

            // Safety Check 1: Check contraindications
            $contraindicationIssues = $this->safetyService->checkContraindications($patient, $exercise);
            if (!empty($contraindicationIssues)) {
                // Check if any issues are critical
                $criticalIssues = array_filter($contraindicationIssues, function($issue) {
                    return in_array($issue['severity'], ['high', 'critical']);
                });

                if (!empty($criticalIssues)) {
                    // Log the safety event
                    $this->safetyService->logSafetyEvent($assignment, 'contraindication_blocked', [
                        'exercise_id' => $exercise->id,
                        'issues' => $contraindicationIssues
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Exercise blocked due to safety concerns. Please consult your healthcare provider.',
                        'safety_issues' => $contraindicationIssues,
                    ], 403);
                }
            }

            // Create or update progress record
            $progress = HepProgress::updateOrCreate(
                [
                    'hep_assignment_id' => $assignment->id,
                    'hep_exercise_id' => $exercise->id,
                    'date' => $request->date,
                ],
                [
                    'completed_sets' => $request->completed_sets,
                    'completed_reps' => $request->completed_reps,
                    'duration_completed' => $request->duration_completed,
                    'pain_level' => $request->pain_level,
                    'difficulty_rating' => $request->difficulty_rating,
                    'notes' => $request->notes,
                ]
            );

            // Safety Check 2: Check pain threshold and monitor for safety concerns
            $painAlerts = [];
            if ($request->pain_level !== null) {
                $painAlerts = $this->safetyService->checkPainThreshold($assignment, $request->pain_level);

                if (!empty($painAlerts)) {
                    // Handle safety concerns (pause program if needed)
                    $programPaused = $this->safetyService->handleSafetyConcerns($assignment, $painAlerts);

                    // Log pain monitoring event
                    $this->safetyService->logSafetyEvent($assignment, 'pain_threshold_exceeded', [
                        'pain_level' => $request->pain_level,
                        'alerts' => $painAlerts,
                        'program_paused' => $programPaused
                    ]);
                }
            }

            // Check if program should be marked as completed
            $this->checkProgramCompletion($assignment);

            $response = [
                'success' => true,
                'message' => 'Progress logged successfully',
                'progress' => $progress->load('hepExercise.exercise'),
            ];

            // Include safety alerts if any
            if (!empty($painAlerts)) {
                $response['safety_alerts'] = $painAlerts;
                $response['message'] .= ' Note: Safety alerts have been generated.';
            }

            if (!empty($contraindicationIssues)) {
                $response['safety_warnings'] = array_filter($contraindicationIssues, function($issue) {
                    return $issue['severity'] === 'warning';
                });
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Progress logging failed', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignment->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to log progress. Please try again.',
            ], 500);
        }
    }

    /**
     * Get progress data for charts/analytics
     */
    public function getProgressData(HepAssignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $progressData = $assignment->hepProgress()
            ->with('hepExercise.exercise')
            ->orderBy('date')
            ->get()
            ->groupBy(function($progress) {
                return $progress->date->format('Y-m-d');
            });

        $chartData = [];
        foreach ($progressData as $date => $progress) {
            $chartData[] = [
                'date' => $date,
                'sessions' => $progress->count(),
                'avg_pain' => $progress->avg('pain_level'),
                'avg_difficulty' => $progress->avg('difficulty_rating'),
                'total_sets' => $progress->sum('completed_sets'),
                'total_reps' => $progress->sum('completed_reps'),
            ];
        }

        return response()->json([
            'assignment' => $assignment->load('hepProgram'),
            'progress_data' => $chartData,
            'summary' => [
                'total_sessions' => $assignment->hepProgress->count(),
                'avg_pain_level' => $assignment->hepProgress->avg('pain_level'),
                'avg_difficulty' => $assignment->hepProgress->avg('difficulty_rating'),
                'completion_percentage' => $assignment->getProgressPercentage(),
            ],
        ]);
    }

    /**
     * Get safety status for an assignment
     */
    public function getSafetyStatus(HepAssignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $patient = Auth::user();
        $safetyStatus = [
            'program_status' => $assignment->hepProgram->status,
            'emergency_contact' => $this->safetyService->getEmergencyContact($patient),
            'recent_alerts' => [],
            'blocked_exercises' => [],
        ];

        // Check all exercises in the program for contraindications
        $exercises = $assignment->hepProgram->hepExercises;
        foreach ($exercises as $exercise) {
            $issues = $this->safetyService->checkContraindications($patient, $exercise);
            $blocked = !empty(array_filter($issues, function($issue) {
                return in_array($issue['severity'], ['high', 'critical']);
            }));

            if ($blocked) {
                $safetyStatus['blocked_exercises'][] = [
                    'exercise_id' => $exercise->id,
                    'exercise_name' => $exercise->exercise->name,
                    'issues' => $issues
                ];
            }
        }

        // Get recent safety alerts from audit logs
        $recentAlerts = \App\Models\AuditLog::where('action_type', 'hep_safety')
            ->where('metadata->assignment_id', $assignment->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $safetyStatus['recent_alerts'] = $recentAlerts->map(function($alert) {
            return [
                'event_type' => $alert->metadata['event_type'] ?? 'unknown',
                'timestamp' => $alert->created_at,
                'details' => $alert->metadata
            ];
        });

        return response()->json($safetyStatus);
    }

    /**
     * Get upcoming exercises for the patient
     */
    private function getUpcomingExercises(int $patientId): array
    {
        $assignments = HepAssignment::where('patient_id', $patientId)
            ->where('completion_status', '!=', 'completed')
            ->with('hepProgram.hepExercises.exercise')
            ->get();

        $upcoming = [];
        $today = Carbon::today();

        foreach ($assignments as $assignment) {
            $currentWeek = min(
                $today->diffInWeeks($assignment->assigned_at) + 1,
                $assignment->hepProgram->duration_weeks
            );

            $weekExercises = $assignment->hepProgram->hepExercises
                ->where('week_number', $currentWeek);

            foreach ($weekExercises as $exercise) {
                // Check if already completed today
                $completedToday = HepProgress::where('hep_assignment_id', $assignment->id)
                    ->where('hep_exercise_id', $exercise->id)
                    ->where('date', $today)
                    ->exists();

                if (!$completedToday) {
                    $upcoming[] = [
                        'assignment' => $assignment,
                        'exercise' => $exercise,
                        'due_date' => $today->format('Y-m-d'),
                    ];
                }
            }
        }

        return array_slice($upcoming, 0, 5); // Return next 5 exercises
    }

    /**
     * Check if program should be marked as completed
     */
    private function checkProgramCompletion(HepAssignment $assignment): void
    {
        $totalExercises = $assignment->hepProgram->hepExercises->count();
        $completedExercises = $assignment->hepProgress->unique('hep_exercise_id')->count();

        if ($completedExercises >= $totalExercises) {
            $assignment->update(['completion_status' => 'completed']);
        }
    }
}
