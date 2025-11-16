<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ClearinghouseAccount;
use App\Models\ClearinghouseSubmission;
use App\Services\CodeSuggestionService;
use App\Services\ClaimDenialPredictionService;
use App\Services\UnderpaymentDetectionService;
use App\Services\ClaimSubmissionService;
use App\Services\ClearinghouseComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ClaimController extends Controller
{
    protected $codeSuggestionService;
    protected $denialPredictionService;
    protected $underpaymentDetectionService;
    protected $claimSubmissionService;
    protected $complianceService;

    public function __construct(
        CodeSuggestionService $codeSuggestionService,
        ClaimDenialPredictionService $denialPredictionService,
        UnderpaymentDetectionService $underpaymentDetectionService,
        ClaimSubmissionService $claimSubmissionService,
        ClearinghouseComplianceService $complianceService
    ) {
        $this->codeSuggestionService = $codeSuggestionService;
        $this->denialPredictionService = $denialPredictionService;
        $this->underpaymentDetectionService = $underpaymentDetectionService;
        $this->claimSubmissionService = $claimSubmissionService;
        $this->complianceService = $complianceService;
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
            // Check eligibility if patient insurance information is provided
            $eligibilityWarning = null;
            if ($validated['patient_insurance_id'] && $validated['patient_insurance_provider']) {
                $eligibilityWarning = $this->checkPatientEligibility(
                    $validated['patient_insurance_id'],
                    $validated['patient_insurance_provider'],
                    $validated['cpt_codes'] ?? ''
                );
            }

            $claim = new Claim();
            $claim->fill($validated);
            $claim->hospital_id = Auth::user()->hospital_id;
            $claim->user_id = Auth::id();
            $claim->status = $validated['status'] ?? 'pending';
            $claim->eligibility_warning = $eligibilityWarning;

            // Process codes
            if ($validated['icd10_codes']) {
                $claim->icd10_codes = array_map('trim', explode("\n", $validated['icd10_codes']));
            }

            if ($validated['cpt_codes']) {
                $claim->cpt_codes = array_map('trim', explode("\n", $validated['cpt_codes']));
            }

            $claim->save();

            // Apply payer rules and get feedback
            $ruleFeedback = $this->applyPayerRules($claim);

            // Apply automated corrections if any
            $correctionsApplied = $this->applyAutomatedCorrections($claim, $ruleFeedback);

            // Generate AI suggestions and predictions
            $this->generateAISuggestions($claim);
            $this->generateDenialPrediction($claim);
            $this->checkUnderpaymentAlert($claim);

            // Check if claim should be blocked for ineligible services
            if ($eligibilityWarning && str_contains($eligibilityWarning, 'ineligible')) {
                return back()->withInput()->with('error', 'Cannot submit claim: Patient is ineligible for this service. Please verify insurance information.');
            }

            // Check if claim should be blocked by payer rules
            $blockingRules = $ruleFeedback->filter(function ($result) {
                return isset($result['actions']) &&
                       collect($result['actions'])->contains(function ($action) {
                           return $action['type'] === 'denial';
                       });
            });

            if ($blockingRules->isNotEmpty()) {
                return back()->withInput()->with('error', 'Cannot submit claim: Claim violates payer rules. Please review the rule violations below.');
            }

            $successMessage = 'Claim created successfully with AI-powered insights.';
            if ($eligibilityWarning) {
                $successMessage .= ' ' . $eligibilityWarning;
            }
            if ($correctionsApplied) {
                $successMessage .= ' Automated corrections have been applied.';
            }

            return redirect()->route('hospital-admin.claims.index')
                ->with('success', $successMessage)
                ->with('rule_feedback', $ruleFeedback->toArray());

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

            // Apply payer rules and get feedback
            $ruleFeedback = $this->applyPayerRules($claim);

            // Apply automated corrections if any
            $correctionsApplied = $this->applyAutomatedCorrections($claim, $ruleFeedback);

            // Regenerate AI insights
            $this->generateAISuggestions($claim);
            $this->generateDenialPrediction($claim);
            $this->checkUnderpaymentAlert($claim);

            // Check if claim should be blocked by payer rules
            $blockingRules = $ruleFeedback->filter(function ($result) {
                return isset($result['actions']) &&
                       collect($result['actions'])->contains(function ($action) {
                           return $action['type'] === 'denial';
                       });
            });

            if ($blockingRules->isNotEmpty()) {
                return back()->withInput()->with('error', 'Cannot update claim: Claim violates payer rules. Please review the rule violations below.');
            }

            $successMessage = 'Claim updated successfully.';
            if ($correctionsApplied) {
                $successMessage .= ' Automated corrections have been applied.';
            }

            return redirect()->route('hospital-admin.claims.index')
                ->with('success', $successMessage)
                ->with('rule_feedback', $ruleFeedback->toArray());

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

    /**
     * Apply payer rules to a claim and return feedback
     */
    private function applyPayerRules(Claim $claim): Collection
    {
        try {
            $rulesEngine = app(PayerRulesEngine::class);
            $ruleResults = $rulesEngine->evaluateClaim($claim);

            // Log rule applications for audit
            foreach ($ruleResults as $result) {
                if (isset($result['rule_id'])) {
                    AuditLoggingService::logRuleApplication(
                        $result['rule_id'],
                        $claim->id,
                        Auth::id(),
                        $result,
                        'claim_creation'
                    );
                }
            }

            return $ruleResults;
        } catch (\Exception $e) {
            Log::error('Error applying payer rules: ' . $e->getMessage(), [
                'claim_id' => $claim->id,
                'user_id' => Auth::id()
            ]);
            return collect();
        }
    }

    /**
     * Apply automated corrections based on rule results
     */
    private function applyAutomatedCorrections(Claim $claim, Collection $ruleFeedback): bool
    {
        $correctionsApplied = false;

        try {
            foreach ($ruleFeedback as $result) {
                if (isset($result['actions'])) {
                    foreach ($result['actions'] as $action) {
                        if ($action['type'] === 'auto_correction' && isset($action['field'])) {
                            $field = $action['field'];
                            $newValue = $action['new_value'];

                            // Only apply correction if field exists and value is different
                            if (isset($claim->$field) && $claim->$field !== $newValue) {
                                $claim->$field = $newValue;
                                $correctionsApplied = true;

                                // Log the correction
                                AuditLoggingService::logRuleApplication(
                                    $result['rule_id'],
                                    $claim->id,
                                    Auth::id(),
                                    [
                                        'action_type' => 'auto_correction',
                                        'field' => $field,
                                        'old_value' => $claim->getOriginal($field),
                                        'new_value' => $newValue
                                    ],
                                    'automated_correction'
                                );
                            }
                        }
                    }
                }
            }

            if ($correctionsApplied) {
                $claim->save();
            }

        } catch (\Exception $e) {
            Log::error('Error applying automated corrections: ' . $e->getMessage(), [
                'claim_id' => $claim->id,
                'user_id' => Auth::id()
            ]);
        }

        return $correctionsApplied;
    }

    /**
     * Check patient eligibility for the service
     */
    private function checkPatientEligibility(string $insuranceId, string $insuranceProvider, string $cptCodes): ?string
    {
        try {
            // Find patient insurance record
            $patientInsurance = \App\Models\PatientInsurance::where('policy_number', $insuranceId)
                ->whereHas('insuranceProvider', function($query) use ($insuranceProvider) {
                    $query->where('name', 'like', '%' . $insuranceProvider . '%');
                })
                ->first();

            if (!$patientInsurance) {
                return 'Patient insurance information not found in system.';
            }

            // Determine service type from CPT codes (simplified logic)
            $serviceType = 'medical'; // Default
            if (str_contains($cptCodes, '99201') || str_contains($cptCodes, '99202') ||
                str_contains($cptCodes, '99211') || str_contains($cptCodes, '99212')) {
                $serviceType = 'office_visit';
            }

            // Check eligibility
            $eligibilityService = app(\App\Services\EligibilityServiceFactory::class)
                ->getServiceForProvider($patientInsurance->insuranceProvider);

            $result = $eligibilityService->checkEligibility($patientInsurance, $serviceType);

            if ($result['status'] === 'ineligible') {
                return 'Warning: Patient is ineligible for this service type.';
            } elseif ($result['status'] === 'error') {
                return 'Unable to verify patient eligibility. Please check manually.';
            }

            return null; // No warning

        } catch (\Exception $e) {
            Log::warning('Eligibility check failed during claim submission', [
                'insurance_id' => $insuranceId,
                'provider' => $insuranceProvider,
                'error' => $e->getMessage()
            ]);
            return 'Unable to verify patient eligibility. Please check manually.';
        }
    }

    /**
     * Submit claims to clearinghouse
     */
    public function submitToClearinghouse(Request $request)
    {
        $request->validate([
            'claim_ids' => 'required|array|min:1',
            'claim_ids.*' => 'integer|exists:claims,id',
            'clearinghouse_account_id' => 'required|integer|exists:clearinghouse_accounts,id',
            'submission_type' => 'nullable|string|in:837P,837I'
        ]);

        try {
            $claims = Claim::whereIn('id', $request->claim_ids)
                ->where('hospital_id', Auth::user()->hospital_id)
                ->get();

            if ($claims->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid claims found for submission'
                ], 400);
            }

            $account = ClearinghouseAccount::findOrFail($request->clearinghouse_account_id);

            // Verify account belongs to user's hospital or is shared
            // For now, allow any active account

            $submissionType = $request->submission_type ?? '837P';
            $submission = $this->claimSubmissionService->submitClaims(
                $claims,
                $account,
                $submissionType
            );

            return response()->json([
                'success' => true,
                'message' => 'Claims submitted to clearinghouse successfully',
                'data' => [
                    'submission_id' => $submission->id,
                    'batch_id' => $submission->batch_id,
                    'claim_count' => $submission->claim_count,
                    'status' => $submission->status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Clearinghouse submission failed', [
                'error' => $e->getMessage(),
                'claim_ids' => $request->claim_ids,
                'account_id' => $request->clearinghouse_account_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit claims to clearinghouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get clearinghouse submission status
     */
    public function getSubmissionStatus(Request $request, ClearinghouseSubmission $submission)
    {
        // Ensure submission belongs to user's hospital
        $hospitalId = Auth::user()->hospital_id;
        $submissionClaims = $submission->claims()->where('hospital_id', $hospitalId)->exists();

        if (!$submissionClaims) {
            abort(403, 'Unauthorized access to submission');
        }

        try {
            // Check for status updates
            $this->claimSubmissionService->checkSubmissionStatus($submission);

            // Reload submission with fresh data
            $submission->load(['responses', 'clearinghouseAccount']);

            return response()->json([
                'success' => true,
                'data' => [
                    'submission' => [
                        'id' => $submission->id,
                        'batch_id' => $submission->batch_id,
                        'status' => $submission->status,
                        'submission_type' => $submission->submission_type,
                        'claim_count' => $submission->claim_count,
                        'total_amount' => $submission->total_amount,
                        'submitted_at' => $submission->submitted_at,
                        'response_received_at' => $submission->response_received_at,
                        'error_message' => $submission->error_message,
                        'clearinghouse_provider' => $submission->clearinghouseAccount->provider,
                    ],
                    'responses' => $submission->responses->map(function ($response) {
                        return [
                            'id' => $response->id,
                            'type' => $response->response_type,
                            'status' => $response->status,
                            'received_at' => $response->received_at,
                            'processed_at' => $response->processed_at,
                            'claim_count' => $response->claim_count,
                            'total_paid_amount' => $response->total_paid_amount,
                            'total_adjustment_amount' => $response->total_adjustment_amount,
                            'processing_errors' => $response->processing_errors,
                        ];
                    }),
                    'claims' => $submission->claims->map(function ($claim) {
                        return [
                            'id' => $claim->id,
                            'patient_name' => $claim->patient_name,
                            'total_amount' => $claim->total_amount,
                            'status' => $claim->claim_status,
                            'clearinghouse_claim_id' => $claim->clearinghouse_claim_id,
                            'clearinghouse_submitted_at' => $claim->clearinghouse_submitted_at,
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get submission status', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve submission status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available clearinghouse accounts
     */
    public function getClearinghouseAccounts()
    {
        try {
            $accounts = ClearinghouseAccount::active()
                ->select('id', 'provider', 'name', 'is_active', 'last_used_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $accounts
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get clearinghouse accounts', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve clearinghouse accounts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get clearinghouse submissions for the hospital
     */
    public function getSubmissions(Request $request)
    {
        $query = ClearinghouseSubmission::with(['clearinghouseAccount', 'claims'])
            ->whereHas('claims', function ($q) {
                $q->where('hospital_id', Auth::user()->hospital_id);
            });

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('provider')) {
            $query->whereHas('clearinghouseAccount', function ($q) use ($request) {
                $q->where('provider', $request->provider);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $submissions = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $submissions
        ]);
    }

    /**
     * Get failed submissions for reconciliation
     */
    public function getFailedSubmissions(Request $request)
    {
        try {
            $filters = $request->only(['provider', 'date_from', 'date_to']);
            $failedSubmissions = $this->claimSubmissionService->getFailedSubmissions(
                Auth::user()->hospital_id,
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => $failedSubmissions->map(function ($submission) {
                    return [
                        'id' => $submission->id,
                        'batch_id' => $submission->batch_id,
                        'status' => $submission->status,
                        'error_message' => $submission->error_message,
                        'claim_count' => $submission->claim_count,
                        'total_amount' => $submission->total_amount,
                        'created_at' => $submission->created_at,
                        'clearinghouse_provider' => $submission->clearinghouseAccount->provider,
                        'retry_count' => $submission->metadata['retry_count'] ?? 0,
                        'last_retry_at' => $submission->metadata['last_retry_at'] ?? null,
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get failed submissions', [
                'error' => $e->getMessage(),
                'hospital_id' => Auth::user()->hospital_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve failed submissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually resubmit a failed submission
     */
    public function manualResubmit(Request $request, ClearinghouseSubmission $submission)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'override_validation' => 'boolean',
            'force_resubmit' => 'boolean'
        ]);

        // Ensure submission belongs to user's hospital
        $hospitalId = Auth::user()->hospital_id;
        $submissionClaims = $submission->claims()->where('hospital_id', $hospitalId)->exists();

        if (!$submissionClaims) {
            abort(403, 'Unauthorized access to submission');
        }

        // Check if submission is in a resubmittable state
        if (!in_array($submission->status, ['failed', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'Submission is not in a failed state that can be resubmitted'
            ], 400);
        }

        try {
            $options = [
                'reason' => $request->reason,
                'override_validation' => $request->boolean('override_validation'),
                'force_resubmit' => $request->boolean('force_resubmit'),
                'manual_user_id' => Auth::id(),
                'manual_user_name' => Auth::user()->name
            ];

            $result = $this->claimSubmissionService->manualResubmit($submission, $options);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Manual resubmit failed', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Manual resubmit failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate HIPAA compliance report
     */
    public function generateComplianceReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        try {
            $dateRange = [];
            if ($request->filled('start_date')) {
                $dateRange['start'] = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            }
            if ($request->filled('end_date')) {
                $dateRange['end'] = \Carbon\Carbon::parse($request->end_date)->endOfDay();
            }

            $report = $this->complianceService->generateHipaaComplianceReport(
                Auth::user()->hospital_id,
                $dateRange
            );

            // Log compliance report generation
            \App\Services\AuditLoggingService::logComplianceAudit(
                'hipaa_compliance_report_generated',
                Auth::id(),
                [
                    'report_period' => $report['report_period'],
                    'compliance_status' => $report['hipaa_compliance_status']
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate compliance report', [
                'error' => $e->getMessage(),
                'hospital_id' => Auth::user()->hospital_id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate compliance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate compliance violation report
     */
    public function generateViolationReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        try {
            $dateRange = [];
            if ($request->filled('start_date')) {
                $dateRange['start'] = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            }
            if ($request->filled('end_date')) {
                $dateRange['end'] = \Carbon\Carbon::parse($request->end_date)->endOfDay();
            }

            $report = $this->complianceService->generateComplianceViolationReport(
                Auth::user()->hospital_id,
                $dateRange
            );

            // Log violation report generation
            \App\Services\AuditLoggingService::logComplianceAudit(
                'compliance_violation_report_generated',
                Auth::id(),
                [
                    'report_period' => $report['report_period'],
                    'total_violations' => $report['total_violations'],
                    'compliance_status' => $report['compliance_status']
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate violation report', [
                'error' => $e->getMessage(),
                'hospital_id' => Auth::user()->hospital_id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate violation report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export audit trail data
     */
    public function exportAuditTrail(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'action_type' => 'nullable|string|in:clearinghouse,hipaa_compliance,compliance',
            'user_id' => 'nullable|integer|exists:users,id',
            'format' => 'nullable|string|in:json,csv'
        ]);

        try {
            $filters = array_filter([
                'start_date' => $request->start_date ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : null,
                'end_date' => $request->end_date ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : null,
                'action_type' => $request->action_type,
                'user_id' => $request->user_id
            ]);

            $auditData = $this->complianceService->exportAuditTrail(
                Auth::user()->hospital_id,
                $filters
            );

            // Log audit trail export
            \App\Services\AuditLoggingService::logComplianceAudit(
                'audit_trail_exported',
                Auth::id(),
                [
                    'export_filters' => $filters,
                    'record_count' => $auditData->count(),
                    'format' => $request->input('format', 'json')
                ]
            );

            if ($request->input('format') === 'csv') {
                $csvContent = $this->convertToCsv($auditData);
                return response($csvContent)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', 'attachment; filename="audit_trail_' . now()->format('Y-m-d_H-i-s') . '.csv"');
            }

            return response()->json([
                'success' => true,
                'data' => $auditData
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to export audit trail', [
                'error' => $e->getMessage(),
                'hospital_id' => Auth::user()->hospital_id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export audit trail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check payer rules for claim data (API endpoint)
     */
    public function checkRules(Request $request)
    {
        $request->validate([
            'patient_insurance_provider' => 'nullable|string',
            'icd10_codes' => 'nullable|array',
            'cpt_codes' => 'nullable|array',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            // Create a temporary claim object for rule evaluation
            $tempClaim = new Claim();
            $tempClaim->fill($request->only([
                'patient_name', 'patient_dob', 'patient_gender', 'patient_insurance_provider',
                'provider_name', 'provider_npi', 'service_date', 'facility_name',
                'diagnosis_description', 'total_amount', 'allowed_amount', 'paid_amount'
            ]));

            // Set payer based on insurance provider
            if ($request->patient_insurance_provider) {
                $tempClaim->payer = $this->mapInsuranceProviderToPayer($request->patient_insurance_provider);
            }

            // Set codes
            $tempClaim->icd10_codes = $request->icd10_codes ?? [];
            $tempClaim->cpt_codes = $request->cpt_codes ?? [];

            // Set hospital ID for proper rule evaluation
            $tempClaim->hospital_id = Auth::user()->hospital_id;

            // Evaluate rules
            $ruleFeedback = $this->applyPayerRules($tempClaim);

            return response()->json([
                'success' => true,
                'feedback' => $ruleFeedback->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking payer rules: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error checking payer rules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Map insurance provider name to payer identifier
     */
    private function mapInsuranceProviderToPayer(string $insuranceProvider): string
    {
        // Simple mapping - in a real system this would be more sophisticated
        $mappings = [
            'aetna' => 'AETNA',
            'anthem' => 'ANTHEM',
            'blue cross' => 'BCBS',
            'blue shield' => 'BCBS',
            'cigna' => 'CIGNA',
            'humana' => 'HUMANA',
            'medicare' => 'MEDICARE',
            'medicaid' => 'MEDICAID',
            'united healthcare' => 'UHC',
            'kaiser' => 'KAISER',
        ];

        $providerLower = strtolower($insuranceProvider);
        foreach ($mappings as $key => $value) {
            if (str_contains($providerLower, $key)) {
                return $value;
            }
        }

        return strtoupper($insuranceProvider); // Default to uppercase version
    }

    /**
     * Convert collection to CSV format
     */
    private function convertToCsv(Collection $data): string
    {
        if ($data->isEmpty()) {
            return '';
        }

        $headers = array_keys($data->first());
        $csv = implode(',', array_map(function ($header) {
            return '"' . str_replace('"', '""', $header) . '"';
        }, $headers)) . "\n";

        foreach ($data as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value ?? '') . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }
}
