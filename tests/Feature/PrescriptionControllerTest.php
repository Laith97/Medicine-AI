<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\Appointment;
use App\Models\PatientData;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class PrescriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient;
    protected $appointment;
    protected $prescription;

    protected function setUp(): void
    {
        parent::setUp();

        // Create specialty
        $specialty = Specialty::factory()->create();

        // Create doctor user
        $doctorUser = User::factory()->create(['role' => 'doctor']);

        // Create doctor
        $this->doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,
        ]);

        // Create patient user
        $this->patient = User::factory()->create(['role' => 'patient']);

        // Create patient data
        PatientData::factory()->create(['user_id' => $this->patient->id]);

        // Create appointment
        $this->appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'confirmed',
        ]);

        // Create prescription
        $this->prescription = Prescription::factory()->create([
            'appointment_id' => $this->appointment->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
        ]);
    }

    public function test_index_requires_doctor_authentication()
    {
        $this->actingAs($this->patient);

        $response = $this->get("/prescriptions/{$this->appointment->id}");

        $response->assertStatus(403);
    }

    public function test_index_doctor_can_view_prescriptions()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->get("/prescriptions/{$this->appointment->id}");

        $response->assertStatus(200)
                ->assertViewIs('prescriptions.index')
                ->assertViewHas(['appointment', 'prescriptions']);
    }

    public function test_index_appointment_not_found()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->get('/prescriptions/99999');

        $response->assertStatus(404);
    }

    public function test_store_requires_doctor_authentication()
    {
        $this->actingAs($this->patient);

        $response = $this->post("/prescriptions/{$this->appointment->id}");

        $response->assertStatus(403);
    }

    public function test_store_doctor_can_create_prescription()
    {
        $this->actingAs($this->doctor->user);

        $prescriptionData = [
            'medication_name' => 'Amoxicillin',
            'dosage' => '500mg',
            'form' => 'capsule',
            'route' => 'oral',
            'quantity' => 30,
            'frequency' => 'three times daily',
            'duration' => '10 days',
            'refills' => 1,
            'indication' => 'Bacterial infection',
            'instructions' => 'Take with food',
            'notes' => 'Monitor for allergic reactions',
        ];

        $response = $this->post("/prescriptions/{$this->appointment->id}", $prescriptionData);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->assertDatabaseHas('prescriptions', [
            'medication_name' => 'Amoxicillin',
            'dosage' => '500mg',
            'appointment_id' => $this->appointment->id,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
        ]);

        // Check that appointment prescription_given flag is set
        $this->appointment->refresh();
        $this->assertTrue($this->appointment->prescription_given);
    }

    public function test_store_validation_errors()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->post("/prescriptions/{$this->appointment->id}", [
            // Missing required fields
        ]);

        $response->assertRedirect()
                ->assertSessionHasErrors(['medication_name', 'dosage', 'form', 'route', 'quantity', 'frequency', 'duration']);
    }

    public function test_store_unauthorized_doctor()
    {
        // Create another doctor
        $otherDoctorUser = User::factory()->create(['role' => 'doctor']);
        $otherDoctor = Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);

        $this->actingAs($otherDoctorUser);

        $response = $this->post("/prescriptions/{$this->appointment->id}", [
            'medication_name' => 'Test Med',
            'dosage' => '100mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 10,
            'frequency' => 'once daily',
            'duration' => '5 days',
        ]);

        $response->assertStatus(403);
    }

    public function test_store_with_ai_suggestions()
    {
        $this->actingAs($this->doctor->user);

        $prescriptionData = [
            'medication_name' => 'Ibuprofen',
            'dosage' => '200mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 20,
            'frequency' => 'as needed',
            'duration' => '7 days',
            'suggest_ai' => true,
            'ai_suggestions' => json_encode([['med' => 'Ibuprofen', 'dosage' => '200mg']]),
            'ai_risk_flags' => json_encode(['May cause stomach irritation']),
        ];

        $response = $this->post("/prescriptions/{$this->appointment->id}", $prescriptionData);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->assertDatabaseHas('prescriptions', [
            'medication_name' => 'Ibuprofen',
            'ai_suggestions' => [['med' => 'Ibuprofen', 'dosage' => '200mg']],
            'ai_risk_flags' => ['May cause stomach irritation'],
        ]);
    }

    public function test_show_patient_can_view_own_prescription()
    {
        $this->actingAs($this->patient);

        $response = $this->get("/prescriptions/{$this->prescription->id}");

        $response->assertStatus(200)
                ->assertViewIs('prescriptions.show')
                ->assertViewHas('prescription');
    }

    public function test_show_doctor_can_view_prescription()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->get("/prescriptions/{$this->prescription->id}");

        $response->assertStatus(200)
                ->assertViewIs('prescriptions.show');
    }

    public function test_show_unauthorized_access()
    {
        // Create another patient
        $otherPatient = User::factory()->create(['role' => 'patient']);
        $this->actingAs($otherPatient);

        $response = $this->get("/prescriptions/{$this->prescription->id}");

        $response->assertStatus(403);
    }

    public function test_show_pdf_download()
    {
        $this->actingAs($this->patient);

        $response = $this->get("/prescriptions/{$this->prescription->id}?pdf=1");

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_update_requires_doctor_authentication()
    {
        $this->actingAs($this->patient);

        $response = $this->put("/prescriptions/{$this->prescription->id}");

        $response->assertStatus(403);
    }

    public function test_update_doctor_can_update_prescription()
    {
        $this->actingAs($this->doctor->user);

        $updateData = [
            'medication_name' => 'Updated Medication',
            'dosage' => '1000mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 60,
            'frequency' => 'twice daily',
            'duration' => '15 days',
            'instructions' => 'Updated instructions',
        ];

        $response = $this->put("/prescriptions/{$this->prescription->id}", $updateData);

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->prescription->refresh();
        $this->assertEquals('Updated Medication', $this->prescription->medication_name);
        $this->assertEquals('1000mg', $this->prescription->dosage);
    }

    public function test_update_unauthorized_doctor()
    {
        // Create another doctor
        $otherDoctorUser = User::factory()->create(['role' => 'doctor']);
        $this->actingAs($otherDoctorUser);

        $response = $this->put("/prescriptions/{$this->prescription->id}", [
            'medication_name' => 'Test',
            'dosage' => '100mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 10,
            'frequency' => 'once daily',
            'duration' => '5 days',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_validation_errors()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->put("/prescriptions/{$this->prescription->id}", [
            'medication_name' => '', // Required field empty
            'dosage' => '100mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 10,
            'frequency' => 'once daily',
            'duration' => '5 days',
        ]);

        $response->assertRedirect()
                ->assertSessionHasErrors('medication_name');
    }

    public function test_destroy_requires_doctor_authentication()
    {
        $this->actingAs($this->patient);

        $response = $this->delete("/prescriptions/{$this->prescription->id}");

        $response->assertStatus(403);
    }

    public function test_destroy_doctor_can_delete_prescription()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->delete("/prescriptions/{$this->prescription->id}");

        $response->assertRedirect()
                ->assertSessionHas('success');

        $this->assertDatabaseMissing('prescriptions', [
            'id' => $this->prescription->id,
        ]);
    }

    public function test_destroy_unauthorized_doctor()
    {
        // Create another doctor
        $otherDoctorUser = User::factory()->create(['role' => 'doctor']);
        $this->actingAs($otherDoctorUser);

        $response = $this->delete("/prescriptions/{$this->prescription->id}");

        $response->assertStatus(403);
    }

    public function test_destroy_resets_appointment_flag_when_last_prescription()
    {
        $this->actingAs($this->doctor->user);

        // Ensure appointment has prescription_given = true
        $this->appointment->update(['prescription_given' => true]);

        $response = $this->delete("/prescriptions/{$this->prescription->id}");

        $response->assertRedirect();

        $this->appointment->refresh();
        $this->assertFalse($this->appointment->prescription_given);
    }

    public function test_destroy_ajax_response()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->deleteJson("/prescriptions/{$this->prescription->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Prescription deleted successfully.'
                ]);
    }

    public function test_store_quantity_must_be_positive()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->post("/prescriptions/{$this->appointment->id}", [
            'medication_name' => 'Test Med',
            'dosage' => '100mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 0, // Invalid
            'frequency' => 'once daily',
            'duration' => '5 days',
        ]);

        $response->assertRedirect()
                ->assertSessionHasErrors('quantity');
    }

    public function test_store_refills_cannot_be_negative()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->post("/prescriptions/{$this->appointment->id}", [
            'medication_name' => 'Test Med',
            'dosage' => '100mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 10,
            'frequency' => 'once daily',
            'duration' => '5 days',
            'refills' => -1, // Invalid
        ]);

        $response->assertRedirect()
                ->assertSessionHasErrors('refills');
    }

    public function test_prescription_not_found()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->get('/prescriptions/99999');

        $response->assertStatus(404);
    }
}
