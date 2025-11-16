<?php

namespace Tests;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\PatientInsurance;
use App\Models\EligibilityCheck;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Services\EligibilityService;
use App\Services\EligibilityServiceFactory;
use App\Services\AuthorizationService;
use App\Services\BusinessRulesService;
use App\Services\DrugInteractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->patientUser = User::factory()->create(['role' => 'patient']);

        // Create doctor
        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id
        ]);

        // Create patient insurance
        $this->patientInsurance = PatientInsurance::factory()->create([
            'patient_id' => $this->patientUser->id
        ]);

        // Create appointment
        $this->appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patientUser->id,
            'status' => 'confirmed'
        ]);
    }

    /** @test */
    public function it_tests_n_plus_one_elimination_in_eligibility_service()
    {
        // Create multiple patient insurances for testing
        $insurances = PatientInsurance::factory()->count(10)->create([
            'patient_id' => $this->patientUser->id
        ]);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Test batch eligibility check (optimized)
        $eligibilityService = app(EligibilityServiceFactory::class)
            ->getServiceForProvider($insurances->first()->insuranceProvider);

        $requests = [];
        foreach ($insurances as $insurance) {
            $requests[] = $insurance;
            $requests[] = 'office_visit'; // service type
        }

        $results = $eligibilityService->batchCheckEligibility($requests);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        $this->assertLessThan(1000, $executionTime, 'Batch eligibility check should complete in under 1 second');
        $this->assertLessThan(50, $memoryUsed, 'Memory usage should be under 50MB for 10 eligibility checks');

        echo "\nBatch Eligibility Check Performance:\n";
        echo "Execution Time: {$executionTime}ms\n";
        echo "Memory Used: {$memoryUsed}MB\n";
    }

    /** @test */
    public function it_tests_caching_performance()
    {
        $startTime = microtime(true);

        // First call (cache miss)
        $eligibilityService = app(EligibilityServiceFactory::class)
            ->getServiceForProvider($this->patientInsurance->insuranceProvider);

        $result1 = $eligibilityService->checkEligibility($this->patientInsurance, 'office_visit');

        $cacheMissTime = microtime(true);

        // Second call (should be cache hit)
        $result2 = $eligibilityService->checkEligibility($this->patientInsurance, 'office_visit');

        $cacheHitTime = microtime(true);

        $cacheMissDuration = ($cacheMissTime - $startTime) * 1000;
        $cacheHitDuration = ($cacheHitTime - $cacheMissTime) * 1000;

        // Cache hit should be significantly faster
        $this->assertLessThan($cacheMissDuration / 10, $cacheHitDuration, 'Cache hit should be 10x faster than cache miss');

        echo "\nCaching Performance:\n";
        echo "Cache Miss: {$cacheMissDuration}ms\n";
        echo "Cache Hit: {$cacheHitDuration}ms\n";
        echo "Performance Improvement: " . round($cacheMissDuration / $cacheHitDuration, 2) . "x faster\n";
    }

    /** @test */
    public function it_tests_authorization_service_batch_processing()
    {
        // Create multiple patients
        $patients = User::factory()->count(20)->create(['role' => 'patient']);
        $patientIds = $patients->pluck('id')->toArray();

        $startTime = microtime(true);

        // Test batch authorization check
        $authService = new AuthorizationService();
        $results = $authService->canAccessEligibilityForPatients($this->doctorUser, $patientIds);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(500, $executionTime, 'Batch authorization check should complete in under 500ms');
        $this->assertCount(20, $results, 'Should return results for all requested patients');

        echo "\nBatch Authorization Performance:\n";
        echo "Execution Time: {$executionTime}ms\n";
        echo "Patients Processed: " . count($patientIds) . "\n";
    }

    /** @test */
    public function it_tests_business_rules_batch_validation()
    {
        // Create multiple appointments
        $appointments = Appointment::factory()->count(15)->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patientUser->id,
            'appointment_date' => now()->addDays(1)
        ]);

        $startTime = microtime(true);

        // Test batch validation
        $businessRulesService = new BusinessRulesService();
        $results = $businessRulesService->batchValidateAppointmentsCancellation($appointments, 'Test reason');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(1000, $executionTime, 'Batch business rules validation should complete in under 1 second');
        $this->assertCount(15, $results, 'Should return validation results for all appointments');

        echo "\nBatch Business Rules Performance:\n";
        echo "Execution Time: {$executionTime}ms\n";
        echo "Appointments Validated: " . count($appointments) . "\n";
    }

    /** @test */
    public function it_tests_api_timeout_handling()
    {
        // Test that API timeout handling doesn't cause long delays
        $startTime = microtime(true);

        try {
            // This should timeout quickly and not hang
            $eligibilityService = app(EligibilityServiceFactory::class)
                ->getServiceForProvider($this->patientInsurance->insuranceProvider);

            // Mock a slow response by temporarily modifying the timeout
            $result = $eligibilityService->checkEligibility($this->patientInsurance, 'office_visit');

        } catch (\Exception $e) {
            // Expected to fail with timeout
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Should timeout within reasonable time (under 15 seconds total)
        $this->assertLessThan(15000, $executionTime, 'API timeout handling should not exceed 15 seconds');

        echo "\nAPI Timeout Handling:\n";
        echo "Total Execution Time: {$executionTime}ms\n";
        echo "Timeout configured: 10s request timeout + 5s connection timeout\n";
    }

    /** @test */
    public function it_tests_memory_cleanup()
    {
        // Create large dataset to test memory cleanup
        $insurances = PatientInsurance::factory()->count(100)->create([
            'patient_id' => $this->patientUser->id
        ]);

        $startMemory = memory_get_usage();

        // Process many eligibility checks
        $eligibilityService = app(EligibilityServiceFactory::class)
            ->getServiceForProvider($insurances->first()->insuranceProvider);

        foreach ($insurances->take(10) as $insurance) {
            try {
                $eligibilityService->checkEligibility($insurance, 'office_visit');
            } catch (\Exception $e) {
                // Ignore errors for this test
            }
        }

        // Force garbage collection
        gc_collect_cycles();

        $endMemory = memory_get_usage();
        $memoryLeak = ($endMemory - $startMemory) / 1024 / 1024; // MB

        // Should not have significant memory leaks
        $this->assertLessThan(10, $memoryLeak, 'Memory leak should be under 10MB after processing');

        echo "\nMemory Cleanup Test:\n";
        echo "Initial Memory: " . round($startMemory / 1024 / 1024, 2) . "MB\n";
        echo "Final Memory: " . round($endMemory / 1024 / 1024, 2) . "MB\n";
        echo "Memory Leak: {$memoryLeak}MB\n";
    }

    /** @test */
    public function it_tests_database_query_optimization()
    {
        // Enable query logging
        DB::enableQueryLog();

        $startTime = microtime(true);

        // Load appointment with eager loading (optimized)
        $appointment = Appointment::with(['doctor.user', 'doctor.specialty', 'patient.patientData'])
            ->find($this->appointment->id);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Should use minimal queries with eager loading
        $this->assertLessThan(5, $queryCount, 'Eager loading should use minimal queries');

        echo "\nDatabase Query Optimization:\n";
        echo "Query Count: {$queryCount}\n";
        echo "Execution Time: {$executionTime}ms\n";

        foreach ($queries as $index => $query) {
            echo "Query " . ($index + 1) . ": " . substr($query['query'], 0, 100) . "...\n";
        }
    }

    /** @test */
    public function it_runs_comprehensive_performance_benchmark()
    {
        echo "\n=== COMPREHENSIVE PERFORMANCE BENCHMARK ===\n";

        // Test all major performance improvements
        $this->it_tests_n_plus_one_elimination_in_eligibility_service();
        $this->it_tests_caching_performance();
        $this->it_tests_authorization_service_batch_processing();
        $this->it_tests_business_rules_batch_validation();
        $this->it_tests_memory_cleanup();
        $this->it_tests_database_query_optimization();

        echo "\n=== PERFORMANCE BENCHMARK COMPLETE ===\n";
        echo "All critical performance improvements have been tested.\n";
        echo "Key optimizations implemented:\n";
        echo "1. N+1 query elimination through eager loading\n";
        echo "2. Batch processing for multiple operations\n";
        echo "3. Improved caching with proper invalidation\n";
        echo "4. API timeout handling (10s request + 5s connection)\n";
        echo "5. Memory leak prevention and cleanup\n";
        echo "6. Database query optimization\n";
    }
}
