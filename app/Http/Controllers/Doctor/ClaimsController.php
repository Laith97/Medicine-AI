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
        $claims = Claim::where('user_id', $user->id)
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
            'appointment_id' => 'required|exists:appointments,id',
            'patient_id' => 'required|exists:users,id',
            'diagnosis_codes' => 'required|string',
            'procedure_codes' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'insurance_provider' => 'required|string|max:255',
            'claim_notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $claim = Claim::create([
            'user_id' => $user->id,
            'appointment_id' => $validatedData['appointment_id'],
            'patient_id' => $validatedData['patient_id'],
            'diagnosis_codes' => $validatedData['diagnosis_codes'],
            'procedure_codes' => $validatedData['procedure_codes'],
            'amount' => $validatedData['amount'],
            'insurance_provider' => $validatedData['insurance_provider'],
            'claim_notes' => $validatedData['claim_notes'],
            'status' => 'pending',
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
        if ($claim->user_id !== Auth::id()) {
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
        if ($claim->user_id !== Auth::id()) {
            abort(403);
        }

        // Only allow editing if claim is pending
        if ($claim->status !== 'pending') {
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
        if ($claim->user_id !== Auth::id()) {
            abort(403);
        }

        // Only allow updating if claim is pending
        if ($claim->status !== 'pending') {
            return redirect()->route('doctor.claims.show', $claim)
                ->with('error', 'This claim cannot be edited because it has already been processed.');
        }

        $validatedData = $request->validate([
            'diagnosis_codes' => 'required|string',
            'procedure_codes' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'insurance_provider' => 'required|string|max:255',
            'claim_notes' => 'nullable|string',
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
        if ($claim->user_id !== Auth::id()) {
            abort(403);
        }

        // Only allow deletion if claim is pending
        if ($claim->status !== 'pending') {
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
            'total_claims' => Claim::where('user_id', $user->id)->count(),
            'pending_claims' => Claim::where('user_id', $user->id)->where('status', 'pending')->count(),
            'approved_claims' => Claim::where('user_id', $user->id)->where('status', 'approved')->count(),
            'denied_claims' => Claim::where('user_id', $user->id)->where('status', 'denied')->count(),
            'total_amount' => Claim::where('user_id', $user->id)->where('status', 'approved')->sum('amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Submit a claim to the clearinghouse for processing.
     */
    public function submitToClearinghouse(Claim $claim)
    {
        // Ensure the claim belongs to the authenticated doctor
        if ($claim->user_id !== Auth::id()) {
            abort(403);
        }

        // Only allow submission if claim is pending
        if ($claim->status !== 'pending') {
            return redirect()->route('doctor.claims.show', $claim)
                ->with('error', 'This claim has already been submitted for processing.');
        }

        // Here you would integrate with actual clearinghouse API
        // For now, we'll just update the status
        $claim->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return redirect()->route('doctor.claims.show', $claim)
            ->with('success', 'Claim submitted to clearinghouse for processing.');
    }
}
