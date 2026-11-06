<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ClinicalIndicator;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FhirController extends Controller
{
    public function metadata(): JsonResponse
    {
        return response()->json([
            'resourceType' => 'CapabilityStatement',
            'status' => 'active',
            'date' => now()->toIso8601String(),
            'kind' => 'instance',
            'fhirVersion' => '4.0.1',
            'format' => ['json'],
            'rest' => [[
                'mode' => 'server',
                'resource' => [
                    ['type' => 'Patient', 'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                    ['type' => 'Observation', 'interaction' => [['code' => 'search-type']]],
                    ['type' => 'Appointment', 'interaction' => [['code' => 'search-type']]],
                ],
            ]],
        ]);
    }

    public function readPatient(Request $request, $id): JsonResponse
    {
        $patient = User::where('role', 'patient')->find($id);
        if (!$patient) {
            return response()->json(['resourceType' => 'OperationOutcome', 'issue' => [['severity' => 'error', 'code' => 'not-found']]], 404);
        }

        if (!Auth::user()->canAccessPatient($patient)) {
            AuditLog::log('fhir_patient_denied', Auth::id(), (int) $id, Auth::id(), ['resource' => 'Patient']);
            return response()->json(['resourceType' => 'OperationOutcome', 'issue' => [['severity' => 'error', 'code' => 'forbidden']]], 403);
        }

        AuditLog::log('fhir_patient_read', Auth::id(), (int) $id, Auth::id(), ['resource' => 'Patient']);

        return response()->json([
            'resourceType' => 'Patient',
            'id' => (string) $patient->id,
            'name' => [['text' => $patient->name]],
            'gender' => $patient->gender,
            'birthDate' => $patient->date_of_birth?->toDateString(),
            'telecom' => array_values(array_filter([
                $patient->email ? ['system' => 'email', 'value' => $patient->email] : null,
                $patient->phone ? ['system' => 'phone', 'value' => $patient->phone] : null,
            ])),
        ]);
    }

    public function searchObservation(Request $request): JsonResponse
    {
        $request->validate(['patient' => 'required|integer']);
        $patient = User::where('role', 'patient')->find($request->patient);
        if (!$patient || !Auth::user()->canAccessPatient($patient)) {
            return response()->json(['resourceType' => 'OperationOutcome', 'issue' => [['severity' => 'error', 'code' => 'forbidden']]], 403);
        }

        $indicators = ClinicalIndicator::where('patient_id', $patient->id)
            ->whereIn('type', ['vital_sign', 'lab_result'])
            ->orderByDesc('measured_at')->limit(100)->get();

        AuditLog::log('fhir_observation_search', Auth::id(), $patient->id, Auth::id(), ['count' => $indicators->count()]);

        return response()->json([
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => $indicators->count(),
            'entry' => $indicators->map(fn ($i) => ['resource' => [
                'resourceType' => 'Observation',
                'id' => (string) $i->id,
                'status' => 'final',
                'category' => [['text' => $i->type]],
                'code' => ['text' => $i->name],
                'subject' => ['reference' => 'Patient/' . $patient->id],
                'effectiveDateTime' => $i->measured_at?->toIso8601String(),
                'valueString' => (string) $i->value . ($i->unit ? ' ' . $i->unit : ''),
            ]])->values(),
        ]);
    }

    public function searchAppointment(Request $request): JsonResponse
    {
        $request->validate(['patient' => 'required|integer']);
        $patient = User::where('role', 'patient')->find($request->patient);
        if (!$patient || !Auth::user()->canAccessPatient($patient)) {
            return response()->json(['resourceType' => 'OperationOutcome', 'issue' => [['severity' => 'error', 'code' => 'forbidden']]], 403);
        }

        $appts = Appointment::where('patient_id', $patient->id)->orderByDesc('appointment_date')->limit(100)->get();

        AuditLog::log('fhir_appointment_search', Auth::id(), $patient->id, Auth::id(), ['count' => $appts->count()]);

        return response()->json([
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => $appts->count(),
            'entry' => $appts->map(fn ($a) => ['resource' => [
                'resourceType' => 'Appointment',
                'id' => (string) $a->id,
                'status' => $a->status,
                'start' => $a->appointment_date?->toIso8601String(),
                'description' => $a->reason ?? $a->appointment_type,
                'participant' => [['actor' => ['reference' => 'Patient/' . $patient->id]]],
            ]])->values(),
        ]);
    }
}
