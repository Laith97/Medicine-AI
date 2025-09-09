<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Services\CodeSuggestionService;
use App\Services\ClaimDenialPredictionService;
use App\Services\UnderpaymentDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClaimController extends Controller
{
    protected $codeSuggestionService;
    protected $denialPredictionService;
    protected $underpaymentDetectionService;

    public function __construct(
        CodeSuggestionService $codeSuggestionService,
        ClaimDenialPredictionService $denialPredictionService,
        UnderpaymentDetectionService $underpaymentDetectionService
    ) {
        $this->codeSuggestionService = $codeSuggestionService;
        $this->denialPredictionService = $denialPredictionService;
        $this->underpaymentDetectionService = $underpaymentDetectionService;
    }

    /**
     * Display a listing of claims
     */
    public function index(Request $request)
    {
        $query = Claim::with(['patient', 'provider'])
            ->where('hospital_id', Auth::user()->hospital_id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('risk_filter')) {
            switch ($request->risk_filter) {
                case 'low':
                    $query->where('denial_risk_probability', '<', 0.4);
                    break;
                case 'medium':
                    $query->whereBetween('denial_risk_probability', [0.4, 0.7]);
                    break;
                case 'high':
                    $query->where('denial_risk_probability', '>=', 0.7);
                    break;
            }
        }

        $claims = $query->orderBy('created_at', 'desc')->paginate(15);

        // Calculate summary statistics
        $totalClaims = Claim::where('hospital_id', Auth::user()->hospital_id)->count();
        $approvedClaims = Claim::where('hospital_id', Auth::user()->hospital_id)->where('status', 'approved')->count();
        $pendingClaims = Claim::where('hospital_id', Auth::user()->hospital_id)->where('status', 'pending')->count();
        $deniedClaims = Claim::where('hospital_id', Auth::user()->hospital_id)->where('status', 'denied')->count();

        return view('hospital-admin.claims.index', compact(
            'claims',
            'totalClaims',
            'approvedClaims',
            'pendingClaims',
            'deniedClaims'
        ));
    }

    /**
     * Show the form for creating a new claim
     */
    public function create()
    {
        return view('hospital-admin.claims.create');
    }

    /**
     * Store a newly created claim
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_name' => 'required|string|max:255',
            'patient_dob' => 'required|date',
            'patient_gender' => 'nullable|string|in:male,female,other',
            'patient_insurance_id' => 'nullable|string|max:255',
            'patient_insurance_provider' => 'nullable|string|max:255',
            'provider_name' => 'required|string|max:255',
            'provider_npi' => 'nullable|string|max:10',
            'service_date' => 'required|date',
            'facility_name' => 'nullable|string|max:255',
            'diagnosis_description' => 'required|string',
            'icd10_codes' => 'nullable|string',
            'cpt_codes' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'allowed_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:pending,approved,denied,paid,draft'
        ]);

        try {
            $claim = new Claim();
            $claim->fill($validated);
            $claim->hospital_id = Auth::user()->hospital_id;
            $claim->user_id = Auth::id();
            $claim->status = $validated['status'] ?? 'pending';

            // Process codes
            if ($validated['icd10_codes']) {
                $claim->icd10_codes = array_map('trim', explode("\n", $validated['icd10_codes']));
            }

            if ($validated['cpt_codes']) {
                $claim->cpt_codes = array_map('trim', explode("\n", $validated['cpt_codes']));
            }

            $claim->save();

            // Generate AI suggestions and predictions
            $this->generateAISuggestions($claim);
            $this->generateDenialPrediction($claim);
            $this->checkUnderpaymentAlert($claim);

            return redirect()->route('hospital-admin.claims.index')
                ->with('success', 'Claim created successfully with AI-powered insights.');

        } catch (\Exception $e) {
            Log::error('Error creating claim: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error creating claim. Please try again.');
        }
    }

    /**
     * Display the specified claim
     */
    public function show(Claim $claim)
    {
        // Ensure claim belongs to user's hospital
        if ($claim->hospital_id !== Auth::user()->hospital_id) {
            abort(403);
        }

        return view('hospital-admin.claims.show', compact('claim'));
    }

    /**
     * Show the form for editing the specified claim
     */
    public function edit(Claim $claim)
    {
        // Ensure claim belongs to user's hospital
        if ($claim->hospital_id !== Auth::user()->hospital_id) {
            abort(403);
        }

        return view('hospital-admin.claims.edit', compact('claim'));
    }

    /**
     * Update the specified claim
     */
    public function update(Request $request, Claim $claim)
    {
        // Ensure claim belongs to user's hospital
        if ($claim->hospital_id !== Auth::user()->hospital_id) {
            abort(403);
        }

        $validated = $request->validate([
            'patient_name' => 'required|string|max:255',
            'patient_dob' => 'required|date',
            'patient_gender' => 'nullable|string|in:male,female,other',
            'patient_insurance_id' => 'nullable|string|max:255',
            'patient_insurance_provider' => 'nullable|string|max:255',
            'provider_name' => 'required|string|max:255',
            'provider_npi' => 'nullable|string|max:10',
            'service_date' => 'required|date',
            'facility_name' => 'nullable|string|max:255',
            'diagnosis_description' => 'required|string',
            'icd10_codes' => 'nullable|string',
            'cpt_codes' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'allowed_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:pending,approved,denied,paid,draft'
        ]);

        try {
            $claim->fill($validated);

            // Process codes
            if ($validated['icd10_codes']) {
                $claim->icd10_codes = array_map('trim', explode("\n", $validated['icd10_codes']));
            }

            if ($validated['cpt_codes']) {
                $claim->cpt_codes = array_map('trim', explode("\n", $validated['cpt_codes']));
            }

            $claim->save();

            // Regenerate AI insights
            $this->generateAISuggestions($claim);
            $this->generateDenialPrediction($claim);
            $this->checkUnderpaymentAlert($claim);

            return redirect()->route('hospital-admin.claims.index')
                ->with('success', 'Claim updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating claim: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error updating claim. Please try again.');
        }
    }

    /**
     * Remove the specified claim
     */
    public function destroy(Claim $claim)
    {
        // Ensure claim belongs to user's hospital
        if ($claim->hospital_id !== Auth::user()->hospital_id) {
            abort(403);
        }

        try {
            $claim->delete();
            return redirect()->route('hospital-admin.claims.index')
                ->with('success', 'Claim deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting claim: ' . $e->getMessage());
            return back()->with('error', 'Error deleting claim. Please try again.');
        }
    }

    /**
     * Generate AI code suggestions for the claim
     */
    private function generateAISuggestions(Claim $claim)
    {
        try {
            $suggestions = $this->codeSuggestionService->suggestCodes($claim->diagnosis_description);

            $claim->ai_suggested_codes = $suggestions;
            $claim->save();
        } catch (\Exception $e) {
            Log::error('Error generating AI suggestions: ' . $e->getMessage());
        }
    }

    /**
     * Generate denial prediction for the claim
     */
    private function generateDenialPrediction(Claim $claim)
    {
        try {
            $prediction = $this->denialPredictionService->predictDenial([
                'icd10_codes' => $claim->icd10_codes ?? [],
                'cpt_codes' => $claim->cpt_codes ?? [],
                'amount' => $claim->total_amount,
                'provider_npi' => $claim->provider_npi,
                'patient_age' => $claim->patient_dob ? now()->diffInYears($claim->patient_dob) : null,
                'patient_gender' => $claim->patient_gender
            ]);

            $claim->denial_risk_probability = $prediction['probability'];
            $claim->denial_risk_explanations = $prediction['explanations'] ?? [];
            $claim->save();
        } catch (\Exception $e) {
            Log::error('Error generating denial prediction: ' . $e->getMessage());
        }
    }

    /**
     * Check for underpayment alerts
     */
    private function checkUnderpaymentAlert(Claim $claim)
    {
        try {
            $alert = $this->underpaymentDetectionService->detectAndFlagUnderpayment($claim);
            if ($alert) {
                $claim->underpayment_alert = true;
                $claim->save();
            }
        } catch (\Exception $e) {
            Log::error('Error checking underpayment alert: ' . $e->getMessage());
        }
    }
}
