<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RtmSession;
use App\Models\RtmMetric;
use App\Models\RtmAlert;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RtmController extends Controller
{
    /**
     * List RTM sessions for the authenticated doctor.
     */
    public function indexSessions(Request $request): JsonResponse
    {
        $query = RtmSession::where('doctor_id', Auth::id())
            ->with(['patient', 'appointment']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($sessions);
    }

    /**
     * Create a new RTM session.
     */
    public function storeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'session_type' => 'required|in:initial,follow_up,monitoring',
            'start_date' => 'required|date',
            'target_days' => 'nullable|integer|min:1|max:365',
            'monitoring_parameters' => 'nullable|array',
            'clinical_notes' => 'nullable|string',
        ]);

        // Verify patient relationship
        $patient = User::findOrFail($validated['patient_id']);
        if ($patient->role !== 'patient') {
            return response()->json(['error' => 'User must be a patient'], 400);
        }

        $validated['doctor_id'] = Auth::id();
        $validated['status'] = 'active';
        $validated['target_days'] = $validated['target_days'] ?? 30;

        $session = RtmSession::create($validated);

        return response()->json([
            'session' => $session->load(['patient', 'appointment']),
            'message' => 'RTM session created successfully',
        ], 201);
    }

    /**
     * Get a specific RTM session.
     */
    public function showSession(int $id): JsonResponse
    {
        $session = RtmSession::where('doctor_id', Auth::id())
            ->with(['patient', 'appointment', 'metrics', 'alerts'])
            ->findOrFail($id);

        return response()->json([
            'session' => $session,
            'metrics_summary' => [
                'pain_level' => $session->getAverageMetric('pain_level'),
                'function_score' => $session->getAverageMetric('function_score'),
                'adherence' => $session->getAverageMetric('adherence'),
            ],
            'days_remaining' => $session->days_remaining,
        ]);
    }

    /**
     * Update RTM session status.
     */
    public function updateSessionStatus(Request $request, int $id): JsonResponse
    {
        $session = RtmSession::where('doctor_id', Auth::id())
            ->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:active,paused,completed,discharged',
            'clinical_notes' => 'nullable|string',
        ]);

        // Update clinical notes if provided
        if (isset($validated['clinical_notes'])) {
            $session->update(['clinical_notes' => $validated['clinical_notes']]);
        }

        // Use model methods for status transitions (they validate transitions)
        $newStatus = $validated['status'];
        switch ($newStatus) {
            case 'paused':
                $session->pause();
                break;
            case 'active':
                $session->resume();
                break;
            case 'completed':
                $session->complete();
                break;
            case 'discharged':
                $session->discharge();
                break;
        }

        return response()->json([
            'session' => $session->fresh(),
            'message' => 'Session status updated',
        ]);
    }

    /**
     * Add a metric reading to a session.
     */
    public function storeMetric(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rtm_session_id' => 'required|exists:rtm_sessions,id',
            'metric_type' => 'required|string',
            'value' => 'required|numeric',
            'unit' => 'nullable|string',
            'notes' => 'nullable|string',
            'recorded_at' => 'nullable|date',
        ]);

        // Verify session belongs to doctor
        $session = RtmSession::where('doctor_id', Auth::id())
            ->findOrFail($validated['rtm_session_id']);

        $validated['patient_id'] = $session->patient_id;
        $validated['recorded_at'] = $validated['recorded_at'] ?? now();

        $metric = RtmMetric::create($validated);

        // Check for threshold alerts using model method
        $alert = $session->checkThresholds($metric);

        return response()->json([
            'metric' => $metric,
            'trend' => $metric->trend,
            'alert' => $alert,
            'message' => 'Metric recorded successfully',
        ], 201);
    }

    /**
     * Get metrics for a session.
     */
    public function getMetrics(Request $request, int $sessionId): JsonResponse
    {
        $session = RtmSession::where('doctor_id', Auth::id())
            ->findOrFail($sessionId);

        $query = $session->metrics();

        if ($request->has('metric_type')) {
            $query->where('metric_type', $request->metric_type);
        }

        if ($request->has('from_date')) {
            $query->where('recorded_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('recorded_at', '<=', $request->to_date);
        }

        $metrics = $query->orderBy('recorded_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($metrics);
    }

    /**
     * List alerts for the authenticated doctor.
     */
    public function indexAlerts(Request $request): JsonResponse
    {
        $query = RtmAlert::where('doctor_id', Auth::id())
            ->with(['patient', 'rtmSession']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        $alerts = $query->orderBy('triggered_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($alerts);
    }

    /**
     * Acknowledge an alert.
     */
    public function acknowledgeAlert(int $id): JsonResponse
    {
        $alert = RtmAlert::where('doctor_id', Auth::id())
            ->findOrFail($id);

        $alert->acknowledge(Auth::user());

        return response()->json([
            'alert' => $alert->fresh(),
            'message' => 'Alert acknowledged',
        ]);
    }

    /**
     * Resolve an alert.
     */
    public function resolveAlert(int $id): JsonResponse
    {
        $alert = RtmAlert::where('doctor_id', Auth::id())
            ->findOrFail($id);

        $alert->resolve();

        return response()->json([
            'alert' => $alert->fresh(),
            'message' => 'Alert resolved',
        ]);
    }

    /**
     * Get RTM dashboard data.
     */
    public function getDashboard(): JsonResponse
    {
        $doctorId = Auth::id();

        $activeSessions = RtmSession::where('doctor_id', $doctorId)
            ->where('status', 'active')
            ->count();

        $criticalAlerts = RtmAlert::where('doctor_id', $doctorId)
            ->critical()
            ->active()
            ->count();

        $unacknowledgedAlerts = RtmAlert::where('doctor_id', $doctorId)
            ->unacknowledged()
            ->count();

        $recentSessions = RtmSession::where('doctor_id', $doctorId)
            ->with('patient')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'active_sessions' => $activeSessions,
                'critical_alerts' => $criticalAlerts,
                'unacknowledged_alerts' => $unacknowledgedAlerts,
            ],
            'recent_sessions' => $recentSessions,
        ]);
    }
}
