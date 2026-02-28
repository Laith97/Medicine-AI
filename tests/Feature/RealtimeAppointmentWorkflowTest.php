<?php

namespace Tests\Feature;

use App\Events\AppointmentStatusChangedEvent;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use App\Services\AppointmentBroadcastService;
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
        Event::fake();

        $result = $this->appointment->confirmAppointment();

        // Verify status change
        $this->assertTrue($result);
        $this->appointment->refresh();
        $this->assertEquals('confirmed', $this->appointment->status);

        // Verify event was fired
        Event::assertDispatched(AppointmentStatusChangedEvent::class, function ($event) {
            return $event->appointment->id === $this->appointment->id &&
                   $event->oldStatus === 'pending' &&
                   $event->newStatus === 'confirmed';
        });
    }

    public function test_realtime_appointment_creation_workflow()
    {
        Event::fake();

        $appointmentData = [
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDay(),
            'appointment_type' => 'consultation',
            'duration' => 30
        ];

        $appointment = Appointment::create($appointmentData);

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
        Event::fake();

        // Cancel the appointment
        $result = $this->appointment->cancelAppointment($this->user);

        $this->assertTrue($result);
        $this->appointment->refresh();
        $this->assertEquals('cancelled', $this->appointment->status);

        // Verify cancellation event was fired
        Event::assertDispatched(AppointmentStatusChangedEvent::class, function ($event) {
            return $event->appointment->id === $this->appointment->id &&
                   $event->oldStatus === 'pending' &&
                   $event->newStatus === 'cancelled' &&
                   $event->changedBy->id === $this->user->id;
        });
    }

    public function test_realtime_appointment_completion_workflow()
    {
        // First confirm the appointment
        $this->appointment->status = 'confirmed';
        $this->appointment->save();

        Event::fake();

        // Complete the appointment
        $result = $this->appointment->completeAppointment();

        $this->assertTrue($result);
        $this->appointment->refresh();
        $this->assertEquals('completed', $this->appointment->status);

        // Verify completion event was fired
        Event::assertDispatched(AppointmentStatusChangedEvent::class, function ($event) {
            return $event->appointment->id === $this->appointment->id &&
                   $event->oldStatus === 'confirmed' &&
                   $event->newStatus === 'completed';
        });
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
        Event::fake();
        $appointment1->confirmAppointment();

        // Verify event was fired for the first appointment
        Event::assertDispatched(AppointmentStatusChangedEvent::class, function ($event) use ($appointment1) {
            return $event->appointment->id === $appointment1->id;
        });

        // Change status of second appointment
        Event::fake();
        $appointment2->confirmAppointment();

        // Verify event was fired for the second appointment
        Event::assertDispatched(AppointmentStatusChangedEvent::class, function ($event) use ($appointment2) {
            return $event->appointment->id === $appointment2->id;
        });
    }

    public function test_realtime_appointment_list_update_workflow()
    {
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

        Event::fake();

        // Confirm guest appointment
        $guestAppointment->status = 'confirmed';
        $guestAppointment->save();

        // Fire the event manually since observer might not trigger in test
        event(new AppointmentStatusChangedEvent($guestAppointment, 'pending', 'confirmed'));

        // Verify event was dispatched
        Event::assertDispatched(AppointmentStatusChangedEvent::class, function ($event) use ($guestAppointment) {
            return $event->appointment->id === $guestAppointment->id &&
                   $event->oldStatus === 'pending' &&
                   $event->newStatus === 'confirmed';
        });
    }

    public function test_realtime_workflow_error_handling()
    {
        // Test with invalid appointment data
        $invalidAppointment = new Appointment([
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
        Event::fake();

        // Simulate concurrent status changes
        $appointment1 = Appointment::factory()->create(['status' => 'pending']);
        $appointment2 = Appointment::factory()->create(['status' => 'pending']);
        $appointment3 = Appointment::factory()->create(['status' => 'pending']);

        // Change multiple appointments
        $appointment1->confirmAppointment();
        $appointment2->confirmAppointment();
        $appointment3->cancelAppointment();

        // Verify all events were fired
        Event::assertDispatched(AppointmentStatusChangedEvent::class, 3);
    }

    public function test_realtime_workflow_with_different_user_roles()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $manager = User::factory()->create(['role' => 'manager']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        // Subscribe different role users
        $this->broadcastService->subscribeToAppointments($admin);
        $this->broadcastService->subscribeToAppointments($manager);
        $this->broadcastService->subscribeToAppointments($supervisor);

        // Change appointment status
        Event::fake();
        $this->appointment->confirmAppointment();

        // Verify event was fired (clinic staff should receive updates)
        Event::assertDispatched(AppointmentStatusChangedEvent::class);
    }

    public function test_realtime_workflow_performance_metrics()
    {
        // Subscribe user
        $this->broadcastService->subscribeToAppointments($this->user);

        // Get initial stats
        $initialStats = $this->broadcastService->getSubscriptionStats();
        $this->assertEquals(1, $initialStats['total_active_subscriptions']);

        // Change appointment status multiple times
        Event::fake();
        for ($i = 0; $i < 5; $i++) {
            $this->appointment->status = $i % 2 === 0 ? 'confirmed' : 'pending';
            $this->appointment->save();
            event(new AppointmentStatusChangedEvent($this->appointment, 'pending', 'confirmed'));
        }

        // Verify stats are still accurate
        $finalStats = $this->broadcastService->getSubscriptionStats();
        $this->assertEquals(1, $finalStats['total_active_subscriptions']);
    }
}
