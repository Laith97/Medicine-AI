<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\TreatmentOptimizationRecommendation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TreatmentOptimizationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_fetch_recommendations()
    {
        $user = User::factory()->create();
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

        TreatmentOptimizationRecommendation::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'effectiveness_score' => 0.9,
            'safety_score' => 0.9,
            'cost_efficiency_score' => 0.9,
            'recommended_medications' => [],
            'outcome_predictions' => [],
            'risk_assessment' => []
        ]);

        $response = $this->actingAs($user)->getJson("/api/treatment-optimization/{$patient->id}/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_it_can_validate_recommendation()
    {
        $user = User::factory()->create();
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

        $recommendation = TreatmentOptimizationRecommendation::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'effectiveness_score' => 0.9,
            'safety_score' => 0.9,
            'cost_efficiency_score' => 0.9,
            'recommended_medications' => [],
            'outcome_predictions' => [],
            'risk_assessment' => []
        ]);

        $response = $this->actingAs($user)->postJson("/api/treatment-optimization/{$recommendation->id}/validate");

        $response->assertStatus(200)
            ->assertJsonPath('recommendation.validated_by_doctor', true);
        
        $this->assertDatabaseHas('treatment_optimization_recommendations', [
            'id' => $recommendation->id,
            'validated_by_doctor' => true
        ]);
    }

    public function test_it_can_reject_recommendation()
    {
        $user = User::factory()->create();
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create(['patient_id' => $patient->id]);

        $recommendation = TreatmentOptimizationRecommendation::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'effectiveness_score' => 0.9,
            'safety_score' => 0.9,
            'cost_efficiency_score' => 0.9,
            'recommended_medications' => [],
            'outcome_predictions' => [],
            'risk_assessment' => []
        ]);

        $response = $this->actingAs($user)->postJson("/api/treatment-optimization/{$recommendation->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('recommendation.validated_by_doctor', false);
    }
}
