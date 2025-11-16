<?php

namespace App\Services\DataWarehouse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class KPICalculationService
{
    protected $cacheTtl = 3600; // 1 hour cache

    /**
     * Calculate comprehensive daily KPIs
     */
    public function calculateDailyKPIs($date = null, $hospitalKey = 1)
    {
        $date = $date ?: Carbon::yesterday();
        $dateKey = (int)$date->format('Ymd');

        $cacheKey = "daily_kpis_{$hospitalKey}_{$dateKey}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Calculate comprehensive daily metrics
        $metrics = $this->getComprehensiveDailyMetrics($date, $hospitalKey);

        DB::table('agg_daily_kpis')->updateOrInsert(
            ['date_key' => $dateKey, 'hospital_key' => $hospitalKey],
            array_merge($metrics, ['created_at' => now()])
        );

        Cache::put($cacheKey, $metrics, $this->cacheTtl);
        return $metrics;
    }

    public function calculateMonthlyKPIs($year = null, $month = null)
    {
        $year = $year ?: Carbon::now()->year;
        $month = $month ?: Carbon::now()->month;

        $metrics = $this->getMonthlyMetrics($year, $month);

        DB::table('agg_monthly_kpis')->updateOrInsert(
            [
                'year' => $year,
                'month' => $month,
                'hospital_key' => 1,
            ],
            [
                'total_appointments' => $metrics['total_appointments'],
                'completed_appointments' => $metrics['completed_appointments'],
                'revenue' => $metrics['total_revenue'],
                'patient_satisfaction' => $metrics['avg_satisfaction'],
                'provider_utilization' => $metrics['provider_utilization'],
                'average_wait_time' => $metrics['avg_wait_time'],
                'churn_rate' => $metrics['churn_rate'],
                'growth_rate' => $metrics['growth_rate'],
                'active_users' => $metrics['active_users'],
                'new_users' => $metrics['new_users'],
                'created_at' => now(),
            ]
        );
    }

    /**
     * Get comprehensive daily metrics with advanced calculations
     */
    private function getComprehensiveDailyMetrics(Carbon $date, $hospitalKey = 1)
    {
        $dateKey = (int)$date->format('Ymd');

        // Revenue KPIs
        $revenueKPIs = $this->calculateRevenueKPIs($dateKey, $hospitalKey);

        // Patient Satisfaction KPIs
        $satisfactionKPIs = $this->calculatePatientSatisfactionKPIs($dateKey, $hospitalKey);

        // Operational Efficiency KPIs
        $efficiencyKPIs = $this->calculateOperationalEfficiencyKPIs($dateKey, $hospitalKey);

        // Clinical Outcomes KPIs
        $clinicalKPIs = $this->calculateClinicalOutcomesKPIs($dateKey, $hospitalKey);

        // User Activity KPIs
        $userKPIs = $this->calculateUserActivityKPIs($dateKey, $hospitalKey);

        return array_merge(
            $revenueKPIs,
            $satisfactionKPIs,
            $efficiencyKPIs,
            $clinicalKPIs,
            $userKPIs
        );
    }

    /**
     * Calculate revenue-related KPIs
     */
    private function calculateRevenueKPIs($dateKey, $hospitalKey)
    {
        $revenue = DB::table('fact_financial_transactions')
            ->where('date_key', $dateKey)
            ->where('hospital_key', $hospitalKey)
            ->selectRaw('
                SUM(CASE WHEN transaction_type = "Payment" THEN amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN transaction_type = "Payment" AND payment_method = "Insurance" THEN amount ELSE 0 END) as insurance_revenue,
                SUM(CASE WHEN transaction_type = "Payment" AND payment_method = "Credit Card" THEN amount ELSE 0 END) as patient_revenue,
                SUM(CASE WHEN transaction_type = "Refund" THEN amount ELSE 0 END) as refunds,
                AVG(CASE WHEN transaction_type = "Payment" THEN amount END) as avg_transaction_value,
                COUNT(DISTINCT patient_key) as paying_patients
            ')
            ->first();

        // Calculate ARPU (Average Revenue Per User)
        $arpu = $revenue->paying_patients > 0
            ? $revenue->total_revenue / $revenue->paying_patients
            : 0;

        return [
            'total_revenue' => $revenue->total_revenue ?? 0,
            'insurance_revenue' => $revenue->insurance_revenue ?? 0,
            'patient_revenue' => $revenue->patient_revenue ?? 0,
            'refunds' => $revenue->refunds ?? 0,
            'net_revenue' => ($revenue->total_revenue ?? 0) - ($revenue->refunds ?? 0),
            'average_transaction_value' => $revenue->avg_transaction_value ?? 0,
            'average_revenue_per_user' => $arpu,
            'paying_patients' => $revenue->paying_patients ?? 0,
        ];
    }

    /**
     * Calculate patient satisfaction KPIs
     */
    private function calculatePatientSatisfactionKPIs($dateKey, $hospitalKey)
    {
        $satisfaction = DB::table('fact_appointments')
            ->where('date_key', $dateKey)
            ->where('hospital_key', $hospitalKey)
            ->selectRaw('
                AVG(patient_satisfaction_score) as avg_satisfaction,
                COUNT(CASE WHEN patient_satisfaction_score >= 4 THEN 1 END) as promoters,
                COUNT(CASE WHEN patient_satisfaction_score = 3 THEN 1 END) as passives,
                COUNT(CASE WHEN patient_satisfaction_score <= 2 THEN 1 END) as detractors,
                COUNT(*) as total_responses
            ')
            ->first();

        // Calculate Net Promoter Score
        $total = $satisfaction->total_responses ?? 1;
        $nps = $total > 0
            ? (($satisfaction->promoters ?? 0) - ($satisfaction->detractors ?? 0)) / $total * 100
            : 0;

        return [
            'patient_satisfaction_score' => $satisfaction->avg_satisfaction ?? 0,
            'net_promoter_score' => round($nps, 2),
            'satisfaction_promoters' => $satisfaction->promoters ?? 0,
            'satisfaction_passives' => $satisfaction->passives ?? 0,
            'satisfaction_detractors' => $satisfaction->detractors ?? 0,
            'satisfaction_response_rate' => $total > 0 ? ($total / $this->getTotalAppointments($dateKey, $hospitalKey)) * 100 : 0,
        ];
    }

    /**
     * Calculate operational efficiency KPIs
     */
    private function calculateOperationalEfficiencyKPIs($dateKey, $hospitalKey)
    {
        $operations = DB::table('fact_appointments')
            ->where('date_key', $dateKey)
            ->where('hospital_key', $hospitalKey)
            ->selectRaw('
                COUNT(*) as total_appointments,
                SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) as completed_appointments,
                SUM(CASE WHEN status = "Cancelled" THEN 1 ELSE 0 END) as cancelled_appointments,
                SUM(CASE WHEN status = "No-show" THEN 1 ELSE 0 END) as no_show_appointments,
                AVG(wait_time_minutes) as avg_wait_time,
                AVG(consultation_duration_minutes) as avg_consultation_duration,
                AVG(CASE WHEN status = "Completed" THEN consultation_duration_minutes END) as avg_completed_duration,
                COUNT(DISTINCT doctor_key) as active_doctors,
                COUNT(DISTINCT patient_key) as unique_patients
            ')
            ->first();

        // Calculate derived metrics
        $total = $operations->total_appointments ?? 1;
        $showUpRate = $total > 0 ? ($operations->completed_appointments ?? 0) / $total * 100 : 0;
        $noShowRate = $total > 0 ? ($operations->no_show_appointments ?? 0) / $total * 100 : 0;
        $cancellationRate = $total > 0 ? ($operations->cancelled_appointments ?? 0) / $total * 100 : 0;

        // Provider utilization (assuming 8-hour workday = 480 minutes)
        $totalConsultationTime = ($operations->avg_completed_duration ?? 0) * ($operations->completed_appointments ?? 0);
        $providerUtilization = $totalConsultationTime > 0 ? min(($totalConsultationTime / (480 * ($operations->active_doctors ?? 1))) * 100, 100) : 0;

        return [
            'total_appointments' => $operations->total_appointments ?? 0,
            'completed_appointments' => $operations->completed_appointments ?? 0,
            'cancelled_appointments' => $operations->cancelled_appointments ?? 0,
            'no_show_appointments' => $operations->no_show_appointments ?? 0,
            'appointment_show_up_rate' => round($showUpRate, 2),
            'appointment_no_show_rate' => round($noShowRate, 2),
            'appointment_cancellation_rate' => round($cancellationRate, 2),
            'average_wait_time_minutes' => $operations->avg_wait_time ?? 0,
            'average_consultation_duration' => $operations->avg_consultation_duration ?? 0,
            'provider_utilization_rate' => round($providerUtilization, 2),
            'active_providers' => $operations->active_doctors ?? 0,
            'total_patients_seen' => $operations->unique_patients ?? 0,
        ];
    }

    /**
     * Calculate clinical outcomes KPIs
     */
    private function calculateClinicalOutcomesKPIs($dateKey, $hospitalKey)
    {
        $outcomes = DB::table('fact_clinical_outcomes')
            ->where('date_key', $dateKey)
            ->where('hospital_key', $hospitalKey)
            ->selectRaw('
                COUNT(*) as total_outcomes,
                AVG(outcome_score) as avg_outcome_score,
                SUM(CASE WHEN outcome_category = "Successful" THEN 1 ELSE 0 END) as successful_outcomes,
                SUM(CASE WHEN outcome_category = "Complication" THEN 1 ELSE 0 END) as complications,
                SUM(CASE WHEN readmission_within_30_days = 1 THEN 1 ELSE 0 END) as readmissions_30_days,
                AVG(length_of_stay_days) as avg_length_of_stay,
                AVG(treatment_cost) as avg_treatment_cost
            ')
            ->first();

        $total = $outcomes->total_outcomes ?? 1;
        $successRate = $total > 0 ? ($outcomes->successful_outcomes ?? 0) / $total * 100 : 0;
        $complicationRate = $total > 0 ? ($outcomes->complications ?? 0) / $total * 100 : 0;
        $readmissionRate = $total > 0 ? ($outcomes->readmissions_30_days ?? 0) / $total * 100 : 0;

        return [
            'total_clinical_outcomes' => $outcomes->total_outcomes ?? 0,
            'average_outcome_score' => $outcomes->avg_outcome_score ?? 0,
            'treatment_success_rate' => round($successRate, 2),
            'complication_rate' => round($complicationRate, 2),
            'readmission_rate_30_days' => round($readmissionRate, 2),
            'average_length_of_stay_days' => $outcomes->avg_length_of_stay ?? 0,
            'average_treatment_cost' => $outcomes->avg_treatment_cost ?? 0,
        ];
    }

    /**
     * Calculate user activity KPIs
     */
    private function calculateUserActivityKPIs($dateKey, $hospitalKey)
    {
        $activity = DB::table('fact_user_activity')
            ->where('date_key', $dateKey)
            ->selectRaw('
                COUNT(DISTINCT user_key) as active_users,
                SUM(CASE WHEN activity_type = "Login" THEN 1 ELSE 0 END) as login_count,
                SUM(CASE WHEN activity_type = "Page View" THEN 1 ELSE 0 END) as page_views,
                SUM(CASE WHEN activity_type = "Registration" THEN 1 ELSE 0 END) as new_registrations,
                AVG(duration_seconds) as avg_session_duration,
                COUNT(DISTINCT CASE WHEN device_type = "Mobile" THEN user_key END) as mobile_users,
                COUNT(DISTINCT CASE WHEN device_type = "Desktop" THEN user_key END) as desktop_users
            ')
            ->first();

        return [
            'active_users' => $activity->active_users ?? 0,
            'total_logins' => $activity->login_count ?? 0,
            'total_page_views' => $activity->page_views ?? 0,
            'new_user_registrations' => $activity->new_registrations ?? 0,
            'average_session_duration_seconds' => $activity->avg_session_duration ?? 0,
            'mobile_users' => $activity->mobile_users ?? 0,
            'desktop_users' => $activity->desktop_users ?? 0,
        ];
    }

    /**
     * Helper method to get total appointments for calculations
     */
    private function getTotalAppointments($dateKey, $hospitalKey)
    {
        return DB::table('fact_appointments')
            ->where('date_key', $dateKey)
            ->where('hospital_key', $hospitalKey)
            ->count();
    }

    private function getMonthlyMetrics($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $dateKeys = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dateKeys[] = (int)$date->format('Ymd');
        }

        // Similar calculations but aggregated for the month
        $appointments = DB::table('appointments_fact')
            ->whereIn('date_key', $dateKeys)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) as completed,
                AVG(wait_time_minutes) as avg_wait,
                AVG(patient_satisfaction_score) as avg_satisfaction
            ')
            ->first();

        $revenue = DB::table('revenue_fact')
            ->whereIn('date_key', $dateKeys)
            ->where('status', 'Completed')
            ->selectRaw('SUM(net_amount) as total_revenue')
            ->first();

        return [
            'total_appointments' => $appointments->total ?? 0,
            'completed_appointments' => $appointments->completed ?? 0,
            'total_revenue' => $revenue->total_revenue ?? 0,
            'avg_satisfaction' => $appointments->avg_satisfaction ?? 0,
            'provider_utilization' => 0.85, // Placeholder
            'avg_wait_time' => $appointments->avg_wait ?? 0,
            'churn_rate' => 0.05, // Placeholder
            'growth_rate' => 0.12, // Placeholder
            'active_users' => 0, // Would need calculation
            'new_users' => 0,
        ];
    }
}
