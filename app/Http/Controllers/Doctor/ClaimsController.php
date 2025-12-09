<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaimsController extends Controller
{
    /**
     * Display a listing of the doctor's claims.
     */
    public function index()
    {
        $user = Auth::user();
        $claims = Claim::where('doctor_id', $user->id)
            ->latest()
            ->paginate(20);

        return view('doctor.claims.index', compact('claims'));
    }

    /**
     * Show the form for creating a new claim.
     */
    public function create()
    {
        return view('doctor.claims.create');
    }

    /**
     * Store a newly created claim in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'diagnosis_text' => 'required|string',
            'procedure_text' => 'required|string',
            'icd10_codes' => 'nullable|json',
            'cpt_codes' => 'nullable|json',
            'payer' => 'required|string|max:255',
            'expected_amount' => 'required|numeric|min:0',
            'service_date' => 'nullable|date',
        ]);

        $user = Auth::user();
        $claim = Claim::create([
            'doctor_id' => $user->id,
            'patient_id' => $validatedData['patient_id'],
            'diagnosis_text' => $validatedData['diagnosis_text'],
            'procedure_text' => $validatedData['procedure_text'],
            'icd10_codes' => $validatedData['icd10_codes'] ? json_decode($validatedData['icd10_codes'], true) : null,
            'cpt_codes' => $validatedData['cpt_codes'] ? json_decode($validatedData['cpt_codes'], true) : null,
            'payer' => $validatedData['payer'],
            'expected_amount' => $validatedData['expected_amount'],
            'service_date' => $validatedData['service_date'] ?? null,
            'claim_status' => 'submitted', // Changed to match actual column name
        ]);

        return redirect()->route('doctor.claims.index')
            ->with('success', 'Claim created successfully and submitted for processing.');
    }

    /**
     * Display the specified claim.
     */
    public function show(Claim $claim)
    {
        // Ensure the claim belongs to the authenticated doctor
        if ($claim->doctor_id !== Auth::id()) {
            abort(403);
        }

        return view('doctor.claims.show', compact('claim'));
    }

    /**
     * Show the form for editing the specified claim.
     */
    public function edit(Claim $claim)
    {
        // Ensure the claim belongs to the authenticated doctor
        if ($claim->doctor_id !== Auth::id()) {
            abort(403);
        }

        // Only allow editing if claim is not yet submitted
        if ($claim->claim_status === 'submitted') {
            return redirect()->route('doctor.claims.show', $claim)
                ->with('error', 'This claim cannot be edited because it has already been processed.');
        }

        return view('doctor.claims.edit', compact('claim'));
    }

    /**
     * Update the specified claim in storage.
     */
    public function update(Request $request, Claim $claim)
    {
        // Ensure the claim belongs to the authenticated doctor
        if ($claim->doctor_id !== Auth::id()) {
            abort(403);
        }

        // Only allow updating if claim is not yet submitted
        if ($claim->claim_status === 'submitted') {
            return redirect()->route('doctor.claims.show', $claim)
                ->with('error', 'This claim cannot be edited because it has already been processed.');
        }

        $validatedData = $request->validate([
            'diagnosis_text' => 'required|string',
            'procedure_text' => 'required|string',
            'icd10_codes' => 'nullable|json',
            'cpt_codes' => 'nullable|json',
            'payer' => 'required|string|max:255',
            'expected_amount' => 'required|numeric|min:0',
            'service_date' => 'nullable|date',
        ]);

        $claim->update($validatedData);

        return redirect()->route('doctor.claims.show', $claim)
            ->with('success', 'Claim updated successfully.');
    }

    /**
     * Remove the specified claim from storage.
     */
    public function destroy(Claim $claim)
    {
        // Ensure the claim belongs to the authenticated doctor
        if ($claim->doctor_id !== Auth::id()) {
            abort(403);
        }

        // Only allow deletion if claim is not yet submitted
        if ($claim->claim_status === 'submitted') {
            return redirect()->route('doctor.claims.index')
                ->with('error', 'This claim cannot be deleted because it has already been processed.');
        }

        $claim->delete();

        return redirect()->route('doctor.claims.index')
            ->with('success', 'Claim deleted successfully.');
    }

    /**
     * Get claim statistics for the doctor dashboard.
     */
    public function getStatistics()
    {
        $user = Auth::user();
        $stats = [
            'total_claims' => Claim::where('doctor_id', $user->id)->count(),
            'pending_claims' => Claim::where('doctor_id', $user->id)->where('claim_status', 'submitted')->count(),
            'approved_claims' => Claim::where('doctor_id', $user->id)->where('claim_status', 'approved')->count(),
            'denied_claims' => Claim::where('doctor_id', $user->id)->where('claim_status', 'denied')->count(),
            'total_amount' => Claim::where('doctor_id', $user->id)->where('claim_status', 'approved')->sum('expected_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Submit a claim to the clearinghouse for processing.
     */
    public function submitToClearinghouse(Claim $claim)
    {
        // Ensure the claim belongs to the authenticated doctor
        if ($claim->doctor_id !== Auth::id()) {
            abort(403);
        }

        // Only allow submission if claim is not already submitted
        if ($claim->claim_status === 'submitted') {
            return redirect()->route('doctor.claims.show', $claim)
                ->with('error', 'This claim has already been submitted for processing.');
        }

        // Here you would integrate with actual clearinghouse API
        // For now, we'll just update the status
        $claim->update([
            'claim_status' => 'submitted',
        ]);

        return redirect()->route('doctor.claims.show', $claim)
            ->with('success', 'Claim submitted to clearinghouse for processing.');
    }
}
