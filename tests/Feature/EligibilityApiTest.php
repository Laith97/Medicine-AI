<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PatientData;
use App\Models\PatientInsurance;
use App\Models\InsuranceProvider;
use App\Models\EligibilityCheck;
use App\Models\User;
use App\Jobs\ProcessEligibilityBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class EligibilityApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $patient;
    protected $insurance;
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();

        // Create a patient
        $this->patient = PatientData::factory()->create();

        // Create an insurance provider
        $this->provider = InsuranceProvider::factory()->create([
            'name' => 'Test Insurance',
            'code' => 'TEST',
        ]);

        // Create patient insurance
        $this->insurance = PatientInsurance::factory()->create([
            'patient_id' => $this->patient->id,
            'insurance_provider_id' => $this->provider->id,
        ]);
    }

    public function test_get_eligibility_status()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/eligibility/{$this->patient->id}/status");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'patient_id',
                        'eligibility_statuses' => [
                            '*' => [
                                'insurance_id',
                                'provider_name',
                                'policy_number',
                                'latest_check'
                            ]
                        ]
                    ]
                ]);
    }

    public function test_get_eligibility_status_with_existing_checks()
    {
        $this->actingAs($this->user);

        // Create an eligibility check
        EligibilityCheck::factory()->create([
            'patient_insurance_id' => $this->insurance->id,
            'eligibility_status' => 'eligible',
            'service_type' => 'general',
            'check_date' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->getJson("/api/eligibility/{$this->patient->id}/status");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'patient_id',
                        'eligibility_statuses' => [
                            '*' => [
                                'insurance_id',
                                'provider_name',
                                'policy_number',
                                'latest_check' => [
                                    'status',
                                    'check_date',
                                    'expires_at',
                                    'service_type'
                                ]
                            ]
                        ]
                    ]
                ]);
    }

    public function test_get_eligibility_status_patient_not_found()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/eligibility/99999/status");

        $response->assertStatus(404);
    }

    public function test_check_eligibility()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/eligibility/check', [
            'patient_insurance_id' => $this->insurance->id,
            'service_type' => 'general',
            'force_refresh' => false
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);
    }

    public function test_check_eligibility_with_force_refresh()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/eligibility/check', [
            'patient_insurance_id' => $this->insurance->id,
            'service_type' => 'general',
            'force_refresh' => true
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);
    }

    public function test_check_eligibility_invalid_patient_insurance()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/eligibility/check', [
            'patient_insurance_id' => 99999,
            'service_type' => 'general'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['patient_insurance_id']);
    }

    public function test_check_eligibility_missing_required_fields()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/eligibility/check', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['patient_insurance_id', 'service_type']);
    }

    public function test_check_eligibility_service_type_too_long()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/eligibility/check', [
            'patient_insurance_id' => $this->insurance->id,
            'service_type' => str_repeat('a', 101)
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['service_type']);
    }

    public function test_check_eligibility_no_service_available()
    {
        $this->actingAs($this->user);

        // Create a provider that doesn't have a service
        $unsupportedProvider = InsuranceProvider::factory()->create([
            'name' => 'Unsupported Insurance',
            'code' => 'UNSUPPORTED',
        ]);

        $unsupportedInsurance = PatientInsurance::factory()->create([
            'patient_id' => $this->patient->id,
            'insurance_provider_id' => $unsupportedProvider->id,
        ]);

        $response = $this->postJson('/api/eligibility/check', [
            'patient_insurance_id' => $unsupportedInsurance->id,
            'service_type' => 'general'
        ]);

        $response->assertStatus(400)
                ->assertJson(['error' => 'No eligibility service available for this insurance provider']);
    }

    public function test_batch_check_eligibility()
    {
        $this->actingAs($this->user);
        Queue::fake();

        $response = $this->postJson('/api/eligibility/batch-check', [
            'checks' => [
                [
                    'patient_insurance_id' => $this->insurance->id,
                    'service_type' => 'general'
                ]
            ]
        ]);

        $response->assertStatus(202)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'batch_id',
                    'estimated_completion'
                ]);

        Queue::assertPushed(ProcessEligibilityBatch::class);
    }

    public function test_batch_check_eligibility_validation_errors()
    {
        $this->actingAs($this->user);

        // Empty checks array
        $response = $this->postJson('/api/eligibility/batch-check', [
            'checks' => []
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['checks']);

        // Too many checks
        $checks = [];
        for ($i = 0; $i < 51; $i++) {
            $checks[] = [
                'patient_insurance_id' => $this->insurance->id,
                'service_type' => 'general'
            ];
        }

        $response = $this->postJson('/api/eligibility/batch-check', [
            'checks' => $checks
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['checks']);
    }

    public function test_get_batch_results_success()
    {
        $this->actingAs($this->user);

        $batchId = 'test_batch_123';
        $results = ['result1', 'result2'];

        Cache::put("eligibility_batch:{$batchId}", $results, 3600);

        $response = $this->getJson("/api/eligibility/batch/{$batchId}/results");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => $results
                ]);
    }

    public function test_get_batch_results_not_found()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/eligibility/batch/nonexistent_batch/results");

        $response->assertStatus(404)
                ->assertJson(['error' => 'Batch results not found or expired']);
    }

    public function test_unauthenticated_access_denied()
    {
        $response = $this->getJson("/api/eligibility/{$this->patient->id}/status");

        $response->assertStatus(401);
    }

    public function test_get_patient_insurance()
    {
        $this->actingAs($this->user);

        $response = $this->getJson("/api/patient-insurance?patient_id={$this->patient->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'patient_id',
                            'insurance_provider_id',
                            'policy_number',
                            'insurance_provider'
                        ]
                    ]
                ]);
    }
}
