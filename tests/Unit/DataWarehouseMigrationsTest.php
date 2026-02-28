<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataWarehouseMigrationsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function dim_date_table_is_created_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('dim_date'));

        $this->assertTrue(Schema::hasColumns('dim_date', [
            'date_key',
            'date',
            'year',
            'quarter',
            'month',
            'month_name',
            'week_of_year',
            'day_of_week',
            'day_name',
            'is_weekend',
            'is_holiday',
            'fiscal_year',
            'fiscal_quarter',
            'created_at'
        ]));
    }

    /** @test */
    public function time_dim_table_is_created_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('time_dim'));

        $this->assertTrue(Schema::hasColumns('time_dim', [
            'time_key',
            'time',
            'hour',
            'minute',
            'hour_of_day',
            'time_of_day',
            'created_at'
        ]));
    }

    /** @test */
    public function doctor_dim_table_is_created_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('doctor_dim'));

        $this->assertTrue(Schema::hasColumns('doctor_dim', [
            'doctor_key',
            'doctor_id',
            'name',
            'specialty',
            'license_number',
            'years_experience',
            'hospital_id',
            'department_id',
            'consultation_fee',
            'rating',
            'total_reviews',
            'availability_score',
            'is_active',
            'effective_start_date',
            'effective_end_date',
            'version',
            'created_at'
        ]));
    }

    /** @test */
    public function patient_dim_table_is_created_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('patient_dim'));

        $this->assertTrue(Schema::hasColumns('patient_dim', [
            'patient_key',
            'patient_id',
            'patient_key_external',
            'date_of_birth',
            'gender',
            'ethnicity',
            'primary_language',
            'insurance_provider',
            'insurance_plan_type',
            'risk_score',
            'chronic_conditions',
            'allergies',
            'primary_doctor_id',
            'hospital_id',
            'first_visit_date',
            'last_visit_date',
            'total_visits',
            'is_active',
            'effective_start_date',
            'effective_end_date',
            'version',
            'created_at'
        ]));
    }

    /** @test */
    public function service_dim_table_is_created_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('service_dim'));

        $this->assertTrue(Schema::hasColumns('service_dim', [
            'service_key',
            'service_id',
            'service_name',
            'service_category',
            'cpt_code',
            'icd_code',
            'average_duration_minutes',
            'average_cost',
            'reimbursement_rate',
            'is_active',
            'effective_start_date',
            'effective_end_date',
            'version',
            'created_at'
        ]));
    }

    /** @test */
    public function appointments_fact_table_is_created_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('appointments_fact'));

        $this->assertTrue(Schema::hasColumns('appointments_fact', [
            'appointment_key',
            'date_key',
            'time_key',
            'patient_key',
            'doctor_key',
            'hospital_key',
            'service_key',
            'appointment_id',
            'scheduled_date',
            'scheduled_time',
            'actual_start_time',
            'actual_end_time',
            'status',
            'appointment_type',
            'booking_method',
            'wait_time_minutes',
            'consultation_duration_minutes',
            'follow_up_required',
            'follow_up_scheduled',
            'patient_satisfaction_score',
            'doctor_notes',
            'total_cost',
            'insurance_covered_amount',
            'patient_paid_amount',
            'created_at'
        ]));
    }

    /** @test */
    public function revenue_fact_table_is_created_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('revenue_fact'));

        $this->assertTrue(Schema::hasColumns('revenue_fact', [
            'transaction_key',
            'date_key',
            'patient_key',
            'doctor_key',
            'hospital_key',
            'transaction_id',
            'transaction_date',
            'transaction_type',
            'payment_method',
            'amount',
            'tax_amount',
            'discount_amount',
            'insurance_adjustment',
            'net_amount',
            'claim_id',
            'invoice_id',
            'subscription_id',
            'description',
            'status',
            'processed_at',
            'created_at'
        ]));
    }

    /** @test */
    public function patient_satisfaction_fact_table_is_created_with_correct_structure()
    {
        $this->assertTrue(Schema::hasTable('patient_satisfaction_fact'));

        $this->assertTrue(Schema::hasColumns('patient_satisfaction_fact', [
            'outcome_key',
            'date_key',
            'patient_key',
            'doctor_key',
            'service_key',
            'diagnosis_id',
            'outcome_date',
            'diagnosis_code',
            'procedure_code',
            'outcome_category',
            'outcome_score',
            'length_of_stay_days',
            'readmission_within_30_days',
            'complication_occurred',
            'patient_satisfaction',
            'treatment_cost',
            'follow_up_required',
            'follow_up_completed',
            'notes',
            'created_at'
        ]));
    }
}
