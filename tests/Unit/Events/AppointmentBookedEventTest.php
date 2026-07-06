<?php

namespace Tests\Unit\Events;

use App\Events\AppointmentBookedEvent;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookedEventTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctorUser;
    protected User $patient;
    protected Doctor $doctorModel;
    protected Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->patient = User::factory()->create(['role' => 'patient', 'name' => 'John Doe']);
        $this->doctorModel = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id,
        ]);

        $this->appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctorModel->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => now()->addDays(1),
            'status' => 'confirmed',
            'appointment_type' => 'in_person',
        ]);
    }

    /** @test */
    public function it_can_be_created()
    {
        $event = new AppointmentBookedEvent($this->appointment);

        $this->assertInstanceOf(AppointmentBookedEvent::class, $event);
        $this->assertEquals($this->appointment->id, $event->appointment->id);
    }

    /** @test */
    public function it_broadcasts_to_doctor_and_patient_channels()
    {
        $event = new AppointmentBookedEvent($this->appointment);

        $channels = $event->broadcastOn();
        $channelNames = array_map(fn($ch) => $ch->name, $channels);

        $this->assertContains('private-App.User.' . $this->doctorUser->id, $channelNames);
        $this->assertContains('doctor.' . $this->doctorModel->id, $channelNames);
        $this->assertContains('private-App.User.' . $this->patient->id, $channelNames);
    }

    /** @test */
    public function it_broadcasts_with_correct_event_name()
    {
        $event = new AppointmentBookedEvent($this->appointment);

        $this->assertEquals('appointment-booked', $event->broadcastAs());
    }

    /** @test */
    public function it_broadcasts_with_appointment_data()
    {
        $event = new AppointmentBookedEvent($this->appointment);

        $data = $event->broadcastWith();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('icon', $data);
        $this->assertArrayHasKey('link', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('created_at', $data);

        $this->assertEquals($this->appointment->id, $data['id']);
        $this->assertEquals('appointment_booked', $data['type']);
        $this->assertEquals('New Appointment Booked', $data['title']);
        $this->assertEquals('calendar', $data['icon']);
    }

    /** @test */
    public function it_broadcasts_data_includes_appointment_details()
    {
        $event = new AppointmentBookedEvent($this->appointment);

        $data = $event->broadcastWith();

        $this->assertArrayHasKey('appointment_id', $data['data']);
        $this->assertArrayHasKey('doctor_name', $data['data']);
        $this->assertArrayHasKey('patient_name', $data['data']);
        $this->assertArrayHasKey('doctor_id', $data['data']);
        $this->assertArrayHasKey('appointment_date', $data['data']);
        $this->assertArrayHasKey('appointment_type', $data['data']);

        $this->assertEquals($this->appointment->id, $data['data']['appointment_id']);
        $this->assertEquals($this->doctorUser->name, $data['data']['doctor_name']);
        $this->assertEquals('John Doe', $data['data']['patient_name']);
        $this->assertEquals($this->doctorModel->id, $data['data']['doctor_id']);
    }

    /** @test */
    public function it_does_not_broadcast_to_patient_channel_for_guest_appointments()
    {
        $guestAppointment = Appointment::factory()->create([
            'doctor_id' => $this->doctorModel->id,
            'patient_id' => null,
            'guest_name' => 'Guest User',
            'guest_email' => 'guest@example.com',
            'appointment_date' => now()->addDays(1),
            'status' => 'confirmed',
        ]);

        $event = new AppointmentBookedEvent($guestAppointment);

        $channels = $event->broadcastOn();
        $channelNames = array_map(fn($ch) => $ch->name, $channels);

        $this->assertContains('private-App.User.' . $this->doctorUser->id, $channelNames);
        $this->assertContains('doctor.' . $this->doctorModel->id, $channelNames);

        $userChannel = 'private-App.User.' . $this->patient->id;
        $this->assertNotContains($userChannel, $channelNames,
            'Guest appointments should not broadcast to a patient channel');
    }

    /** @test */
    public function it_generates_link_to_doctor_appointment()
    {
        $event = new AppointmentBookedEvent($this->appointment);

        $data = $event->broadcastWith();

        $this->assertStringContainsString('appointments/' . $this->appointment->id, $data['link']);
        $this->assertEquals('View Appointment', $data['link_text']);
    }

    /** @test */
    public function it_handles_missing_doctor_user_gracefully()
    {
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctorModel->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => now()->addDays(1),
            'status' => 'confirmed',
        ]);

        // Simulate missing doctor user (doctor->user is null)
        $appointment->load('doctor.user');
        $appointment->doctor->setRelation('user', null);

        $event = new AppointmentBookedEvent($appointment);
        $data = $event->broadcastWith();

        $this->assertStringContainsString('Unknown Doctor', $data['data']['doctor_name']);
    }
}
