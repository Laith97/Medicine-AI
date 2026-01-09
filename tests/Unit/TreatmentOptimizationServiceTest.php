<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\ClinicalIndicator;
use App\Models\PatientTreatmentResponse;
use App\Models\Prescription;
use App\Services\TreatmentOptimizationService;
use App\Services\AIAssistant;
use App\Services\DrugInteractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

class TreatmentOptimizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TreatmentOptimizationService $service;
    private $aiAssistant;
    private $drugInteractionService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->aiAssistant = $this->createMock(AIAssistant::class);
        $this->drugInteractionService = $this->createMock(DrugInteractionService::class);
        
        $this->service = new TreatmentOptimizationService(
            $this->aiAssistant,
            $this->drugInteractionService
        );
    }

    public function test_it_calculates_optimization_scores_correctly()
    {
        $patient = User::factory()->create();
        
        // Mock treatment history with adverse reaction
        PatientTreatmentResponse::create([
            'patient_id' => $patient->id,
            'medication_name' => 'Drug A',
            'outcome' => 'adverse_reaction',
            'side_effects' => ['Nausea'],
            'start_date' => now()->subMonth()
        ]);

        // Mock low adherence
        PatientTreatmentResponse::create([
            'patient_id' => $patient->id,
            'medication_name' => 'Drug B',
            'outcome' => 'effective',
            'adherence_rate' => 0.5,
            'start_date' => now()->subMonths(2)
        ]);

        // Access private method for testing scores
        $reflection = new \ReflectionClass(TreatmentOptimizationService::class);
        $method = $reflection->getMethod('calculateOptimizationScores');
        $method->setAccessible(true);

        $treatmentHistory = [
            'adverse_reactions' => [['medication' => 'Drug A']],
            'average_adherence' => 0.5
        ];

        $scores = $method->invokeArgs($this->service, [[], $treatmentHistory, []]);

        $this->assertEquals(0.70, $scores['effectiveness']); // 0.85 - 0.15
        $this->assertEquals(0.80, $scores['safety']); // 0.90 - 0.10
    }

    public function test_it_analyzes_treatment_history_correctly()
    {
        $patient = User::factory()->create();
        
        PatientTreatmentResponse::create([
            'patient_id' => $patient->id,
            'medication_name' => 'Drug A',
            'outcome' => 'effective',
            'effectiveness_score' => 0.9,
            'adherence_rate' => 0.95,
            'start_date' => now()->subMonth()
        ]);

        $reflection = new \ReflectionClass(TreatmentOptimizationService::class);
        $method = $reflection->getMethod('analyzeTreatmentHistory');
        $method->setAccessible(true);

        $analysis = $method->invoke($this->service, $patient->id);

        $this->assertCount(1, $analysis['effective_treatments']);
        $this->assertEquals(0.95, $analysis['average_adherence']);
    }
}
