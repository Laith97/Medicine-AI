<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ClearinghouseSubmission;
use App\Models\ClearinghouseResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ClearinghouseComplianceService
{
    /**
     * Generate HIPAA compliance report
     */
    public function generateHipaaComplianceReport(int $hospitalId, array $dateRange = []): array
    {
        $startDate = $dateRange['start'] ?? now()->startOfMonth();
        $endDate = $dateRange['end'] ?? now()->endOfMonth();

        // Get EDI data access logs
        $ediAccessLogs = AuditLog::where('action_type', 'hipaa_compliance')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->whereHas('user', function ($q) use ($hospitalId) {
                $q->where('hospital_id', $hospitalId);
            })
            ->get();

        // Get clearinghouse transactions
        $transactions = AuditLog::where('action_type', 'clearinghouse')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->whereHas('user', function ($q) use ($hospitalId) {
                $q->where('hospital_id', $hospitalId);
            })
            ->get();

        // Get submissions with EDI data
        $submissions = ClearinghouseSubmission::with(['clearinghouseAccount', 'claims'])
            ->whereHas('claims', function ($q) use ($hospitalId) {
                $q->where('hospital_id', $hospitalId);
            })
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->get();

        // Analyze data encryption status
        $encryptionStatus = $this->analyzeEncryptionStatus($submissions);

        // Check for unauthorized access attempts
        $securityIncidents = $this->analyzeSecurityIncidents($ediAccessLogs, $startDate, $endDate);

        return [
            'report_period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'hospital_id' => $hospitalId,
            'hipaa_compliance_status' => $this->calculateComplianceStatus($encryptionStatus, $securityIncidents),
            'encryption_status' => $encryptionStatus,
            'security_incidents' => $securityIncidents,
            'edi_access_summary' => [
                'total_access_events' => $ediAccessLogs->count(),
                'unique_users' => $ediAccessLogs->pluck('user_id')->unique()->count(),
                'access_by_action' => $ediAccessLogs->groupBy('context.action')->map->count()
            ],
            'transaction_summary' => [
                'total_transactions' => $transactions->count(),
                'successful_submissions' => $transactions->where('context.action', 'submission_successful')->count(),
                'failed_submissions' => $transactions->where('context.action', 'submission_failed')->count(),
                'manual_resubmits' => $transactions->where('context.action', 'manual_resubmit')->count()
            ],
            'data_protection_measures' => [
                'encryption_enabled' => config('app.edi_encryption_key') !== null,
                'audit_logging_enabled' => true,
                'access_controls_implemented' => true
            ],
            'recommendations' => $this->generateComplianceRecommendations($encryptionStatus, $securityIncidents)
        ];
    }

    /**
     * Export audit trail data
     */
    public function exportAuditTrail(int $hospitalId, array $filters = []): Collection
    {
        $query = AuditLog::with(['user'])
            ->whereIn('action_type', ['clearinghouse', 'hipaa_compliance', 'compliance'])
            ->whereHas('user', function ($q) use ($hospitalId) {
                $q->where('hospital_id', $hospitalId);
            });

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($log) {
            return [
                'timestamp' => $log->created_at->toISOString(),
                'user_id' => $log->user_id,
                'user_name' => $log->user->name ?? 'Unknown',
                'action_type' => $log->action_type,
                'action' => $log->action,
                'ip_address' => $log->context['ip_address'] ?? null,
                'user_agent' => $log->context['user_agent'] ?? null,
                'details' => $log->context
            ];
        });
    }

    /**
     * Generate compliance violation report
     */
    public function generateComplianceViolationReport(int $hospitalId, array $dateRange = []): array
    {
        $startDate = $dateRange['start'] ?? now()->startOfMonth();
        $endDate = $dateRange['end'] ?? now()->endOfMonth();

        // Find potential violations
        $violations = [];

        // Check for unauthorized EDI access
        $unauthorizedAccess = AuditLog::where('action_type', 'hipaa_compliance')
            ->where('action', 'unauthorized_access')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->get();

        if ($unauthorizedAccess->count() > 0) {
            $violations[] = [
                'type' => 'unauthorized_edi_access',
                'severity' => 'high',
                'count' => $unauthorizedAccess->count(),
                'description' => 'Unauthorized attempts to access EDI data',
                'incidents' => $unauthorizedAccess->toArray()
            ];
        }

        // Check for unencrypted EDI storage
        $unencryptedSubmissions = ClearinghouseSubmission::whereNotNull('edi_content')
            ->whereHas('claims', function ($q) use ($hospitalId) {
                $q->where('hospital_id', $hospitalId);
            })
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->get()
            ->filter(function ($submission) {
                // Check if EDI content appears to be unencrypted (simple heuristic)
                return !str_starts_with($submission->edi_content, 'eyJ') || strlen($submission->edi_content) < 50;
            });

        if ($unencryptedSubmissions->count() > 0) {
            $violations[] = [
                'type' => 'unencrypted_edi_storage',
                'severity' => 'critical',
                'count' => $unencryptedSubmissions->count(),
                'description' => 'EDI data stored without proper encryption',
                'affected_submissions' => $unencryptedSubmissions->pluck('id')->toArray()
            ];
        }

        // Check for missing audit logs
        $submissionsWithoutAudit = ClearinghouseSubmission::whereDoesntHave('auditLogs')
            ->whereHas('claims', function ($q) use ($hospitalId) {
                $q->where('hospital_id', $hospitalId);
            })
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->count();

        if ($submissionsWithoutAudit > 0) {
            $violations[] = [
                'type' => 'missing_audit_logs',
                'severity' => 'medium',
                'count' => $submissionsWithoutAudit,
                'description' => 'Clearinghouse submissions without corresponding audit logs'
            ];
        }

        return [
            'report_period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ],
            'hospital_id' => $hospitalId,
            'total_violations' => count($violations),
            'violations_by_severity' => collect($violations)->groupBy('severity')->map->count(),
            'violations' => $violations,
            'compliance_status' => count($violations) === 0 ? 'compliant' : 'violations_found'
        ];
    }

    /**
     * Analyze encryption status
     */
    protected function analyzeEncryptionStatus(Collection $submissions): array
    {
        $totalSubmissions = $submissions->count();
        $encryptedCount = 0;
        $unencryptedCount = 0;

        foreach ($submissions as $submission) {
            if (empty($submission->edi_content)) {
                continue;
            }

            // Simple check: Laravel encrypted strings start with 'eyJ'
            if (str_starts_with($submission->edi_content, 'eyJ') && strlen($submission->edi_content) > 50) {
                $encryptedCount++;
            } else {
                $unencryptedCount++;
            }
        }

        return [
            'total_submissions_with_edi' => $totalSubmissions,
            'encrypted_count' => $encryptedCount,
            'unencrypted_count' => $unencryptedCount,
            'encryption_rate' => $totalSubmissions > 0 ? round(($encryptedCount / $totalSubmissions) * 100, 2) : 0,
            'encryption_enabled' => config('app.edi_encryption_key') !== null
        ];
    }

    /**
     * Analyze security incidents
     */
    protected function analyzeSecurityIncidents(Collection $ediAccessLogs, Carbon $startDate, Carbon $endDate): array
    {
        $suspiciousActivities = $ediAccessLogs->filter(function ($log) {
            $context = $log->context ?? [];
            return isset($context['suspicious']) && $context['suspicious'];
        });

        $unauthorizedAccess = $ediAccessLogs->where('action', 'unauthorized_access');

        $unusualAccessPatterns = $this->detectUnusualAccessPatterns($ediAccessLogs, $startDate, $endDate);

        return [
            'suspicious_activities' => $suspiciousActivities->count(),
            'unauthorized_access_attempts' => $unauthorizedAccess->count(),
            'unusual_access_patterns' => $unusualAccessPatterns,
            'total_incidents' => $suspiciousActivities->count() + $unauthorizedAccess->count() + count($unusualAccessPatterns)
        ];
    }

    /**
     * Detect unusual access patterns
     */
    protected function detectUnusualAccessPatterns(Collection $logs, Carbon $startDate, Carbon $endDate): array
    {
        $patterns = [];

        // Check for excessive access from single IP
        $accessByIp = $logs->groupBy('context.ip_address');
        foreach ($accessByIp as $ip => $ipLogs) {
            if ($ipLogs->count() > 100) { // Threshold for suspicious activity
                $patterns[] = [
                    'type' => 'excessive_access_from_ip',
                    'ip_address' => $ip,
                    'access_count' => $ipLogs->count(),
                    'description' => "Excessive EDI access from single IP address"
                ];
            }
        }

        // Check for access outside business hours
        $offHoursAccess = $logs->filter(function ($log) {
            $hour = $log->created_at->hour;
            return $hour < 6 || $hour > 22; // Outside 6 AM - 10 PM
        });

        if ($offHoursAccess->count() > 0) {
            $patterns[] = [
                'type' => 'off_hours_access',
                'count' => $offHoursAccess->count(),
                'description' => "EDI access outside normal business hours"
            ];
        }

        return $patterns;
    }

    /**
     * Calculate overall compliance status
     */
    protected function calculateComplianceStatus(array $encryptionStatus, array $securityIncidents): string
    {
        if ($securityIncidents['total_incidents'] > 0) {
            return 'non_compliant';
        }

        if ($encryptionStatus['encryption_rate'] < 100) {
            return 'partial_compliance';
        }

        return 'fully_compliant';
    }

    /**
     * Generate compliance recommendations
     */
    protected function generateComplianceRecommendations(array $encryptionStatus, array $securityIncidents): array
    {
        $recommendations = [];

        if (!$encryptionStatus['encryption_enabled']) {
            $recommendations[] = [
                'priority' => 'critical',
                'recommendation' => 'Configure EDI encryption key in environment settings',
                'action_required' => 'Set EDI_ENCRYPTION_KEY in .env file'
            ];
        }

        if ($encryptionStatus['unencrypted_count'] > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'recommendation' => 'Re-encrypt existing unencrypted EDI data',
                'action_required' => 'Run data migration to encrypt stored EDI content'
            ];
        }

        if ($securityIncidents['unauthorized_access_attempts'] > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'recommendation' => 'Review and strengthen access controls for EDI data',
                'action_required' => 'Audit user permissions and implement additional security measures'
            ];
        }

        if ($securityIncidents['suspicious_activities'] > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'recommendation' => 'Investigate suspicious EDI access patterns',
                'action_required' => 'Review access logs and implement anomaly detection'
            ];
        }

        return $recommendations;
    }
}
