<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RuleEffectivenessTrackingService;
use App\Services\ComplianceDataExportService;
use App\Models\RuleApplication;
use App\Models\Payer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ComplianceDashboardController extends Controller
{
    protected RuleEffectivenessTrackingService $effectivenessService;
    protected ComplianceDataExportService $exportService;

    public function __construct(
        RuleEffectivenessTrackingService $effectivenessService,
        ComplianceDataExportService $exportService
    ) {
        $this->effectivenessService = $effectivenessService;
        $this->exportService = $exportService;
    }

    /**
     * Display the compliance dashboard.
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $payerId = $request->get('payer_id');

        $payers = Payer::orderBy('name')->get();

        // Get basic compliance metrics
        $complianceMetrics = $this->getComplianceMetrics($startDate, $endDate, $payerId);

        return view('admin.compliance.dashboard', compact(
            'complianceMetrics',
            'payers',
            'startDate',
            'endDate',
            'payerId'
        ));
    }

    /**
     * Get compliance metrics data via AJAX.
     */
    public function metrics(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->get('start_date', now()->subDays(30)));
        $endDate = Carbon::parse($request->get('end_date', now()));
        $payerId = $request->get('payer_id');

        $metrics = $this->effectivenessService->calculateRuleEffectiveness($startDate, $endDate, $payerId);

        return response()->json($metrics);
    }

    /**
     * Get rule effectiveness data.
     */
    public function ruleEffectiveness(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->get('start_date', now()->subDays(30)));
        $endDate = Carbon::parse($request->get('end_date', now()));
        $limit = $request->get('limit', 20);

        $topRules = $this->effectivenessService->getTopPerformingRules($limit, $startDate, $endDate);

        return response()->json([
            'rules' => $topRules,
            'total' => $topRules->count(),
        ]);
    }

    /**
     * Get rules needing attention.
     */
    public function rulesNeedingAttention(Request $request): JsonResponse
    {
        $threshold = $request->get('threshold', 30.0);
        $limit = $request->get('limit', 10);

        $rulesNeedingAttention = $this->effectivenessService->getRulesNeedingAttention($threshold, $limit);

        return response()->json([
            'rules' => $rulesNeedingAttention,
            'threshold' => $threshold,
            'total' => $rulesNeedingAttention->count(),
        ]);
    }

    /**
     * Get detailed rule effectiveness report.
     */
    public function ruleReport(Request $request, int $ruleId): JsonResponse
    {
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : null;
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : null;

        $report = $this->effectivenessService->generateRuleEffectivenessReport($ruleId, $startDate, $endDate);

        return response()->json($report);
    }

    /**
     * Get HIPAA compliance metrics.
     */
    public function hipaaCompliance(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->get('start_date', now()->subDays(30)));
        $endDate = Carbon::parse($request->get('end_date', now()));

        $applications = RuleApplication::whereBetween('applied_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_applications,
                SUM(CASE WHEN hipaa_compliance_flags IS NOT NULL THEN 1 ELSE 0 END) as hipaa_flagged,
                SUM(CASE WHEN data_classification = "phi" THEN 1 ELSE 0 END) as phi_records,
                SUM(CASE WHEN data_retention_until < NOW() THEN 1 ELSE 0 END) as expired_retention,
                SUM(CASE WHEN user_acknowledged = 1 THEN 1 ELSE 0 END) as acknowledged
            ')
            ->first();

        $metrics = [
            'total_applications' => $applications->total_applications ?? 0,
            'hipaa_compliance_rate' => $applications->total_applications > 0 ?
                round((($applications->total_applications - $applications->hipaa_flagged) / $applications->total_applications) * 100, 2) : 100,
            'phi_records_count' => $applications->phi_records ?? 0,
            'retention_compliance_rate' => $applications->total_applications > 0 ?
                round((($applications->total_applications - $applications->expired_retention) / $applications->total_applications) * 100, 2) : 100,
            'user_acknowledgment_rate' => $applications->total_applications > 0 ?
                round(($applications->acknowledged / $applications->total_applications) * 100, 2) : 0,
        ];

        return response()->json($metrics);
    }

    /**
     * Get audit trail summary.
     */
    public function auditTrail(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->get('start_date', now()->subDays(30)));
        $endDate = Carbon::parse($request->get('end_date', now()));

        $auditSummary = RuleApplication::whereBetween('applied_at', [$startDate, $endDate])
            ->selectRaw('
                DATE(applied_at) as date,
                COUNT(*) as total_applications,
                SUM(CASE WHEN rule_triggered = 1 THEN 1 ELSE 0 END) as triggered_rules,
                SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as with_user_id,
                SUM(CASE WHEN ip_address IS NOT NULL THEN 1 ELSE 0 END) as with_ip_address,
                SUM(CASE WHEN audit_metadata IS NOT NULL THEN 1 ELSE 0 END) as with_audit_metadata
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $summary = $auditSummary->map(function ($day) {
            $completeness = ($day->with_user_id + $day->with_ip_address + $day->with_audit_metadata) / ($day->total_applications * 3) * 100;

            return [
                'date' => $day->date,
                'total_applications' => $day->total_applications,
                'triggered_rules' => $day->triggered_rules,
                'audit_completeness' => round($completeness, 2),
            ];
        });

        return response()->json([
            'audit_trail' => $summary,
            'overall_completeness' => $summary->avg('audit_completeness'),
        ]);
    }

    /**
     * Export compliance report.
     */
    public function export(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', now()->subDays(30)));
        $endDate = Carbon::parse($request->get('end_date', now()));
        $payerId = $request->get('payer_id');
        $format = $request->get('format', 'json');
        $filters = $request->only(['rule_triggered', 'outcome_status', 'data_classification', 'hipaa_compliant']);

        try {
            $filePath = $this->exportService->export($format, $startDate, $endDate, $payerId, $filters);

            $filename = basename($filePath);
            $mimeType = match ($format) {
                'csv' => 'text/csv',
                'json' => 'application/json',
                'xml' => 'application/xml',
                default => 'application/octet-stream',
            };

            return response()->download(Storage::path($filePath), $filename, [
                'Content-Type' => $mimeType,
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Export failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get basic compliance metrics for dashboard display.
     */
    protected function getComplianceMetrics(string $startDate, string $endDate, ?int $payerId): array
    {
        $query = RuleApplication::whereBetween('applied_at', [$startDate, $endDate]);

        if ($payerId) {
            $query->whereHas('rule', function ($q) use ($payerId) {
                $q->where('payer_id', $payerId);
            });
        }

        $metrics = $query->selectRaw('
            COUNT(*) as total_applications,
            SUM(CASE WHEN rule_triggered = 1 THEN 1 ELSE 0 END) as triggered_rules,
            SUM(CASE WHEN outcome_status = "denied" THEN 1 ELSE 0 END) as denials,
            SUM(CASE WHEN outcome_status = "warning" THEN 1 ELSE 0 END) as warnings,
            SUM(CASE WHEN hipaa_compliance_flags IS NOT NULL THEN 1 ELSE 0 END) as hipaa_violations,
            AVG(execution_time_ms) as avg_execution_time
        ')->first();

        return [
            'total_applications' => $metrics->total_applications ?? 0,
            'trigger_rate' => $metrics->total_applications > 0 ?
                round(($metrics->triggered_rules / $metrics->total_applications) * 100, 2) : 0,
            'denial_rate' => $metrics->triggered_rules > 0 ?
                round(($metrics->denials / $metrics->triggered_rules) * 100, 2) : 0,
            'warning_rate' => $metrics->triggered_rules > 0 ?
                round(($metrics->warnings / $metrics->triggered_rules) * 100, 2) : 0,
            'hipaa_compliance_rate' => $metrics->total_applications > 0 ?
                round((($metrics->total_applications - $metrics->hipaa_violations) / $metrics->total_applications) * 100, 2) : 100,
            'avg_execution_time' => round($metrics->avg_execution_time ?? 0, 2),
        ];
    }
}
