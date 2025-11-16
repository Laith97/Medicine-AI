<?php

namespace App\Services\DataWarehouse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataQualityService
{
    public function validateAndCleanData()
    {
        $this->validateDimensions();
        $this->validateFacts();
        $this->cleanData();
    }

    private function validateDimensions()
    {
        $this->validateDateDimension();
        $this->validateDoctorDimension();
        $this->validatePatientDimension();
    }

    private function validateDateDimension()
    {
        // Check for missing dates
        $missingDates = DB::select("
            SELECT DISTINCT DATE(scheduled_date) as date
            FROM appointments_fact af
            LEFT JOIN dim_date dd ON dd.date_key = DATE_FORMAT(af.scheduled_date, '%Y%m%d')
            WHERE dd.date_key IS NULL
        ");

        if (!empty($missingDates)) {
            Log::warning('Missing dates in dim_date', ['dates' => $missingDates]);
            // Could trigger regeneration of date dimension
        }
    }

    private function validateDoctorDimension()
    {
        // Check for orphaned doctor keys
        $orphaned = DB::table('appointments_fact')
            ->leftJoin('doctor_dim', 'appointments_fact.doctor_key', '=', 'doctor_dim.doctor_key')
            ->whereNull('doctor_dim.doctor_key')
            ->count();

        if ($orphaned > 0) {
            Log::warning("Found {$orphaned} orphaned doctor keys in appointments_fact");
        }
    }

    private function validatePatientDimension()
    {
        // Check for orphaned patient keys
        $orphaned = DB::table('appointments_fact')
            ->leftJoin('patient_dim', 'appointments_fact.patient_key', '=', 'patient_dim.patient_key')
            ->whereNull('patient_dim.patient_key')
            ->count();

        if ($orphaned > 0) {
            Log::warning("Found {$orphaned} orphaned patient keys in appointments_fact");
        }
    }

    private function validateFacts()
    {
        $this->validateAppointmentsFact();
        $this->validateRevenueFact();
        $this->validatePatientSatisfactionFact();
    }

    private function validateAppointmentsFact()
    {
        // Check for invalid status values
        $invalidStatuses = DB::table('appointments_fact')
            ->whereNotIn('status', ['Scheduled', 'Completed', 'Cancelled', 'No-show'])
            ->count();

        if ($invalidStatuses > 0) {
            Log::warning("Found {$invalidStatuses} invalid status values in appointments_fact");
        }

        // Check for negative costs
        $negativeCosts = DB::table('appointments_fact')
            ->where('total_cost', '<', 0)
            ->orWhere('insurance_covered_amount', '<', 0)
            ->orWhere('patient_paid_amount', '<', 0)
            ->count();

        if ($negativeCosts > 0) {
            Log::warning("Found {$negativeCosts} negative cost values in appointments_fact");
        }
    }

    private function validateRevenueFact()
    {
        // Check for invalid transaction types
        $invalidTypes = DB::table('revenue_fact')
            ->whereNotIn('transaction_type', ['Payment', 'Refund', 'Adjustment'])
            ->count();

        if ($invalidTypes > 0) {
            Log::warning("Found {$invalidTypes} invalid transaction types in revenue_fact");
        }

        // Check for negative amounts
        $negativeAmounts = DB::table('revenue_fact')
            ->where('amount', '<', 0)
            ->count();

        if ($negativeAmounts > 0) {
            Log::warning("Found {$negativeAmounts} negative amounts in revenue_fact");
        }
    }

    private function validatePatientSatisfactionFact()
    {
        // Check for invalid satisfaction scores
        $invalidScores = DB::table('patient_satisfaction_fact')
            ->where('patient_satisfaction', '<', 0)
            ->orWhere('patient_satisfaction', '>', 5)
            ->count();

        if ($invalidScores > 0) {
            Log::warning("Found {$invalidScores} invalid satisfaction scores in patient_satisfaction_fact");
        }
    }

    private function cleanData()
    {
        $this->removeDuplicates();
        $this->fixNullValues();
        $this->standardizeFormats();
    }

    private function removeDuplicates()
    {
        // Remove duplicate appointments
        DB::statement("
            DELETE t1 FROM appointments_fact t1
            INNER JOIN appointments_fact t2
            WHERE t1.appointment_key < t2.appointment_key
            AND t1.appointment_id = t2.appointment_id
        ");

        // Remove duplicate revenue transactions
        DB::statement("
            DELETE t1 FROM revenue_fact t1
            INNER JOIN revenue_fact t2
            WHERE t1.transaction_key < t2.transaction_key
            AND t1.transaction_id = t2.transaction_id
        ");
    }

    private function fixNullValues()
    {
        // Set default values for nullable fields
        DB::table('appointments_fact')
            ->whereNull('follow_up_required')
            ->update(['follow_up_required' => false]);

        DB::table('appointments_fact')
            ->whereNull('follow_up_scheduled')
            ->update(['follow_up_scheduled' => false]);

        DB::table('patient_satisfaction_fact')
            ->whereNull('follow_up_required')
            ->update(['follow_up_required' => false]);

        DB::table('patient_satisfaction_fact')
            ->whereNull('follow_up_completed')
            ->update(['follow_up_completed' => false]);
    }

    private function standardizeFormats()
    {
        // Standardize phone numbers, emails, etc. if needed
        // For now, this is a placeholder
    }

    public function generateQualityReport()
    {
        $report = [
            'dimensions' => [
                'date_completeness' => $this->checkDateCompleteness(),
                'doctor_completeness' => $this->checkDoctorCompleteness(),
                'patient_completeness' => $this->checkPatientCompleteness(),
            ],
            'facts' => [
                'appointments_validity' => $this->checkAppointmentsValidity(),
                'revenue_validity' => $this->checkRevenueValidity(),
                'satisfaction_validity' => $this->checkSatisfactionValidity(),
            ],
            'overall_quality_score' => 0, // Calculate based on above
        ];

        return $report;
    }

    private function checkDateCompleteness()
    {
        $totalAppointments = DB::table('appointments_fact')->count();
        $validDates = DB::table('appointments_fact')
            ->join('dim_date', 'appointments_fact.date_key', '=', 'dim_date.date_key')
            ->count();

        return $totalAppointments > 0 ? ($validDates / $totalAppointments) * 100 : 0;
    }

    private function checkDoctorCompleteness()
    {
        $totalAppointments = DB::table('appointments_fact')->count();
        $validDoctors = DB::table('appointments_fact')
            ->join('doctor_dim', 'appointments_fact.doctor_key', '=', 'doctor_dim.doctor_key')
            ->count();

        return $totalAppointments > 0 ? ($validDoctors / $totalAppointments) * 100 : 0;
    }

    private function checkPatientCompleteness()
    {
        $totalAppointments = DB::table('appointments_fact')->count();
        $validPatients = DB::table('appointments_fact')
            ->join('patient_dim', 'appointments_fact.patient_key', '=', 'patient_dim.patient_key')
            ->count();

        return $totalAppointments > 0 ? ($validPatients / $totalAppointments) * 100 : 0;
    }

    private function checkAppointmentsValidity()
    {
        $total = DB::table('appointments_fact')->count();
        $valid = DB::table('appointments_fact')
            ->whereIn('status', ['Scheduled', 'Completed', 'Cancelled', 'No-show'])
            ->where('total_cost', '>=', 0)
            ->count();

        return $total > 0 ? ($valid / $total) * 100 : 0;
    }

    private function checkRevenueValidity()
    {
        $total = DB::table('revenue_fact')->count();
        $valid = DB::table('revenue_fact')
            ->whereIn('transaction_type', ['Payment', 'Refund', 'Adjustment'])
            ->where('amount', '>=', 0)
            ->count();

        return $total > 0 ? ($valid / $total) * 100 : 0;
    }

    private function checkSatisfactionValidity()
    {
        $total = DB::table('patient_satisfaction_fact')->count();
        $valid = DB::table('patient_satisfaction_fact')
            ->whereBetween('patient_satisfaction', [0, 5])
            ->count();

        return $total > 0 ? ($valid / $total) * 100 : 0;
    }
}
