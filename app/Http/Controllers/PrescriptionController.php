<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prescription;
use App\Models\Appointment;
use App\Models\User; // Patients are Users
use App\Models\PatientData; // Assuming patient_data table model exists
use App\Services\AIAssistant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class PrescriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Removed global 'doctor' middleware - authorization handled per method
    }

    public function index($appointmentId)
    {
        $this->middleware('doctor'); // Only doctors can list prescriptions for an appointment

        $appointment = Appointment::findOrFail($appointmentId);
        $prescriptions = $appointment->prescriptions;

        return view('prescriptions.index', compact('appointment', 'prescriptions'));
        // Or return response()->json($prescriptions); if API
    }

    public function store(Request $request, $appointmentId)
    {
        $this->middleware('doctor'); // Only doctors can create prescriptions

        $request->validate([
            'medication_name' => 'required|string',
            'dosage' => 'required|string',
            'form' => 'required|string',
            'route' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'frequency' => 'required|string',
            'duration' => 'required|string',
            'refills' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'indication' => 'nullable|string',
            'generic_allowed' => 'nullable|boolean',
            'instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            'suggest_ai' => 'nullable|boolean',
            'ai_suggestions' => 'nullable|string',
            'ai_risk_flags' => 'nullable|string',
        ]);

        $appointment = Appointment::findOrFail($appointmentId);
        if (Auth::id() != $appointment->doctor->user_id) {
            abort(403, 'Unauthorized');
        }

        $patient = $appointment->patient;

        // Decode AI suggestions and risk flags from form
        $aiSuggestions = [];
        $aiRiskFlags = [];

        if ($request->filled('ai_suggestions')) {
            $aiSuggestions = json_decode($request->ai_suggestions, true) ?? [];
        }

        if ($request->filled('ai_risk_flags')) {
            $aiRiskFlags = json_decode($request->ai_risk_flags, true) ?? [];
        }

        $prescription = new Prescription([
            'medication_name' => $request->medication_name,
            'dosage' => $request->dosage,
            'form' => $request->form,
            'route' => $request->route,
            'quantity' => $request->quantity,
            'frequency' => $request->frequency,
            'duration' => $request->duration,
            'refills' => $request->refills ?? 0,
            'start_date' => $request->start_date,
            'indication' => $request->indication,
            'generic_allowed' => $request->generic_allowed ?? true,
            'instructions' => $request->instructions,
            'notes' => $request->notes,
            'appointment_id' => $appointmentId,
            'doctor_id' => Auth::id(),
            'patient_id' => $patient->id,
            'ai_suggestions' => $aiSuggestions,
            'ai_risk_flags' => $aiRiskFlags,
        ]);

        // AI Integration
        if ($request->suggest_ai && config('ai.prescription_suggestions.enabled', true)) {
            $aiAssistant = new AIAssistant();
            $patientData = $aiAssistant->processPatientData($patient);
            $symptoms = $patientData['symptoms'] ?: ($appointment->notes ? json_decode($appointment->notes, true) ?? [] : []);

            try {
                $aiResult = $aiAssistant->generatePrescriptionSuggestions($appointment, $symptoms, $patientData['allergies'], $patientData['past_medications']);

                $prescription->ai_suggestions = $aiResult['suggestions'] ?? [];
                $prescription->ai_risk_flags = $aiResult['risk_flags'] ?? [];
            } catch (\Exception $e) {
                // Handle error gracefully, log or set empty
                Log::error('AI Suggestion failed: ' . $e->getMessage());
            }
        } elseif ($request->suggest_ai && !config('ai.prescription_suggestions.enabled', true)) {
            // AI is requested but disabled - log this
            Log::info('AI prescription suggestions requested but disabled by feature flag', [
                'appointment_id' => $appointmentId,
                'doctor_id' => Auth::id(),
            ]);
        }

        $prescription->save();

        $appointment->update(['prescription_given' => true]);

        return redirect()->back()->with('success', 'Prescription created successfully.');
        // Or return response()->json(['success' => true]);
    }

    public function show(Prescription $prescription)
    {
        $prescription->load(['doctor', 'patient', 'appointment']);

        // Authorize access
        $user = Auth::user();
        if ($user->id != $prescription->doctor_id &&
            $user->id != $prescription->patient_id &&
            !$user->hasRole(['admin', 'hospital_admin'])) {
            abort(403, 'Unauthorized');
        }

        // Load additional data for PDF
        $activePrescriptions = Prescription::getActiveForPatient($prescription->patient_id)->filter(function ($p) use ($prescription) {
            return $p->id != $prescription->id;
        });
        $patientData = $prescription->patient->patientData()->first();
        $medicationHistory = $patientData ? ($patientData->past_medications ?? []) : [];

        if (request()->has('pdf')) {
            $pdf = Pdf::loadView('prescriptions.pdf', compact('prescription', 'activePrescriptions', 'medicationHistory'));
            return $pdf->download('prescription.pdf');
        }

        return view('prescriptions.show', compact('prescription'));
        // Or return response()->json($prescription);
    }

    public function update(Request $request, Prescription $prescription)
    {
        $this->middleware('doctor'); // Only doctors can update prescriptions

        if (Auth::id() != $prescription->doctor_id) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'medication_name' => 'required|string',
            'dosage' => 'required|string',
            'form' => 'required|string',
            'route' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'frequency' => 'required|string',
            'duration' => 'required|string',
            'refills' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'indication' => 'nullable|string',
            'generic_allowed' => 'nullable|boolean',
            'instructions' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $prescription->update([
            'medication_name' => $request->medication_name,
            'dosage' => $request->dosage,
            'form' => $request->form,
            'route' => $request->route,
            'quantity' => $request->quantity,
            'frequency' => $request->frequency,
            'duration' => $request->duration,
            'refills' => $request->refills ?? 0,
            'start_date' => $request->start_date,
            'indication' => $request->indication,
            'generic_allowed' => $request->generic_allowed ?? true,
            'instructions' => $request->instructions,
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Prescription updated successfully.');
        // Or return response()->json(['success' => true]);
    }

    public function destroy(Prescription $prescription)
    {
        $this->middleware('doctor'); // Only doctors can delete prescriptions

        if (Auth::id() != $prescription->doctor_id) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized');
        }

        $appointment = $prescription->appointment;
        $prescription->delete();

        // Reset appointment flag if no more prescriptions
        if ($appointment->prescriptions()->count() == 0) {
            $appointment->update(['prescription_given' => false]);
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Prescription deleted successfully.']);
        }

        return redirect()->back()->with('success', 'Prescription deleted successfully.');
    }
}