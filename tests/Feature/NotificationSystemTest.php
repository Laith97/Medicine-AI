<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Notifications\AppointmentStatusChangedNotification;
use App\Notifications\AppointmentBookedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Carbon\Carbon;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctor;
    protected User $patient;
    protected Doctor $doctorProfile;
    protected Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->patient = User::factory()->create(['role' => 'patient']);
        $this->doctorProfile = Doctor::factory()->create(['user_id' => $this->doctor->id]);

        $this->appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctorProfile->id,
            'patient_id' => $this->patient->id,
            'status' => 'pending',
            'appointment_date' => Carbon::now()->addDays(1),
        ]);
    }

    /** @test */
    public function notification_can_be_sent_directly_to_user()
    {
        NotificationFacade::fake();

        $this->patient->notify(new AppointmentStatusChangedNotification(
            $this->appointment,
            'pending',
            'confirmed'
        ));

        NotificationFacade::assertSentTo($this->patient, AppointmentStatusChangedNotification::class);
    }

    /** @test */
    public function notification_contains_correct_data_for_doctor()
    {
        NotificationFacade::fake();

        $this->doctor->notify(new AppointmentStatusChangedNotification(
            $this->appointment,
            'pending',
            'confirmed',
            $this->patient
        ));

        NotificationFacade::assertSentTo(
            $this->doctor,
            AppointmentStatusChangedNotification::class,
            function ($notification, $channels, $notifiable) {
                $data = $notification->toArray($notifiable);
                return $data['type'] === 'appointment_status_changed' &&
                       str_contains($data['title'], 'Confirmed');
            }
        );
    }

    /** @test */
    public function notification_contains_correct_data_for_patient()
    {
        NotificationFacade::fake();

        $this->patient->notify(new AppointmentStatusChangedNotification(
            $this->appointment,
            'pending',
            'confirmed',
            $this->doctor
        ));

        NotificationFacade::assertSentTo(
            $this->patient,
            AppointmentStatusChangedNotification::class,
            function ($notification, $channels, $notifiable) {
                $data = $notification->toArray($notifiable);
                return $data['type'] === 'appointment_status_changed' &&
                       str_contains($data['title'], 'Confirmed') &&
                       str_contains($data['message'], 'confirmed');
            }
        );
    }

    /** @test */
    public function notification_broadcast_channel_is_private_user()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment,
            'pending',
            'confirmed'
        );

        $channels = $notification->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertStringContainsString('App.User.', $channels[0]->name);
    }

    /** @test */
    public function appointment_booked_notification_works()
    {
        NotificationFacade::fake();

        $newAppointment = Appointment::factory()->create([
            'doctor_id' => $this->doctorProfile->id,
            'patient_id' => $this->patient->id,
            'status' => 'pending',
        ]);

        $this->doctor->notify(new AppointmentBookedNotification($newAppointment));

        NotificationFacade::assertSentTo($this->doctor, AppointmentBookedNotification::class);
    }

    /** @test */
    public function notification_data_has_required_fields()
    {
        NotificationFacade::fake();

        $this->doctor->notify(new AppointmentStatusChangedNotification(
            $this->appointment,
            'pending',
            'confirmed'
        ));

        NotificationFacade::assertSentTo(
            $this->doctor,
            AppointmentStatusChangedNotification::class,
            function ($notification, $channels, $notifiable) {
                $data = $notification->toArray($notifiable);

                $requiredFields = ['type', 'title', 'message', 'icon', 'link', 'data'];

                foreach ($requiredFields as $field) {
                    if (!isset($data[$field])) {
                        return false;
                    }
                }

                return $data['data']['appointment_id'] === $this->appointment->id &&
                       $data['data']['old_status'] === 'pending' &&
                       $data['data']['new_status'] === 'confirmed';
            }
        );
    }

    /** @test */
    public function notification_via_includes_database_and_broadcast()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment,
            'pending',
            'confirmed'
        );

        $channels = $notification->via($this->doctor);

        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    /** @test */
    public function broadcast_message_has_correct_structure()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment,
            'pending',
            'confirmed'
        );

        $broadcast = $notification->toBroadcast($this->doctor);

        $this->assertEquals('appointment_status_changed', $broadcast->data['type']);
        $this->assertArrayHasKey('title', $broadcast->data);
        $this->assertArrayHasKey('message', $broadcast->data);
        $this->assertArrayHasKey('icon', $broadcast->data);
        $this->assertArrayHasKey('link', $broadcast->data);
        $this->assertArrayHasKey('data', $broadcast->data);
    }

    /** @test */
    public function cancelled_status_notification_has_correct_title_and_icon()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment,
            'confirmed',
            'cancelled'
        );

        $data = $notification->toArray($this->doctor);

        $this->assertStringContainsString('Cancelled', $data['title']);
        $this->assertEquals('calendar-times', $data['icon']);
    }

    /** @test */
    public function completed_status_notification_has_correct_title_and_icon()
    {
        $notification = new AppointmentStatusChangedNotification(
            $this->appointment,
            'confirmed',
            'completed'
        );

        $data = $notification->toArray($this->doctor);

        $this->assertStringContainsString('Completed', $data['title']);
        $this->assertEquals('check-circle', $data['icon']);
    }

    /** @test */
    public function multiple_users_can_receive_same_notification()
    {
        NotificationFacade::fake();

        $notification = new AppointmentStatusChangedNotification(
            $this->appointment,
            'pending',
            'confirmed'
        );

        $this->doctor->notify($notification);
        $this->patient->notify($notification);

        NotificationFacade::assertSentTo($this->doctor, AppointmentStatusChangedNotification::class);
        NotificationFacade::assertSentTo($this->patient, AppointmentStatusChangedNotification::class);
    }
}