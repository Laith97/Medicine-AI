<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ClinicalIndicator;
use App\Models\ClinicalAlert;
use App\Models\ClinicalAlertRule;
use App\Models\PatientMonitoringSession;
use App\Jobs\ProcessClinicalDataJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Models\EarlyWarningScore;
use App\Services\ClinicalDecisionSupportService;
use App\Services\PredictiveRiskEngine;

class ClinicalMonitoringController extends Controller
{
    /**
     * Receive vital signs for a patient
     */
    public function receiveVitals(Request $request, $patientId): JsonResponse
    {
        try {
            $request->validate([
                'vitals' => 'required|array',
                'vitals.*.name' => 'required|string',
                'vitals.*.value' => 'required',
                'vitals.*.unit' => 'nullable|string',
                'device_id' => 'nullable|exists:clinical_monitoring_devices,id',
            ]);

            $patient = User::findOrFail($patientId);
            
            // Authorization check (e.g., device or authorized staff)
            // For now, allowing authenticated users with appropriate roles
            if (!Auth::user()->hasAnyRole(['admin', 'doctor', 'nurse', 'device'])) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $session = PatientMonitoringSession::where('patient_id', $patientId)->active()->first();

            foreach ($request->vitals as $vital) {
                ClinicalIndicator::create([
                    'patient_id' => $patientId,
                    'session_id' => $session?->id,
                    'device_id' => $request->device_id,
                    'type' => 'vital_sign',
                    'name' => $vital['name'],
                    'value' => $vital['value'],
                    'unit' => $vital['unit'],
                    'measured_at' => now(),
                ]);
            }

            // Push to Redis Stream for high-throughput processing
            app(\App\Services\ClinicalDataStreamService::class)->pushToStream([
                'patient_id' => $patientId,
                'type' => 'vitals_update',
                'timestamp' => now()->toIso8601String(),
            ]);

            // Also dispatch job as fallback or for intensive processing
            ProcessClinicalDataJob::dispatch($patient);

            return response()->json(['message' => 'Vitals received and processing started']);
        } catch (\Exception $e) {
            Log::error("Error receiving vitals for patient {$patientId}: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Receive lab results
     */
    public function receiveLabs(Request $request, $patientId): JsonResponse
    {
        try {
            $request->validate([
                'labs' => 'required|array',
                'labs.*.name' => 'required|string',
                'labs.*.value' => 'required',
                'labs.*.unit' => 'nullable|string',
            ]);

            if (!Auth::user()->hasAnyRole(['admin', 'doctor', 'nurse', 'lab_tech'])) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            foreach ($request->labs as $lab) {
                ClinicalIndicator::create([
                    'patient_id' => $patientId,
                    'type' => 'lab_result',
                    'name' => $lab['name'],
                    'value' => $lab['value'],
                    'unit' => $lab['unit'],
                    'measured_at' => now(),
                ]);
            }

            ProcessClinicalDataJob::dispatch(User::findOrFail($patientId));

            return response()->json(['message' => 'Labs received and processing started']);
        } catch (\Exception $e) {
            Log::error("Error receiving labs for patient {$patientId}: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Receive clinical notes
     */
    public function receiveNotes(Request $request, $patientId): JsonResponse
    {
        try {
            $request->validate([
                'note' => 'required|string',
            ]);

            if (!Auth::user()->hasAnyRole(['admin', 'doctor', 'nurse'])) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            ClinicalIndicator::create([
                'patient_id' => $patientId,
                'type' => 'clinical_note',
                'name' => 'note',
                'value' => $request->note,
                'measured_at' => now(),
            ]);

            ProcessClinicalDataJob::dispatch(User::findOrFail($patientId));

            return response()->json(['message' => 'Note received and processing started']);
        } catch (\Exception $e) {
            Log::error("Error receiving notes for patient {$patientId}: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Get active alerts for the user
     */
    public function getAlerts(Request $request): JsonResponse
    {
        if (!Auth::user()->hasAnyRole(['admin', 'doctor', 'nurse'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $alerts = ClinicalAlert::with('patient', 'rule')
            ->whereIn('status', ['triggered', 'escalated'])
            ->orderBy('triggered_at', 'desc')
            ->get();

        return response()->json($alerts);
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledgeAlert(Request $request, $id): JsonResponse
    {
        if (!Auth::user()->hasAnyRole(['admin', 'doctor', 'nurse'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $alert = ClinicalAlert::findOrFail($id);
            $alert->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now(),
                'acknowledged_by' => Auth::id(),
            ]);

            return response()->json(['message' => 'Alert acknowledged']);
        } catch (\Exception $e) {
            Log::error("Error acknowledging alert {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Escalate an alert
     */
    public function escalateAlert(Request $request, $id): JsonResponse
    {
        if (!Auth::user()->hasAnyRole(['admin', 'doctor', 'nurse'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $alert = ClinicalAlert::findOrFail($id);
            $alert->update([
                'status' => 'escalated',
            ]);

            // Logic for escalation (e.g., notifying senior staff) could go here

            return response()->json(['message' => 'Alert escalated']);
        } catch (\Exception $e) {
            Log::error("Error escalating alert {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Get alert rules
     */
    public function getRules(): JsonResponse
    {
        return response()->json(ClinicalAlertRule::all());
    }

    /**
     * Update alert rule
     */
    public function updateRule(Request $request, $id): JsonResponse
    {
        if (!Auth::user()->hasAnyRole(['admin', 'doctor', 'hospital_admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $rule = ClinicalAlertRule::findOrFail($id);
            $rule->update($request->all());

            return response()->json(['message' => 'Rule updated']);
        } catch (\Exception $e) {
            Log::error("Error updating rule {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Get historical scores for a patient
     */
    public function getHistoricalScores($patientId): JsonResponse
    {
        if (!Auth::user()->hasAnyRole(['admin', 'doctor', 'nurse'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $scores = EarlyWarningScore::where('patient_id', $patientId)
                ->orderBy('calculated_at', 'asc')
                ->take(50)
                ->get();

            return response()->json($scores);
        } catch (\Exception $e) {
            Log::error("Error fetching historical scores for patient {$patientId}: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Get latest AI insights for a patient
     */
    public function getLatestInsights($patientId): JsonResponse
    {
        if (!Auth::user()->hasAnyRole(['admin', 'doctor', 'nurse'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $patient = User::findOrFail($patientId);
            
            // Fetch latest scores to generate fresh insights
            $news2 = EarlyWarningScore::where('patient_id', $patientId)->where('algorithm_type', 'news2')->latest('calculated_at')->first();
            $qsofa = EarlyWarningScore::where('patient_id', $patientId)->where('algorithm_type', 'sepsis')->latest('calculated_at')->first();
            $aki = EarlyWarningScore::where('patient_id', $patientId)->where('algorithm_type', 'aki')->latest('calculated_at')->first();
            
            $predictiveRiskEngine = app(PredictiveRiskEngine::class);
            $news2Trend = $predictiveRiskEngine->predictTrend($patient, 'news2_score');
            $rapidDeterioration = $predictiveRiskEngine->detectRapidDeterioration($patient);

            $cdsService = app(ClinicalDecisionSupportService::class);
            $insights = $cdsService->generateClinicalInsights($patient, [
                'news2' => $news2 ? ['score' => $news2->score, 'risk_level' => $news2->risk_level] : null,
                'qsofa' => $qsofa ? ['score' => $qsofa->score, 'risk_level' => $qsofa->risk_level] : null,
                'aki' => $aki ? ['score' => $aki->score, 'risk_level' => $aki->risk_level] : null,
                'trend' => $news2Trend,
                'rapid_deterioration' => $rapidDeterioration
            ]);

            return response()->json($insights);
        } catch (\Exception $e) {
            Log::error("Error generating insights for patient {$patientId}: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Show the clinical monitoring dashboard view
     */
    public function dashboard(Request $request)
    {
        $patientId = $request->query('patient_id');
        return view('clinical.monitoring', compact('patientId'));
    }
}
