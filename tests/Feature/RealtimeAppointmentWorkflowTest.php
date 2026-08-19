<?php

namespace Tests\Feature;

use App\Events\AppointmentStatusChangedEvent;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use App\Services\AppointmentBroadcastService;
use App\Services\PusherConnectionPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class RealtimeAppointmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $doctor;
    protected $appointment;
    protected $broadcastService;
    protected $capturedStatusEvents = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'patient']);
        $this->doctor = Doctor::factory()->create();
        $this->doctor->user = User::factory()->create(['role' => 'doctor']);
        $this->appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending'
        ]);

        $this->broadcastService = app(AppointmentBroadcastService::class);

        Event::listen(AppointmentStatusChangedEvent::class, function ($event) {
            $this->capturedStatusEvents[] = $event;
        });
    }

    protected function assertStatusEventFired(int $appointmentId, string $oldStatus, string $newStatus): void
    {
        foreach ($this->capturedStatusEvents as $event) {
            if (
                $event->appointment->id === $appointmentId &&
                $event->oldStatus === $oldStatus &&
                $event->newStatus === $newStatus
            ) {
                $this->assertTrue(true);
                return;
            }
        }

        $this->fail("AppointmentStatusChangedEvent not fired for appointment {$appointmentId}: {$oldStatus} -> {$newStatus}");
    }

    public function test_complete_appointment_status_change_workflow()
    {
        // Subscribe user to real-time updates
        $this->broadcastService->subscribeToAppointments($this->user);

        // Verify subscription is cached
        $subscription = Cache::get("appointment_sub_{$this->user->id}");
        $this->assertNotNull($subscription);
        $this->assertEquals($this->user->id, $subscription['user_id']);

        // Change appointment status
        $this->capturedStatusEvents = [];
        $result = $this->appointment->confirmAppointment();

        // Verify status change
        $this->assertTrue($result);
        $this->appointment->refresh();
        $this->assertEquals('confirmed', $this->appointment->status);

        // Verify event was fired
        $this->assertStatusEventFired($this->appointment->id, 'pending', 'confirmed');
    }

    public function test_realtime_appointment_creation_workflow()
    {
        // Mock the pusher pool so broadcasts succeed in test environment
        $pusherPool = Mockery::mock(PusherConnectionPool::class);
        $pusherPool->shouldReceive('broadcast')->andReturn(true);
        $this->app->instance(PusherConnectionPool::class, $pusherPool);
        $this->broadcastService = app(AppointmentBroadcastService::class);

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDay(),
            'appointment_type' => 'in_person',
            'duration' => 30,
            'status' => 'pending'
        ]);

        // Verify appointment was created
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals('pending', $appointment->status);

        // Check that creation event would be fired (in real implementation)
        // Note: The actual firing happens in observers, so we test the service directly
        $result = $this->broadcastService->broadcastAppointmentCreated($appointment);
        $this->assertTrue($result);
    }

    public function test_realtime_appointment_update_workflow()
    {
        // Mock the pusher pool so broadcasts succeed in test environment
        $pusherPool = Mockery::mock(PusherConnectionPool::class);
        $pusherPool->shouldReceive('broadcast')->andReturn(true);
        $this->app->instance(PusherConnectionPool::class, $pusherPool);
        $this->broadcastService = app(AppointmentBroadcastService::class);

        // Update appointment details
        $originalDate = $this->appointment->appointment_date;
        $newDate = now()->addDays(2);

        $this->appointment->appointment_date = $newDate;
        $this->appointment->save();

        // Broadcast the update
        $changedAttributes = ['appointment_date' => $originalDate];
        $result = $this->broadcastService->broadcastAppointmentUpdated($this->appointment, $changedAttributes);

        $this->assertTrue($result);
        $this->appointment->refresh();
        $this->assertEquals($newDate->format('Y-m-d H:i:s'), $this->appointment->appointment_date->format('Y-m-d H:i:s'));
    }

    public function test_realtime_appointment_cancellation_workflow()
    {
        $this->capturedStatusEvents = [];

        // Cancel the appointment
        $result = $this->appointment->cancelAppointment($this->user);

        $this->assertTrue($result);
        $this->appointment->refresh();
        $this->assertEquals('cancelled', $this->appointment->status);

        // Verify cancellation event was fired
        $this->assertStatusEventFired($this->appointment->id, 'pending', 'cancelled');
    }

    public function test_realtime_appointment_completion_workflow()
    {
        // First confirm the appointment
        $this->appointment->appointment_date = now()->subHour();
        $this->appointment->status = 'confirmed';
        $this->appointment->save();

        $this->capturedStatusEvents = [];

        // Complete the appointment
        $result = $this->appointment->completeAppointment();

        $this->assertTrue($result);
        $this->appointment->refresh();
        $this->assertEquals('completed', $this->appointment->status);

        // Verify completion event was fired
        $this->assertStatusEventFired($this->appointment->id, 'confirmed', 'completed');
    }

    public function test_multiple_users_realtime_subscription_workflow()
    {
        $doctor2 = Doctor::factory()->create();
        $doctor2->user = User::factory()->create(['role' => 'doctor']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Create multiple appointments
        $appointment1 = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'status' => 'pending'
        ]);

        $appointment2 = Appointment::factory()->create([
            'doctor_id' => $doctor2->id,
            'status' => 'pending'
        ]);

        // Subscribe multiple users
        $this->broadcastService->subscribeToAppointments($this->doctor->user);
        $this->broadcastService->subscribeToAppointments($doctor2->user);
        $this->broadcastService->subscribeToAppointments($admin);

        // Change status of first appointment
        $this->capturedStatusEvents = [];
        $appointment1->confirmAppointment();

        // Verify event was fired for the first appointment
        $this->assertStatusEventFired($appointment1->id, 'pending', 'confirmed');

        // Change status of second appointment
        $this->capturedStatusEvents = [];
        $appointment2->confirmAppointment();

        // Verify event was fired for the second appointment
        $this->assertStatusEventFired($appointment2->id, 'pending', 'confirmed');
    }

    public function test_realtime_appointment_list_update_workflow()
    {
        // Mock the pusher pool so broadcasts succeed in test environment
        $pusherPool = Mockery::mock(PusherConnectionPool::class);
        $pusherPool->shouldReceive('broadcast')->andReturn(true);
        $this->app->instance(PusherConnectionPool::class, $pusherPool);
        $this->broadcastService = app(AppointmentBroadcastService::class);

        $users = User::factory()->count(3)->create(['role' => 'patient']);

        // Subscribe users to appointment updates
        foreach ($users as $user) {
            $this->broadcastService->subscribeToAppointments($user);
        }

        // Create a new appointment
        $newAppointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'status' => 'pending'
        ]);

        // Broadcast list update to all subscribed users
        $userIds = $users->pluck('id')->toArray();
        $result = $this->broadcastService->broadcastAppointmentListUpdate($userIds);

        $this->assertTrue($result);
    }

    public function test_realtime_workflow_with_guest_appointments()
    {
        // Create appointment for guest patient
        $guestAppointment = Appointment::factory()->create([
            'patient_id' => null,
            'guest_name' => 'John Doe',
            'guest_email' => 'john@example.com',
            'doctor_id' => $this->doctor->id,
            'status' => 'pending'
        ]);

        // Confirm guest appointment
        $this->capturedStatusEvents = [];
        $guestAppointment->status = 'confirmed';
        $guestAppointment->save();

        // Verify event was dispatched
        $this->assertStatusEventFired($guestAppointment->id, 'pending', 'confirmed');
    }

    public function test_realtime_workflow_error_handling()
    {
        // Build an appointment with invalid date data (bypassing the date cast)
        $invalidAppointment = new Appointment();
        $invalidAppointment->setRawAttributes([
            'appointment_date' => 'invalid-date',
            'status' => 'pending'
        ]);

        // Try to broadcast with invalid appointment
        $result = $this->broadcastService->broadcastAppointmentCreated($invalidAppointment);

        // Should handle gracefully (may return false or throw exception that's caught)
        $this->assertIsBool($result);
    }

    public function test_realtime_workflow_subscription_cleanup()
    {
        // Subscribe user
        $this->broadcastService->subscribeToAppointments($this->user);

        // Verify subscription exists
        $subscription = Cache::get("appointment_sub_{$this->user->id}");
        $this->assertNotNull($subscription);

        // Unsubscribe user
        $this->broadcastService->unsubscribeFromAppointments($this->user);

        // Verify subscription is removed
        $subscription = Cache::get("appointment_sub_{$this->user->id}");
        $this->assertNull($subscription);
    }

    public function test_realtime_workflow_concurrent_status_changes()
    {
        $this->capturedStatusEvents = [];

        // Simulate concurrent status changes
        $appointment1 = Appointment::factory()->create(['status' => 'pending']);
        $appointment2 = Appointment::factory()->create(['status' => 'pending']);
        $appointment3 = Appointment::factory()->create(['status' => 'pending']);

        // Change multiple appointments
        $appointment1->confirmAppointment();
        $appointment2->confirmAppointment();
        $appointment3->cancelAppointment();

        // Verify all events were fired
        $this->assertStatusEventFired($appointment1->id, 'pending', 'confirmed');
        $this->assertStatusEventFired($appointment2->id, 'pending', 'confirmed');
        $this->assertStatusEventFired($appointment3->id, 'pending', 'cancelled');
    }

    public function test_realtime_workflow_with_different_user_roles()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hospitalAdmin = User::factory()->create(['role' => 'hospital_admin']);
        $doctorUser = User::factory()->create(['role' => 'doctor']);

        // Subscribe different role users
        $this->broadcastService->subscribeToAppointments($admin);
        $this->broadcastService->subscribeToAppointments($hospitalAdmin);
        $this->broadcastService->subscribeToAppointments($doctorUser);

        // Change appointment status
        $this->capturedStatusEvents = [];
        $this->appointment->confirmAppointment();

        // Verify event was fired (clinic staff should receive updates)
        $this->assertStatusEventFired($this->appointment->id, 'pending', 'confirmed');
    }

    public function test_realtime_workflow_performance_metrics()
    {
        // Subscribe user
        $this->broadcastService->subscribeToAppointments($this->user);

        // Get initial stats
        $initialStats = $this->broadcastService->getSubscriptionStats();
        $this->assertEquals(1, $initialStats['total_active_subscriptions']);

        // Change appointment status multiple times
        for ($i = 0; $i < 5; $i++) {
            $this->appointment->status = $i % 2 === 0 ? 'confirmed' : 'pending';
            $this->appointment->save();
        }

        // Verify stats are still accurate
        $finalStats = $this->broadcastService->getSubscriptionStats();
        $this->assertEquals(1, $finalStats['total_active_subscriptions']);
    }
}
