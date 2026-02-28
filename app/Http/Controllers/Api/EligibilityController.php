<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BatchCheckEligibilityRequest;
use App\Http\Requests\CheckEligibilityRequest;
use App\Jobs\ProcessEligibilityBatch;
use App\Models\EligibilityCheck;
use App\Models\PatientData;
use App\Models\PatientInsurance;
use App\Services\EligibilityServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EligibilityController extends Controller
{
    public function __construct(
        private EligibilityServiceFactory $serviceFactory
    ) {}

    /**
     * Manual eligibility verification
     *
     * @param CheckEligibilityRequest $request
     * @return JsonResponse
     */
    public function check(CheckEligibilityRequest $request): JsonResponse
    {
        try {
            $patientInsurance = PatientInsurance::findOrFail($request->patient_insurance_id);

            // Clear cache if force refresh requested
            if ($request->boolean('force_refresh')) {
                $cacheKey = "eligibility:{$patientInsurance->id}:{$request->service_type}";
                Cache::forget($cacheKey);
            }

            $service = $this->serviceFactory->getServiceForProvider($patientInsurance->insuranceProvider);
            $result = $service->checkEligibility($patientInsurance, $request->service_type);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'No eligibility service available for this insurance provider'
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            Log::error('Eligibility check failed', [
                'error' => $e->getMessage(),
                'patient_insurance_id' => $request->patient_insurance_id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'error' => 'Eligibility check failed: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get current eligibility status for a patient
     *
     * @param int $patientId
     * @return JsonResponse
     */
    public function getStatus(int $patientId): JsonResponse
    {
        try {
            $patient = PatientData::findOrFail($patientId);

            $patientInsurances = $patient->patientInsurances()->with('insuranceProvider')->get();

            $eligibilityStatuses = [];
            foreach ($patientInsurances as $insurance) {
                $latestCheck = $insurance->eligibilityChecks()
                    ->where('expires_at', '>', now())
                    ->orderBy('check_date', 'desc')
                    ->first();

                $eligibilityStatuses[] = [
                    'insurance_id' => $insurance->id,
                    'provider_name' => $insurance->insuranceProvider->name,
                    'policy_number' => $insurance->policy_number,
                    'latest_check' => $latestCheck ? [
                        'status' => $latestCheck->eligibility_status,
                        'check_date' => $latestCheck->check_date,
                        'expires_at' => $latestCheck->expires_at,
                        'service_type' => $latestCheck->service_type,
                    ] : null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'patient_id' => $patientId,
                    'eligibility_statuses' => $eligibilityStatuses,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get eligibility status', [
                'error' => $e->getMessage(),
                'patient_id' => $patientId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve eligibility status'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bulk eligibility checks (background processing)
     *
     * @param BatchCheckEligibilityRequest $request
     * @return JsonResponse
     */
    public function batchCheck(BatchCheckEligibilityRequest $request): JsonResponse
    {
        try {
            $batchId = uniqid('batch_');

            // Dispatch job to queue
            ProcessEligibilityBatch::dispatch($request->checks, Auth::id(), $batchId)
                ->onQueue('eligibility');

            Log::info('Batch eligibility check queued', [
                'batch_id' => $batchId,
                'batch_size' => count($request->checks),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Batch eligibility check has been queued for processing',
                'batch_id' => $batchId,
                'estimated_completion' => now()->addMinutes(5)->toISOString(), // Rough estimate
            ], Response::HTTP_ACCEPTED);

        } catch (\Exception $e) {
            Log::error('Failed to queue batch eligibility check', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'error' => 'Failed to queue batch eligibility check'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get batch results
     *
     * @param Request $request
     * @param string $batchId
     * @return JsonResponse
     */
    public function getBatchResults(Request $request, string $batchId): JsonResponse
    {
        $results = Cache::get("eligibility_batch:{$batchId}");

        if (!$results) {
            return response()->json([
                'error' => 'Batch results not found or expired'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
