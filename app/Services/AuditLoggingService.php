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
}
