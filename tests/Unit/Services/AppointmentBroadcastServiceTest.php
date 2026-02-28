<?php

namespace Tests\Unit\Services;

use App\Services\AppointmentBroadcastService;
use App\Services\PusherConnectionPool;
use App\Services\PayloadCompressionService;
use App\Services\RealtimePerformanceMonitoringService;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;
use Mockery;

class AppointmentBroadcastServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $broadcastService;
    protected $pusherPoolMock;
    protected $compressionServiceMock;
    protected $performanceServiceMock;
    protected $user;
    protected $doctor;
    protected $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->pusherPoolMock = Mockery::mock(PusherConnectionPool::class);
        $this->compressionServiceMock = Mockery::mock(PayloadCompressionService::class);
        $this->performanceServiceMock = Mockery::mock(RealtimePerformanceMonitoringService::class);

        // Create the service with mocks
        $this->broadcastService = new AppointmentBroadcastService(
            $this->pusherPoolMock,
            $this->compressionServiceMock,
            $this->performanceServiceMock
        );

        // Create test data
        $this->user = User::factory()->create(['role' => 'patient']);
        $this->doctor = Doctor::factory()->create();
        $this->doctor->user = User::factory()->create(['role' => 'doctor']);
        $this->appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending'
        ]);

        // Clear rate limiter for tests
        RateLimiter::clear('broadcast:*');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_broadcast_service_can_be_instantiated()
    {
        $this->assertInstanceOf(AppointmentBroadcastService::class, $this->broadcastService);
    }

    public function test_broadcast_status_change_success()
    {
        // Setup mocks
        $this->performanceServiceMock->shouldReceive('recordBroadcastMetrics')
            ->once()
            ->with(Mockery::on(function ($metrics) {
                return $metrics['success'] === true &&
                       isset($metrics['latency']) &&
                       $metrics['compressed'] === true;
            }));

        // Mock rate limiter to allow broadcast
        RateLimiter::shouldReceive('tooManyAttempts')
            ->andReturn(false);
        RateLimiter::shouldReceive('hit')
            ->andReturn(null);

        $result = $this->broadcastService->broadcastStatusChange(
            $this->appointment,
            'pending',
            'confirmed'
        );

        $this->assertTrue($result);
    }

    public function test_broadcast_status_change_rate_limited()
    {
        // Setup rate limiter to block broadcasts
        RateLimiter::shouldReceive('tooManyAttempts')
            ->andReturn(true);
        RateLimiter::shouldReceive('availableIn')
            ->andReturn(30);

        $this->performanceServiceMock->shouldReceive('recordBroadcastMetrics')
            ->once()
            ->with(Mockery::on(function ($metrics) {
                return $metrics['success'] === false;
            }));

        $result = $this->broadcastService->broadcastStatusChange(
            $this->appointment,
            'pending',
            'confirmed'
        );

        $this->assertFalse($result);
    }

    public function test_subscribe_to_appointments()
    {
        Cache::shouldReceive('put')
            ->once()
            ->with(
                "appointment_sub_{$this->user->id}",
                Mockery::on(function ($subscription) {
                    return $subscription['user_id'] === $this->user->id &&
                           isset($subscription['subscribed_at']);
                }),
                3600
            );

        $result = $this->broadcastService->subscribeToAppointments($this->user);

        $this->assertTrue($result);
    }

    public function test_unsubscribe_from_appointments()
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with("appointment_sub_{$this->user->id}");

        $result = $this->broadcastService->unsubscribeFromAppointments($this->user);

        $this->assertTrue($result);
    }

    public function test_get_todays_appointments_for_doctor()
    {
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create();
        $doctor->user = $doctorUser;

        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => today()->setTime(10, 0),
            'status' => 'confirmed'
        ]);

        $result = $this->broadcastService->getTodaysAppointments($doctorUser);

        $this->assertArrayHasKey('appointments', $result);
        $this->assertArrayHasKey('subscription_channels', $result);
        $this->assertCount(1, $result['appointments']);
    }

    public function test_get_todays_appointments_for_patient()
    {
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'appointment_date' => today()->setTime(14, 0),
            'status' => 'confirmed'
        ]);

        $result = $this->broadcastService->getTodaysAppointments($this->user);

        $this->assertArrayHasKey('appointments', $result);
        $this->assertArrayHasKey('subscription_channels', $result);
    }

    public function test_broadcast_appointment_list_update()
    {
        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->andReturn(true);

        $result = $this->broadcastService->broadcastAppointmentListUpdate([$this->user->id]);

        $this->assertTrue($result);
    }

    public function test_broadcast_appointment_created()
    {
        $this->compressionServiceMock->shouldReceive('compressAppointmentData')
            ->once()
            ->andReturn('compressed_data');

        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->andReturn(true);

        $result = $this->broadcastService->broadcastAppointmentCreated($this->appointment);

        $this->assertTrue($result);
    }

    public function test_broadcast_appointment_updated()
    {
        $this->compressionServiceMock->shouldReceive('compressAppointmentData')
            ->once()
            ->andReturn('compressed_data');

        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->andReturn(true);

        $result = $this->broadcastService->broadcastAppointmentUpdated(
            $this->appointment,
            ['status' => 'confirmed']
        );

        $this->assertTrue($result);
    }

    public function test_broadcast_appointment_deleted()
    {
        $this->compressionServiceMock->shouldReceive('compress')
            ->once()
            ->andReturn('compressed_data');

        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->andReturn(true);

        $result = $this->broadcastService->broadcastAppointmentDeleted($this->appointment);

        $this->assertTrue($result);
    }

    public function test_get_user_subscription_channels_for_doctor()
    {
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create();
        $doctor->user = $doctorUser;

        $reflection = new \ReflectionClass($this->broadcastService);
        $method = $reflection->getMethod('getUserSubscriptionChannels');
        $method->setAccessible(true);

        $channels = $method->invoke($this->broadcastService, $doctorUser);

        $this->assertContains("user.{$doctorUser->id}", $channels);
        $this->assertContains("App.User.{$doctorUser->id}", $channels);
    }

    public function test_get_user_subscription_channels_for_admin()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);

        $reflection = new \ReflectionClass($this->broadcastService);
        $method = $reflection->getMethod('getUserSubscriptionChannels');
        $method->setAccessible(true);

        $channels = $method->invoke($this->broadcastService, $adminUser);

        $this->assertContains("user.{$adminUser->id}", $channels);
        $this->assertContains("App.User.{$adminUser->id}", $channels);
        $this->assertContains("admin", $channels);
    }

    public function test_cleanup_inactive_subscriptions()
    {
        // Mock cache operations
        Cache::shouldReceive('get')
            ->with('appointment_subscriptions')
            ->andReturn([
                'sub1' => ['last_activity' => now()->subHours(25)],
                'sub2' => ['last_activity' => now()->subHours(1)]
            ]);

        Cache::shouldReceive('forget')
            ->once()
            ->with('sub1');

        Cache::shouldReceive('put')
            ->once()
            ->with('appointment_subscriptions', ['sub2' => ['last_activity' => now()->subHours(1)]]);

        $cleaned = $this->broadcastService->cleanupInactiveSubscriptions(24);

        $this->assertEquals(1, $cleaned);
    }

    public function test_get_subscription_stats()
    {
        Cache::shouldReceive('get')
            ->with('appointment_subscriptions')
            ->andReturn(['sub1' => [], 'sub2' => []]);

        $stats = $this->broadcastService->getSubscriptionStats();

        $this->assertArrayHasKey('total_active_subscriptions', $stats);
        $this->assertArrayHasKey('cache_ttl', $stats);
        $this->assertEquals(2, $stats['total_active_subscriptions']);
        $this->assertEquals(3600, $stats['cache_ttl']);
    }

    public function test_format_appointment_data()
    {
        $reflection = new \ReflectionClass($this->broadcastService);
        $method = $reflection->getMethod('formatAppointmentData');
        $method->setAccessible(true);

        $formatted = $method->invoke($this->broadcastService, $this->appointment);

        $this->assertArrayHasKey('id', $formatted);
        $this->assertArrayHasKey('appointment_number', $formatted);
        $this->assertArrayHasKey('appointment_date', $formatted);
        $this->assertArrayHasKey('status', $formatted);
        $this->assertArrayHasKey('doctor', $formatted);
        $this->assertArrayHasKey('patient', $formatted);
        $this->assertEquals($this->appointment->id, $formatted['id']);
        $this->assertEquals($this->appointment->status, $formatted['status']);
    }

    public function test_get_appointment_channels()
    {
        $reflection = new \ReflectionClass($this->broadcastService);
        $method = $reflection->getMethod('getAppointmentChannels');
        $method->setAccessible(true);

        $channels = $method->invoke($this->broadcastService, $this->appointment);

        $this->assertContains("doctor.{$this->appointment->doctor->id}", $channels);
        $this->assertContains("admin.appointments", $channels);
        $this->assertContains("appointments." . $this->appointment->appointment_date->format('Y-m-d'), $channels);
    }

    public function test_broadcast_status_change_with_multi_device_sync()
    {
        // Mock the multi-device sync service
        $this->mock(\App\Services\MultiDeviceSynchronizationService::class, function ($mock) {
            $mock->shouldReceive('handleMultiDeviceAppointmentUpdate')
                ->once()
                ->andReturn(['sync_status' => 'success']);
        });

        $this->performanceServiceMock->shouldReceive('recordBroadcastMetrics')
            ->once();

        // Mock request to simulate device ID
        $this->mock(\Illuminate\Http\Request::class, function ($mock) {
            $mock->shouldReceive('header')
                ->with('X-Device-ID')
                ->andReturn('test_device_123');
        });

        RateLimiter::shouldReceive('tooManyAttempts')->andReturn(false);
        RateLimiter::shouldReceive('hit')->andReturn(null);

        $result = $this->broadcastService->broadcastStatusChange(
            $this->appointment,
            'pending',
            'confirmed',
            $this->user
        );

        $this->assertTrue($result);
    }

    public function test_broadcast_handles_exceptions_gracefully()
    {
        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->andThrow(new \Exception('Broadcast failed'));

        $this->compressionServiceMock->shouldReceive('compressAppointmentData')
            ->andReturn('compressed_data');

        $result = $this->broadcastService->broadcastAppointmentCreated($this->appointment);

        $this->assertFalse($result);
    }

    public function test_rate_limiting_burst_protection()
    {
        // Simulate burst rate limiting
        RateLimiter::shouldReceive('tooManyAttempts')
            ->with('burst:status_change')
            ->andReturn(true);

        $this->performanceServiceMock->shouldReceive('recordBroadcastMetrics')
            ->once()
            ->with(Mockery::on(function ($metrics) {
                return $metrics['success'] === false;
            }));

        $result = $this->broadcastService->broadcastStatusChange(
            $this->appointment,
            'pending',
            'confirmed'
        );

        $this->assertFalse($result);
    }

    public function test_update_user_activity()
    {
        $subscription = [
            'user_id' => $this->user->id,
            'last_activity' => now()->subMinutes(5)
        ];

        Cache::shouldReceive('get')
            ->with("appointment_sub_{$this->user->id}")
            ->andReturn($subscription);

        Cache::shouldReceive('put')
            ->once()
            ->with(
                "appointment_sub_{$this->user->id}",
                Mockery::on(function ($updatedSubscription) {
                    return $updatedSubscription['last_activity'] > now()->subMinutes(1);
                }),
                3600
            );

        $this->broadcastService->updateUserActivity($this->user);
    }
}
