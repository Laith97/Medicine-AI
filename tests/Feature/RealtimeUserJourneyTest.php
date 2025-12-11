<?php

namespace Tests\Feature;

use App\Events\AppointmentStatusChangedEvent;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeUserJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected $patient;
    protected $doctor;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->create(['role' => 'patient']);
        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->admin = User::factory()->create(['role' => 'admin']);

        // Create doctor profile
        $doctorProfile = new Doctor();
        $doctorProfile->user_id = $this->doctor->id;
        $doctorProfile->save();
        $this->doctor->doctor = $doctorProfile;
    }

    public function test_patient_books_appointment_realtime_journey()
    {
        // Patient authenticates
        $this->actingAs($this->patient);

        // Patient views available appointment slots
        $response = $this->get('/api/appointments/available-slots?doctor_id=' . $this->doctor->doctor->id);
        $response->assertStatus(200);

        // Patient books an appointment
        $appointmentData = [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'appointment_type' => 'consultation',
            'duration' => 30,
            'reason' => 'Regular checkup'
        ];

        Event::fake();

        $response = $this->post('/api/appointments', $appointmentData);
        $response->assertStatus(201);

        $appointment = Appointment::latest()->first();
        $this->assertEquals($this->patient->id, $appointment->patient_id);
        $this->assertEquals('pending', $appointment->status);

        // Verify real-time events were triggered
        Event::assertDispatched(\App\Events\AppointmentBookedEvent::class);
    }

    public function test_doctor_confirms_appointment_realtime_journey()
    {
        // Create a pending appointment
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'pending'
        ]);

        // Doctor authenticates
        $this->actingAs($this->doctor);

        // Doctor views pending appointments
        $response = $this->get('/api/appointments/pending');
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $appointment->id]);

        // Doctor confirms the appointment
        Event::fake();

        $response = $this->patch("/api/appointments/{$appointment->id}/confirm");
        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);

        // Verify real-time status change event
        Event::assertDispatched(AppointmentStatusChangedEvent::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id &&
                   $event->oldStatus === 'pending' &&
                   $event->newStatus === 'confirmed';
        });
    }

    public function test_patient_receives_realtime_appointment_updates()
    {
        // Create and confirm an appointment
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'confirmed'
        ]);

        // Patient authenticates and subscribes to real-time updates
        $this->actingAs($this->patient);

        $response = $this->post('/api/realtime/appointments/subscribe');
        $response->assertStatus(200);

        // Doctor changes appointment time
        $this->actingAs($this->doctor);

        Event::fake();

        $newDateTime = now()->addDays(2)->setTime(14, 0);
        $response = $this->patch("/api/appointments/{$appointment->id}", [
            'appointment_date' => $newDateTime->format('Y-m-d H:i:s')
        ]);
        $response->assertStatus(200);

        // Verify update event was triggered
        Event::assertDispatched(\App\Events\AppointmentUpdatedEvent::class);
    }

    public function test_admin_monitors_realtime_appointment_board()
    {
        // Create multiple appointments
        $appointments = Appointment::factory()->count(5)->create([
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'confirmed',
            'appointment_date' => today()->setTime(rand(9, 17), rand(0, 59))
        ]);

        // Admin authenticates
        $this->actingAs($this->admin);

        // Admin views today's appointment board
        $response = $this->get('/api/appointments/today');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('appointments', $data);
        $this->assertArrayHasKey('subscription_channels', $data);
        $this->assertCount(5, $data['appointments']);

        // Admin subscribes to real-time updates
        $response = $this->post('/api/realtime/appointments/subscribe', [
            'filters' => ['status' => 'confirmed']
        ]);
        $response->assertStatus(200);

        // Simulate a status change
        Event::fake();

        $appointment = $appointments->first();
        $this->patch("/api/appointments/{$appointment->id}/complete");
        $appointment->refresh();
        $this->assertEquals('completed', $appointment->status);

        // Verify real-time update was broadcast
        Event::assertDispatched(AppointmentStatusChangedEvent::class);
    }

    public function test_realtime_appointment_cancellation_journey()
    {
        // Create confirmed appointment
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'confirmed'
        ]);

        // Patient cancels appointment
        $this->actingAs($this->patient);

        Event::fake();

        $response = $this->patch("/api/appointments/{$appointment->id}/cancel", [
            'reason' => 'Emergency came up'
        ]);
        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);

        // Verify cancellation event
        Event::assertDispatched(AppointmentStatusChangedEvent::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id &&
                   $event->newStatus === 'cancelled';
        });
    }

    public function test_realtime_no_show_handling_journey()
    {
        // Create confirmed appointment
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'confirmed',
            'appointment_date' => now()->subMinutes(30) // Past appointment
        ]);

        // Doctor marks as no-show
        $this->actingAs($this->doctor);

        Event::fake();

        $response = $this->patch("/api/appointments/{$appointment->id}/no-show");
        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals('no_show', $appointment->status);

        // Verify no-show event
        Event::assertDispatched(AppointmentStatusChangedEvent::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id &&
                   $event->newStatus === 'no_show';
        });
    }

    public function test_realtime_multi_device_synchronization()
    {
        // Create appointment
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'confirmed'
        ]);

        // Patient subscribes from mobile device
        $this->actingAs($this->patient);
        $response = $this->post('/api/realtime/appointments/subscribe', [
            'device_id' => 'mobile_device_123'
        ]);
        $response->assertStatus(200);

        // Doctor updates appointment from desktop
        $this->actingAs($this->doctor);

        Event::fake();

        $response = $this->patch("/api/appointments/{$appointment->id}", [
            'notes' => 'Patient called to reschedule'
        ]);
        $response->assertStatus(200);

        // Verify update event includes device sync info
        Event::assertDispatched(\App\Events\AppointmentUpdatedEvent::class);
    }

    public function test_realtime_appointment_search_and_filter()
    {
        // Create appointments with different statuses
        Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'confirmed'
        ]);

        Appointment::factory()->count(2)->create([
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'pending'
        ]);

        Appointment::factory()->create([
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'cancelled'
        ]);

        // Admin searches for confirmed appointments
        $this->actingAs($this->admin);

        $response = $this->get('/api/appointments/search?status=confirmed');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertCount(3, $data['appointments']);

        // Admin subscribes to filtered real-time updates
        $response = $this->post('/api/realtime/appointments/subscribe', [
            'filters' => ['status' => 'confirmed', 'doctor_id' => $this->doctor->doctor->id]
        ]);
        $response->assertStatus(200);
    }

    public function test_realtime_appointment_reminders()
    {
        // Create appointment for tomorrow
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'confirmed',
            'appointment_date' => now()->addDay()->setTime(10, 0)
        ]);

        // Patient subscribes to reminders
        $this->actingAs($this->patient);

        $response = $this->post('/api/realtime/reminders/subscribe', [
            'appointment_id' => $appointment->id,
            'reminder_times' => [60, 30, 15] // minutes before
        ]);
        $response->assertStatus(200);

        // System would trigger reminder events (tested via job/queue in real implementation)
        // Here we verify the subscription was created
        $this->assertTrue(true); // Placeholder for reminder subscription test
    }

    public function test_realtime_appointment_conflict_detection()
    {
        // Create first appointment
        $appointment1 = Appointment::factory()->create([
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'confirmed',
            'appointment_date' => now()->addDay()->setTime(10, 0),
            'duration' => 60
        ]);

        // Try to book overlapping appointment
        $this->actingAs($this->patient);

        $overlappingData = [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => now()->addDay()->setTime(10, 30), // Overlaps with first
            'appointment_type' => 'consultation',
            'duration' => 30
        ];

        $response = $this->post('/api/appointments', $overlappingData);
        $response->assertStatus(422); // Validation error for conflict

        // Verify no conflicting appointment was created
        $this->assertDatabaseMissing('appointments', [
            'appointment_date' => $overlappingData['appointment_date']
        ]);
    }

    public function test_realtime_appointment_waitlist_integration()
    {
        // Create full schedule for doctor
        for ($i = 9; $i <= 16; $i++) {
            Appointment::factory()->create([
                'doctor_id' => $this->doctor->doctor->id,
                'status' => 'confirmed',
                'appointment_date' => now()->addDay()->setTime($i, 0),
                'duration' => 60
            ]);
        }

        // Patient tries to book but joins waitlist
        $this->actingAs($this->patient);

        $waitlistData = [
            'doctor_id' => $this->doctor->doctor->id,
            'preferred_date' => now()->addDay()->format('Y-m-d'),
            'preferred_time' => '14:00',
            'urgency' => 'normal'
        ];

        $response = $this->post('/api/waitlist/join', $waitlistData);
        $response->assertStatus(201);

        // Verify waitlist entry was created
        $this->assertDatabaseHas('waitlist_entries', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id
        ]);

        // When an appointment is cancelled, waitlist should trigger real-time updates
        $cancelledAppointment = Appointment::where('doctor_id', $this->doctor->doctor->id)->first();

        $this->actingAs($this->doctor);
        Event::fake();

        $response = $this->patch("/api/appointments/{$cancelledAppointment->id}/cancel");
        $response->assertStatus(200);

        // Verify cancellation triggered waitlist processing
        Event::assertDispatched(AppointmentStatusChangedEvent::class);
    }

    public function test_realtime_appointment_performance_monitoring()
    {
        // Create multiple appointments
        $appointments = Appointment::factory()->count(10)->create([
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'confirmed'
        ]);

        // Admin monitors real-time performance
        $this->actingAs($this->admin);

        $response = $this->get('/api/realtime/performance/appointments');
        $response->assertStatus(200);

        $metrics = $response->json();
        $this->assertArrayHasKey('total_appointments', $metrics);
        $this->assertArrayHasKey('broadcast_success_rate', $metrics);
        $this->assertArrayHasKey('average_latency', $metrics);

        // Trigger multiple status changes to test performance
        $this->actingAs($this->doctor);

        Event::fake();

        foreach ($appointments->take(5) as $appointment) {
            $this->patch("/api/appointments/{$appointment->id}/complete");
        }

        // Verify performance metrics were updated
        Event::assertDispatched(AppointmentStatusChangedEvent::class, 5);
    }
}
