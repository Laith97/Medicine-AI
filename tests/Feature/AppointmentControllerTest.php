<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\PatientData;
use App\Models\Specialty;
use App\Models\InsuranceProvider;
use App\Models\PatientInsurance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $doctor;
    protected $patient;
    protected $specialty;
    protected $insuranceProvider;

    protected function setUp(): void
    {
        parent::setUp();

        // Create specialty
        $this->specialty = Specialty::factory()->create([
            'name' => 'General Medicine',
            'code' => 'GM'
        ]);

        // Create doctor user
        $doctorUser = User::factory()->create([
            'role' => 'doctor'
        ]);

        // Create doctor
        $this->doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'specialty_id' => $this->specialty->id,
            'consultation_fee' => 100.00,
            'appointment_duration' => 30,
            'auto_approve_appointments' => true,
        ]);

        // Create patient user
        $this->user = User::factory()->create([
            'role' => 'patient'
        ]);

        // Create patient data
        $this->patient = PatientData::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Create insurance provider
        $this->insuranceProvider = InsuranceProvider::factory()->create();
    }

    public function test_index_authenticated_user()
    {
        $this->actingAs($this->user);

        $response = $this->get('/appointments');

        $response->assertStatus(200)
                ->assertViewIs('appointments.index');
    }

    public function test_index_guest_redirects_to_lookup()
    {
        $response = $this->get('/appointments');

        $response->assertRedirect(route('appointments.guest.lookup'));
    }

    public function test_index_with_filters()
    {
        $this->actingAs($this->user);

        // Create appointments
        Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'confirmed',
            'appointment_date' => now()->addDays(1),
        ]);

        Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
            'appointment_date' => now()->addDays(2),
        ]);

        $response = $this->get('/appointments?status=confirmed');

        $response->assertStatus(200)
                ->assertViewIs('appointments.index');
    }

    public function test_create_shows_booking_form()
    {
        $this->actingAs($this->user);

        $response = $this->get("/appointments/create/{$this->doctor->id}");

        $response->assertStatus(200)
                ->assertViewIs('appointments.create')
                ->assertViewHas(['doctor', 'availableSlots']);
    }

    public function test_store_authenticated_user_success()
    {
        $this->actingAs($this->user);
        Queue::fake();
        Event::fake();

        $appointmentDate = now()->addDays(1)->setHour(10)->setMinute(0);

        $response = $this->post('/appointments', [
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentDate->toDateTimeString(),
            'reason' => 'Regular checkup',
            'symptoms' => 'Mild headache',
            'appointment_type' => 'consultation',
            'patient_notes' => 'First visit',
        ]);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'confirmed', // auto-approved
            'reason' => 'Regular checkup',
        ]);
    }

    public function test_store_guest_booking_success()
    {
        Queue::fake();
        Event::fake();

        $appointmentDate = now()->addDays(1)->setHour(10)->setMinute(0);

        $response = $this->post('/appointments', [
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentDate->toDateTimeString(),
            'reason' => 'Regular checkup',
            'appointment_type' => 'consultation',
            'booking_type' => 'guest',
            'guest_name' => 'John Doe',
            'guest_email' => 'john@example.com',
            'guest_phone' => '+1234567890',
            'guest_date_of_birth' => '1990-01-01',
            'guest_gender' => 'male',
        ]);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $this->doctor->id,
            'guest_name' => 'John Doe',
            'guest_email' => 'john@example.com',
            'status' => 'confirmed',
        ]);
    }

    public function test_store_guest_registration_success()
    {
        Queue::fake();
        Event::fake();

        $appointmentDate = now()->addDays(1)->setHour(10)->setMinute(0);

        $response = $this->post('/appointments', [
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentDate->toDateTimeString(),
            'reason' => 'Regular checkup',
            'appointment_type' => 'consultation',
            'booking_type' => 'register',
            'reg_name' => 'Jane Smith',
            'reg_email' => 'jane@example.com',
            'reg_password' => 'password123',
            'reg_password_confirmation' => 'password123',
        ]);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'role' => 'patient',
        ]);

        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $this->doctor->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_store_with_insurance_information()
    {
        $this->actingAs($this->user);
        Queue::fake();
        Event::fake();

        $appointmentDate = now()->addDays(1)->setHour(10)->setMinute(0);

        $response = $this->post('/appointments', [
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentDate->toDateTimeString(),
            'reason' => 'Regular checkup',
            'appointment_type' => 'consultation',
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456',
            'group_number' => 'GRP001',
            'subscriber_id' => 'SUB001',
            'relationship_to_subscriber' => 'self',
            'effective_date' => '2020-01-01',
        ]);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->assertDatabaseHas('patient_insurances', [
            'patient_id' => $this->user->id,
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456',
        ]);
    }

    public function test_store_validation_errors()
    {
        $this->actingAs($this->user);

        $response = $this->post('/appointments', [
            // Missing required fields
        ]);

        $response->assertRedirect()
                ->assertSessionHasErrors(['doctor_id', 'appointment_date', 'reason', 'appointment_type']);
    }

    public function test_store_unavailable_slot()
    {
        $this->actingAs($this->user);

        // Create existing appointment at the same time
        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1)->setHour(10)->setMinute(0),
            'appointment_end' => now()->addDays(1)->setHour(10)->setMinute(30),
            'status' => 'confirmed',
        ]);

        $response = $this->post('/appointments', [
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1)->setHour(10)->setMinute(0)->toDateTimeString(),
            'reason' => 'Regular checkup',
            'appointment_type' => 'consultation',
        ]);

        $response->assertRedirect()
                ->assertSessionHasErrors(['appointment_date']);
    }

    public function test_store_ajax_success()
    {
        $this->actingAs($this->user);
        Queue::fake();
        Event::fake();

        $appointmentDate = now()->addDays(1)->setHour(10)->setMinute(0);

        $response = $this->postJson('/appointments', [
            'doctor_id' => $this->doctor->id,
            'appointment_date' => $appointmentDate->toDateTimeString(),
            'reason' => 'Regular checkup',
            'appointment_type' => 'consultation',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'appointment_id' => 1,
                ]);
    }

    public function test_show_own_appointment()
    {
        $this->actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $response = $this->get("/appointments/{$appointment->id}");

        $response->assertStatus(200)
                ->assertViewIs('appointments.show')
                ->assertViewHas('appointment');
    }

    public function test_show_doctor_can_view_patient_appointment()
    {
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->actingAs($doctorUser);

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $response = $this->get("/appointments/{$appointment->id}");

        $response->assertStatus(200);
    }

    public function test_show_unauthorized_access()
    {
        $otherUser = User::factory()->create(['role' => 'patient']);
        $this->actingAs($otherUser);

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $response = $this->get("/appointments/{$appointment->id}");

        $response->assertStatus(403);
    }

    public function test_cancel_appointment_success()
    {
        $this->actingAs($this->user);
        Queue::fake();

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(2),
            'status' => 'confirmed',
        ]);

        $response = $this->post("/appointments/{$appointment->id}/cancel", [
            'cancellation_reason' => 'Not feeling well',
        ]);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
    }

    public function test_cancel_appointment_too_late()
    {
        $this->actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addHours(2), // Less than 24 hours
            'status' => 'confirmed',
        ]);

        $response = $this->post("/appointments/{$appointment->id}/cancel");

        $response->assertRedirect()
                ->assertSessionHasErrors('error');
    }

    public function test_cancel_unauthorized_appointment()
    {
        $otherUser = User::factory()->create(['role' => 'patient']);
        $this->actingAs($otherUser);

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $response = $this->post("/appointments/{$appointment->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_reschedule_appointment_success()
    {
        $this->actingAs($this->user);
        Queue::fake();

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(3),
            'status' => 'confirmed',
        ]);

        $newDate = now()->addDays(4)->setHour(11)->setMinute(0);

        $response = $this->post("/appointments/{$appointment->id}/reschedule", [
            'new_appointment_date' => $newDate->toDateTimeString(),
        ]);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertEquals($newDate->toDateTimeString(), $appointment->appointment_date->toDateTimeString());
    }

    public function test_reschedule_unavailable_slot()
    {
        $this->actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(3),
            'status' => 'confirmed',
        ]);

        // Create conflicting appointment
        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(4)->setHour(11)->setMinute(0),
            'appointment_end' => now()->addDays(4)->setHour(11)->setMinute(30),
            'status' => 'confirmed',
        ]);

        $response = $this->post("/appointments/{$appointment->id}/reschedule", [
            'new_appointment_date' => now()->addDays(4)->setHour(11)->setMinute(0)->toDateTimeString(),
        ]);

        $response->assertRedirect()
                ->assertSessionHasErrors('new_appointment_date');
    }

    public function test_get_calendar_events()
    {
        $this->actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1)->setHour(10)->setMinute(0),
            'appointment_end' => now()->addDays(1)->setHour(10)->setMinute(30),
        ]);

        $response = $this->getJson('/appointments/calendar-events', [
            'start' => now()->toDateString(),
            'end' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    '*' => [
                        'id',
                        'title',
                        'start',
                        'end',
                        'color',
                        'url',
                        'extendedProps'
                    ]
                ]);
    }

    public function test_guest_lookup_shows_form()
    {
        $response = $this->get('/appointments/guest/lookup');

        $response->assertStatus(200)
                ->assertViewIs('appointments.guest.lookup');
    }

    public function test_guest_search_success()
    {
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'guest_email' => 'guest@example.com',
            'guest_name' => 'Guest User',
        ]);

        $response = $this->post('/appointments/guest/search', [
            'email' => 'guest@example.com',
        ]);

        $response->assertStatus(200)
                ->assertViewIs('appointments.guest.list')
                ->assertViewHas('appointments');
    }

    public function test_guest_search_no_appointments()
    {
        $response = $this->post('/appointments/guest/search', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect()
                ->assertSessionHasErrors('email');
    }

    public function test_guest_show_appointment()
    {
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'guest_email' => 'guest@example.com',
            'appointment_number' => 'APP123456',
        ]);

        $response = $this->get("/appointments/guest/APP123456?email=guest@example.com");

        $response->assertStatus(200)
                ->assertViewIs('appointments.guest.show')
                ->assertViewHas('appointment');
    }

    public function test_guest_verify_success()
    {
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'guest_email' => 'guest@example.com',
            'appointment_number' => 'APP123456',
            'verification_token' => 'valid_token_123',
        ]);

        $response = $this->post("/appointments/guest/APP123456/verify", [
            'token' => 'valid_token_123',
        ]);

        $response->assertRedirect()
                ->assertSessionHas('success');
    }

    public function test_guest_verify_invalid_token()
    {
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'appointment_number' => 'APP123456',
        ]);

        $response = $this->post("/appointments/guest/APP123456/verify", [
            'token' => 'invalid_token',
        ]);

        $response->assertRedirect()
                ->assertSessionHasErrors('token');
    }

    public function test_guest_cancel_success()
    {
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'guest_email' => 'guest@example.com',
            'appointment_number' => 'APP123456',
            'appointment_date' => now()->addDays(2),
            'status' => 'confirmed',
        ]);

        $response = $this->post("/appointments/guest/APP123456/cancel", [
            'email' => 'guest@example.com',
            'cancellation_reason' => 'Cannot attend',
        ]);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
    }

    public function test_test_openai_unauthorized()
    {
        $this->actingAs($this->user); // Patient user

        $response = $this->post('/appointments/test-openai');

        $response->assertStatus(403);
    }

    public function test_ai_suggest_unauthorized()
    {
        $this->actingAs($this->user); // Patient user

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $response = $this->post("/appointments/{$appointment->id}/ai-suggest");

        $response->assertStatus(403);
    }

    public function test_ai_suggest_doctor_success()
    {
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->actingAs($doctorUser);

        // Create doctor profile for the user
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'specialty_id' => $this->specialty->id,
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $this->user->id,
            'doctor_id' => $doctor->id,
        ]);

        $response = $this->postJson("/appointments/{$appointment->id}/ai-suggest", [
            'symptoms' => 'Headache, fever',
            'allergies' => json_encode(['penicillin']),
            'past_meds' => json_encode(['ibuprofen']),
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'suggestions',
                    'risk_flags',
                    'message',
                    'source'
                ]);
    }
}
