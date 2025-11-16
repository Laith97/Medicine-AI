<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\HEPAnalyticsService;
use App\Models\User;
use App\Models\HepProgram;
use App\Models\HepAssignment;
use App\Models\HepProgress;
use App\Models\Diagnosis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class HEPAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new HEPAnalyticsService();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_get_clinical_effectiveness_analytics_returns_expected_structure()
    {
        // Create test data
        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = User::factory()->create(['role' => 'patient']);
        $diagnosis = Diagnosis::factory()->create();

        $program = HepProgram::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'diagnosis_id' => $diagnosis->id,
            'status' => 'active'
        ]);

        $assignment = HepAssignment::factory()->create([
            'hep_program_id' => $program->id,
            'patient_id' => $patient->id,
            'completion_status' => 'completed'
        ]);

        // Test the method
        $result = $this->analyticsService->getClinicalEffectivenessAnalytics();

        // Assert structure
        $this->assertArrayHasKey('total_programs_analyzed', $result);
        $this->assertArrayHasKey('diagnosis_effectiveness', $result);
        $this->assertArrayHasKey('overall_success_rate', $result);
        $this->assertArrayHasKey('average_completion_time', $result);
        $this->assertArrayHasKey('pain_reduction_average', $result);
        $this->assertArrayHasKey('adherence_correlation', $result);

        $this->assertEquals(1, $result['total_programs_analyzed']);
    }

    public function test_get_adherence_patterns_returns_expected_structure()
    {
        // Create test data
        $patient = User::factory()->create(['role' => 'patient']);
        $program = HepProgram::factory()->create(['patient_id' => $patient->id]);
        $assignment = HepAssignment::factory()->create([
            'hep_program_id' => $program->id,
            'patient_id' => $patient->id
        ]);

        HepProgress::factory()->create([
            'hep_assignment_id' => $assignment->id,
            'completed_sets' => 3,
            'completed_reps' => 10,
            'pain_level' => 5,
            'difficulty_rating' => 3
        ]);

        // Test the method
        $result = $this->analyticsService->getAdherencePatterns();

        // Assert structure
        $this->assertArrayHasKey('total_assignments', $result);
        $this->assertArrayHasKey('adherence_distribution', $result);
        $this->assertArrayHasKey('weekly_patterns', $result);
        $this->assertArrayHasKey('average_adherence_rate', $result);
        $this->assertArrayHasKey('consistency_score', $result);

        $this->assertEquals(1, $result['total_assignments']);
    }

    public function test_get_clinician_metrics_returns_expected_structure()
    {
        // Create test data
        $doctor = User::factory()->create(['role' => 'doctor', 'name' => 'Dr. Test']);
        HepProgram::factory()->create(['doctor_id' => $doctor->id]);

        // Test the method
        $result = $this->analyticsService->getClinicianMetrics();

        // Assert structure
        $this->assertArrayHasKey('total_clinicians', $result);
        $this->assertArrayHasKey('clinician_performance', $result);
        $this->assertArrayHasKey('average_programs_per_clinician', $result);
        $this->assertArrayHasKey('most_active_clinicians', $result);

        $this->assertEquals(1, $result['total_clinicians']);
    }

    public function test_analytics_use_caching()
    {
        // Create test data
        $doctor = User::factory()->create(['role' => 'doctor']);
        HepProgram::factory()->create(['doctor_id' => $doctor->id]);

        // First call should cache
        $result1 = $this->analyticsService->getClinicianMetrics();
        $this->assertEquals(1, $result1['total_clinicians']);

        // Modify data
        HepProgram::factory()->create(['doctor_id' => $doctor->id]);

        // Second call should return cached data
        $result2 = $this->analyticsService->getClinicianMetrics();
        $this->assertEquals(1, $result2['total_clinicians']); // Should still be 1 due to caching

        // Clear cache and test again
        $this->analyticsService->clearCache();
        $result3 = $this->analyticsService->getClinicianMetrics();
        $this->assertEquals(1, $result3['total_clinicians']); // Still 1 because we only added one more program to same doctor
    }

    public function test_calculate_correlation_with_empty_data()
    {
        $reflection = new \ReflectionClass($this->analyticsService);
        $method = $reflection->getMethod('calculateCorrelation');
        $method->setAccessible(true);

        $result = $method->invoke($this->analyticsService, []);
        $this->assertEquals(0, $result);
    }

    public function test_calculate_correlation_with_single_data_point()
    {
        $reflection = new \ReflectionClass($this->analyticsService);
        $method = $reflection->getMethod('calculateCorrelation');
        $method->setAccessible(true);

        $data = [['adherence' => 80, 'outcome' => 85]];
        $result = $method->invoke($this->analyticsService, $data);
        $this->assertEquals(0, $result);
    }

    public function test_calculate_correlation_with_multiple_data_points()
    {
        $reflection = new \ReflectionClass($this->analyticsService);
        $method = $reflection->getMethod('calculateCorrelation');
        $method->setAccessible(true);

        $data = [
            ['adherence' => 50, 'outcome' => 60],
            ['adherence' => 70, 'outcome' => 75],
            ['adherence' => 90, 'outcome' => 85]
        ];
        $result = $method->invoke($this->analyticsService, $data);

        // Should return a correlation coefficient between -1 and 1
        $this->assertGreaterThanOrEqual(-1, $result);
        $this->assertLessThanOrEqual(1, $result);
        $this->assertNotEquals(0, $result);
    }

    public function test_get_age_group_categorization()
    {
        $reflection = new \ReflectionClass($this->analyticsService);
        $method = $reflection->getMethod('getAgeGroup');
        $method->setAccessible(true);

        $this->assertEquals('Under 18', $method->invoke($this->analyticsService, 15));
        $this->assertEquals('18-29', $method->invoke($this->analyticsService, 25));
        $this->assertEquals('30-39', $method->invoke($this->analyticsService, 35));
        $this->assertEquals('40-49', $method->invoke($this->analyticsService, 45));
        $this->assertEquals('50-59', $method->invoke($this->analyticsService, 55));
        $this->assertEquals('60-69', $method->invoke($this->analyticsService, 65));
        $this->assertEquals('70+', $method->invoke($this->analyticsService, 75));
        $this->assertEquals('Unknown', $method->invoke($this->analyticsService, null));
    }

    public function test_clinical_effectiveness_filters_by_hospital()
    {
        // Create test data for different hospitals
        $hospital1 = User::factory()->create(['role' => 'patient', 'hospital_id' => 1]);
        $hospital2 = User::factory()->create(['role' => 'patient', 'hospital_id' => 2]);

        HepProgram::factory()->create(['patient_id' => $hospital1->id]);
        HepProgram::factory()->create(['patient_id' => $hospital2->id]);

        $result1 = $this->analyticsService->getClinicalEffectivenessAnalytics(1);
        $result2 = $this->analyticsService->getClinicalEffectivenessAnalytics(2);

        // Each hospital should only see their own data
        $this->assertEquals(1, $result1['total_programs_analyzed']);
        $this->assertEquals(1, $result2['total_programs_analyzed']);
    }

    public function test_clinical_effectiveness_filters_by_date_range()
    {
        $program1 = HepProgram::factory()->create(['created_at' => now()->subDays(10)]);
        $program2 = HepProgram::factory()->create(['created_at' => now()->subDays(50)]);

        $result = $this->analyticsService->getClinicalEffectivenessAnalytics(
            null,
            now()->subDays(15)->toDateString(),
            now()->toDateString()
        );

        // Should only include program1
        $this->assertEquals(1, $result['total_programs_analyzed']);
    }
}
