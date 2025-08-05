<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Notifications\AppointmentBookedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentBookedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient;
    protected $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->patient = User::factory()->create(['role' => 'patient']);

        $this->appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => now()->addDays(2),
            'status' => 'confirmed',
            'appointment_type' => 'video',
            'duration' => 30,
            'fee' => 5000,
            'reason' => 'General checkup',
        ]);
    }

    /** @test */
    public function it_can_be_created()
    {
        $notification = new AppointmentBookedNotification($this->appointment);

        $this->assertInstanceOf(AppointmentBookedNotification::class, $notification);
    }

    /** @test */
    public function it_has_correct_notification_channels()
    {
        $notification = new AppointmentBookedNotification($this->appointment);

        $this->assertEquals(['database', 'mail'], $notification->via($this->doctor));
    }

    /** @test */
    public function it_has_correct_array_content()
    {
        $notification = new AppointmentBookedNotification($this->appointment);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertEquals('appointment_booked', $arrayContent['type']);
        $this->assertEquals('New Appointment Booked', $arrayContent['title']);
        $this->assertStringContainsString("A new appointment has been booked with {$this->appointment->doctor->name}", $arrayContent['message']);
        $this->assertEquals('calendar', $arrayContent['icon']);
        $this->assertEquals(route('appointments.show', $this->appointment->id), $arrayContent['link']);
        $this->assertEquals('View Appointment', $arrayContent['link_text']);
        $this->assertEquals('appointment', $arrayContent['related_type']);
        $this->assertEquals($this->appointment->id, $arrayContent['related_id']);

        $this->assertArrayHasKey('data', $arrayContent);
        $this->assertEquals($this->appointment->id, $arrayContent['data']['appointment_id']);
        $this->assertEquals($this->appointment->doctor->name, $arrayContent['data']['doctor_name']);
        $this->assertEquals($this->appointment->appointment_date->format('Y-m-d H:i:s'), $arrayContent['data']['appointment_date']);
        $this->assertEquals($this->appointment->appointment_type, $arrayContent['data']['appointment_type']);
    }

    /** @test */
    public function it_has_correct_mail_content()
    {
        $notification = new AppointmentBookedNotification($this->appointment);

        $mailData = $notification->toMail($this->doctor);

        $this->assertEquals('New Appointment Booked', $mailData->subject);
        $this->assertStringContainsString("Hello {$this->doctor->name}", $mailData->greeting);
        $this->assertStringContainsString("A new appointment has been booked with {$this->appointment->doctor->name}", $mailData->introLines[0]);
        $this->assertStringContainsString($this->appointment->appointment_date->format('M j, Y g:i A'), $mailData->introLines[1]);
        $this->assertStringContainsString('View Appointment', $mailData->actionText);
        $this->assertEquals(route('appointments.show', $this->appointment->id), $mailData->actionUrl);
        $this->assertStringContainsString('Thank you for using our platform', $mailData->outroLines[0]);
    }

    /** @test */
    public function it_has_correct_sms_content()
    {
        $notification = new AppointmentBookedNotification($this->appointment);

        $smsContent = $notification->toSms($this->doctor);

        $this->assertStringContainsString("New appointment booked with {$this->appointment->doctor->name}", $smsContent);
        $this->assertStringContainsString($this->appointment->appointment_date->format('M j, Y g:i A'), $smsContent);
        $this->assertStringContainsString(route('appointments.show', $this->appointment->id), $smsContent);
    }

    /** @test */
    public function it_handles_different_appointment_types()
    {
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'appointment_type' => 'in_person',
            'status' => 'pending',
        ]);

        $notification = new AppointmentBookedNotification($appointment);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertEquals('appointment_booked', $arrayContent['type']);
        $this->assertEquals('New Appointment Booked', $arrayContent['title']);
        $this->assertEquals('in_person', $arrayContent['data']['appointment_type']);
    }

    /** @test */
    public function it_handles_guest_appointments()
    {
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => null,
            'guest_name' => 'John Doe',
            'guest_email' => 'john@example.com',
            'guest_phone' => '+1234567890',
            'appointment_type' => 'video',
            'status' => 'confirmed',
        ]);

        $notification = new AppointmentBookedNotification($appointment);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertEquals('New Appointment Booked', $arrayContent['title']);
        $this->assertStringContainsString("A new appointment has been booked with {$appointment->doctor->name}", $arrayContent['message']);
    }

    /** @test */
    public function it_can_be_sent_to_doctor()
    {
        $notification = new AppointmentBookedNotification($this->appointment);

        $this->doctor->notify($notification);

        $this->assertEquals(1, $this->doctor->notifications()->whereNull('read_at')->count());

        $storedNotification = $this->doctor->notifications()->first();
        $data = json_decode($storedNotification->data, true);

        $this->assertEquals('New Appointment Booked', $data['title']);
        $this->assertEquals('appointment_booked', $storedNotification->type);
    }

    /** @test */
    public function it_can_be_sent_to_patient()
    {
        $notification = new AppointmentBookedNotification($this->appointment);

        $this->patient->notify($notification);

        $this->assertEquals(1, $this->patient->notifications()->whereNull('read_at')->count());

        $storedNotification = $this->patient->notifications()->first();
        $data = json_decode($storedNotification->data, true);

        $this->assertEquals('New Appointment Booked', $data['title']);
        $this->assertEquals('appointment_booked', $storedNotification->type);
    }

    /** @test */
    public function it_includes_ai_assistant_flag()
    {
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
        ]);

        $notification = new AppointmentBookedNotification($appointment);

        $arrayContent = $notification->toArray($this->doctor);

        $this->assertArrayHasKey('data', $arrayContent);
        $this->assertArrayHasKey('appointment_id', $arrayContent['data']);
        $this->assertArrayHasKey('doctor_name', $arrayContent['data']);
        $this->assertArrayHasKey('appointment_date', $arrayContent['data']);
        $this->assertArrayHasKey('appointment_type', $arrayContent['data']);
    }
}
