<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetCodeSuggestionsRequest;
use App\Http\Requests\PredictDenialRequest;
use App\Http\Requests\SuggestCodesRequest;
use App\Models\Claim;
use App\Services\CodeSuggestionService;
use App\Services\ClaimDenialPredictionService;
use App\Services\UnderpaymentDetectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{
    protected CodeSuggestionService $codeSuggestionService;
    protected ClaimDenialPredictionService $denialPredictionService;
    protected UnderpaymentDetectionService $underpaymentDetectionService;

    public function __construct(
        CodeSuggestionService $codeSuggestionService,
        ClaimDenialPredictionService $denialPredictionService,
        UnderpaymentDetectionService $underpaymentDetectionService
    ) {
        $this->codeSuggestionService = $codeSuggestionService;
        $this->denialPredictionService = $denialPredictionService;
        $this->underpaymentDetectionService = $underpaymentDetectionService;
    }

    public function suggestCodes(SuggestCodesRequest $request): JsonResponse
    {
        $clinicalText = $request->clinical_text;
        $suggestions = $this->codeSuggestionService->suggestCodes($clinicalText);

        if (isset($suggestions['error'])) {
            return response()->json(['error' => $suggestions['error']], 500);
        }

        return response()->json([
            'encounter_id' => $request->input('encounter_id'),
            'suggested_icd10' => $suggestions['suggested_icd10'] ?? [],
            'suggested_cpt' => $suggestions['suggested_cpt'] ?? [],
        ]);
    }

    /**
     * Predict claim denial risk
     */
    public function predictDenial(PredictDenialRequest $request): JsonResponse
    {
        $claimId = $request->claim_id;

        // Find the claim
        $claim = Claim::where('claim_id', $claimId)->first();

        if (!$claim) {
            return response()->json([
                'error' => 'Claim not found'
            ], 404);
        }

        // Predict denial risk
        $prediction = $this->denialPredictionService->predictDenialRisk($claim);

        if (isset($prediction['error'])) {
            return response()->json([
                'error' => 'Prediction failed: ' . $prediction['error']
            ], 500);
        }

        return response()->json($prediction);
    }

    /**
     * Get underpayment information for a specific claim
     */
    public function getUnderpayments(Request $request, string $claimId): JsonResponse
    {
        // Find the claim
        $claim = Claim::where('claim_id', $claimId)->first();

        if (!$claim) {
            return response()->json([
                'error' => 'Claim not found'
            ], 404);
        }

        // Get underpayment data
        $underpaymentData = $this->underpaymentDetectionService->getUnderpaymentData($claim);

        // Check if it's flagged as underpayment
        $isUnderpayment = $this->underpaymentDetectionService->isUnderpayment($claim);

        return response()->json([
            'claim_id' => $underpaymentData['claim_id'],
            'expected' => $underpaymentData['expected'],
            'paid' => $underpaymentData['paid'],
            'variance' => $underpaymentData['variance'],
            'is_underpayment' => $isUnderpayment,
            'threshold_percentage' => $this->underpaymentDetectionService->getThresholdPercentage(),
        ]);
    }

    /**
     * Get AI-powered code suggestions for clinical description
     */
    public function getCodeSuggestions(GetCodeSuggestionsRequest $request): JsonResponse
    {
        try {
            // For now, just use the description - the service expects a string
            $suggestions = $this->codeSuggestionService->suggestCodes($request->description);

            return response()->json([
                'icd10_codes' => $suggestions['suggested_icd10'] ?? [],
                'cpt_codes' => $suggestions['suggested_cpt'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate code suggestions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get denial risk prediction for claim data
     */
    public function getDenialPrediction(PredictDenialRequest $request): JsonResponse
    {
        $claimId = $request->claim_id;

        // Find the claim
        $claim = Claim::where('claim_id', $claimId)->first();

        if (!$claim) {
            return response()->json([
                'error' => 'Claim not found'
            ], 404);
        }

        try {
            // Use the actual denial prediction service
            $prediction = $this->denialPredictionService->predictDenialRisk($claim);

            if (isset($prediction['error'])) {
                return response()->json([
                    'error' => 'Prediction failed: ' . $prediction['error']
                ], 500);
            }

            return response()->json([
                'risk_probability' => $prediction['denial_risk'],
                'explanations' => $prediction['top_factors'] ?? [],
                'risk_level' => $this->getRiskLevel($prediction['denial_risk']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to predict denial risk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get risk level based on probability
     */
    private function getRiskLevel(float $probability): string
    {
        if ($probability >= 0.7) {
            return 'high';
        } elseif ($probability >= 0.4) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}
