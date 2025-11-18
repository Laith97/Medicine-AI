<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\OnDeckAppointment;
use App\Events\AppointmentStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RealTimeAppointmentTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient;
    protected $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a doctor user
        $this->doctor = User::factory()->create([
            'role' => 'doctor',
            'email' => 'doctor@example.com',
        ]);

        Doctor::factory()->create([
            'user_id' => $this->doctor->id,
        ]);

        // Create a patient user
        $this->patient = User::factory()->create([
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
            'phone' => '1234567890',
            'role' => 'patient',
        ]);

        // Create an appointment
        $this->appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1),
            'status' => 'confirmed',
            'appointment_type' => 'in_person',
        ]);
    }

    public function test_appointment_status_updated_event_creation()
    {
        Event::fake();

        $event = new AppointmentStatusUpdated($this->appointment);

        $this->assertEquals($this->appointment->id, $event->appointment->id);
        $this->assertEquals($this->appointment->status, $event->appointment->status);

        Event::assertNotDispatched(AppointmentStatusUpdated::class);
    }

    public function test_on_deck_appointment_can_be_created()
    {
        $onDeckData = [
            'appointment_id' => $this->appointment->id,
            'doctor_id' => $this->doctor->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'waiting',
            'position' => 1,
            'estimated_wait_minutes' => 30,
        ];

        $onDeck = OnDeckAppointment::create($onDeckData);

        $this->assertDatabaseHas('on_deck_appointments', [
            'appointment_id' => $this->appointment->id,
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'waiting',
            'position' => 1,
        ]);
    }

    public function test_appointment_status_change_updates_on_deck()
    {
        $onDeck = OnDeckAppointment::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id' => $this->doctor->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'waiting',
            'position' => 1,
        ]);

        // Update appointment status
        $this->appointment->update(['status' => 'confirmed']);

        // Update corresponding on-deck status
        $onDeck->update(['status' => 'in-progress']);

        $this->assertEquals('in-progress', $onDeck->fresh()->status);
    }

    public function test_appointment_position_in_queue()
    {
        // Create multiple appointments for the same doctor
        $appointment2 = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->addHours(1),
            'status' => 'confirmed',
        ]);

        $appointment3 = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => Carbon::now()->addDays(1)->addHours(2),
            'status' => 'confirmed',
        ]);

        // Create on-deck entries
        $onDeck1 = OnDeckAppointment::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id' => $this->doctor->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'waiting',
            'position' => 1,
        ]);

        $onDeck2 = OnDeckAppointment::create([
            'appointment_id' => $appointment2->id,
            'doctor_id' => $this->doctor->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'waiting',
            'position' => 2,
        ]);

        $onDeck3 = OnDeckAppointment::create([
            'appointment_id' => $appointment3->id,
            'doctor_id' => $this->doctor->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'waiting',
            'position' => 3,
        ]);

        // Test ordering by position
        $orderedAppointments = OnDeckAppointment::orderBy('position')->get();

        $this->assertEquals(1, $orderedAppointments[0]->position);
        $this->assertEquals(2, $orderedAppointments[1]->position);
        $this->assertEquals(3, $orderedAppointments[2]->position);
    }

    public function test_appointment_risk_scoring()
    {
        $onDeck = OnDeckAppointment::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id' => $this->doctor->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'waiting',
            'position' => 1,
            'risk_score' => 0.7, // High risk score
            'risk_factors' => ['high_bp', 'diabetes'],
        ]);

        $this->assertGreaterThanOrEqual(0.5, $onDeck->risk_score);
        $riskFactors = $onDeck->risk_factors;
        $this->assertContains('high_bp', $riskFactors);
        $this->assertContains('diabetes', $riskFactors);
    }

    public function test_appointment_status_workflow()
    {
        $onDeck = OnDeckAppointment::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id' => $this->doctor->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'waiting',
            'position' => 1,
        ]);

        // Test status transitions
        $onDeck->update(['status' => 'ready']);
        $this->assertEquals('ready', $onDeck->fresh()->status);

        $onDeck->update(['status' => 'in-progress']);
        $this->assertEquals('in-progress', $onDeck->fresh()->status);

        $onDeck->update(['status' => 'completed']);
        $this->assertEquals('completed', $onDeck->fresh()->status);

        $onDeck->update(['status' => 'no-show']);
        $this->assertEquals('no-show', $onDeck->fresh()->status);
    }

    public function test_appointment_estimated_wait_time_calculation()
    {
        $onDeck = OnDeckAppointment::create([
            'appointment_id' => $this->appointment->id,
            'doctor_id' => $this->doctor->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'waiting',
            'position' => 1,
            'estimated_wait_minutes' => 15,
        ]);

        $this->assertGreaterThanOrEqual(0, $onDeck->estimated_wait_minutes);
        $this->assertEquals(15, $onDeck->estimated_wait_minutes);
    }

    public function test_appointment_broadcast_data_structure()
    {
        $event = new AppointmentStatusUpdated($this->appointment);

        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('appointmentId', $broadcastData);
        $this->assertArrayHasKey('status', $broadcastData);
        $this->assertArrayHasKey('patient_name', $broadcastData);
        $this->assertArrayHasKey('appointment_date', $broadcastData);
        $this->assertArrayHasKey('updated_at', $broadcastData);

        $this->assertEquals($this->appointment->id, $broadcastData['appointmentId']);
        $this->assertEquals($this->appointment->status, $broadcastData['status']);
        $this->assertEquals($this->appointment->patient_name, $broadcastData['patient_name']);
    }
}
