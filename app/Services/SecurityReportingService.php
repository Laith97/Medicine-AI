<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;

class SecurityReportingService
{
    /**
     * Detect doctors accessing patients not assigned to them
     */
    public static function detectUnauthorizedPatientAccess($timeRange = '24_hours')
    {
        $startDate = self::getStartDate($timeRange);

        return AuditLog::where('action', 'doctor_access_patient')
            ->whereHas('user', function($query) {
                $query->where('role', 'doctor');
            })
            ->whereHas('patient', function($query) {
                $query->where('role', 'patient');
            })
            ->with(['user', 'patient'])
            ->get()
            ->filter(function($log) {
                $doctor = $log->user;
                $patient = $log->patient;

                // Check if patient is assigned to this doctor
                return $patient->primary_doctor_id !== $doctor->id;
            });
    }

    /**
     * Detect frequent impersonation activities
     */
    public static function detectFrequentImpersonation($timeRange = '24_hours')
    {
        $startDate = self::getStartDate($timeRange);

        // Group impersonation activities by user and count occurrences
        return AuditLog::whereIn('action', ['admin_impersonation_started', 'hospital_admin_impersonation_started'])
            ->where('created_at', '>=', $startDate)
            ->select('user_id', 'action')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('user_id', 'action')
            ->having('count', '>', 5) // More than 5 impersonations in the time range
            ->with('user')
            ->get();
    }

    /**
     * Detect unusual patient assignment changes
     */
    public static function detectUnusualPatientAssignments($timeRange = '24_hours')
    {
        $startDate = self::getStartDate($timeRange);

        // Find patients who have been reassigned multiple times
        return AuditLog::where('action', 'patient_assignment_changed')
            ->where('created_at', '>=', $startDate)
            ->select('patient_id')
            ->selectRaw('COUNT(*) as reassignment_count')
            ->groupBy('patient_id')
            ->having('reassignment_count', '>', 2) // More than 2 reassignments
            ->with('patient')
            ->get();
    }

    /**
     * Detect sub-users accessing patient data without proper permissions
     */
    public static function detectUnauthorizedSubUserAccess($timeRange = '24_hours')
    {
        $startDate = self::getStartDate($timeRange);

        return AuditLog::where('action', 'sub_user_access_patient')
            ->where('created_at', '>=', $startDate)
            ->with(['user', 'patient'])
            ->get()
            ->filter(function($log) {
                $subUser = $log->user;
                $patient = $log->patient;

                // Check if sub-user has proper permissions for this patient
                // (patient should be assigned to the sub-user's parent doctor)
                $parentDoctor = $subUser->parentUser;
                if (!$parentDoctor) {
                    return true; // No parent doctor - unauthorized access
                }

                return $patient->primary_doctor_id !== $parentDoctor->id;
            });
    }

    /**
     * Detect multiple doctors accessing the same patient in short time
     */
    public static function detectMultipleDoctorAccess($timeRange = '1_hour')
    {
        $startDate = self::getStartDate($timeRange);

        // Find patients accessed by multiple doctors within a short time period
        return AuditLog::where('action', 'doctor_access_patient')
            ->where('created_at', '>=', $startDate)
            ->select('patient_id')
            ->selectRaw('COUNT(DISTINCT user_id) as doctor_count')
            ->selectRaw('MIN(created_at) as first_access')
            ->selectRaw('MAX(created_at) as last_access')
            ->groupBy('patient_id')
            ->having('doctor_count', '>', 1) // Accessed by more than 1 doctor
            ->with('patient')
            ->get();
    }

    /**
     * Get start date based on time range
     */
    private static function getStartDate($timeRange)
    {
        switch ($timeRange) {
            case '1_hour':
                return Carbon::now()->subHour();
            case '24_hours':
                return Carbon::now()->subDay();
            case '7_days':
                return Carbon::now()->subWeek();
            case '30_days':
                return Carbon::now()->subMonth();
            default:
                return Carbon::now()->subDay();
        }
    }
}
