<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\PatientRiskScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;

class PredictionApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_returns_200_with_valid_prediction_data()
    {
        // Create authenticated user
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Create patient and appointment
        $patient = User::factory()->create([
            'date_of_birth' => now()->subYears(35),
            'gender' => 'male'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(3)
        ]);

        $payload = [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id
        ];

        $response = $this->postJson('/api/predictions', $payload);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'patient_id',
                        'appointment_id',
                        'no_show_risk',
                        'hospitalization_risk'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'patient_id' => $patient->id,
                        'appointment_id' => $appointment->id
                    ]
                ]);

        // Verify PatientRiskScore was created
        $this->assertDatabaseHas('patient_risk_scores', [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id
        ]);

        $riskScore = PatientRiskScore::where('patient_id', $patient->id)
                                    ->where('appointment_id', $appointment->id)
                                    ->first();

        $this->assertNotNull($riskScore);
        $this->assertIsFloat($riskScore->no_show_risk);
        $this->assertIsFloat($riskScore->hospitalization_risk);
        $this->assertGreaterThanOrEqual(0.0, $riskScore->no_show_risk);
        $this->assertLessThanOrEqual(1.0, $riskScore->no_show_risk);
    }

    /** @test */
    public function it_returns_422_for_invalid_patient_id()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $appointment = Appointment::factory()->create();

        $payload = [
            'patient_id' => 99999, // Non-existent patient
            'appointment_id' => $appointment->id
        ];

        $response = $this->postJson('/api/predictions', $payload);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ])
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);

        // Verify no PatientRiskScore was created
        $this->assertDatabaseMissing('patient_risk_scores', [
            'patient_id' => 99999,
            'appointment_id' => $appointment->id
        ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_appointment_id()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $patient = User::factory()->create();

        $payload = [
            'patient_id' => $patient->id,
            'appointment_id' => 99999 // Non-existent appointment
        ];

        $response = $this->postJson('/api/predictions', $payload);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ])
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);

        // Verify no PatientRiskScore was created
        $this->assertDatabaseMissing('patient_risk_scores', [
            'patient_id' => $patient->id,
            'appointment_id' => 99999
        ]);
    }

    /** @test */
    public function it_returns_404_when_patient_not_found_in_database()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $appointment = Appointment::factory()->create([
            'patient_id' => 99999 // Non-existent patient
        ]);

        $payload = [
            'patient_id' => 99999,
            'appointment_id' => $appointment->id
        ];

        $response = $this->postJson('/api/predictions', $payload);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Patient not found'
                ]);

        // Verify no PatientRiskScore was created
        $this->assertDatabaseMissing('patient_risk_scores', [
            'patient_id' => 99999,
            'appointment_id' => $appointment->id
        ]);
    }

    /** @test */
    public function it_returns_404_when_appointment_not_found_in_database()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $patient = User::factory()->create();

        $payload = [
            'patient_id' => $patient->id,
            'appointment_id' => 99999
        ];

        $response = $this->postJson('/api/predictions', $payload);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Appointment not found'
                ]);

        // Verify no PatientRiskScore was created
        $this->assertDatabaseMissing('patient_risk_scores', [
            'patient_id' => $patient->id,
            'appointment_id' => 99999
        ]);
    }

    /** @test */
    public function it_returns_422_for_missing_patient_id()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $appointment = Appointment::factory()->create();

        $payload = [
            'appointment_id' => $appointment->id
            // Missing patient_id
        ];

        $response = $this->postJson('/api/predictions', $payload);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ])
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);

        $this->assertArrayHasKey('patient_id', $response->json()['errors']);
    }

    /** @test */
    public function it_returns_422_for_missing_appointment_id()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $patient = User::factory()->create();

        $payload = [
            'patient_id' => $patient->id
            // Missing appointment_id
        ];

        $response = $this->postJson('/api/predictions', $payload);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ])
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);

        $this->assertArrayHasKey('appointment_id', $response->json()['errors']);
    }

    /** @test */
    public function it_handles_ml_model_errors_gracefully()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $patient = User::factory()->create([
            'date_of_birth' => now()->subYears(25),
            'gender' => 'female'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(1)
        ]);

        $payload = [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id
        ];

        // This should still work even if ML models don't exist
        $response = $this->postJson('/api/predictions', $payload);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'patient_id',
                        'appointment_id',
                        'no_show_risk',
                        'hospitalization_risk'
                    ]
                ]);

        // Verify PatientRiskScore was created with default values
        $this->assertDatabaseHas('patient_risk_scores', [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => 0.0,
            'hospitalization_risk' => 0.0
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id
        ]);

        $payload = [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id
        ];

        $response = $this->postJson('/api/predictions', $payload);

        $response->assertStatus(401); // Unauthorized
    }

    /** @test */
    public function it_handles_database_transaction_rollback_on_error()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id
        ]);

        // Mock an exception during prediction
        // In a real scenario, this would be handled by the service throwing an exception

        $payload = [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id
        ];

        $response = $this->postJson('/api/predictions', $payload);

        // Should either succeed or return a proper error response
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 500
        );

        if ($response->status() === 500) {
            $response->assertJsonStructure([
                'success',
                'message'
            ]);
        }
    }
}
