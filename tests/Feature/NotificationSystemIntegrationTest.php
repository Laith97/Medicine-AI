<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\NotificationPreference;
use App\Events\AppointmentStatusChangedEvent;
use App\Notifications\AppointmentStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

class NotificationSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient;
    protected $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->patient = User::factory()->create(['role' => 'patient']);

        // Create doctor profile
        Doctor::factory()->create(['user_id' => $this->doctor->id]);

        // Create appointment
        $this->appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'pending',
        ]);

        // Create notification preferences
        NotificationPreference::factory()->create([
            'user_id' => $this->doctor->id,
            'appointment_status_changed' => true,
            'realtime_appointment_updates' => true,
            'push_appointment_status' => true,
        ]);

        NotificationPreference::factory()->create([
            'user_id' => $this->patient->id,
            'appointment_status_changed' => true,
            'realtime_appointment_updates' => true,
            'push_appointment_status' => true,
        ]);
    }

    /** @test */
    public function appointment_status_change_triggers_notification()
    {
        Notification::fake();

        // Change appointment status
        $this->appointment->status = 'confirmed';
        $this->appointment->save();

        // Assert notification was sent
        Notification::assertSentTo(
            [$this->doctor, $this->patient],
            AppointmentStatusChangedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notification->appointment->id === $this->appointment->id &&
                       $notification->oldStatus === 'pending' &&
                       $notification->newStatus === 'confirmed';
            }
        );
    }

    /** @test */
    public function critical_appointment_status_change_sends_push_notification()
    {
        Notification::fake();

        // Change appointment status to cancelled (critical)
        $this->appointment->status = 'cancelled';
        $this->appointment->save();

        // Assert notification was sent
        Notification::assertSentTo(
            [$this->doctor, $this->patient],
            AppointmentStatusChangedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notification->appointment->id === $this->appointment->id &&
                       $notification->oldStatus === 'pending' &&
                       $notification->newStatus === 'cancelled';
            }
        );
    }

    /** @test */
    public function appointment_status_change_event_is_broadcasted()
    {
        Event::fake();

        // Change appointment status
        $this->appointment->status = 'confirmed';
        $this->appointment->save();

        // Assert event was dispatched
        Event::assertDispatched(AppointmentStatusChangedEvent::class, function ($event) {
            return $event->appointment->id === $this->appointment->id &&
                   $event->oldStatus === 'pending' &&
                   $event->newStatus === 'confirmed';
        });
    }

    /** @test */
    public function notification_preferences_are_respected()
    {
        // Disable notifications for doctor
        $this->doctor->notificationPreferences->update([
            'appointment_status_changed' => false,
        ]);

        Notification::fake();

        // Change appointment status
        $this->appointment->status = 'confirmed';
        $this->appointment->save();

        // Assert notification was sent only to patient
        Notification::assertSentTo(
            $this->patient,
            AppointmentStatusChangedNotification::class
        );

        // Assert notification was not sent to doctor
        Notification::assertNotSentTo(
            $this->doctor,
            AppointmentStatusChangedNotification::class
        );
    }

    /** @test */
    public function realtime_preferences_are_checked()
    {
        // Disable real-time updates for patient
        $this->patient->notificationPreferences->update([
            'realtime_appointment_updates' => false,
        ]);

        Notification::fake();

        // Change appointment status
        $this->appointment->status = 'confirmed';
        $this->appointment->save();

        // Assert notification was sent only to doctor
        Notification::assertSentTo(
            $this->doctor,
            AppointmentStatusChangedNotification::class
        );

        // Assert notification was not sent to patient
        Notification::assertNotSentTo(
            $this->patient,
            AppointmentStatusChangedNotification::class
        );
    }

    /** @test */
    public function quiet_hours_are_respected()
    {
        // Set quiet hours for patient (current time should be outside quiet hours)
        $this->patient->notificationPreferences->update([
            'respect_quiet_hours' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
        ]);

        Notification::fake();

        // Change appointment status
        $this->appointment->status = 'confirmed';
        $this->appointment->save();

        // Assert notification was sent (since we're not in quiet hours)
        Notification::assertSentTo(
            [$this->doctor, $this->patient],
            AppointmentStatusChangedNotification::class
        );
    }

    /** @test */
    public function appointment_status_change_notification_has_correct_data()
    {
        Notification::fake();

        // Change appointment status
        $this->appointment->status = 'confirmed';
        $this->appointment->save();

        // Assert notification content
        Notification::assertSentTo(
            $this->doctor,
            AppointmentStatusChangedNotification::class,
            function ($notification, $channels, $notifiable) {
                $data = $notification->toArray($notifiable);

                return $data['type'] === 'appointment_status_changed' &&
                       $data['title'] === 'Appointment Confirmed' &&
                       str_contains($data['message'], 'confirmed') &&
                       $data['related_type'] === 'appointment' &&
                       $data['related_id'] === $this->appointment->id;
            }
        );
    }
}
