<?php

namespace App\Services;

use App\Models\RuleApplication;
use App\Models\AuditLog;
use App\Models\ComplianceEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use League\Csv\Writer;

class ComplianceAuditTrailService
{
    /**
     * Perform data retention cleanup based on HIPAA requirements.
     *
     * @param bool $dryRun Whether to perform a dry run
     * @return array
     */
    public function performDataRetentionCleanup(bool $dryRun = true): array
    {
        $results = [
            'processed' => 0,
            'deleted' => 0,
            'errors' => 0,
            'details' => [],
        ];

        // Get records past their retention date
        $expiredRecords = RuleApplication::pastRetentionDate()
            ->with(['rule.ruleType', 'rule.payer'])
            ->get();

        foreach ($expiredRecords as $record) {
            try {
                $results['processed']++;

                if (!$dryRun) {
                    // Log the deletion for audit purposes
                    Log::info('Compliance data retention: Deleting expired rule application record', [
                        'application_id' => $record->id,
                        'rule_id' => $record->rule_id,
                        'rule_name' => $record->rule->ruleType->name ?? 'Unknown',
                        'payer_name' => $record->rule->payer->name ?? 'Unknown',
                        'data_classification' => $record->data_classification,
                        'retention_until' => $record->data_retention_until?->toDateString(),
                        'applied_at' => $record->applied_at->toDateString(),
                    ]);

                    $record->delete();
                    $results['deleted']++;
                }

                $results['details'][] = [
                    'application_id' => $record->id,
                    'rule_name' => $record->rule->ruleType->name ?? 'Unknown',
                    'data_classification' => $record->data_classification,
                    'retention_until' => $record->data_retention_until?->toDateString(),
                    'action' => $dryRun ? 'would_delete' : 'deleted',
                ];

            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Error during compliance data retention cleanup', [
                    'application_id' => $record->id,
                    'error' => $e->getMessage(),
                ]);

                $results['details'][] = [
                    'application_id' => $record->id,
                    'error' => $e->getMessage(),
                    'action' => 'error',
                ];
            }
        }

        return $results;
    }

    /**
     * Audit HIPAA compliance across all rule applications.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function auditHipaaCompliance(Carbon $startDate, Carbon $endDate): array
    {
        $applications = RuleApplication::whereBetween('applied_at', [$startDate, $endDate])
            ->with(['rule.ruleType', 'rule.payer', 'user'])
            ->get();

        $auditResults = [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_applications' => $applications->count(),
            'compliance_summary' => [
                'compliant' => 0,
                'non_compliant' => 0,
                'compliance_rate' => 0,
            ],
            'violations_by_type' => [],
            'phi_data_handling' => [
                'total_phi_records' => 0,
                'properly_classified' => 0,
                'retention_policy_applied' => 0,
            ],
            'detailed_violations' => [],
        ];

        foreach ($applications as $application) {
            $isCompliant = $this->checkApplicationCompliance($application);

            if ($isCompliant) {
                $auditResults['compliance_summary']['compliant']++;
            } else {
                $auditResults['compliance_summary']['non_compliant']++;

                // Categorize violations
                $violations = $this->identifyViolations($application);
                foreach ($violations as $violationType) {
                    if (!isset($auditResults['violations_by_type'][$violationType])) {
                        $auditResults['violations_by_type'][$violationType] = 0;
                    }
                    $auditResults['violations_by_type'][$violationType]++;
                }

                $auditResults['detailed_violations'][] = [
                    'application_id' => $application->id,
                    'rule_name' => $application->rule->ruleType->name ?? 'Unknown',
                    'payer_name' => $application->rule->payer->name ?? 'Unknown',
                    'applied_at' => $application->applied_at->toISOString(),
                    'violations' => $violations,
                    'data_classification' => $application->data_classification,
                    'hipaa_flags' => $application->hipaa_compliance_flags,
                ];
            }

            // Check PHI data handling
            if ($application->data_classification === 'phi') {
                $auditResults['phi_data_handling']['total_phi_records']++;

                if ($application->data_classification === 'phi') {
                    $auditResults['phi_data_handling']['properly_classified']++;
                }

                if ($application->data_retention_until) {
                    $auditResults['phi_data_handling']['retention_policy_applied']++;
                }
            }
        }

        // Calculate compliance rate
        $total = $auditResults['total_applications'];
        if ($total > 0) {
            $auditResults['compliance_summary']['compliance_rate'] =
                round(($auditResults['compliance_summary']['compliant'] / $total) * 100, 2);
        }

        return $auditResults;
    }

    /**
     * Check if a rule application is HIPAA compliant.
     */
    protected function checkApplicationCompliance(RuleApplication $application): bool
    {
        // Check for explicit HIPAA compliance flags
        if (!empty($application->hipaa_compliance_flags)) {
            return false;
        }

        // Check data classification requirements
        if ($application->data_classification === 'phi' && !$application->data_retention_until) {
            return false;
        }

        // Check audit trail completeness
        if (!$application->user_id || !$application->applied_at) {
            return false;
        }

        // Check for proper data handling based on classification
        if ($application->data_classification === 'phi') {
            // PHI data should have appropriate retention policies
            if (!$application->data_retention_until || $application->data_retention_until->year < 2025) {
                return false;
            }
        }

        return true;
    }

    /**
     * Identify specific violations in a rule application.
     */
    protected function identifyViolations(RuleApplication $application): array
    {
        $violations = [];

        if (!empty($application->hipaa_compliance_flags)) {
            $violations = array_merge($violations, $application->hipaa_compliance_flags);
        }

        if ($application->data_classification === 'phi' && !$application->data_retention_until) {
            $violations[] = 'missing_phi_retention_policy';
        }

        if (!$application->user_id) {
            $violations[] = 'missing_user_audit_trail';
        }

        if (!$application->ip_address) {
            $violations[] = 'missing_ip_audit_trail';
        }

        if ($application->data_classification === 'phi' && $application->data_retention_until && $application->data_retention_until->year < 2025) {
            $violations[] = 'insufficient_phi_retention_period';
        }

        return array_unique($violations);
    }

    /**
     * Generate compliance monitoring alerts.
     *
     * @return array
     */
    public function generateComplianceAlerts(): array
    {
        $alerts = [];

        // Check for expired retention records
        $expiredCount = RuleApplication::pastRetentionDate()->count();
        if ($expiredCount > 0) {
            $alerts[] = [
                'type' => 'retention_cleanup_required',
                'severity' => 'medium',
                'message' => "{$expiredCount} rule application records have exceeded their retention period",
                'action_required' => 'Run data retention cleanup',
                'count' => $expiredCount,
            ];
        }

        // Check for HIPAA compliance issues
        $hipaaViolations = RuleApplication::whereNotNull('hipaa_compliance_flags')
            ->where('applied_at', '>=', now()->subDays(7))
            ->count();

        if ($hipaaViolations > 0) {
            $alerts[] = [
                'type' => 'hipaa_compliance_violation',
                'severity' => 'high',
                'message' => "{$hipaaViolations} HIPAA compliance violations detected in the last 7 days",
                'action_required' => 'Review and remediate compliance violations',
                'count' => $hipaaViolations,
            ];
        }

        // Check for audit trail completeness
        $incompleteAudits = RuleApplication::where(function ($query) {
            $query->whereNull('user_id')
                  ->orWhereNull('ip_address')
                  ->orWhereNull('applied_at');
        })->where('applied_at', '>=', now()->subDays(1))->count();

        if ($incompleteAudits > 0) {
            $alerts[] = [
                'type' => 'incomplete_audit_trail',
                'severity' => 'medium',
                'message' => "{$incompleteAudits} rule applications have incomplete audit trails",
                'action_required' => 'Review audit logging configuration',
                'count' => $incompleteAudits,
            ];
        }

        // Check for performance issues
        $slowApplications = RuleApplication::where('execution_time_ms', '>', 5000)
            ->where('applied_at', '>=', now()->subDays(1))
            ->count();

        if ($slowApplications > 0) {
            $alerts[] = [
                'type' => 'performance_degradation',
                'severity' => 'low',
                'message' => "{$slowApplications} rule applications took longer than 5 seconds to execute",
                'action_required' => 'Monitor and optimize rule engine performance',
                'count' => $slowApplications,
            ];
        }

        return $alerts;
    }

    /**
     * Log compliance audit event.
     */
    public function logComplianceAuditEvent(string $eventType, array $metadata = []): void
    {
        AuditLog::log('compliance_audit', auth()->id(), null, null, array_merge([
            'event_type' => $eventType,
            'compliance_system' => 'payer_rules_engine',
            'timestamp' => now()->toISOString(),
        ], $metadata));
    }

    /**
     * Get compliance metrics for monitoring dashboard.
     */
    public function getComplianceMetrics(): array
    {
        $last30Days = now()->subDays(30);

        return [
            'hipaa_compliance' => [
                'current_rate' => $this->calculateComplianceRate($last30Days, now()),
                'trend' => $this->calculateComplianceTrend($last30Days, now()),
            ],
            'data_retention' => [
                'expired_records' => RuleApplication::pastRetentionDate()->count(),
                'records_nearing_expiry' => RuleApplication::where('data_retention_until', '<=', now()->addDays(30))
                    ->where('data_retention_until', '>', now())
                    ->count(),
            ],
            'audit_completeness' => [
                'complete_records' => RuleApplication::whereNotNull(['user_id', 'ip_address', 'applied_at'])
                    ->where('applied_at', '>=', $last30Days)
                    ->count(),
                'total_records' => RuleApplication::where('applied_at', '>=', $last30Days)->count(),
            ],
            'performance' => [
                'avg_execution_time' => RuleApplication::where('applied_at', '>=', $last30Days)
                    ->whereNotNull('execution_time_ms')
                    ->avg('execution_time_ms'),
                'slow_executions' => RuleApplication::where('execution_time_ms', '>', 1000)
                    ->where('applied_at', '>=', $last30Days)
                    ->count(),
            ],
        ];
    }

    /**
     * Calculate compliance rate for a given period.
     */
    protected function calculateComplianceRate(Carbon $startDate, Carbon $endDate): float
    {
        $applications = RuleApplication::whereBetween('applied_at', [$startDate, $endDate])->count();

        if ($applications === 0) {
            return 100.0;
        }

        $compliant = RuleApplication::whereBetween('applied_at', [$startDate, $endDate])
            ->whereNull('hipaa_compliance_flags')
            ->count();

        return round(($compliant / $applications) * 100, 2);
    }

    /**
     * Calculate compliance trend over time.
     */
    protected function calculateComplianceTrend(Carbon $startDate, Carbon $endDate): array
    {
        $days = $startDate->diffInDays($endDate);
        $trend = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $rate = $this->calculateComplianceRate($date->startOfDay(), $date->endOfDay());

            $trend[] = [
                'date' => $date->toDateString(),
                'compliance_rate' => $rate,
            ];
        }

        return $trend;
    }

    /**
     * Log a compliance-specific audit event.
     */
    public function logComplianceEvent(string $eventType, string $eventCategory, array $eventData = []): void
    {
        $user = auth()->user();

        ComplianceEvent::create([
            'event_type' => $eventType,
            'event_category' => $eventCategory,
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'resource_id' => $eventData['resource_id'] ?? null,
            'resource_type' => $eventData['resource_type'] ?? null,
            'action_performed' => $eventData['action_performed'] ?? 'unknown',
            'event_data' => $eventData['event_data'] ?? [],
            'compliance_context' => $eventData['compliance_context'] ?? [],
            'severity_level' => $eventData['severity_level'] ?? 'low',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_id' => $eventData['request_id'] ?? null,
            'event_timestamp' => now(),
        ]);

        // Also log to traditional audit log for backward compatibility
        AuditLog::log('compliance_event', $user?->id, null, null, array_merge([
            'event_type' => $eventType,
            'event_category' => $eventCategory,
            'timestamp' => now()->toISOString(),
        ], $eventData));
    }

    /**
     * Export compliance audit trail data.
     */
    public function exportComplianceAuditTrail(Carbon $startDate, Carbon $endDate, array $filters = []): string
    {
        $query = ComplianceEvent::with(['user'])
            ->whereBetween('event_timestamp', [$startDate, $endDate]);

        // Apply filters
        if (!empty($filters['event_type'])) {
            $query->ofType($filters['event_type']);
        }

        if (!empty($filters['event_category'])) {
            $query->inCategory($filters['event_category']);
        }

        if (!empty($filters['severity_level'])) {
            $query->withSeverity($filters['severity_level']);
        }

        if (!empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        $events = $query->orderBy('event_timestamp')->get();

        $filename = 'compliance-audit-trail-' . now()->format('Y-m-d-H-i-s') . '.csv';
        $path = 'exports/' . $filename;

        $csv = Writer::createFromString('');

        // Add headers
        $headers = [
            'Event ID',
            'Event Type',
            'Event Category',
            'Timestamp',
            'User',
            'User Role',
            'Resource Type',
            'Resource ID',
            'Action Performed',
            'Severity Level',
            'IP Address',
            'Compliance Context',
            'Event Data',
        ];
        $csv->insertOne($headers);

        // Add data rows
        foreach ($events as $event) {
            $csv->insertOne([
                $event->id,
                $event->event_type,
                $event->event_category,
                $event->event_timestamp->toISOString(),
                $event->user?->name ?? 'System',
                $event->user_role,
                $event->resource_type,
                $event->resource_id,
                $event->action_performed,
                $event->severity_level,
                $event->ip_address,
                json_encode($event->compliance_context),
                json_encode($event->event_data),
            ]);
        }

        Storage::put($path, $csv->getContent());

        return $path;
    }

    /**
     * Generate comprehensive compliance audit report.
     */
    public function generateComprehensiveAuditReport(Carbon $startDate, Carbon $endDate): array
    {
        $events = ComplianceEvent::whereBetween('event_timestamp', [$startDate, $endDate])
            ->with(['user'])
            ->get();

        $ruleApplications = RuleApplication::whereBetween('applied_at', [$startDate, $endDate])
            ->with(['rule.ruleType', 'rule.payer', 'user'])
            ->get();

        return [
            'report_period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_compliance_events' => $events->count(),
                'total_rule_applications' => $ruleApplications->count(),
                'events_by_category' => $events->groupBy('event_category')->map->count()->toArray(),
                'events_by_severity' => $events->groupBy('severity_level')->map->count()->toArray(),
                'violations_count' => $events->filter->isViolation()->count(),
                'high_severity_events' => $events->where('severity_level', 'high')->count(),
                'critical_events' => $events->where('severity_level', 'critical')->count(),
            ],
            'compliance_events' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'event_category' => $event->event_category,
                    'timestamp' => $event->event_timestamp->toISOString(),
                    'user' => $event->user?->name ?? 'System',
                    'severity_level' => $event->severity_level,
                    'is_violation' => $event->isViolation(),
                    'compliance_context' => $event->compliance_context,
                ];
            })->toArray(),
            'rule_applications' => $ruleApplications->map(function ($application) {
                return [
                    'id' => $application->id,
                    'rule_name' => $application->rule->ruleType->name ?? 'Unknown',
                    'applied_at' => $application->applied_at->toISOString(),
                    'outcome_status' => $application->outcome_status,
                    'data_classification' => $application->data_classification,
                    'hipaa_compliant' => empty($application->hipaa_compliance_flags),
                    'user' => $application->user?->name ?? 'System',
                ];
            })->toArray(),
        ];
    }

    /**
     * Get compliance event analytics.
     */
    public function getComplianceAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $events = ComplianceEvent::whereBetween('event_timestamp', [$startDate, $endDate])->get();

        return [
            'event_type_distribution' => $events->groupBy('event_type')->map->count()->toArray(),
            'category_distribution' => $events->groupBy('event_category')->map->count()->toArray(),
            'severity_distribution' => $events->groupBy('severity_level')->map->count()->toArray(),
            'user_activity' => $events->groupBy('user_id')->map(function ($userEvents) {
                $user = $userEvents->first()->user;
                return [
                    'user_name' => $user?->name ?? 'System',
                    'event_count' => $userEvents->count(),
                    'violations' => $userEvents->filter->isViolation()->count(),
                ];
            })->values()->toArray(),
            'timeline' => $events->groupBy(function ($event) {
                return $event->event_timestamp->format('Y-m-d');
            })->map->count()->toArray(),
            'violation_trends' => [
                'total_violations' => $events->filter->isViolation()->count(),
                'violations_by_type' => $events->filter->isViolation()
                    ->groupBy('event_type')->map->count()->toArray(),
            ],
        ];
    }

    /**
     * Clean up old compliance events based on retention policy.
     */
    public function cleanupOldComplianceEvents(int $daysOld = 2555): int // 7 years for HIPAA compliance
    {
        $cutoffDate = now()->subDays($daysOld);

        $deleted = ComplianceEvent::where('event_timestamp', '<', $cutoffDate)->delete();

        Log::info('Cleaned up old compliance events', [
            'deleted_count' => $deleted,
            'cutoff_date' => $cutoffDate->toDateString(),
        ]);

        return $deleted;
    }
}
