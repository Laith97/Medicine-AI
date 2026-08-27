<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLoggingService
{
    /**
     * Log doctor accessing patient data
     */
    public static function logDoctorAccessPatient($doctorId, $patientId, $context = [])
    {
        AuditLog::log('doctor_access_patient', $doctorId, $patientId, $doctorId, array_merge($context, [
            'route' => Request::route()->getName() ?? 'unknown'
        ]));
    }

    /**
     * Log diagnosis creation
     */
    public static function logDiagnosisCreated($doctorId, $patientId, $diagnosisId, $context = [])
    {
        AuditLog::log('diagnosis_created', $doctorId, $patientId, $doctorId, array_merge($context, [
            'diagnosis_id' => $diagnosisId
        ]));
    }

    /**
     * Log diagnosis follow-up
     */
    public static function logDiagnosisFollowUp($userId, $patientId, $diagnosisId, $context = [])
    {
        AuditLog::log('diagnosis_follow_up', $userId, $patientId, null, array_merge($context, [
            'diagnosis_id' => $diagnosisId
        ]));
    }

    /**
     * Log patient assignment change
     */
    public static function logPatientAssignment($doctorId, $patientId, $context = [])
    {
        AuditLog::log('patient_assignment_changed', $doctorId, $patientId, $doctorId, $context);
    }

    /**
     * Log sub-user access
     */
    public static function logSubUserAccess($subUserId, $patientId, $context = [])
    {
        AuditLog::log('sub_user_access_patient', $subUserId, $patientId, null, array_merge($context, [
            'route' => Request::route()->getName() ?? 'unknown'
        ]));
    }

    /**
      * Log admin impersonation — admin IDs are in `admins` table, not `users`, so avoid FK violation
      */
    public static function logAdminImpersonation($adminId, $targetUserId, $context = [])
    {
        try {
            // Verify adminId exists in users to satisfy audit_logs.user_id FK; admins table is separate
            $adminUserExists = $adminId ? \App\Models\User::where('id', $adminId)->exists() : false;
            $effectiveUserId = $adminUserExists ? $adminId : null;
            // Always keep admin identity in metadata for traceability
            $context = array_merge($context, ['admin_id' => $adminId, 'admin_user_exists' => $adminUserExists]);
            AuditLog::log('admin_impersonation_started', $effectiveUserId, $targetUserId, null, $context);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Audit log failed for admin_impersonation_started: '.$e->getMessage(), ['adminId'=>$adminId,'target'=>$targetUserId]);
        }
    }

    /**
      * Log admin impersonation ended — same FK-safe handling
      */
    public static function logAdminImpersonationEnded($adminId, $context = [])
    {
        try {
            $adminUserExists = $adminId ? \App\Models\User::where('id', $adminId)->exists() : false;
            $effectiveUserId = $adminUserExists ? $adminId : null;
            $context = array_merge($context, ['admin_id' => $adminId]);
            AuditLog::log('admin_impersonation_ended', $effectiveUserId, null, null, $context);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Audit log failed for admin_impersonation_ended: '.$e->getMessage(), ['adminId'=>$adminId]);
        }
    }

    /**
     * Log hospital admin impersonation
     */
    public static function logHospitalAdminImpersonation($hospitalAdminId, $targetUserId, $context = [])
    {
        AuditLog::log('hospital_admin_impersonation_started', $hospitalAdminId, $targetUserId, null, $context);
    }

    /**
     * Log hospital admin impersonation ended
     */
    public static function logHospitalAdminImpersonationEnded($hospitalAdminId, $context = [])
    {
        AuditLog::log('hospital_admin_impersonation_ended', $hospitalAdminId, null, null, $context);
    }

    /**
     * Log kiosk session started
     */
    public static function logKioskSessionStarted($kioskId, $sessionId, $context = [])
    {
        AuditLog::log('kiosk_session_started', null, null, null, array_merge($context, [
            'kiosk_id' => $kioskId,
            'session_id' => $sessionId,
            'action_type' => 'kiosk_session'
        ]));
    }

    /**
     * Log kiosk session ended
     */
    public static function logKioskSessionEnded($kioskId, $sessionId, $context = [])
    {
        AuditLog::log('kiosk_session_ended', null, null, null, array_merge($context, [
            'kiosk_id' => $kioskId,
            'session_id' => $sessionId,
            'action_type' => 'kiosk_session'
        ]));
    }

    /**
     * Log kiosk check-in
     */
    public static function logKioskCheckin($appointmentId, $kioskSessionId, $verificationMethod, $context = [])
    {
        AuditLog::log('kiosk_checkin', null, null, null, array_merge($context, [
            'appointment_id' => $appointmentId,
            'kiosk_session_id' => $kioskSessionId,
            'verification_method' => $verificationMethod,
            'action_type' => 'kiosk_checkin'
        ]));
    }

    /**
     * Log kiosk payment
     */
    public static function logKioskPayment($appointmentId, $kioskSessionId, $amount, $status, $context = [])
    {
        AuditLog::log('kiosk_payment', null, null, null, array_merge($context, [
            'appointment_id' => $appointmentId,
            'kiosk_session_id' => $kioskSessionId,
            'amount' => $amount,
            'payment_status' => $status,
            'action_type' => 'kiosk_payment'
        ]));
    }

    /**
     * Log kiosk security event
     */
    public static function logKioskSecurityEvent($eventType, $kioskSessionId = null, $context = [])
    {
        AuditLog::log('kiosk_security_event', null, null, null, array_merge($context, [
            'event_type' => $eventType,
            'kiosk_session_id' => $kioskSessionId,
            'action_type' => 'kiosk_security'
        ]));
    }

    /**
     * Log kiosk rate limit exceeded
     */
    public static function logKioskRateLimitExceeded($kioskSessionId = null, $context = [])
    {
        self::logKioskSecurityEvent('rate_limit_exceeded', $kioskSessionId, $context);
    }

    /**
     * Log kiosk suspicious activity
     */
    public static function logKioskSuspiciousActivity($activityType, $kioskSessionId = null, $context = [])
    {
        AuditLog::log('kiosk_suspicious_activity', null, null, null, array_merge($context, [
            'activity_type' => $activityType,
            'kiosk_session_id' => $kioskSessionId,
            'action_type' => 'kiosk_security'
        ]));
    }

    /**
     * Log clearinghouse transaction
     */
    public static function logClearinghouseTransaction($submissionId, $action, $userId = null, $accountId = null, $context = [])
    {
        $userId = $userId ?? Auth::id();

        AuditLog::log('clearinghouse_transaction', $userId, null, null, array_merge($context, [
            'submission_id' => $submissionId,
            'clearinghouse_account_id' => $accountId,
            'action' => $action,
            'action_type' => 'clearinghouse',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]));
    }

    /**
     * Log EDI data access for HIPAA compliance
     */
    public static function logEdiDataAccess($submissionId, $action, $userId = null, $context = [])
    {
        $userId = $userId ?? Auth::id();

        AuditLog::log('edi_data_access', $userId, null, null, array_merge($context, [
            'submission_id' => $submissionId,
            'action' => $action,
            'action_type' => 'hipaa_compliance',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'access_timestamp' => now(),
        ]));
    }

    /**
     * Log rule application event
     */
    public static function logRuleApplication($ruleId, $claimId, $userId = null, $result = [], $contextType = 'rule_application')
    {
        $userId = $userId ?? Auth::id();

        AuditLog::log('rule_application', $userId, null, null, [
            'rule_id' => $ruleId,
            'claim_id' => $claimId,
            'application_result' => $result,
            'context_type' => $contextType,
            'action_type' => 'payer_rules',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'application_timestamp' => now(),
        ]);
    }

    /**
     * Log compliance audit event
     */
    public static function logComplianceAudit($eventType, $userId = null, $context = [])
    {
        $userId = $userId ?? Auth::id();

        AuditLog::log('compliance_audit', $userId, null, null, array_merge($context, [
            'event_type' => $eventType,
            'action_type' => 'compliance',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'audit_timestamp' => now(),
        ]));
    }

    /**
     * Log appointment status change
     */
    public static function logAppointmentStatusChange($appointmentId, $oldStatus, $newStatus, $userId = null, $context = [])
    {
        $userId = $userId ?? Auth::id();

        AuditLog::log('appointment_status_change', $userId, null, null, array_merge($context, [
            'appointment_id' => $appointmentId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'status_transition' => "{$oldStatus}_to_{$newStatus}",
            'action_type' => 'appointment_management',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'change_timestamp' => now(),
        ]));
    }

    /**
     * Log appointment broadcasting event
     */
    public static function logAppointmentBroadcast($appointmentId, $eventType, $channel, $userId = null, $context = [])
    {
        $userId = $userId ?? Auth::id();

        AuditLog::log('appointment_broadcast', $userId, null, null, array_merge($context, [
            'appointment_id' => $appointmentId,
            'event_type' => $eventType,
            'broadcast_channel' => $channel,
            'action_type' => 'real_time_broadcasting',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'broadcast_timestamp' => now(),
        ]));
    }

    /**
     * Log appointment broadcasting failure
     */
    public static function logAppointmentBroadcastFailure($appointmentId, $eventType, $channel, $error, $userId = null, $context = [])
    {
        $userId = $userId ?? Auth::id();

        AuditLog::log('appointment_broadcast_failure', $userId, null, null, array_merge($context, [
            'appointment_id' => $appointmentId,
            'event_type' => $eventType,
            'broadcast_channel' => $channel,
            'error_message' => $error,
            'action_type' => 'real_time_broadcasting',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'failure_timestamp' => now(),
        ]));
    }

    /**
     * Log appointment real-time subscription
     */
    public static function logAppointmentSubscription($userId, $subscriptionType, $filters = [], $context = [])
    {
        AuditLog::log('appointment_subscription', $userId, null, null, array_merge($context, [
            'subscription_type' => $subscriptionType,
            'filters' => $filters,
            'action_type' => 'real_time_subscription',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'subscription_timestamp' => now(),
        ]));
    }

    /**
     * Log appointment broadcasting rate limit hit
     */
    public static function logAppointmentBroadcastRateLimit($userId, $limitType, $currentAttempts, $maxAttempts, $context = [])
    {
        AuditLog::log('appointment_broadcast_rate_limit', $userId, null, null, array_merge($context, [
            'limit_type' => $limitType,
            'current_attempts' => $currentAttempts,
            'max_attempts' => $maxAttempts,
            'action_type' => 'rate_limiting',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'rate_limit_timestamp' => now(),
        ]));
    }

    /**
     * Log appointment broadcasting security event
     */
    public static function logAppointmentBroadcastSecurity($userId, $eventType, $channel = null, $context = [])
    {
        AuditLog::log('appointment_broadcast_security', $userId, null, null, array_merge($context, [
            'security_event_type' => $eventType,
            'channel' => $channel,
            'action_type' => 'broadcasting_security',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'security_timestamp' => now(),
        ]));
    }
}
