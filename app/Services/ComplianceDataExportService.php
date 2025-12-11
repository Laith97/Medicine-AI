<?php

namespace App\Services;

use App\Models\RuleApplication;
use App\Models\Payer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use League\Csv\Writer;
use Illuminate\Support\Str;

class ComplianceDataExportService
{
    /**
     * Export compliance data in the specified format.
     *
     * @param string $format
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $payerId
     * @param array $filters
     * @return string
     */
    public function export(string $format, Carbon $startDate, Carbon $endDate, ?int $payerId = null, array $filters = []): string
    {
        $data = $this->gatherComplianceData($startDate, $endDate, $payerId, $filters);

        return match ($format) {
            'csv' => $this->exportToCsv($data),
            'json' => $this->exportToJson($data),
            'xml' => $this->exportToXml($data),
            'pdf' => $this->exportToPdf($data),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
        };
    }

    /**
     * Gather compliance data for export.
     */
    protected function gatherComplianceData(Carbon $startDate, Carbon $endDate, ?int $payerId, array $filters): array
    {
        $query = RuleApplication::with(['rule.ruleType', 'rule.payer', 'claim', 'user'])
            ->whereBetween('applied_at', [$startDate, $endDate]);

        if ($payerId) {
            $query->whereHas('rule', function ($q) use ($payerId) {
                $q->where('payer_id', $payerId);
            });
        }

        // Apply additional filters
        if (!empty($filters['rule_triggered'])) {
            $query->where('rule_triggered', $filters['rule_triggered'] === 'true');
        }

        if (!empty($filters['outcome_status'])) {
            $query->where('outcome_status', $filters['outcome_status']);
        }

        if (!empty($filters['data_classification'])) {
            $query->where('data_classification', $filters['data_classification']);
        }

        if (!empty($filters['hipaa_compliant'])) {
            $query->where('hipaa_compliance_flags', $filters['hipaa_compliant'] === 'true' ? 'not null' : 'null');
        }

        $applications = $query->orderBy('applied_at')->get();

        return [
            'metadata' => [
                'export_date' => now()->toISOString(),
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
                'total_records' => $applications->count(),
                'filters_applied' => $filters,
                'generated_by' => auth()->user()?->name ?? 'System',
            ],
            'summary' => $this->generateSummaryData($applications),
            'records' => $applications->map(function ($application) {
                return $this->formatApplicationRecord($application);
            })->toArray(),
        ];
    }

    /**
     * Generate summary data for export.
     */
    protected function generateSummaryData(Collection $applications): array
    {
        return [
            'total_applications' => $applications->count(),
            'triggered_rules' => $applications->where('rule_triggered', true)->count(),
            'unique_rules' => $applications->pluck('rule_id')->unique()->count(),
            'unique_claims' => $applications->pluck('claim_id')->unique()->count(),
            'outcome_distribution' => $applications->where('rule_triggered', true)
                ->groupBy('outcome_status')
                ->map->count()
                ->toArray(),
            'data_classification_breakdown' => $applications->groupBy('data_classification')
                ->map->count()
                ->toArray(),
            'hipaa_compliance' => [
                'compliant' => $applications->whereNull('hipaa_compliance_flags')->count(),
                'non_compliant' => $applications->whereNotNull('hipaa_compliance_flags')->count(),
                'compliance_rate' => $applications->count() > 0 ?
                    round(($applications->whereNull('hipaa_compliance_flags')->count() / $applications->count()) * 100, 2) : 0,
            ],
            'performance_metrics' => [
                'avg_execution_time' => round($applications->whereNotNull('execution_time_ms')->avg('execution_time_ms') ?? 0, 2),
                'max_execution_time' => $applications->whereNotNull('execution_time_ms')->max('execution_time_ms') ?? 0,
                'min_execution_time' => $applications->whereNotNull('execution_time_ms')->min('execution_time_ms') ?? 0,
            ],
        ];
    }

    /**
     * Format a single application record for export.
     */
    protected function formatApplicationRecord($application): array
    {
        return [
            'application_id' => $application->id,
            'applied_at' => $application->applied_at->toISOString(),
            'rule_id' => $application->rule_id,
            'rule_name' => $application->rule->ruleType->name ?? 'Unknown',
            'rule_priority' => $application->rule->priority ?? 0,
            'payer_id' => $application->rule->payer->id ?? null,
            'payer_name' => $application->rule->payer->name ?? 'Unknown',
            'claim_id' => $application->claim_id,
            'claim_amount' => $application->claim->expected_amount ?? 0,
            'claim_status' => $application->claim->claim_status ?? 'unknown',
            'user_id' => $application->user_id,
            'user_name' => $application->user?->name ?? 'System',
            'session_id' => $application->session_id,
            'ip_address' => $application->ip_address,
            'user_agent' => $application->user_agent,
            'request_id' => $application->request_id,
            'rule_triggered' => $application->rule_triggered,
            'execution_time_ms' => $application->execution_time_ms,
            'data_classification' => $application->data_classification,
            'outcome_status' => $application->outcome_status,
            'outcome_reason' => $application->outcome_reason,
            'user_acknowledged' => $application->user_acknowledged,
            'user_acknowledged_at' => $application->user_acknowledged_at?->toISOString(),
            'hipaa_compliance_flags' => $application->hipaa_compliance_flags,
            'data_retention_until' => $application->data_retention_until?->toDateString(),
            'compliance_event_type' => $application->compliance_event_type,
            'rule_conditions' => $application->rule_conditions,
            'rule_actions' => $application->rule_actions,
            'audit_metadata' => $application->audit_metadata,
        ];
    }

    /**
     * Export data to CSV format.
     */
    protected function exportToCsv(array $data): string
    {
        $filename = 'compliance-export-' . now()->format('Y-m-d-H-i-s') . '.csv';
        $path = 'exports/' . $filename;

        // Create CSV writer
        $csv = Writer::createFromString('');

        // Add headers
        $headers = [
            'Application ID',
            'Applied At',
            'Rule ID',
            'Rule Name',
            'Rule Priority',
            'Payer ID',
            'Payer Name',
            'Claim ID',
            'Claim Amount',
            'Claim Status',
            'User ID',
            'User Name',
            'Session ID',
            'IP Address',
            'User Agent',
            'Request ID',
            'Rule Triggered',
            'Execution Time (ms)',
            'Data Classification',
            'Outcome Status',
            'Outcome Reason',
            'User Acknowledged',
            'User Acknowledged At',
            'HIPAA Compliance Flags',
            'Data Retention Until',
            'Compliance Event Type',
        ];
        $csv->insertOne($headers);

        // Add data rows
        foreach ($data['records'] as $record) {
            $csv->insertOne([
                $record['application_id'],
                $record['applied_at'],
                $record['rule_id'],
                $record['rule_name'],
                $record['rule_priority'],
                $record['payer_id'],
                $record['payer_name'],
                $record['claim_id'],
                $record['claim_amount'],
                $record['claim_status'],
                $record['user_id'],
                $record['user_name'],
                $record['session_id'],
                $record['ip_address'],
                substr($record['user_agent'] ?? '', 0, 255), // Truncate user agent
                $record['request_id'],
                $record['rule_triggered'] ? 'Yes' : 'No',
                $record['execution_time_ms'],
                $record['data_classification'],
                $record['outcome_status'],
                substr($record['outcome_reason'] ?? '', 0, 500), // Truncate outcome reason
                $record['user_acknowledged'] ? 'Yes' : 'No',
                $record['user_acknowledged_at'],
                json_encode($record['hipaa_compliance_flags']),
                $record['data_retention_until'],
                $record['compliance_event_type'],
            ]);
        }

        // Save to storage
        Storage::put($path, $csv->getContent());

        return $path;
    }

    /**
     * Export data to JSON format.
     */
    protected function exportToJson(array $data): string
    {
        $filename = 'compliance-export-' . now()->format('Y-m-d-H-i-s') . '.json';
        $path = 'exports/' . $filename;

        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * Export data to XML format.
     */
    protected function exportToXml(array $data): string
    {
        $filename = 'compliance-export-' . now()->format('Y-m-d-H-i-s') . '.xml';
        $path = 'exports/' . $filename;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><compliance-export/>');

        // Add metadata
        $metadata = $xml->addChild('metadata');
        foreach ($data['metadata'] as $key => $value) {
            $metadata->addChild($key, is_array($value) ? json_encode($value) : $value);
        }

        // Add summary
        $summary = $xml->addChild('summary');
        foreach ($data['summary'] as $key => $value) {
            if (is_array($value)) {
                $child = $summary->addChild($key);
                foreach ($value as $subKey => $subValue) {
                    $child->addChild($subKey, is_array($subValue) ? json_encode($subValue) : $subValue);
                }
            } else {
                $summary->addChild($key, $value);
            }
        }

        // Add records
        $records = $xml->addChild('records');
        foreach ($data['records'] as $record) {
            $recordElement = $records->addChild('record');
            foreach ($record as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $recordElement->addChild($key)->addCData(json_encode($value));
                } else {
                    $recordElement->addChild($key, htmlspecialchars($value ?? ''));
                }
            }
        }

        Storage::put($path, $xml->asXML());

        return $path;
    }

    /**
     * Export data to PDF format (placeholder - would require additional PDF library).
     */
    protected function exportToPdf(array $data): string
    {
        // This would require a PDF library like TCPDF, DomPDF, etc.
        // For now, return a message indicating PDF export is not implemented
        throw new \RuntimeException('PDF export is not yet implemented. Please use CSV, JSON, or XML formats.');
    }

    /**
     * Generate compliance audit trail report.
     */
    public function generateAuditTrailReport(Carbon $startDate, Carbon $endDate, ?int $payerId = null): array
    {
        $applications = RuleApplication::with(['rule.ruleType', 'rule.payer', 'user'])
            ->whereBetween('applied_at', [$startDate, $endDate]);

        if ($payerId) {
            $applications->whereHas('rule', function ($q) use ($payerId) {
                $q->where('payer_id', $payerId);
            });
        }

        $applications = $applications->orderBy('applied_at')->get();

        return [
            'report_title' => 'Payer Rules Engine Compliance Audit Trail',
            'report_period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toISOString(),
            'generated_by' => auth()->user()?->name ?? 'System',
            'total_events' => $applications->count(),
            'audit_events' => $applications->map(function ($app) {
                return [
                    'timestamp' => $app->applied_at->toISOString(),
                    'event_type' => $app->compliance_event_type ?? 'rule_application',
                    'user' => $app->user?->name ?? 'System',
                    'rule_name' => $app->rule->ruleType->name ?? 'Unknown',
                    'payer_name' => $app->rule->payer->name ?? 'Unknown',
                    'outcome' => $app->outcome_status,
                    'data_classification' => $app->data_classification,
                    'hipaa_compliant' => empty($app->hipaa_compliance_flags),
                    'ip_address' => $app->ip_address,
                    'session_id' => $app->session_id,
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate HIPAA compliance report.
     */
    public function generateHipaaComplianceReport(Carbon $startDate, Carbon $endDate, ?int $payerId = null): array
    {
        $applications = RuleApplication::whereBetween('applied_at', [$startDate, $endDate]);

        if ($payerId) {
            $applications->whereHas('rule', function ($q) use ($payerId) {
                $q->where('payer_id', $payerId);
            });
        }

        $stats = $applications->selectRaw('
            COUNT(*) as total_applications,
            SUM(CASE WHEN hipaa_compliance_flags IS NULL THEN 1 ELSE 0 END) as hipaa_compliant,
            SUM(CASE WHEN hipaa_compliance_flags IS NOT NULL THEN 1 ELSE 0 END) as hipaa_violations,
            SUM(CASE WHEN data_classification = "phi" THEN 1 ELSE 0 END) as phi_records,
            SUM(CASE WHEN data_retention_until < NOW() THEN 1 ELSE 0 END) as expired_retention,
            SUM(CASE WHEN user_acknowledged = 1 THEN 1 ELSE 0 END) as acknowledged_records
        ')->first();

        $violations = RuleApplication::whereBetween('applied_at', [$startDate, $endDate])
            ->whereNotNull('hipaa_compliance_flags')
            ->with(['rule.ruleType', 'rule.payer'])
            ->orderBy('applied_at', 'desc')
            ->limit(100)
            ->get();

        return [
            'report_title' => 'HIPAA Compliance Report - Payer Rules Engine',
            'report_period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toISOString(),
            'compliance_summary' => [
                'total_applications' => $stats->total_applications ?? 0,
                'hipaa_compliant' => $stats->hipaa_compliant ?? 0,
                'hipaa_violations' => $stats->hipaa_violations ?? 0,
                'compliance_rate' => $stats->total_applications > 0 ?
                    round(($stats->hipaa_compliant / $stats->total_applications) * 100, 2) : 100,
                'phi_records_processed' => $stats->phi_records ?? 0,
                'expired_retention_records' => $stats->expired_retention ?? 0,
                'user_acknowledged_records' => $stats->acknowledged_records ?? 0,
            ],
            'violations' => $violations->map(function ($violation) {
                return [
                    'application_id' => $violation->id,
                    'timestamp' => $violation->applied_at->toISOString(),
                    'rule_name' => $violation->rule->ruleType->name ?? 'Unknown',
                    'payer_name' => $violation->rule->payer->name ?? 'Unknown',
                    'violation_flags' => $violation->hipaa_compliance_flags,
                    'data_classification' => $violation->data_classification,
                    'outcome_status' => $violation->outcome_status,
                ];
            })->toArray(),
        ];
    }

    /**
     * Clean up old export files.
     */
    public function cleanupOldExports(int $daysOld = 30): int
    {
        $oldFiles = Storage::files('exports');

        $deleted = 0;
        foreach ($oldFiles as $file) {
            $fileModified = Storage::lastModified($file);
            if (now()->timestamp - $fileModified > ($daysOld * 24 * 60 * 60)) {
                Storage::delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
