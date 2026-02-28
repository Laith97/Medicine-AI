<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Prescription;
use App\Models\Appointment;
use App\Models\PatientData;
use App\Models\DrugInteraction;
use App\Models\DrugContraindication;
use App\Services\DrugInteractionService;
use Carbon\Carbon;

class DrugInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->seedDrugInteractions();
    }

    private function seedDrugInteractions()
    {
        // Seed some test drug interactions
        DrugInteraction::create([
            'drug_1' => 'warfarin',
            'drug_2' => 'aspirin',
            'description' => 'Increased bleeding risk',
            'severity' => 'moderate'
        ]);

        DrugInteraction::create([
            'drug_1' => 'ciprofloxacin',
            'drug_2' => 'tizanidine',
            'description' => 'Severe hypotension risk',
            'severity' => 'severe'
        ]);

        DrugContraindication::create([
            'drug_name' => 'isotretinoin',
            'condition' => 'pregnancy',
            'reason' => 'Teratogenic effects',
            'severity' => 'severe'
        ]);
    }

    public function test_drug_drug_interaction_detection()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);

        // Create existing prescription with warfarin
        Prescription::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Warfarin',
            'dosage' => '5mg',
            'frequency' => 'daily',
            'duration' => '30 days',
            'quantity' => 30,
            'refills' => 0,
            'route' => 'oral',
            'form' => 'tablet',
            'start_date' => now()
        ]);

        // Test new prescription with aspirin
        $newPrescription = new Prescription([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Aspirin',
            'dosage' => '81mg',
            'frequency' => 'daily',
            'duration' => '30 days',
            'quantity' => 30,
            'refills' => 0,
            'route' => 'oral',
            'form' => 'tablet',
            'start_date' => now()
        ]);

        $service = new DrugInteractionService();
        $result = $service->validatePrescription($newPrescription);

        $this->assertFalse($result['is_safe']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertContains('Increased bleeding risk', $result['warnings']);
    }

    public function test_drug_allergy_interaction_detection()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);

        // Create patient data with penicillin allergy
        PatientData::create([
            'assigned_patient_id' => $patient->id,
            'allergies' => ['penicillin', 'sulfa drugs']
        ]);

        // Test prescription with amoxicillin (penicillin derivative)
        $prescription = new Prescription([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Amoxicillin',
            'dosage' => '500mg',
            'frequency' => 'three times daily',
            'duration' => '10 days',
            'quantity' => 30,
            'refills' => 0,
            'route' => 'oral',
            'form' => 'capsule',
            'start_date' => now()
        ]);

        $service = new DrugInteractionService();
        $result = $service->validatePrescription($prescription);

        $this->assertFalse($result['is_safe']);
        $this->assertNotEmpty($result['errors']);
        $this->assertContains('allergic', $result['errors']);
    }

    public function test_contraindication_detection()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);

        // Create diagnosis for pregnancy
        \App\Models\Diagnosis::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_id' => $appointment->id,
            'diagnosis' => 'Pregnancy',
            'notes' => 'Confirmed pregnancy at 8 weeks'
        ]);

        // Test prescription with isotretinoin
        $prescription = new Prescription([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Isotretinoin',
            'dosage' => '40mg',
            'frequency' => 'twice daily',
            'duration' => '20 weeks',
            'quantity' => 140,
            'refills' => 0,
            'route' => 'oral',
            'form' => 'capsule',
            'start_date' => now()
        ]);

        $service = new DrugInteractionService();
        $result = $service->validatePrescription($prescription);

        $this->assertFalse($result['is_safe']);
        $this->assertNotEmpty($result['errors']);
        $this->assertContains('teratogenic', $result['errors']);
    }

    public function test_safe_prescription_validation()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);

        // Test prescription with no known interactions
        $prescription = new Prescription([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Lisinopril',
            'dosage' => '10mg',
            'frequency' => 'daily',
            'duration' => '30 days',
            'quantity' => 30,
            'refills' => 0,
            'route' => 'oral',
            'form' => 'tablet',
            'start_date' => now()
        ]);

        $service = new DrugInteractionService();
        $result = $service->validatePrescription($prescription);

        $this->assertTrue($result['is_safe']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals('low', $result['severity']);
    }

    public function test_prescription_creation_with_drug_interactions()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);

        // Create existing prescription
        Prescription::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Warfarin',
            'dosage' => '5mg',
            'frequency' => 'daily',
            'duration' => '30 days',
            'quantity' => 30,
            'refills' => 0,
            'route' => 'oral',
            'form' => 'tablet',
            'start_date' => now()
        ]);

        // Attempt to create prescription with interacting drug
        $response = $this->actingAs($doctor)->post(route('prescriptions.store', $appointment), [
            'medication_name' => 'Aspirin',
            'dosage' => '81mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 30,
            'frequency' => 'daily',
            'duration' => '30 days',
            'refills' => 0,
            'start_date' => now()->format('Y-m-d'),
            'indication' => 'Cardiovascular prevention',
            'instructions' => 'Take with food',
            'notes' => 'Test prescription',
            'suggest_ai' => false
        ]);

        // Should redirect back with errors
        $response->assertRedirect();
        $response->assertSessionHasErrors('medication_name');
    }

    public function test_force_override_prescription_creation()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id
        ]);

        // Create existing prescription
        Prescription::create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Warfarin',
            'dosage' => '5mg',
            'frequency' => 'daily',
            'duration' => '30 days',
            'quantity' => 30,
            'refills' => 0,
            'route' => 'oral',
            'form' => 'tablet',
            'start_date' => now()
        ]);

        // Create prescription with force override
        $response = $this->actingAs($doctor)->post(route('prescriptions.store', $appointment), [
            'medication_name' => 'Aspirin',
            'dosage' => '81mg',
            'form' => 'tablet',
            'route' => 'oral',
            'quantity' => 30,
            'frequency' => 'daily',
            'duration' => '30 days',
            'refills' => 0,
            'start_date' => now()->format('Y-m-d'),
            'indication' => 'Cardiovascular prevention',
            'instructions' => 'Take with food',
            'notes' => 'Test prescription with override',
            'suggest_ai' => false,
            'force_prescription' => true
        ]);

        // Should succeed with override
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check that prescription was created with override flag
        $prescription = Prescription::where('medication_name', 'Aspirin')->first();
        $this->assertTrue($prescription->force_override);
        $this->assertNotEmpty($prescription->drug_interaction_warnings);
    }
}
