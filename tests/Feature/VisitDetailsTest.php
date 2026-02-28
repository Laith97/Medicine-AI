<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PatientAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class VisitDetailsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $doctor;
    protected $patient;
    protected $patientAnalysis;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a doctor user
        $this->doctor = User::factory()->create([
            'role' => 'doctor',
            'subscription_active' => true,
        ]);

        // Create a patient
        $this->patient = User::factory()->create([
            'role' => 'patient',
        ]);

        // Create a patient analysis
        $this->patientAnalysis = PatientAnalysis::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'ai_response' => $this->faker->paragraph(5),
            'diagnosis' => $this->faker->sentence(),
            'notes' => $this->faker->paragraph(2),
        ]);
    }

    /** @test */
    public function visit_details_load_without_persistent_loading_message()
    {
        // Act as the doctor
        $this->actingAs($this->doctor);

        // Test the visit details API endpoint
        $response = $this->getJson("/api/cases/visit-history/{$this->patientAnalysis->id}");

        // Assert that the response is successful
        $response->assertStatus(200);

        // Assert the response structure
        $response->assertJsonStructure([
            'success',
            'visit_details' => [
                'id',
                'patient_id',
                'doctor_id',
                'diagnosis',
                'ai_response',
                'notes',
                'created_at'
            ]
        ]);

        // Assert success is true
        $response->assertJson([
            'success' => true
        ]);

        // Ensure the response contains actual visit details, not loading messages
        $visitData = $response->json();
        $this->assertNotContains('Loading visit details...', $visitData['visit_details']['ai_response'] ?? '');
        $this->assertNotContains('Loading...', $visitData['visit_details']['diagnosis'] ?? '');
        $this->assertNotEmpty($visitData['visit_details']['ai_response']);
        $this->assertNotEmpty($visitData['visit_details']['diagnosis']);
    }

    /** @test */
    public function visit_details_handles_nonexistent_record()
    {
        // Act as the doctor
        $this->actingAs($this->doctor);

        // Test with a non-existent record ID
        $response = $this->getJson('/api/cases/visit-history/99999');

        // Should return an error response
        $response->assertStatus(200); // API returns 200 with error in JSON
        $response->assertJson([
            'success' => false,
            'message' => 'Visit details not found'
        ]);
    }

    /** @test */
    public function visit_details_requires_authentication()
    {
        // Test without authentication
        $response = $this->getJson("/api/cases/visit-history/{$this->patientAnalysis->id}");

        // Should return unauthorized
        $response->assertStatus(401);
    }

    /** @test */
    public function visit_details_returns_proper_data_structure()
    {
        // Act as the doctor
        $this->actingAs($this->doctor);

        // Test the visit details API endpoint
        $response = $this->getJson("/api/cases/visit-history/{$this->patientAnalysis->id}");

        $response->assertStatus(200);

        $data = $response->json();

        // Verify all expected fields are present
        $this->assertArrayHasKey('id', $data['visit_details']);
        $this->assertArrayHasKey('patient_id', $data['visit_details']);
        $this->assertArrayHasKey('doctor_id', $data['visit_details']);
        $this->assertArrayHasKey('diagnosis', $data['visit_details']);
        $this->assertArrayHasKey('ai_response', $data['visit_details']);
        $this->assertArrayHasKey('notes', $data['visit_details']);
        $this->assertArrayHasKey('created_at', $data['visit_details']);

        // Verify data integrity
        $this->assertEquals($this->patientAnalysis->id, $data['visit_details']['id']);
        $this->assertEquals($this->patient->id, $data['visit_details']['patient_id']);
        $this->assertEquals($this->doctor->id, $data['visit_details']['doctor_id']);
    }

    /** @test */
    public function patient_visits_api_returns_visit_history()
    {
        // Create multiple analyses for the patient
        PatientAnalysis::factory()->count(2)->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        // Act as the doctor
        $this->actingAs($this->doctor);

        // Test the patient visits API endpoint
        $response = $this->getJson("/api/cases/patient-visits/{$this->patient->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'visits' => [
                '*' => [
                    'id',
                    'diagnosis',
                    'created_at',
                    'doctor_name'
                ]
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertCount(3, $data['visits']); // Original + 2 new ones
    }
}