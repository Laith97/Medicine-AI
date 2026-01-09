<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PatientMonitoringSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClinicalMonitoringApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'doctor']);
    }

    public function test_receive_vitals_endpoint()
    {
        $patient = User::factory()->create(['role' => 'patient']);
        
        $response = $this->actingAs($this->user)
            ->postJson("/api/monitoring/{$patient->id}/vitals", [
                'vitals' => [
                    ['name' => 'heart_rate', 'value' => 85, 'unit' => 'bpm'],
                    ['name' => 'systolic_bp', 'value' => 125, 'unit' => 'mmHg'],
                ]
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Vitals received and processing started']);
            
        $this->assertDatabaseHas('clinical_indicators', [
            'patient_id' => $patient->id,
            'name' => 'heart_rate',
            'value' => 85
        ]);
    }

    public function test_get_alerts_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/monitoring/alerts');

        $response->assertStatus(200);
    }
}
