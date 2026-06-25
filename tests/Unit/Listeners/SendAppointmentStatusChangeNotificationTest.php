<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Events\AppointmentStatusChangedEvent;
use App\Listeners\SendAppointmentStatusChangeNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class SendAppointmentStatusChangeNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctor;
    protected User $patient;
    protected Doctor $doctorModel;
    protected Appointment $appointment;
    protected SendAppointmentStatusChangeNotification $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->patient = User::factory()->create(['role' => 'patient']);
        $this->doctorModel = Doctor::factory()->create([
            'user_id' => $this->doctor->id,
        ]);

        $this->appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctorModel->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => now()->addDays(1),
            'status' => 'pending',
        ]);

        $this->listener = new SendAppointmentStatusChangeNotification(
            app(NotificationService::class)
        );
    }

    /** @test */
    public function it_sends_notification_to_doctor_and_patient()
    {
        Notification::fake();

        $event = new AppointmentStatusChangedEvent(
            $this->appointment, 'pending', 'confirmed'
        );

        $this->listener->handle($event);

        Notification::assertSentTo(
            [$this->doctor, $this->patient],
            \App\Notifications\AppointmentStatusChangedNotification::class
        );
    }

    /** @test */
    public function it_does_not_duplicate_notifications_within_window()
    {
        Notification::fake();

        $event = new AppointmentStatusChangedEvent(
            $this->appointment, 'pending', 'confirmed'
        );

        $this->listener->handle($event);
        $this->listener->handle($event);

        Notification::assertSentTo(
            $this->doctor,
            \App\Notifications\AppointmentStatusChangedNotification::class,
            1
        );
    }

    /** @test */
    public function it_notifies_admins_for_critical_status_changes()
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $event = new AppointmentStatusChangedEvent(
            $this->appointment, 'confirmed', 'cancelled'
        );

        $this->listener->handle($event);

        Notification::assertSentTo(
            $admin,
            \App\Notifications\AppointmentStatusChangedNotification::class
        );
    }

    /** @test */
    public function it_handles_appointment_without_patient()
    {
        Notification::fake();

        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctorModel->id,
            'patient_id' => null,
            'appointment_date' => now()->addDays(1),
            'status' => 'pending',
        ]);

        $event = new AppointmentStatusChangedEvent(
            $appointment, 'pending', 'confirmed'
        );

        $this->listener->handle($event);

        Notification::assertSentTo(
            $this->doctor,
            \App\Notifications\AppointmentStatusChangedNotification::class
        );
    }

    /** @test */
    public function it_handles_appointment_without_doctor()
    {
        Notification::fake();

        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctorModel->id,
            'patient_id' => $this->patient->id,
            'appointment_date' => now()->addDays(1),
            'status' => 'pending',
        ]);

        // Remove the doctor's user so getUsersToNotify doesn't find them
        $this->doctor->delete();

        $event = new AppointmentStatusChangedEvent(
            $appointment, 'pending', 'confirmed'
        );

        $this->listener->handle($event);

        Notification::assertSentTo(
            $this->patient,
            \App\Notifications\AppointmentStatusChangedNotification::class
        );
    }

    /** @test */
    public function it_does_not_crash_on_exception()
    {
        $event = new AppointmentStatusChangedEvent(
            $this->appointment, 'pending', 'confirmed'
        );

        $this->listener->handle($event);

        $this->assertTrue(true, 'Listener handled event without throwing');
    }
}
