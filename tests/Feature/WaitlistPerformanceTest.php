<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Waitlist;
use App\Models\WaitlistPatientPreference;
use App\Models\AvailabilitySlot;
use App\Services\WaitlistService;
use App\Services\WaitlistPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class WaitlistPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected WaitlistService $waitlistService;
    protected WaitlistPreferenceService $preferenceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->waitlistService = app(WaitlistService::class);
        $this->preferenceService = app(WaitlistPreferenceService::class);
    }

    /** @test */
    public function waitlist_service_methods_respond_within_acceptable_time_limits()
    {
        // Setup test data
        $doctor = Doctor::factory()->create();
        $patient = User::factory()->create();

        // Test addToWaitlist performance
        $startTime = microtime(true);
        $waitlist = $this->waitlistService->addToWaitlist($patient->id, $doctor->id, [
            'service_type' => 'consultation',
            'priority_level' => 'medium',
        ]);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertLessThan(100, $executionTime, 'addToWaitlist should complete within 100ms');
        $this->assertInstanceOf(Waitlist::class, $waitlist);

        // Test getWaitlistPosition performance
        $startTime = microtime(true);
        $position = $this->waitlistService->getWaitlistPosition($waitlist->id);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(50, $executionTime, 'getWaitlistPosition should complete within 50ms');
        $this->assertIsArray($position);

        // Test getWaitlistStatistics performance
        $startTime = microtime(true);
        $stats = $this->waitlistService->getWaitlistStatistics($doctor->id);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(100, $executionTime, 'getWaitlistStatistics should complete within 100ms');
        $this->assertIsArray($stats);
    }

    /** @test */
    public function preference_service_methods_perform_well_under_load()
    {
        // Setup test data
        $doctor = Doctor::factory()->create();
        $patient = User::factory()->create();

        $preferences = WaitlistPatientPreference::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'preferred_times' => ['morning', 'afternoon'],
            'preferred_days' => ['monday', 'wednesday', 'friday'],
        ]);

        // Test calculateMatchingScore performance
        $slot = [
            'date' => '2025-11-18',
            'time' => '09:00:00',
        ];

        $startTime = microtime(true);
        $score = $this->preferenceService->calculateMatchingScore($slot, $preferences, $doctor->id);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(20, $executionTime, 'calculateMatchingScore should complete within 20ms');
        $this->assertIsFloat($score);

        // Test getMatchingRecommendations performance
        $startTime = microtime(true);
        $recommendations = $this->preferenceService->getMatchingRecommendations($patient->id, $doctor->id);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(50, $executionTime, 'getMatchingRecommendations should complete within 50ms');
        $this->assertIsArray($recommendations);
    }

    /** @test */
    public function find_available_slots_performs_well_with_large_dataset()
    {
        $doctor = Doctor::factory()->create();

        // Create 100 availability slots
        $slots = [];
        for ($i = 0; $i < 100; $i++) {
            $slots[] = [
                'doctor_id' => $doctor->id,
                'date' => now()->addDays($i % 30)->toDateString(),
                'start_time' => sprintf('%02d:00:00', 9 + ($i % 8)), // 9 AM to 4 PM
                'duration' => 30,
                'is_available' => true,
            ];
        }

        DB::table('availability_slots')->insert($slots);

        // Test performance with 100 slots
        $startTime = microtime(true);
        $availableSlots = $this->waitlistService->findAvailableSlots($doctor->id, 30);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(200, $executionTime, 'findAvailableSlots should complete within 200ms with 100 slots');
        $this->assertGreaterThan(0, count($availableSlots));
    }

    /** @test */
    public function batch_slot_processing_handles_multiple_appointments_efficiently()
    {
        $doctor = Doctor::factory()->create();

        // Create 10 patients on waitlist
        $patients = User::factory()->count(10)->create();
        foreach ($patients as $patient) {
            $this->waitlistService->addToWaitlist($patient->id, $doctor->id, []);
        }

        // Create 5 cancelled appointments
        $cancelledAppointments = [];
        for ($i = 0; $i < 5; $i++) {
            $appointment = \App\Models\Appointment::create([
                'patient_id' => User::factory()->create()->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => now()->addDays($i + 1)->setTime(9, 0),
                'status' => 'cancelled',
                'appointment_type' => 'consultation',
                'duration' => 30,
            ]);

            AvailabilitySlot::create([
                'doctor_id' => $doctor->id,
                'date' => $appointment->appointment_date->toDateString(),
                'start_time' => $appointment->appointment_date->format('H:i:s'),
                'duration' => 30,
                'is_available' => true,
            ]);

            $cancelledAppointments[] = $appointment;
        }

        // Test batch processing performance
        $startTime = microtime(true);
        $results = $this->waitlistService->processBatchSlotOpenings($cancelledAppointments);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(500, $executionTime, 'processBatchSlotOpenings should complete within 500ms for 5 appointments');
        $this->assertEquals(5, $results['processed']);
        $this->assertEquals(5, $results['slots_offered']);
    }

    /** @test */
    public function database_queries_are_optimized_for_waitlist_operations()
    {
        $doctor = Doctor::factory()->create();

        // Create 50 patients on waitlist
        $patients = User::factory()->count(50)->create();
        foreach ($patients as $patient) {
            $this->waitlistService->addToWaitlist($patient->id, $doctor->id, []);
        }

        // Test that statistics query doesn't trigger N+1 problems
        DB::enableQueryLog();

        $stats = $this->waitlistService->getWaitlistStatistics($doctor->id);

        $queryCount = count(DB::getQueryLog());

        // Should be efficient (less than 5 queries for basic stats)
        $this->assertLessThan(5, $queryCount, 'Statistics query should be optimized and use less than 5 queries');

        DB::disableQueryLog();

        $this->assertEquals(50, $stats['total_active']);
    }

    /** @test */
    public function preference_analytics_perform_well_with_multiple_preferences()
    {
        $patient = User::factory()->create();

        // Create 10 different preferences for different doctors
        $doctors = Doctor::factory()->count(10)->create();
        foreach ($doctors as $doctor) {
            WaitlistPatientPreference::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'preferred_times' => ['morning', 'afternoon'],
                'preferred_days' => ['monday', 'wednesday', 'friday'],
                'auto_accept_threshold' => rand(3, 14),
            ]);
        }

        // Test analytics performance
        $startTime = microtime(true);
        $analytics = $this->preferenceService->getPreferenceAnalytics($patient->id);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(100, $executionTime, 'getPreferenceAnalytics should complete within 100ms with 10 preferences');
        $this->assertEquals(10, $analytics['total_preferences']);
    }

    /** @test */
    public function memory_usage_remains_reasonable_during_heavy_operations()
    {
        $doctor = Doctor::factory()->create();

        // Create large dataset
        $patients = User::factory()->count(100)->create();
        foreach ($patients as $patient) {
            $this->waitlistService->addToWaitlist($patient->id, $doctor->id, []);
        }

        $initialMemory = memory_get_usage();

        // Perform memory-intensive operation
        $stats = $this->waitlistService->getWaitlistStatistics($doctor->id);

        $finalMemory = memory_get_usage();
        $memoryUsed = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Should use less than 10MB for this operation
        $this->assertLessThan(10, $memoryUsed, 'Memory usage should remain under 10MB for statistics operation');

        $this->assertEquals(100, $stats['total_active']);
    }

    /** @test */
    public function concurrent_slot_offers_maintain_performance()
    {
        $doctor = Doctor::factory()->create();

        // Create 20 patients with different priorities
        $patients = User::factory()->count(20)->create();
        $priorities = ['urgent', 'high', 'medium', 'low'];

        foreach ($patients as $index => $patient) {
            $this->waitlistService->addToWaitlist($patient->id, $doctor->id, [
                'priority_level' => $priorities[$index % 4],
            ]);
        }

        // Create multiple slot openings simultaneously
        $appointments = [];
        for ($i = 0; $i < 5; $i++) {
            $appointment = \App\Models\Appointment::create([
                'patient_id' => User::factory()->create()->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => now()->addDays($i + 1)->setTime(9, 0),
                'status' => 'cancelled',
                'appointment_type' => 'consultation',
                'duration' => 30,
            ]);

            AvailabilitySlot::create([
                'doctor_id' => $doctor->id,
                'date' => $appointment->appointment_date->toDateString(),
                'start_time' => $appointment->appointment_date->format('H:i:s'),
                'duration' => 30,
                'is_available' => true,
            ]);

            $appointments[] = $appointment;
        }

        // Process all slots
        $startTime = microtime(true);
        foreach ($appointments as $appointment) {
            $this->waitlistService->processSlotOpening($appointment);
        }
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Should complete within reasonable time even with priority sorting
        $this->assertLessThan(1000, $executionTime, 'Processing 5 slot openings should complete within 1000ms');

        // Verify that urgent patients got the first slots
        $urgentEntries = \App\Models\WaitlistEntry::whereHas('waitlist', function ($query) {
            $query->where('priority_level', 'urgent');
        })->get();

        $this->assertGreaterThan(0, $urgentEntries->count());
    }

    /** @test */
    public function api_response_times_meet_mobile_app_requirements()
    {
        // Simulate mobile app API calls with realistic data load
        $doctor = Doctor::factory()->create();
        $patient = User::factory()->create();

        // Setup realistic scenario
        $waitlist = $this->waitlistService->addToWaitlist($patient->id, $doctor->id, []);
        $preferences = WaitlistPatientPreference::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'preferred_times' => ['morning'],
            'preferred_days' => ['monday', 'wednesday'],
        ]);

        // Test critical mobile API endpoints simulation
        $operations = [
            'get_position' => function() use ($waitlist) {
                return $this->waitlistService->getWaitlistPosition($waitlist->id);
            },
            'get_recommendations' => function() use ($patient, $doctor) {
                return $this->preferenceService->getMatchingRecommendations($patient->id, $doctor->id);
            },
            'get_statistics' => function() use ($doctor) {
                return $this->waitlistService->getWaitlistStatistics($doctor->id);
            },
        ];

        foreach ($operations as $name => $operation) {
            $startTime = microtime(true);
            $result = $operation();
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000;

            // Mobile apps typically require <300ms response times
            $this->assertLessThan(300, $executionTime, "$name should respond within 300ms for mobile app requirements");
            $this->assertNotNull($result);
        }
    }
}
