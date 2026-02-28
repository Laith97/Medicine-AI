<?php

namespace App\Http\Controllers;

use App\Services\HEPAnalyticsService;
use App\Services\HEPDataExportService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class HEPAnalyticsController extends Controller
{
    protected $analyticsService;
    protected $exportService;

    public function __construct(HEPAnalyticsService $analyticsService, HEPDataExportService $exportService)
    {
        $this->analyticsService = $analyticsService;
        $this->exportService = $exportService;
    }

    /**
     * Get clinical effectiveness analytics
     */
    public function getClinicalEffectiveness(Request $request): JsonResponse
    {
        $this->authorize('viewAnalytics', User::class);

        $request->validate([
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $analytics = $this->analyticsService->getClinicalEffectivenessAnalytics(
                $request->hospital_id,
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get clinical effectiveness analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve clinical effectiveness analytics'
            ], 500);
        }
    }

    /**
     * Get patient adherence patterns
     */
    public function getAdherencePatterns(Request $request): JsonResponse
    {
        $this->authorize('viewAnalytics', User::class);

        $request->validate([
            'patient_id' => 'nullable|integer|exists:users,id',
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $patterns = $this->analyticsService->getAdherencePatterns(
                $request->patient_id,
                $request->hospital_id,
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'data' => $patterns
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get adherence patterns', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve adherence patterns'
            ], 500);
        }
    }

    /**
     * Get clinician metrics
     */
    public function getClinicianMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAnalytics', User::class);

        $request->validate([
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $metrics = $this->analyticsService->getClinicianMetrics(
                $request->hospital_id,
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get clinician metrics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve clinician metrics'
            ], 500);
        }
    }

    /**
     * Export data for research
     */
    public function exportForResearch(Request $request)
    {
        $this->authorize('exportAnalytics', User::class);

        $request->validate([
            'format' => 'required|in:csv,json,xml',
            'anonymize' => 'boolean',
            'filters' => 'array',
            'filters.hospital_id' => 'nullable|integer|exists:hospitals,id',
            'filters.diagnosis_id' => 'nullable|integer|exists:diagnoses,id',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
            'filters.status' => 'nullable|in:active,completed,paused',
        ]);

        // Validate export request
        $format = $request->input('format');
        $errors = $this->exportService->validateExportRequest($request->filters ?? [], $format);
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);
        }

        try {
            $export = $this->exportService->exportForResearch(
                auth()->user(),
                $request->filters ?? [],
                $format,
                $request->anonymize ?? true
            );

            Log::info('HEP research data exported', [
                'user_id' => auth()->id(),
                'format' => $format,
                'anonymized' => $request->anonymize ?? true,
                'records' => $export['records']
            ]);

            return response()->json([
                'success' => true,
                'data' => $export
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to export research data', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export research data'
            ], 500);
        }
    }

    /**
     * Export data for insurance
     */
    public function exportForInsurance(Request $request, int $patientId)
    {
        $this->authorize('exportPatientData', User::class);

        $request->validate([
            'format' => 'required|in:csv,json,xml',
        ]);

        try {
            $format = $request->input('format');
            $export = $this->exportService->exportForInsurance(
                auth()->user(),
                $patientId,
                $format
            );

            Log::info('HEP insurance data exported', [
                'user_id' => auth()->id(),
                'patient_id' => $patientId,
                'format' => $format,
                'records' => $export['records']
            ]);

            return response()->json([
                'success' => true,
                'data' => $export
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to export insurance data', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'patient_id' => $patientId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export insurance data'
            ], 500);
        }
    }

    /**
     * Get available export formats
     */
    public function getExportFormats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'formats' => HEPDataExportService::getAvailableFormats()
            ]
        ]);
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): JsonResponse
    {
        $this->authorize('manageAnalytics', User::class);

        try {
            $this->analyticsService->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Analytics cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear analytics cache', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear analytics cache'
            ], 500);
        }
    }

    /**
     * Get analytics dashboard data
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $this->authorize('viewAnalytics', User::class);

        $request->validate([
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'period' => 'nullable|in:7d,30d,90d,1y',
        ]);

        $period = $request->period ?? '30d';
        $endDate = now();
        $startDate = match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
        };

        try {
            $clinical = $this->analyticsService->getClinicalEffectivenessAnalytics(
                $request->hospital_id,
                $startDate->toDateString(),
                $endDate->toDateString()
            );

            $adherence = $this->analyticsService->getAdherencePatterns(
                null,
                $request->hospital_id,
                $startDate->toDateString(),
                $endDate->toDateString()
            );

            $clinician = $this->analyticsService->getClinicianMetrics(
                $request->hospital_id,
                $startDate->toDateString(),
                $endDate->toDateString()
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'clinical_effectiveness' => $clinical,
                    'adherence_patterns' => $adherence,
                    'clinician_metrics' => $clinician,
                    'generated_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get dashboard data', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data'
            ], 500);
        }
    }
}
