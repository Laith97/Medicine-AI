<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PatientAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PatientSummaryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $doctor;
    protected $patient;

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
    }

    /** @test */
    public function patient_summary_button_generates_summary_without_persistent_loading()
    {
        // Create some patient analyses for the patient
        PatientAnalysis::factory()->count(3)->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'ai_response' => $this->faker->paragraph(),
            'diagnosis' => $this->faker->sentence(),
        ]);

        // Act as the doctor
        $this->actingAs($this->doctor);

        // Visit the cases page
        $response = $this->get('/cases');
        $response->assertStatus(200);

        // Test the patient summary API endpoint
        $summaryResponse = $this->postJson('/ai/patient-summary', [
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->name,
            'patient_age' => $this->patient->age ?? 30,
            'patient_gender' => $this->patient->gender ?? 'male',
            'visit_count' => 3,
            'visits' => [
                [
                    'date' => now()->format('Y-m-d'),
                    'diagnosis' => 'Test diagnosis 1',
                    'ai_response' => 'Test AI response 1'
                ],
                [
                    'date' => now()->subDays(1)->format('Y-m-d'),
                    'diagnosis' => 'Test diagnosis 2',
                    'ai_response' => 'Test AI response 2'
                ],
                [
                    'date' => now()->subDays(2)->format('Y-m-d'),
                    'diagnosis' => 'Test diagnosis 3',
                    'ai_response' => 'Test AI response 3'
                ]
            ]
        ]);

        // Assert that the response is successful and doesn't indicate persistent loading
        $summaryResponse->assertStatus(200);
        $summaryResponse->assertJsonStructure([
            'success',
            'summary'
        ]);

        $summaryResponse->assertJson([
            'success' => true
        ]);

        // Ensure the summary contains actual content, not loading messages
        $summaryData = $summaryResponse->json();
        $this->assertNotContains('Generating patient summary...', $summaryData['summary']);
        $this->assertNotContains('Loading...', $summaryData['summary']);
        $this->assertGreaterThan(50, strlen($summaryData['summary'])); // Ensure meaningful content
    }

    /** @test */
    public function patient_summary_handles_empty_patient_history()
    {
        // Act as the doctor
        $this->actingAs($this->doctor);

        // Test with a patient that has no analyses
        $summaryResponse = $this->postJson('/ai/patient-summary', [
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->name,
            'patient_age' => $this->patient->age ?? 30,
            'patient_gender' => $this->patient->gender ?? 'male',
            'visit_count' => 0,
            'visits' => []
        ]);

        // Should still return a response, possibly with a message about no history
        $summaryResponse->assertStatus(200);
        $summaryResponse->assertJsonStructure([
            'success',
            'summary'
        ]);
    }

    /** @test */
    public function patient_summary_requires_authentication()
    {
        // Test without authentication
        $response = $this->postJson('/ai/patient-summary', [
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->name,
            'patient_age' => 30,
            'patient_gender' => 'male',
            'visit_count' => 1,
            'visits' => []
        ]);

        // Should redirect to login or return unauthorized
        $response->assertStatus(401);
    }
}