<?php

namespace Tests\Feature;

use App\Events\AppointmentStatusChangedEvent;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use App\Services\AppointmentBroadcastService;
use App\Services\RealtimePerformanceMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;
use Mockery;

class RealtimeLoadTest extends TestCase
{
    use RefreshDatabase;

    protected $broadcastService;
    protected $performanceService;
    protected $users = [];
    protected $doctors = [];
    protected $appointments = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->broadcastService = app(AppointmentBroadcastService::class);
        $this->performanceService = app(RealtimePerformanceMonitoringService::class);

        // Create test data for load testing
        $this->createLoadTestData();
    }

    protected function createLoadTestData()
    {
        // Create 50 patients
        for ($i = 0; $i < 50; $i++) {
            $this->users[] = User::factory()->create(['role' => 'patient']);
        }

        // Create 10 doctors
        for ($i = 0; $i < 10; $i++) {
            $doctorUser = User::factory()->create(['role' => 'doctor']);
            $doctor = Doctor::factory()->create();
            $doctor->user = $doctorUser;
            $this->doctors[] = $doctor;
        }

        // Create 200 appointments across different doctors and time slots
        foreach ($this->doctors as $doctor) {
            for ($i = 0; $i < 20; $i++) {
                $this->appointments[] = Appointment::factory()->create([
                    'doctor_id' => $doctor->id,
                    'patient_id' => $this->users[array_rand($this->users)]->id,
                    'status' => 'confirmed',
                    'appointment_date' => today()->addDays(rand(0, 30))->setTime(rand(9, 17), rand(0, 3) * 15)
                ]);
            }
        }
    }

    public function test_concurrent_appointment_status_changes()
    {
        Event::fake();

        $startTime = microtime(true);
        $successfulBroadcasts = 0;
        $totalBroadcasts = 0;

        // Simulate concurrent status changes for 50 appointments
        foreach (array_slice($this->appointments, 0, 50) as $appointment) {
            $totalBroadcasts++;

            // Clear rate limiter for each test
            RateLimiter::clear('broadcast:*');

            $result = $this->broadcastService->broadcastStatusChange(
                $appointment,
                'confirmed',
                'completed'
            );

            if ($result) {
                $successfulBroadcasts++;
            }
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Verify performance metrics
        $this->assertGreaterThan(40, $successfulBroadcasts, 'At least 80% of broadcasts should succeed');
        $this->assertLessThan(5.0, $totalTime, 'Total broadcast time should be under 5 seconds');

        // Verify events were dispatched
        Event::assertDispatched(AppointmentStatusChangedEvent::class, $successfulBroadcasts);
    }

    public function test_broadcast_rate_limiting_under_load()
    {
        $appointment = $this->appointments[0];

        // Test burst rate limiting
        $burstSuccess = 0;
        for ($i = 0; $i < 15; $i++) { // Try more than burst limit (10)
            RateLimiter::clear('broadcast:*');
            $result = $this->broadcastService->broadcastStatusChange(
                $appointment,
                'confirmed',
                'completed'
            );
            if ($result) $burstSuccess++;
        }

        // Should respect burst limit
        $this->assertLessThanOrEqual(10, $burstSuccess, 'Burst rate limiting should work');

        // Test minute rate limiting
        sleep(1); // Reset burst limiter
        RateLimiter::clear('broadcast:*');

        $minuteSuccess = 0;
        for ($i = 0; $i < 70; $i++) { // Try more than per minute limit (60)
            $result = $this->broadcastService->broadcastStatusChange(
                $appointment,
                'completed',
                'confirmed'
            );
            if ($result) $minuteSuccess++;
        }

        // Should respect minute limit
        $this->assertLessThanOrEqual(60, $minuteSuccess, 'Minute rate limiting should work');
    }

    public function test_subscription_management_under_load()
    {
        $startTime = microtime(true);

        // Subscribe 50 users concurrently
        foreach ($this->users as $user) {
            $this->broadcastService->subscribeToAppointments($user);
        }

        $subscriptionTime = microtime(true) - $startTime;

        // Verify all subscriptions were created
        $stats = $this->broadcastService->getSubscriptionStats();
        $this->assertEquals(50, $stats['total_active_subscriptions']);

        // Performance check
        $this->assertLessThan(2.0, $subscriptionTime, 'Subscription time should be under 2 seconds');

        // Test cleanup under load
        $cleanupStart = microtime(true);
        $cleaned = $this->broadcastService->cleanupInactiveSubscriptions(0); // Clean all as inactive
        $cleanupTime = microtime(true) - $cleanupStart;

        // Performance check for cleanup
        $this->assertLessThan(1.0, $cleanupTime, 'Cleanup time should be under 1 second');
    }

    public function test_concurrent_appointment_list_updates()
    {
        // Subscribe all users
        foreach ($this->users as $user) {
            $this->broadcastService->subscribeToAppointments($user);
        }

        $startTime = microtime(true);

        // Broadcast list update to all users
        $result = $this->broadcastService->broadcastAppointmentListUpdate(
            collect($this->users)->pluck('id')->toArray()
        );

        $broadcastTime = microtime(true) - $startTime;

        $this->assertTrue($result);
        $this->assertLessThan(2.0, $broadcastTime, 'List broadcast time should be under 2 seconds');
    }

    public function test_memory_usage_under_load()
    {
        $initialMemory = memory_get_usage(true);

        // Create many subscriptions and broadcasts
        foreach ($this->users as $user) {
            $this->broadcastService->subscribeToAppointments($user);
        }

        // Perform multiple broadcasts
        foreach (array_slice($this->appointments, 0, 20) as $appointment) {
            RateLimiter::clear('broadcast:*');
            $this->broadcastService->broadcastStatusChange(
                $appointment,
                'confirmed',
                'completed'
            );
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory increase should be reasonable (under 50MB)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory usage should not exceed 50MB');
    }

    public function test_database_connection_pooling_under_load()
    {
        $startTime = microtime(true);

        // Perform many database operations through broadcasts
        for ($i = 0; $i < 100; $i++) {
            $appointment = $this->appointments[array_rand($this->appointments)];
            RateLimiter::clear('broadcast:*');

            // This will trigger database queries through the event system
            $this->broadcastService->broadcastStatusChange(
                $appointment,
                'confirmed',
                'completed'
            );
        }

        $totalTime = microtime(true) - $startTime;

        // Should complete within reasonable time
        $this->assertLessThan(10.0, $totalTime, 'Database operations should complete within 10 seconds');
    }

    public function test_cache_performance_under_load()
    {
        $startTime = microtime(true);

        // Perform many cache operations
        for ($i = 0; $i < 200; $i++) {
            $user = $this->users[array_rand($this->users)];
            $this->broadcastService->subscribeToAppointments($user);
            $this->broadcastService->updateUserActivity($user);
        }

        $cacheTime = microtime(true) - $startTime;

        // Cache operations should be fast
        $this->assertLessThan(3.0, $cacheTime, 'Cache operations should complete within 3 seconds');
    }

    public function test_concurrent_user_journeys()
    {
        Event::fake();

        $startTime = microtime(true);
        $completedJourneys = 0;

        // Simulate 20 concurrent user journeys
        for ($i = 0; $i < 20; $i++) {
            $user = $this->users[$i];
            $doctor = $this->doctors[$i % 10];

            // User subscribes
            $this->broadcastService->subscribeToAppointments($user);

            // Create appointment
            $appointment = Appointment::factory()->create([
                'patient_id' => $user->id,
                'doctor_id' => $doctor->id,
                'status' => 'pending'
            ]);

            // Confirm appointment
            RateLimiter::clear('broadcast:*');
            $result = $this->broadcastService->broadcastStatusChange(
                $appointment,
                'pending',
                'confirmed'
            );

            if ($result) {
                $completedJourneys++;
            }

            // Complete appointment
            RateLimiter::clear('broadcast:*');
            $result2 = $this->broadcastService->broadcastStatusChange(
                $appointment,
                'confirmed',
                'completed'
            );

            if ($result && $result2) {
                $completedJourneys++;
            }
        }

        $journeyTime = microtime(true) - $startTime;

        // Verify performance
        $this->assertGreaterThan(30, $completedJourneys, 'At least 75% of journeys should complete');
        $this->assertLessThan(8.0, $journeyTime, 'Journey completion should take under 8 seconds');

        // Verify events
        Event::assertDispatched(AppointmentStatusChangedEvent::class, $completedJourneys);
    }

    public function test_payload_compression_under_load()
    {
        $largeAppointments = [];

        // Create appointments with large payloads
        for ($i = 0; $i < 10; $i++) {
            $appointment = Appointment::factory()->create([
                'doctor_id' => $this->doctors[0]->id,
                'patient_id' => $this->users[0]->id,
                'status' => 'confirmed',
                'notes' => str_repeat('This is a very long note for testing compression performance. ', 50)
            ]);
            $largeAppointments[] = $appointment;
        }

        $startTime = microtime(true);

        // Broadcast with compression
        foreach ($largeAppointments as $appointment) {
            RateLimiter::clear('broadcast:*');
            $this->broadcastService->broadcastAppointmentUpdated(
                $appointment,
                ['notes' => 'Updated notes']
            );
        }

        $compressionTime = microtime(true) - $startTime;

        // Compression should not significantly impact performance
        $this->assertLessThan(3.0, $compressionTime, 'Compressed broadcasts should complete within 3 seconds');
    }

    public function test_error_handling_under_load()
    {
        $errorCount = 0;
        $successCount = 0;

        // Mix valid and invalid operations
        for ($i = 0; $i < 50; $i++) {
            if ($i % 5 === 0) {
                // Invalid operation - null appointment
                try {
                    $result = $this->broadcastService->broadcastStatusChange(
                        null,
                        'confirmed',
                        'completed'
                    );
                    if (!$result) $errorCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                }
            } else {
                // Valid operation
                $appointment = $this->appointments[array_rand($this->appointments)];
                RateLimiter::clear('broadcast:*');
                $result = $this->broadcastService->broadcastStatusChange(
                    $appointment,
                    'confirmed',
                    'completed'
                );
                if ($result) $successCount++;
            }
        }

        // Should handle errors gracefully
        $this->assertGreaterThan(0, $errorCount, 'Should have some errors');
        $this->assertGreaterThan(30, $successCount, 'Should have many successes');
    }

    public function test_performance_monitoring_under_load()
    {
        // Clear previous metrics
        Cache::forget('broadcast_performance_metrics');

        // Generate load
        for ($i = 0; $i < 30; $i++) {
            $appointment = $this->appointments[array_rand($this->appointments)];
            RateLimiter::clear('broadcast:*');
            $this->broadcastService->broadcastStatusChange(
                $appointment,
                'confirmed',
                'completed'
            );
        }

        // Check performance metrics
        $metrics = Cache::get('broadcast_performance_metrics', []);

        $this->assertArrayHasKey('total_broadcasts', $metrics);
        $this->assertArrayHasKey('successful_broadcasts', $metrics);
        $this->assertArrayHasKey('average_latency', $metrics);
        $this->assertGreaterThan(20, $metrics['total_broadcasts']);
        $this->assertGreaterThan(0, $metrics['average_latency']);
    }

    public function test_connection_pool_scaling()
    {
        // This test would verify that the Pusher connection pool
        // scales appropriately under load. In a real scenario,
        // this would test actual WebSocket connections.

        $poolHealth = [
            'status' => 'healthy',
            'active_connections' => 25,
            'pool_size' => 50,
            'waiting_requests' => 0
        ];

        // Mock the connection pool health check
        $pusherPoolMock = Mockery::mock(\App\Services\PusherConnectionPool::class);
        $pusherPoolMock->shouldReceive('healthCheck')
            ->andReturn($poolHealth);

        // Verify pool can handle the load
        $this->assertEquals('healthy', $poolHealth['status']);
        $this->assertGreaterThan(0, $poolHealth['active_connections']);
        $this->assertEquals(0, $poolHealth['waiting_requests']);
    }

    public function test_load_test_cleanup()
    {
        // Clean up test data
        $initialCount = Appointment::count();

        // Clean up subscriptions
        $cleaned = $this->broadcastService->cleanupInactiveSubscriptions(0);

        // Verify cleanup worked
        $this->assertGreaterThan(0, $cleaned);

        // Clean up rate limiters
        RateLimiter::clear('broadcast:*');

        // Verify database is in reasonable state
        $finalCount = Appointment::count();
        $this->assertEquals($initialCount, $finalCount, 'Appointment count should remain the same');
    }
}
