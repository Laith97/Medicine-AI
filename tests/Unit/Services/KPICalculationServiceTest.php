<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DataWarehouse\KPICalculationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class KPICalculationServiceTest extends TestCase
{
    protected KPICalculationService $kpiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kpiService = new KPICalculationService();
        Cache::flush();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(KPICalculationService::class, $this->kpiService);
    }

    /** @test */
    public function it_has_correct_cache_ttl()
    {
        $this->assertEquals(3600, $this->getPrivateProperty($this->kpiService, 'cacheTtl'));
    }

    /** @test */
    public function it_calculates_revenue_kpis_with_zero_division_handling()
    {
        // Test the ARPU calculation with zero paying patients
        $service = new KPICalculationService();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateRevenueKPIs');
        $method->setAccessible(true);

        // Mock DB response with zero paying patients
        DB::shouldReceive('table')
            ->with('fact_financial_transactions')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('selectRaw')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->andReturn((object) [
                'total_revenue' => 1000,
                'insurance_revenue' => 800,
                'patient_revenue' => 200,
                'refunds' => 50,
                'avg_transaction_value' => 50,
                'paying_patients' => 0 // Zero patients
            ]);

        $result = $method->invoke($service, 20241115, 1);

        $this->assertEquals(1000, $result['total_revenue']);
        $this->assertEquals(950, $result['net_revenue']);
        $this->assertEquals(0, $result['average_revenue_per_user']); // Should handle division by zero
    }

    /** @test */
    public function it_calculates_patient_satisfaction_kpis_with_nps_calculation()
    {
        $service = new KPICalculationService();

        // Mock DB response
        DB::shouldReceive('table')
            ->with('fact_appointments')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('selectRaw')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->andReturn((object) [
                'avg_satisfaction' => 4.2,
                'promoters' => 25,
                'passives' => 10,
                'detractors' => 5,
                'total_responses' => 40
            ]);

        // Mock getTotalAppointments method
        $reflection = new \ReflectionClass($service);
        $totalAppointmentsMethod = $reflection->getMethod('getTotalAppointments');
        $totalAppointmentsMethod->setAccessible(true);

        DB::shouldReceive('table')
            ->with('fact_appointments')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('count')
            ->andReturn(50);

        $method = $reflection->getMethod('calculatePatientSatisfactionKPIs');
        $method->setAccessible(true);

        $result = $method->invoke($service, 20241115, 1);

        $this->assertEquals(4.2, $result['patient_satisfaction_score']);
        $this->assertEquals(50, $result['net_promoter_score']); // (25-5)/40 * 100
        $this->assertEquals(80, $result['satisfaction_response_rate']); // 40/50 * 100
    }

    /** @test */
    public function it_calculates_operational_efficiency_kpis_with_provider_utilization()
    {
        $service = new KPICalculationService();

        // Mock DB response
        DB::shouldReceive('table')
            ->with('fact_appointments')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('selectRaw')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->andReturn((object) [
                'total_appointments' => 100,
                'completed_appointments' => 80,
                'cancelled_appointments' => 15,
                'no_show_appointments' => 5,
                'avg_wait_time' => 20,
                'avg_consultation_duration' => 25,
                'avg_completed_duration' => 30,
                'active_doctors' => 4,
                'unique_patients' => 75
            ]);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateOperationalEfficiencyKPIs');
        $method->setAccessible(true);

        $result = $method->invoke($service, 20241115, 1);

        $this->assertEquals(100, $result['total_appointments']);
        $this->assertEquals(80, $result['completed_appointments']);
        $this->assertEquals(80, $result['appointment_show_up_rate']); // 80/100 * 100
        $this->assertEquals(5, $result['appointment_no_show_rate']);
        $this->assertEquals(15, $result['appointment_cancellation_rate']);
        $this->assertEquals(62.5, $result['provider_utilization_rate']); // (30*80)/(480*4) * 100
    }

    /** @test */
    public function it_calculates_clinical_outcomes_kpis_with_rates()
    {
        $service = new KPICalculationService();

        // Mock DB response
        DB::shouldReceive('table')
            ->with('fact_clinical_outcomes')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('selectRaw')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->andReturn((object) [
                'total_outcomes' => 100,
                'avg_outcome_score' => 4.1,
                'successful_outcomes' => 85,
                'complications' => 8,
                'readmissions_30_days' => 7,
                'avg_length_of_stay' => 4.5,
                'avg_treatment_cost' => 3200
            ]);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateClinicalOutcomesKPIs');
        $method->setAccessible(true);

        $result = $method->invoke($service, 20241115, 1);

        $this->assertEquals(100, $result['total_clinical_outcomes']);
        $this->assertEquals(4.1, $result['average_outcome_score']);
        $this->assertEquals(85, $result['treatment_success_rate']);
        $this->assertEquals(8, $result['complication_rate']);
        $this->assertEquals(7, $result['readmission_rate_30_days']);
        $this->assertEquals(4.5, $result['average_length_of_stay_days']);
        $this->assertEquals(3200, $result['average_treatment_cost']);
    }

    /** @test */
    public function it_calculates_user_activity_kpis()
    {
        $service = new KPICalculationService();

        // Mock DB response
        DB::shouldReceive('table')
            ->with('fact_user_activity')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('selectRaw')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->andReturn((object) [
                'active_users' => 150,
                'login_count' => 450,
                'page_views' => 1200,
                'new_registrations' => 15,
                'avg_session_duration' => 280,
                'mobile_users' => 100,
                'desktop_users' => 50
            ]);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateUserActivityKPIs');
        $method->setAccessible(true);

        $result = $method->invoke($service, 20241115, 1);

        $this->assertEquals(150, $result['active_users']);
        $this->assertEquals(450, $result['total_logins']);
        $this->assertEquals(1200, $result['total_page_views']);
        $this->assertEquals(15, $result['new_user_registrations']);
        $this->assertEquals(280, $result['average_session_duration_seconds']);
        $this->assertEquals(100, $result['mobile_users']);
        $this->assertEquals(50, $result['desktop_users']);
    }

    /** @test */
    public function it_handles_null_database_responses_gracefully()
    {
        $service = new KPICalculationService();

        // Mock null DB response
        DB::shouldReceive('table')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('selectRaw')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->andReturn(null);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateRevenueKPIs');
        $method->setAccessible(true);

        $result = $method->invoke($service, 20241115, 1);

        $this->assertEquals(0, $result['total_revenue']);
        $this->assertEquals(0, $result['net_revenue']);
        $this->assertEquals(0, $result['average_revenue_per_user']);
    }

    /** @test */
    public function it_calculates_monthly_kpis_structure()
    {
        $service = new KPICalculationService();

        // Mock DB responses for monthly calculation
        DB::shouldReceive('table')
            ->with('appointments_fact')
            ->andReturnSelf();
        DB::shouldReceive('whereIn')
            ->andReturnSelf();
        DB::shouldReceive('selectRaw')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->andReturn((object) [
                'total' => 2500,
                'completed' => 2100,
                'avg_wait' => 18,
                'avg_satisfaction' => 4.3
            ]);

        DB::shouldReceive('table')
            ->with('revenue_fact')
            ->andReturnSelf();
        DB::shouldReceive('whereIn')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('selectRaw')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->andReturn((object) ['total_revenue' => 250000]);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getMonthlyMetrics');
        $method->setAccessible(true);

        $result = $method->invoke($service, 2024, 11);

        $this->assertEquals(2500, $result['total_appointments']);
        $this->assertEquals(2100, $result['completed_appointments']);
        $this->assertEquals(250000, $result['total_revenue']);
        $this->assertEquals(4.3, $result['avg_satisfaction']);
        $this->assertEquals(18, $result['avg_wait_time']);
    }

    /**
     * Helper method to access private properties
     */
    private function getPrivateProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }
}
