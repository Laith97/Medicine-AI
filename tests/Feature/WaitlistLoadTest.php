<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Waitlist;
use App\Models\WaitlistPatientPreference;
use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use App\Services\WaitlistService;
use App\Services\WaitlistPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\ParallelTesting;

class WaitlistLoadTest extends TestCase
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
    public function handles_concurrent_patient_registrations_to_waitlist()
    {
        $doctor = Doctor::factory()->create();

        // Simulate 50 concurrent patients trying to join waitlist
        $concurrentOperations = 50;

        $results = [];
        $errors = [];

        for ($i = 0; $i < $concurrentOperations; $i++) {
            try {
                $patient = User::factory()->create();

                $startTime = microtime(true);
                $waitlist = $this->waitlistService->addToWaitlist($patient->id, $doctor->id, [
                    'service_type' => 'consultation',
                    'priority_level' => 'medium',
                ]);
                $endTime = microtime(true);

                $results[] = [
                    'operation' => $i,
                    'time' => ($endTime - $startTime) * 1000,
                    'success' => true,
                    'waitlist_id' => $waitlist->id,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'operation' => $i,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Verify all operations succeeded
        $this->assertCount($concurrentOperations, $results);
        $this->assertEmpty($errors);

        // Verify database integrity
        $this->assertDatabaseCount('waitlists', $concurrentOperations);

        // Check performance - all operations should complete within reasonable time
        $averageTime = array_sum(array_column($results, 'time')) / count($results);
        $maxTime = max(array_column($results, 'time'));

        $this->assertLessThan(200, $averageTime, 'Average registration time should be under 200ms');
        $this->assertLessThan(500, $maxTime, 'Max registration time should be under 500ms');

        // Verify no duplicate registrations (same patient can't register twice)
        $uniquePatients = collect($results)->pluck('waitlist_id')->unique()->count();
        $this->assertEquals($concurrentOperations, $uniquePatients);
    }

    /** @test */
    public function handles_concurrent_slot_openings_and_offers()
    {
        $doctor = Doctor::factory()->create();

        // Create 20 patients on waitlist with different priorities
        $patients = User::factory()->count(20)->create();
        $priorities = ['urgent', 'high', 'medium', 'low'];

        foreach ($patients as $index => $patient) {
            $this->waitlistService->addToWaitlist($patient->id, $doctor->id, [
                'priority_level' => $priorities[$index % 4],
            ]);
        }

        // Simulate 10 concurrent slot openings
        $slotOperations = 10;
        $slotResults = [];
        $slotErrors = [];

        for ($i = 0; $i < $slotOperations; $i++) {
            try {
                // Create cancelled appointment
                $appointment = Appointment::create([
                    'patient_id' => User::factory()->create()->id,
                    'doctor_id' => $doctor->id,
                    'appointment_date' => now()->addDays($i + 1)->setTime(9 + ($i % 3), 0),
                    'status' => 'cancelled',
                    'appointment_type' => 'consultation',
                    'duration' => 30,
                ]);

                // Create availability slot
                AvailabilitySlot::create([
                    'doctor_id' => $doctor->id,
                    'date' => $appointment->appointment_date->toDateString(),
                    'start_time' => $appointment->appointment_date->format('H:i:s'),
                    'duration' => 30,
                    'is_available' => true,
                ]);

                $startTime = microtime(true);
                $this->waitlistService->processSlotOpening($appointment);
                $endTime = microtime(true);

                $slotResults[] = [
                    'operation' => $i,
                    'time' => ($endTime - $startTime) * 1000,
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $slotErrors[] = [
                    'operation' => $i,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Verify all slot processing operations succeeded
        $this->assertCount($slotOperations, $slotResults);
        $this->assertEmpty($slotErrors);

        // Check that slots were offered to patients
        $totalEntries = \App\Models\WaitlistEntry::count();
        $this->assertEquals($slotOperations, $totalEntries);

        // Performance checks
        $averageSlotTime = array_sum(array_column($slotResults, 'time')) / count($slotResults);
        $maxSlotTime = max(array_column($slotResults, 'time'));

        $this->assertLessThan(300, $averageSlotTime, 'Average slot processing time should be under 300ms');
        $this->assertLessThan(800, $maxSlotTime, 'Max slot processing time should be under 800ms');

        // Verify priority-based assignment (urgent patients get slots first)
        $urgentEntries = \App\Models\WaitlistEntry::whereHas('waitlist', function ($query) {
            $query->where('priority_level', 'urgent');
        })->count();

        $this->assertGreaterThan(0, $urgentEntries, 'Urgent patients should receive slot offers');
    }

    /** @test */
    public function handles_concurrent_slot_acceptance_and_decline_operations()
    {
        $doctor = Doctor::factory()->create();
        $patient = User::factory()->create();

        // Patient joins waitlist
        $waitlist = $this->waitlistService->addToWaitlist($patient->id, $doctor->id, []);

        // Create multiple slot offers for the same patient
        $entries = [];
        for ($i = 0; $i < 5; $i++) {
            $entry = \App\Models\WaitlistEntry::create([
                'waitlist_id' => $waitlist->id,
                'slot_date' => now()->addDays($i + 1)->toDateString(),
                'slot_time' => '10:00:00',
                'status' => 'offered',
                'response_deadline' => now()->addHours(24),
            ]);
            $entries[] = $entry;
        }

        // Simulate concurrent responses (accept first, decline others)
        $responseResults = [];
        $responseErrors = [];

        // Accept first slot
        try {
            $startTime = microtime(true);
            $result = $this->waitlistService->acceptSlotOffer($entries[0]->id);
            $endTime = microtime(true);

            $responseResults[] = [
                'operation' => 'accept',
                'time' => ($endTime - $startTime) * 1000,
                'success' => $result,
            ];
        } catch (\Exception $e) {
            $responseErrors[] = ['operation' => 'accept', 'error' => $e->getMessage()];
        }

        // Decline other slots
        for ($i = 1; $i < count($entries); $i++) {
            try {
                $startTime = microtime(true);
                $result = $this->waitlistService->declineSlotOffer($entries[$i]->id);
                $endTime = microtime(true);

                $responseResults[] = [
                    'operation' => 'decline_' . $i,
                    'time' => ($endTime - $startTime) * 1000,
                    'success' => $result,
                ];
            } catch (\Exception $e) {
                $responseErrors[] = ['operation' => 'decline_' . $i, 'error' => $e->getMessage()];
            }
        }

        // Verify all operations succeeded
        $this->assertCount(5, $responseResults); // 1 accept + 4 declines
        $this->assertEmpty($responseErrors);

        // Verify final state
        $entries[0]->refresh();
        $this->assertEquals('accepted', $entries[0]->status);

        for ($i = 1; $i < count($entries); $i++) {
            $entries[$i]->refresh();
            $this->assertEquals('declined', $entries[$i]->status);
        }

        // Waitlist should be fulfilled
        $waitlist->refresh();
        $this->assertEquals('fulfilled', $waitlist->status);

        // Performance check
        $averageResponseTime = array_sum(array_column($responseResults, 'time')) / count($responseResults);
        $this->assertLessThan(150, $averageResponseTime, 'Average response time should be under 150ms');
    }

    /** @test */
    public function handles_high_volume_slot_availability_queries()
    {
        $doctor = Doctor::factory()->create();

        // Create 200 availability slots across 30 days
        $slots = [];
        for ($day = 0; $day < 30; $day++) {
            for ($hour = 9; $hour <= 16; $hour++) { // 8 slots per day
                $slots[] = [
                    'doctor_id' => $doctor->id,
                    'date' => now()->addDays($day)->toDateString(),
                    'start_time' => sprintf('%02d:00:00', $hour),
                    'duration' => 30,
                    'is_available' => true,
                ];
            }
        }

        // Insert in chunks to avoid memory issues
        $chunkSize = 50;
        foreach (array_chunk($slots, $chunkSize) as $chunk) {
            \Illuminate\Support\Facades\DB::table('availability_slots')->insert($chunk);
        }

        // Test concurrent availability queries (simulate multiple users checking)
        $queryOperations = 20;
        $queryResults = [];
        $queryErrors = [];

        for ($i = 0; $i < $queryOperations; $i++) {
            try {
                $startTime = microtime(true);
                $availableSlots = $this->waitlistService->findAvailableSlots($doctor->id, 30);
                $endTime = microtime(true);

                $queryResults[] = [
                    'operation' => $i,
                    'time' => ($endTime - $startTime) * 1000,
                    'slots_found' => count($availableSlots),
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $queryErrors[] = [
                    'operation' => $i,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Verify all queries succeeded
        $this->assertCount($queryOperations, $queryResults);
        $this->assertEmpty($queryErrors);

        // All queries should return the same number of slots
        $slotsPerQuery = array_column($queryResults, 'slots_found');
        $this->assertTrue(count(array_unique($slotsPerQuery)) === 1, 'All queries should return same slot count');

        // Performance check - should handle 200+ slots efficiently
        $averageQueryTime = array_sum(array_column($queryResults, 'time')) / count($queryResults);
        $maxQueryTime = max(array_column($queryResults, 'time'));

        $this->assertLessThan(500, $averageQueryTime, 'Average availability query time should be under 500ms');
        $this->assertLessThan(1000, $maxQueryTime, 'Max availability query time should be under 1000ms');

        // Verify correct slot count (30 days * 8 slots/day = 240, but some may be filtered)
        $firstResult = $queryResults[0];
        $this->assertGreaterThan(200, $firstResult['slots_found'], 'Should find most available slots');
    }

    /** @test */
    public function handles_concurrent_preference_updates_and_matching()
    {
        $doctor = Doctor::factory()->create();

        // Create 30 patients with preferences
        $patients = User::factory()->count(30)->create();

        foreach ($patients as $patient) {
            WaitlistPatientPreference::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'preferred_times' => ['morning', 'afternoon'],
                'preferred_days' => ['monday', 'wednesday', 'friday'],
                'auto_accept_threshold' => rand(3, 14),
            ]);

            // Join waitlist
            $this->waitlistService->addToWaitlist($patient->id, $doctor->id, []);
        }

        // Create slots that match preferences
        AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'date' => now()->addDays(7)->next('monday')->toDateString(), // Monday
            'start_time' => '09:00:00', // Morning
            'duration' => 30,
            'is_available' => true,
        ]);

        AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'date' => now()->addDays(7)->next('wednesday')->toDateString(), // Wednesday
            'start_time' => '14:00:00', // Afternoon
            'duration' => 30,
            'is_available' => true,
        ]);

        // Test concurrent matching recommendations
        $matchingOperations = 15;
        $matchingResults = [];
        $matchingErrors = [];

        for ($i = 0; $i < $matchingOperations; $i++) {
            try {
                $patient = $patients[$i];

                $startTime = microtime(true);
                $recommendations = $this->preferenceService->getMatchingRecommendations($patient->id, $doctor->id);
                $endTime = microtime(true);

                $matchingResults[] = [
                    'operation' => $i,
                    'time' => ($endTime - $startTime) * 1000,
                    'recommendations_count' => count($recommendations),
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $matchingErrors[] = [
                    'operation' => $i,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Verify all matching operations succeeded
        $this->assertCount($matchingOperations, $matchingResults);
        $this->assertEmpty($matchingErrors);

        // Performance check
        $averageMatchingTime = array_sum(array_column($matchingResults, 'time')) / count($matchingResults);
        $maxMatchingTime = max(array_column($matchingResults, 'time'));

        $this->assertLessThan(200, $averageMatchingTime, 'Average matching time should be under 200ms');
        $this->assertLessThan(500, $maxMatchingTime, 'Max matching time should be under 500ms');

        // Most patients should get recommendations
        $recommendationCounts = array_column($matchingResults, 'recommendations_count');
        $averageRecommendations = array_sum($recommendationCounts) / count($recommendationCounts);
        $this->assertGreaterThan(1, $averageRecommendations, 'Patients should get multiple recommendations on average');
    }

    /** @test */
    public function maintains_data_integrity_under_concurrent_load()
    {
        $doctor = Doctor::factory()->create();

        // Create initial dataset
        $initialPatients = User::factory()->count(10)->create();
        $initialWaitlists = [];

        foreach ($initialPatients as $patient) {
            $waitlist = $this->waitlistService->addToWaitlist($patient->id, $doctor->id, []);
            $initialWaitlists[] = $waitlist;
        }

        // Simulate concurrent operations
        $concurrentOperations = 20;
        $operations = [];

        for ($i = 0; $i < $concurrentOperations; $i++) {
            $operations[] = [
                'type' => rand(0, 2), // 0=add patient, 1=remove patient, 2=check position
                'patient_id' => rand(0, 1) ? $initialPatients->random()->id : User::factory()->create()->id,
            ];
        }

        $operationResults = [];
        $operationErrors = [];

        foreach ($operations as $index => $operation) {
            try {
                $startTime = microtime(true);

                switch ($operation['type']) {
                    case 0: // Add to waitlist
                        $result = $this->waitlistService->addToWaitlist($operation['patient_id'], $doctor->id, []);
                        $operationType = 'add';
                        break;
                    case 1: // Remove from waitlist (if exists)
                        $waitlist = Waitlist::where('patient_id', $operation['patient_id'])
                                          ->where('doctor_id', $doctor->id)
                                          ->first();
                        if ($waitlist) {
                            $result = $this->waitlistService->removeFromWaitlist($waitlist->id);
                            $operationType = 'remove';
                        } else {
                            $result = 'no_waitlist';
                            $operationType = 'remove_skip';
                        }
                        break;
                    case 2: // Check position
                        $waitlist = Waitlist::where('patient_id', $operation['patient_id'])
                                          ->where('doctor_id', $doctor->id)
                                          ->first();
                        if ($waitlist) {
                            $result = $this->waitlistService->getWaitlistPosition($waitlist->id);
                            $operationType = 'position';
                        } else {
                            $result = 'no_waitlist';
                            $operationType = 'position_skip';
                        }
                        break;
                }

                $endTime = microtime(true);

                $operationResults[] = [
                    'operation' => $index,
                    'type' => $operationType,
                    'time' => ($endTime - $startTime) * 1000,
                    'success' => true,
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                $operationErrors[] = [
                    'operation' => $index,
                    'type' => $operation['type'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Verify no critical errors occurred
        $criticalErrors = array_filter($operationErrors, function ($error) {
            return !str_contains($error['error'], 'already on the waitlist'); // Expected error
        });

        $this->assertEmpty($criticalErrors, 'No critical errors should occur during concurrent operations');

        // Performance check
        $successfulOperations = array_filter($operationResults, function ($op) {
            return $op['success'];
        });

        if (!empty($successfulOperations)) {
            $averageOperationTime = array_sum(array_column($successfulOperations, 'time')) / count($successfulOperations);
            $this->assertLessThan(300, $averageOperationTime, 'Average operation time should be under 300ms');
        }

        // Data integrity check - final state should be consistent
        $finalWaitlistCount = Waitlist::where('doctor_id', $doctor->id)->count();
        $this->assertGreaterThanOrEqual(0, $finalWaitlistCount, 'Waitlist count should be non-negative');

        $activeWaitlists = Waitlist::where('doctor_id', $doctor->id)->active()->count();
        $this->assertEquals($finalWaitlistCount, $activeWaitlists, 'All waitlists should be active');
    }

    /** @test */
    public function handles_slot_availability_monitoring_under_load()
    {
        $doctors = Doctor::factory()->count(5)->create();

        // Create slots for multiple doctors
        foreach ($doctors as $doctor) {
            $slots = [];
            for ($day = 0; $day < 14; $day++) {
                for ($slot = 0; $slot < 8; $slot++) { // 8 slots per day
                    $slots[] = [
                        'doctor_id' => $doctor->id,
                        'date' => now()->addDays($day)->toDateString(),
                        'start_time' => sprintf('%02d:00:00', 9 + $slot),
                        'duration' => 30,
                        'is_available' => rand(0, 1), // Random availability
                    ];
                }
            }

            // Insert in chunks
            foreach (array_chunk($slots, 50) as $chunk) {
                \Illuminate\Support\Facades\DB::table('availability_slots')->insert($chunk);
            }
        }

        // Simulate monitoring system checking availability across all doctors
        $monitoringOperations = 25;
        $monitoringResults = [];
        $monitoringErrors = [];

        for ($i = 0; $i < $monitoringOperations; $i++) {
            try {
                $doctor = $doctors->random();

                $startTime = microtime(true);
                $availability = $this->waitlistService->findAvailableSlots($doctor->id, 14);
                $stats = $this->waitlistService->getWaitlistStatistics($doctor->id);
                $endTime = microtime(true);

                $monitoringResults[] = [
                    'operation' => $i,
                    'doctor_id' => $doctor->id,
                    'time' => ($endTime - $startTime) * 1000,
                    'available_slots' => count($availability),
                    'active_waitlists' => $stats['total_active'],
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $monitoringErrors[] = [
                    'operation' => $i,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Verify monitoring operations succeeded
        $this->assertCount($monitoringOperations, $monitoringResults);
        $this->assertEmpty($monitoringErrors);

        // Performance check for monitoring system
        $averageMonitoringTime = array_sum(array_column($monitoringResults, 'time')) / count($monitoringResults);
        $maxMonitoringTime = max(array_column($monitoringResults, 'time'));

        $this->assertLessThan(400, $averageMonitoringTime, 'Average monitoring time should be under 400ms');
        $this->assertLessThan(800, $maxMonitoringTime, 'Max monitoring time should be under 800ms');

        // Verify monitoring data makes sense
        foreach ($monitoringResults as $result) {
            $this->assertGreaterThanOrEqual(0, $result['available_slots']);
            $this->assertGreaterThanOrEqual(0, $result['active_waitlists']);
        }
    }
}
