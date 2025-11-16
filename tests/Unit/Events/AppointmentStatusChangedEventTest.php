<?php

namespace Tests\Unit\Events;

use App\Events\AppointmentStatusChangedEvent;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentStatusChangedEventTest extends TestCase
{
    use RefreshDatabase;

    protected $appointment;
    protected $user;
    protected $doctor;

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
    }

    public function test_event_can_be_created()
    {
        $event = new AppointmentStatusChangedEvent(
            $this->appointment,
            'pending',
            'confirmed',
            $this->user
        );

        $this->assertInstanceOf(AppointmentStatusChangedEvent::class, $event);
        $this->assertEquals($this->appointment->id, $event->appointment->id);
        $this->assertEquals('pending', $event->oldStatus);
        $this->assertEquals('confirmed', $event->newStatus);
        $this->assertEquals($this->user->id, $event->changedBy->id);
    }

    public function test_event_broadcast_on_channels()
    {
        $event = new AppointmentStatusChangedEvent(
            $this->appointment,
            'pending',
            'confirmed'
        );

        $channels = $event->broadcastOn();

        $this->assertContains("doctor.{$this->doctor->id}", $channels);
        $this->assertContains("App.User.{$this->doctor->id}", $channels);
        $this->assertContains("App.User.{$this->user->id}", $channels);
        $this->assertContains('admin', $channels);
        $this->assertContains('clinic-staff', $channels);
        $this->assertContains("appointment.{$this->appointment->id}", $channels);
    }

    public function test_event_broadcast_as()
    {
        $event = new AppointmentStatusChangedEvent(
            $this->appointment,
            'pending',
            'confirmed'
        );

        $this->assertEquals('appointment.status-changed', $event->broadcastAs());
    }

    public function test_event_broadcast_with_data()
    {
        $event = new AppointmentStatusChangedEvent(
            $this->appointment,
            'pending',
            'confirmed',
            $this->user
        );

        $data = $event->broadcastWith();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertEquals($this->appointment->id, $data['id']);
        $this->assertEquals('appointment_status_changed', $data['type']);
        $this->assertEquals('Appointment Confirmed', $data['title']);
    }

    public function test_event_broadcast_with_different_statuses()
    {
        $statuses = [
            'confirmed' => 'Appointment Confirmed',
            'cancelled' => 'Appointment Cancelled',
            'completed' => 'Appointment Completed',
            'no_show' => 'Appointment No-Show',
            'pending' => 'Appointment Status Updated'
        ];

        foreach ($statuses as $status => $expectedTitle) {
            $event = new AppointmentStatusChangedEvent(
                $this->appointment,
                'pending',
                $status
            );

            $data = $event->broadcastWith();
            $this->assertEquals($expectedTitle, $data['title']);
        }
    }

    public function test_event_broadcast_with_icons()
    {
        $statusIcons = [
            'confirmed' => 'calendar-check',
            'cancelled' => 'calendar-times',
            'completed' => 'check-circle',
            'no_show' => 'user-times',
            'pending' => 'clock'
        ];

        foreach ($statusIcons as $status => $expectedIcon) {
            $event = new AppointmentStatusChangedEvent(
                $this->appointment,
                'pending',
                $status
            );

            $data = $event->broadcastWith();
            $this->assertEquals($expectedIcon, $data['icon']);
        }
    }

    public function test_event_broadcast_without_doctor()
    {
        // Create appointment without doctor
        $appointmentNoDoctor = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => null,
            'status' => 'pending'
        ]);

        $event = new AppointmentStatusChangedEvent(
            $appointmentNoDoctor,
            'pending',
            'confirmed'
        );

        $channels = $event->broadcastOn();

        // Should not include doctor channels
        $this->assertNotContains("doctor.{$this->doctor->id}", $channels);
        $this->assertNotContains("App.User.{$this->doctor->id}", $channels);
        // But should still include other channels
        $this->assertContains("App.User.{$this->user->id}", $channels);
        $this->assertContains('admin', $channels);
    }

    public function test_event_broadcast_without_patient()
    {
        // Create appointment without registered patient (guest)
        $appointmentGuest = Appointment::factory()->create([
            'patient_id' => null,
            'guest_name' => 'John Doe',
            'doctor_id' => $this->doctor->id,
            'status' => 'pending'
        ]);

        $event = new AppointmentStatusChangedEvent(
            $appointmentGuest,
            'pending',
            'confirmed'
        );

        $channels = $event->broadcastOn();

        // Should not include patient channels since no registered patient
        $this->assertNotContains("App.User.{$this->user->id}", $channels);
        // But should include doctor and admin channels
        $this->assertContains("doctor.{$this->doctor->id}", $channels);
        $this->assertContains('admin', $channels);
    }

    public function test_event_broadcast_data_includes_all_fields()
    {
        $event = new AppointmentStatusChangedEvent(
            $this->appointment,
            'pending',
            'confirmed',
            $this->user
        );

        $data = $event->broadcastWith();

        $expectedFields = [
            'appointment_id', 'old_status', 'new_status',
            'doctor_name', 'patient_name', 'appointment_date',
            'appointment_type', 'changed_by'
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $data['data']);
        }

        $this->assertEquals($this->appointment->id, $data['data']['appointment_id']);
        $this->assertEquals('pending', $data['data']['old_status']);
        $this->assertEquals('confirmed', $data['data']['new_status']);
    }

    public function test_event_broadcast_link_generation()
    {
        $event = new AppointmentStatusChangedEvent(
            $this->appointment,
            'pending',
            'confirmed'
        );

        $data = $event->broadcastWith();

        $this->assertArrayHasKey('link', $data);
        $this->assertArrayHasKey('link_text', $data);
        $this->assertTrue(str_contains($data['link'], 'appointments/show'));
        $this->assertEquals('View Appointment', $data['link_text']);
    }
}
