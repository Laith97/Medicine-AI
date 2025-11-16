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
     * Log admin impersonation
     */
    public static function logAdminImpersonation($adminId, $targetUserId, $context = [])
    {
        AuditLog::log('admin_impersonation_started', $adminId, $targetUserId, null, $context);
    }

    /**
     * Log admin impersonation ended
     */
    public static function logAdminImpersonationEnded($adminId, $context = [])
    {
        AuditLog::log('admin_impersonation_ended', $adminId, null, null, $context);
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
}
