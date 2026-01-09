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

class ClinicalMonitoringController extends Controller
{
    /**
     * Receive vital signs for a patient
     */
    public function receiveVitals(Request $request, $patientId): JsonResponse
    {
        $request->validate([
            'vitals' => 'required|array',
            'vitals.*.name' => 'required|string',
            'vitals.*.value' => 'required',
            'vitals.*.unit' => 'nullable|string',
            'device_id' => 'nullable|exists:clinical_monitoring_devices,id',
        ]);

        $patient = User::findOrFail($patientId);
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
    }

    /**
     * Receive lab results
     */
    public function receiveLabs(Request $request, $patientId): JsonResponse
    {
        $request->validate([
            'labs' => 'required|array',
            'labs.*.name' => 'required|string',
            'labs.*.value' => 'required',
            'labs.*.unit' => 'nullable|string',
        ]);

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
    }

    /**
     * Receive clinical notes
     */
    public function receiveNotes(Request $request, $patientId): JsonResponse
    {
        $request->validate([
            'note' => 'required|string',
        ]);

        ClinicalIndicator::create([
            'patient_id' => $patientId,
            'type' => 'clinical_note',
            'name' => 'note',
            'value' => $request->note,
            'measured_at' => now(),
        ]);

        ProcessClinicalDataJob::dispatch(User::findOrFail($patientId));

        return response()->json(['message' => 'Note received and processing started']);
    }

    /**
     * Get active alerts for the user
     */
    public function getAlerts(Request $request): JsonResponse
    {
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
        $alert = ClinicalAlert::findOrFail($id);
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Alert acknowledged']);
    }

    /**
     * Escalate an alert
     */
    public function escalateAlert(Request $request, $id): JsonResponse
    {
        $alert = ClinicalAlert::findOrFail($id);
        $alert->update([
            'status' => 'escalated',
        ]);

        // Logic for escalation (e.g., notifying senior staff) could go here

        return response()->json(['message' => 'Alert escalated']);
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
        $rule = ClinicalAlertRule::findOrFail($id);
        $rule->update($request->all());

        return response()->json(['message' => 'Rule updated']);
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
