<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Notifications\AppointmentStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentStatusChangedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctor;
    protected User $patient;
    protected Doctor $doctorModel;
    protected Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->create(['role' => 'doctor', 'name' => 'Dr. Smith']);
        $this->patient = User::factory()->create(['role' => 'patient', 'name' => 'John Doe']);
        $this->doctorModel = Doctor::factory()->create([
            'user_id' => $this->doctor->id,
        ]);

        $this->appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctorModel->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => now()->addDays(1),
            'status' => 'pending',
            'appointment_type' => 'in_person',
        ]);
    }

    /** @test */
    public function it_can_be_created()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'pending', 'confirmed'
        );

        $this->assertInstanceOf(AppointmentStatusChangedNotification::class, $notification);
    }

    /** @test */
    public function it_has_correct_channels()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'pending', 'confirmed'
        );

        $channels = $notification->via($this->doctor);

        // Only database channel - broadcasting is handled by AppointmentStatusChangedEvent
        $this->assertEquals(['database'], $channels);
        $this->assertNotContains('broadcast', $channels);
    }

    /** @test */
    public function it_generates_correct_title_for_doctor()
    {
        $statusMap = [
            'confirmed' => 'Patient Appointment Confirmed',
            'cancelled' => 'Patient Appointment Cancelled',
            'completed' => 'Patient Appointment Completed',
            'no_show' => 'Patient No-Show',
            'pending' => 'Patient Appointment Updated',
        ];

        foreach ($statusMap as $status => $expectedTitle) {
            $notification = new AppointmentStatusChangedNotification(
                $this->appointment, 'pending', $status
            );
            $array = $notification->toArray($this->doctor);
            $this->assertEquals($expectedTitle, $array['title'], "Failed for status: {$status}");
        }
    }

    /** @test */
    public function it_generates_correct_title_for_patient()
    {
        $statusMap = [
            'confirmed' => 'Appointment Confirmed',
            'cancelled' => 'Appointment Cancelled',
            'completed' => 'Appointment Completed',
            'no_show' => 'Appointment No-Show',
            'pending' => 'Appointment Status Updated',
        ];

        foreach ($statusMap as $status => $expectedTitle) {
            $notification = new AppointmentStatusChangedNotification(
                $this->appointment, 'pending', $status
            );
            $array = $notification->toArray($this->patient);
            $this->assertEquals($expectedTitle, $array['title'], "Failed for status: {$status}");
        }
    }

    /** @test */
    public function it_generates_correct_message_for_confirmed()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'pending', 'confirmed'
        );

        $doctorArray = $notification->toArray($this->doctor);
        $this->assertStringContainsString('has been confirmed', $doctorArray['message']);
        $this->assertStringContainsString('John Doe', $doctorArray['message']);

        $patientArray = $notification->toArray($this->patient);
        $this->assertStringContainsString('has been confirmed', $patientArray['message']);
        $this->assertStringContainsString('Dr. Smith', $patientArray['message']);
    }

    /** @test */
    public function it_generates_correct_message_for_cancelled()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'confirmed', 'cancelled'
        );

        $doctorArray = $notification->toArray($this->doctor);
        $this->assertStringContainsString('has been cancelled', $doctorArray['message']);

        $patientArray = $notification->toArray($this->patient);
        $this->assertStringContainsString('has been cancelled', $patientArray['message']);
    }

    /** @test */
    public function it_generates_correct_message_for_no_show()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'confirmed', 'no_show'
        );

        $doctorArray = $notification->toArray($this->doctor);
        $this->assertStringContainsString('did not show up', $doctorArray['message']);

        $patientArray = $notification->toArray($this->patient);
        $this->assertStringContainsString('did not show up', $patientArray['message']);
    }

    /** @test */
    public function it_uses_correct_icon_per_status()
    {
        $iconMap = [
            'confirmed' => 'calendar-check',
            'cancelled' => 'calendar-times',
            'completed' => 'check-circle',
            'no_show' => 'user-times',
            'pending' => 'clock',
        ];

        foreach ($iconMap as $status => $expectedIcon) {
            $notification = new AppointmentStatusChangedNotification(
                $this->appointment, 'pending', $status
            );
            $broadcast = $notification->toBroadcast($this->doctor);
            $this->assertEquals($expectedIcon, $broadcast->data['icon'], "Icon mismatch for: {$status}");
        }
    }

    /** @test */
    public function it_generates_correct_link_for_doctor()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'pending', 'confirmed'
        );

        $array = $notification->toArray($this->doctor);
        $this->assertStringContainsString('doctor/appointments/' . $this->appointment->id, $array['link']);

        $broadcast = $notification->toBroadcast($this->doctor);
        $this->assertStringContainsString('doctor/appointments/' . $this->appointment->id, $broadcast->data['link']);
    }

    /** @test */
    public function it_generates_correct_link_for_patient()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'pending', 'confirmed'
        );

        $array = $notification->toArray($this->patient);
        $this->assertStringContainsString('appointments/' . $this->appointment->id, $array['link']);
        $this->assertStringNotContainsString('doctor/', $array['link']);
    }

    /** @test */
    public function it_includes_status_data()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'pending', 'confirmed', $this->doctor->id
        );

        $array = $notification->toArray($this->doctor);

        $this->assertEquals($this->appointment->id, $array['data']['appointment_id']);
        $this->assertEquals('pending', $array['data']['old_status']);
        $this->assertEquals('confirmed', $array['data']['new_status']);
        $this->assertEquals($this->doctor->id, $array['data']['changed_by']);
    }

    /** @test */
    public function it_broadcasts_on_correct_channel()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'pending', 'confirmed'
        );

        $channels = $notification->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertStringContainsString('private-App.User.', $channels[0]->name);
    }

    /** @test */
    public function it_broadcasts_with_correct_event_name()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'pending', 'confirmed'
        );

        $this->assertEquals('appointment-status-changed', $notification->broadcastAs());
    }

    /** @test */
    public function it_can_be_stored_in_database()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment, 'pending', 'confirmed'
        );

        $this->doctor->notify($notification);

        $dbNotification = $this->doctor->notifications()->first();
        $this->assertNotNull($dbNotification);
        $this->assertEquals(AppointmentStatusChangedNotification::class, $dbNotification->type);

        $data = $dbNotification->data;
        $this->assertEquals('appointment_status_changed', $data['type']);
        $this->assertEquals('confirmed', $data['data']['new_status']);
    }

    /** @test */
    public function it_handles_missing_doctor_relationship()
    {
        // Create appointment through factory which creates its own doctor
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'appointment_date' => now()->addDays(1),
            'status' => 'pending',
            'appointment_type' => 'in_person',
        ]);

        // Remove the doctor's associated user to simulate missing user relationship
        $doctorUser = $appointment->doctor->user;
        if ($doctorUser) {
            $doctorUser->delete();
        }

        // Refresh the appointment and its relationships
        $appointment->unsetRelation('doctor');
        $appointment->load('doctor.user');

        $notification = new AppointmentStatusChangedNotification(
            $appointment, 'pending', 'confirmed'
        );

        $array = $notification->toArray($this->patient);
        $this->assertStringContainsString('Unknown Doctor', $array['data']['doctor_name']);
    }
}
