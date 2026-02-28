<?php

namespace App\Services\DataWarehouse;

use Illuminate\Support\Facades\DB;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Diagnosis;
use App\Models\StripeInvoice;
use Carbon\Carbon;

class ETLService
{
    public function runFullLoad()
    {
        $this->loadDimensions();
        $this->loadFacts();
        $this->validateAndCleanData();
        $this->calculateKPIs();
    }

    public function runIncrementalLoad()
    {
        // Load only new/changed data since last run
        $this->loadDimensionsIncremental();
        $this->loadFactsIncremental();
        $this->calculateKPIs();
    }

    private function loadDimensions()
    {
        $this->loadDateDimension();
        $this->loadTimeDimension();
        $this->loadDoctorDimension();
        $this->loadPatientDimension();
        $this->loadServiceDimension();
    }

    private function loadDimensionsIncremental()
    {
        // Similar but check for updates
        $this->loadDoctorDimensionIncremental();
        $this->loadPatientDimensionIncremental();
        $this->loadServiceDimensionIncremental();
    }

    private function loadDateDimension()
    {
        // Generate date dimension for next few years
        $startDate = Carbon::create(2024, 1, 1);
        $endDate = Carbon::create(2027, 12, 31);

        $dates = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dates[] = [
                'date_key' => (int)$date->format('Ymd'),
                'date' => $date->toDateString(),
                'year' => $date->year,
                'quarter' => ceil($date->month / 3),
                'month' => $date->month,
                'month_name' => $date->format('F'),
                'week_of_year' => $date->weekOfYear,
                'day_of_week' => $date->dayOfWeek + 1, // 1 = Monday
                'day_name' => $date->format('l'),
                'is_weekend' => $date->isWeekend(),
                'is_holiday' => false, // Could be enhanced with holiday logic
                'fiscal_year' => $date->year, // Simplified
                'fiscal_quarter' => ceil($date->month / 3),
                'created_at' => now(),
            ];
        }

        DB::table('dim_date')->insertOrIgnore($dates);
    }

    private function loadTimeDimension()
    {
        $times = [];
        for ($hour = 0; $hour < 24; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 15) { // Every 15 minutes
                $time = Carbon::createFromTime($hour, $minute);
                $timeKey = $hour * 100 + $minute;

                $times[] = [
                    'time_key' => $timeKey,
                    'time' => $time->format('H:i:s'),
                    'hour' => $hour,
                    'minute' => $minute,
                    'hour_of_day' => $hour,
                    'time_of_day' => $this->getTimeOfDay($hour),
                    'created_at' => now(),
                ];
            }
        }

        DB::table('time_dim')->insertOrIgnore($times);
    }

    private function getTimeOfDay($hour)
    {
        if ($hour >= 5 && $hour < 12) return 'Morning';
        if ($hour >= 12 && $hour < 17) return 'Afternoon';
        if ($hour >= 17 && $hour < 22) return 'Evening';
        return 'Night';
    }

    private function loadDoctorDimension()
    {
        $doctors = Doctor::all();

        foreach ($doctors as $doctor) {
            DB::table('doctor_dim')->updateOrInsert(
                ['doctor_id' => $doctor->id],
                [
                    'doctor_key' => $doctor->id, // Simplified, should be surrogate key
                    'name' => $doctor->name,
                    'specialty' => $doctor->specialty,
                    'license_number' => $doctor->license_number,
                    'years_experience' => $doctor->years_experience,
                    'hospital_id' => $doctor->hospital_id,
                    'department_id' => $doctor->department_id,
                    'consultation_fee' => $doctor->consultation_fee,
                    'rating' => $doctor->rating,
                    'total_reviews' => $doctor->reviews()->count(),
                    'availability_score' => 0.8, // Placeholder
                    'is_active' => $doctor->is_active ?? true,
                    'effective_start_date' => $doctor->created_at->toDateString(),
                    'effective_end_date' => null,
                    'version' => 1,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function loadPatientDimension()
    {
        $patients = Patient::all();

        foreach ($patients as $patient) {
            DB::table('patient_dim')->updateOrInsert(
                ['patient_id' => $patient->id],
                [
                    'patient_key' => $patient->id,
                    'patient_key_external' => $patient->patient_key,
                    'date_of_birth' => $patient->date_of_birth,
                    'gender' => $patient->gender,
                    'ethnicity' => $patient->ethnicity,
                    'primary_language' => $patient->primary_language,
                    'insurance_provider' => $patient->insurance_provider,
                    'insurance_plan_type' => $patient->insurance_plan_type,
                    'risk_score' => $patient->risk_score,
                    'chronic_conditions' => $patient->chronic_conditions,
                    'allergies' => $patient->allergies,
                    'primary_doctor_id' => $patient->primary_doctor_id,
                    'hospital_id' => $patient->hospital_id,
                    'first_visit_date' => $patient->first_visit_date,
                    'last_visit_date' => $patient->last_visit_date,
                    'total_visits' => $patient->total_visits,
                    'is_active' => $patient->is_active ?? true,
                    'effective_start_date' => $patient->created_at->toDateString(),
                    'effective_end_date' => null,
                    'version' => 1,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function loadServiceDimension()
    {
        // This would need a services table, for now placeholder
        // Assuming services are derived from appointments or diagnoses
    }

    private function loadFacts()
    {
        $this->loadAppointmentsFact();
        $this->loadRevenueFact();
        $this->loadPatientSatisfactionFact();
    }

    private function loadAppointmentsFact()
    {
        $appointments = Appointment::with(['doctor', 'patient'])->get();

        foreach ($appointments as $appointment) {
            $scheduledDate = Carbon::parse($appointment->scheduled_date);
            $dateKey = (int)$scheduledDate->format('Ymd');
            $timeKey = (int)Carbon::parse($appointment->scheduled_time)->format('Hi');

            DB::table('appointments_fact')->updateOrInsert(
                ['appointment_id' => $appointment->id],
                [
                    'date_key' => $dateKey,
                    'time_key' => $timeKey,
                    'patient_key' => $appointment->patient_id,
                    'doctor_key' => $appointment->doctor_id,
                    'hospital_key' => $appointment->hospital_id,
                    'service_key' => null, // To be determined
                    'scheduled_date' => $appointment->scheduled_date,
                    'scheduled_time' => $appointment->scheduled_time,
                    'actual_start_time' => $appointment->actual_start_time,
                    'actual_end_time' => $appointment->actual_end_time,
                    'status' => $appointment->status,
                    'appointment_type' => $appointment->appointment_type,
                    'booking_method' => $appointment->booking_method,
                    'wait_time_minutes' => $appointment->wait_time_minutes,
                    'consultation_duration_minutes' => $appointment->consultation_duration_minutes,
                    'follow_up_required' => $appointment->follow_up_required,
                    'follow_up_scheduled' => $appointment->follow_up_scheduled,
                    'patient_satisfaction_score' => $appointment->patient_satisfaction_score,
                    'doctor_notes' => $appointment->doctor_notes,
                    'total_cost' => $appointment->total_cost,
                    'insurance_covered_amount' => $appointment->insurance_covered_amount,
                    'patient_paid_amount' => $appointment->patient_paid_amount,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function loadRevenueFact()
    {
        $invoices = StripeInvoice::all();

        foreach ($invoices as $invoice) {
            $dateKey = (int)Carbon::parse($invoice->created_at)->format('Ymd');

            DB::table('revenue_fact')->updateOrInsert(
                ['transaction_id' => $invoice->id],
                [
                    'date_key' => $dateKey,
                    'patient_key' => $invoice->patient_id,
                    'doctor_key' => $invoice->doctor_id,
                    'transaction_date' => $invoice->created_at->toDateString(),
                    'transaction_type' => 'Payment',
                    'amount' => $invoice->amount,
                    'net_amount' => $invoice->amount - ($invoice->tax_amount ?? 0) - ($invoice->discount_amount ?? 0),
                    'status' => $invoice->status,
                    'processed_at' => $invoice->processed_at,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function loadPatientSatisfactionFact()
    {
        $diagnoses = Diagnosis::with(['patient', 'doctor'])->get();

        foreach ($diagnoses as $diagnosis) {
            $dateKey = (int)Carbon::parse($diagnosis->created_at)->format('Ymd');

            DB::table('patient_satisfaction_fact')->updateOrInsert(
                ['diagnosis_id' => $diagnosis->id],
                [
                    'date_key' => $dateKey,
                    'patient_key' => $diagnosis->patient_id,
                    'doctor_key' => $diagnosis->doctor_id,
                    'outcome_date' => $diagnosis->created_at->toDateString(),
                    'diagnosis_code' => $diagnosis->diagnosis_code,
                    'outcome_category' => 'Completed', // Placeholder
                    'patient_satisfaction' => $diagnosis->patient_satisfaction,
                    'treatment_cost' => $diagnosis->treatment_cost,
                    'notes' => $diagnosis->notes,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function validateAndCleanData()
    {
        $qualityService = app(DataQualityService::class);
        $qualityService->validateAndCleanData();
    }

    private function calculateKPIs()
    {
        $kpiService = app(KPICalculationService::class);
        $kpiService->calculateDailyKPIs();
        $kpiService->calculateMonthlyKPIs();
    }

    // Incremental methods would check timestamps and only load changed records
    private function loadDoctorDimensionIncremental() { /* ... */ }
    private function loadPatientDimensionIncremental() { /* ... */ }
    private function loadServiceDimensionIncremental() { /* ... */ }
    private function loadFactsIncremental() { /* ... */ }
}
