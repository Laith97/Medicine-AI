<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PatientInsurance;
use App\Models\InsuranceProvider;
use App\Models\PatientData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PatientInsuranceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $patient;
    protected $insuranceProvider;
    protected $insurance;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user
        $this->user = User::factory()->create(['role' => 'patient']);

        // Create patient data
        $this->patient = PatientData::factory()->create(['user_id' => $this->user->id]);

        // Create insurance provider
        $this->insuranceProvider = InsuranceProvider::factory()->create();

        // Create patient insurance
        $this->insurance = PatientInsurance::factory()->create([
            'patient_id' => $this->patient->id,
            'insurance_provider_id' => $this->insuranceProvider->id,
        ]);
    }

    public function test_index_returns_insurance_records()
    {
        $response = $this->getJson('/api/patient-insurance');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
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

    public function test_index_filters_by_patient_id()
    {
        // Create another patient and insurance
        $otherPatient = PatientData::factory()->create();
        $otherInsurance = PatientInsurance::factory()->create([
            'patient_id' => $otherPatient->id,
            'insurance_provider_id' => $this->insuranceProvider->id,
        ]);

        $response = $this->getJson("/api/patient-insurance?patient_id={$this->patient->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        // Should only return insurance for the specified patient
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->patient->id, $data[0]['patient_id']);
    }

    public function test_index_handles_exceptions()
    {
        // Force an exception by mocking the query
        $this->mock(PatientInsurance::class, function ($mock) {
            $mock->shouldReceive('with->get')->andThrow(new \Exception('Database error'));
        });

        $response = $this->getJson('/api/patient-insurance');

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Failed to load insurance records'
                ]);
    }

    public function test_store_creates_insurance_record()
    {
        Storage::fake('public');

        $insuranceData = [
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456789',
            'group_number' => 'GRP001',
            'member_id' => 'MEM123456',
            'effective_date' => '2023-01-01',
            'expiration_date' => '2024-01-01',
            'notes' => 'Primary insurance',
        ];

        $response = $this->postJson('/api/patient-insurance', $insuranceData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Insurance record created successfully'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'insurance' => [
                        'id',
                        'insurance_provider_id',
                        'policy_number',
                        'insurance_provider'
                    ]
                ]);

        $this->assertDatabaseHas('patient_insurances', [
            'policy_number' => 'POL123456789',
            'member_id' => 'MEM123456',
            'patient_id' => 1, // Default patient ID as per controller
        ]);
    }

    public function test_store_with_file_upload()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('insurance_card.jpg');

        $insuranceData = [
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456789',
            'member_id' => 'MEM123456',
            'effective_date' => '2023-01-01',
            'expiration_date' => '2024-01-01',
            'insurance_card' => $file,
        ];

        $response = $this->postJson('/api/patient-insurance', $insuranceData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true
                ]);

        // Check that file was stored
        $insurance = PatientInsurance::where('policy_number', 'POL123456789')->first();
        $this->assertNotNull($insurance->card_path);
        $this->assertTrue(Storage::disk('public')->exists($insurance->card_path));
    }

    public function test_store_validation_errors()
    {
        $response = $this->postJson('/api/patient-insurance', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'insurance_provider_id',
                        'policy_number',
                        'member_id',
                        'effective_date',
                        'expiration_date'
                    ]
                ]);
    }

    public function test_store_invalid_insurance_provider()
    {
        $response = $this->postJson('/api/patient-insurance', [
            'insurance_provider_id' => 99999, // Non-existent
            'policy_number' => 'POL123456789',
            'member_id' => 'MEM123456',
            'effective_date' => '2023-01-01',
            'expiration_date' => '2024-01-01',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('insurance_provider_id');
    }

    public function test_store_expiration_before_effective_date()
    {
        $response = $this->postJson('/api/patient-insurance', [
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456789',
            'member_id' => 'MEM123456',
            'effective_date' => '2024-01-01',
            'expiration_date' => '2023-01-01', // Before effective date
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('expiration_date');
    }

    public function test_store_invalid_file_type()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.txt', 1000);

        $response = $this->postJson('/api/patient-insurance', [
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456789',
            'member_id' => 'MEM123456',
            'effective_date' => '2023-01-01',
            'expiration_date' => '2024-01-01',
            'insurance_card' => $file,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('insurance_card');
    }

    public function test_store_file_too_large()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('large.pdf', 6000); // 6MB, exceeds 5MB limit

        $response = $this->postJson('/api/patient-insurance', [
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456789',
            'member_id' => 'MEM123456',
            'effective_date' => '2023-01-01',
            'expiration_date' => '2024-01-01',
            'insurance_card' => $file,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('insurance_card');
    }

    public function test_store_handles_exceptions()
    {
        // Mock an exception during creation
        $this->mock(PatientInsurance::class, function ($mock) {
            $mock->shouldReceive('create')->andThrow(new \Exception('Database error'));
        });

        $response = $this->postJson('/api/patient-insurance', [
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456789',
            'member_id' => 'MEM123456',
            'effective_date' => '2023-01-01',
            'expiration_date' => '2024-01-01',
        ]);

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Failed to create insurance record'
                ]);
    }

    public function test_show_returns_insurance_record()
    {
        $response = $this->getJson("/api/patient-insurance/{$this->insurance->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'success',
                    'insurance' => [
                        'id',
                        'insurance_provider_id',
                        'policy_number',
                        'insurance_provider'
                    ]
                ]);
    }

    public function test_show_insurance_not_found()
    {
        $response = $this->getJson('/api/patient-insurance/99999');

        $response->assertStatus(404);
    }

    public function test_show_handles_exceptions()
    {
        // Mock an exception
        $this->mock(PatientInsurance::class, function ($mock) {
            $mock->shouldReceive('load')->andThrow(new \Exception('Database error'));
        });

        $response = $this->getJson("/api/patient-insurance/{$this->insurance->id}");

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Failed to load insurance record'
                ]);
    }

    public function test_update_modifies_insurance_record()
    {
        $updateData = [
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'UPDATED_POL123',
            'member_id' => 'UPDATED_MEM123',
            'effective_date' => '2023-02-01',
            'expiration_date' => '2024-02-01',
            'notes' => 'Updated notes',
        ];

        $response = $this->putJson("/api/patient-insurance/{$this->insurance->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Insurance record updated successfully'
                ]);

        $this->insurance->refresh();
        $this->assertEquals('UPDATED_POL123', $this->insurance->policy_number);
        $this->assertEquals('UPDATED_MEM123', $this->insurance->member_id);
    }

    public function test_update_with_file_replaces_existing()
    {
        Storage::fake('public');

        // Set existing file
        $this->insurance->update(['card_path' => 'insurance_cards/old_file.jpg']);
        Storage::disk('public')->put('insurance_cards/old_file.jpg', 'old content');

        $newFile = UploadedFile::fake()->image('new_card.jpg');

        $response = $this->putJson("/api/patient-insurance/{$this->insurance->id}", [
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => $this->insurance->policy_number,
            'member_id' => $this->insurance->member_id,
            'effective_date' => $this->insurance->effective_date->format('Y-m-d'),
            'expiration_date' => $this->insurance->expiration_date->format('Y-m-d'),
            'insurance_card' => $newFile,
        ]);

        $response->assertStatus(200);

        $this->insurance->refresh();
        $this->assertNotNull($this->insurance->card_path);
        $this->assertFalse(Storage::disk('public')->exists('insurance_cards/old_file.jpg'));
        $this->assertTrue(Storage::disk('public')->exists($this->insurance->card_path));
    }

    public function test_update_validation_errors()
    {
        $response = $this->putJson("/api/patient-insurance/{$this->insurance->id}", [
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => '', // Required but empty
            'member_id' => $this->insurance->member_id,
            'effective_date' => $this->insurance->effective_date->format('Y-m-d'),
            'expiration_date' => $this->insurance->expiration_date->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('policy_number');
    }

    public function test_update_handles_exceptions()
    {
        // Mock an exception during update
        $this->mock(PatientInsurance::class, function ($mock) {
            $mock->shouldReceive('update')->andThrow(new \Exception('Database error'));
        });

        $response = $this->putJson("/api/patient-insurance/{$this->insurance->id}", [
            'insurance_provider_id' => $this->insuranceProvider->id,
            'policy_number' => 'POL123456789',
            'member_id' => 'MEM123456',
            'effective_date' => '2023-01-01',
            'expiration_date' => '2024-01-01',
        ]);

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Failed to update insurance record'
                ]);
    }

    public function test_destroy_deletes_insurance_record()
    {
        $response = $this->deleteJson("/api/patient-insurance/{$this->insurance->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Insurance record deleted successfully'
                ]);

        $this->assertDatabaseMissing('patient_insurances', [
            'id' => $this->insurance->id,
        ]);
    }

    public function test_destroy_with_file_deletes_file()
    {
        Storage::fake('public');

        // Set file path
        $this->insurance->update(['card_path' => 'insurance_cards/test_file.jpg']);
        Storage::disk('public')->put('insurance_cards/test_file.jpg', 'file content');

        $response = $this->deleteJson("/api/patient-insurance/{$this->insurance->id}");

        $response->assertStatus(200);

        $this->assertFalse(Storage::disk('public')->exists('insurance_cards/test_file.jpg'));
    }

    public function test_destroy_handles_exceptions()
    {
        // Mock an exception during deletion
        $this->mock(PatientInsurance::class, function ($mock) {
            $mock->shouldReceive('delete')->andThrow(new \Exception('Database error'));
        });

        $response = $this->deleteJson("/api/patient-insurance/{$this->insurance->id}");

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Failed to delete insurance record'
                ]);
    }
}
