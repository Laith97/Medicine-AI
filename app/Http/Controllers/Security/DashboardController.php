<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\SecurityReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        // Only admins can access security dashboard
        $this->middleware('admin');
    }

    /**
     * Display security dashboard with audit logs and reports
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $timeRange = $request->get('time_range', '24_hours');
        $actionType = $request->get('action_type', 'all');
        $userId = $request->get('user_id', null);

        // Build query for audit logs
        $query = AuditLog::with(['user', 'doctor', 'patient'])->orderBy('created_at', 'desc');

        // Apply time range filter
        $startDate = $this->getStartDate($timeRange);
        $query->where('created_at', '>=', $startDate);

        // Apply action type filter
        if ($actionType !== 'all') {
            $query->where('action', $actionType);
        }

        // Apply user filter
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Get paginated audit logs
        $auditLogs = $query->paginate(50);

        // Get security reports
        $unauthorizedAccessReports = SecurityReportingService::detectUnauthorizedPatientAccess($timeRange);
        $frequentImpersonationReports = SecurityReportingService::detectFrequentImpersonation($timeRange);
        $unusualAssignmentReports = SecurityReportingService::detectUnusualPatientAssignments($timeRange);
        $unauthorizedSubUserReports = SecurityReportingService::detectUnauthorizedSubUserAccess($timeRange);
        $multipleDoctorAccessReports = SecurityReportingService::detectMultipleDoctorAccess($timeRange);

        // Get action types for filter dropdown
        $actionTypes = AuditLog::select('action')->distinct()->pluck('action');

        return view('security.dashboard', compact(
            'auditLogs',
            'unauthorizedAccessReports',
            'frequentImpersonationReports',
            'unusualAssignmentReports',
            'unauthorizedSubUserReports',
            'multipleDoctorAccessReports',
            'actionTypes',
            'timeRange',
            'actionType',
            'userId'
        ));
    }

    /**
     * Get start date based on time range
     */
    private function getStartDate($timeRange)
    {
        switch ($timeRange) {
            case '1_hour':
                return now()->subHour();
            case '24_hours':
                return now()->subDay();
            case '7_days':
                return now()->subWeek();
            case '30_days':
                return now()->subMonth();
            default:
                return now()->subDay();
        }
    }

    /**
     * Display detailed audit log information
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load(['user', 'doctor', 'patient']);

        return view('security.audit-log-show', compact('auditLog'));
    }

    /**
     * Export audit logs as CSV
     */
    public function export(Request $request)
    {
        // Get filter parameters
        $timeRange = $request->get('time_range', '24_hours');
        $actionType = $request->get('action_type', 'all');
        $userId = $request->get('user_id', null);

        // Build query for audit logs
        $query = AuditLog::with(['user', 'doctor', 'patient'])->orderBy('created_at', 'desc');

        // Apply time range filter
        $startDate = $this->getStartDate($timeRange);
        $query->where('created_at', '>=', $startDate);

        // Apply action type filter
        if ($actionType !== 'all') {
            $query->where('action', $actionType);
        }

        // Apply user filter
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Get audit logs
        $auditLogs = $query->get();

        // Generate CSV
        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($auditLogs) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'ID', 'Action', 'User ID', 'User Name', 'Doctor ID', 'Doctor Name',
                'Patient ID', 'Patient Name', 'Timestamp', 'IP Address', 'User Agent', 'Metadata'
            ]);

            // Add data rows
            foreach ($auditLogs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->action,
                    $log->user_id,
                    $log->user ? $log->user->name : 'N/A',
                    $log->doctor_id,
                    $log->doctor ? $log->doctor->name : 'N/A',
                    $log->patient_id,
                    $log->patient ? $log->patient->name : 'N/A',
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->ip_address,
                    $log->user_agent,
                    json_encode($log->metadata)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
