<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\HEPPersonalizationService;
use App\Services\HEPSafetyService;
use App\Models\User;
use App\Models\Diagnosis;
use App\Models\HepProgram;
use App\Models\HepExercise;
use App\Models\Exercise;
use App\Models\HepProgramTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HEPPersonalizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HEPPersonalizationService $personalizationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->personalizationService = app(HEPPersonalizationService::class);
    }

    public function test_select_appropriate_template()
    {
        // Create test templates
        HepProgramTemplate::create([
            'name' => 'Knee Osteoarthritis Program',
            'category' => 'orthopedic',
            'diagnosis_type' => 'knee_osteoarthritis',
            'is_active' => true,
        ]);

        HepProgramTemplate::create([
            'name' => 'General Orthopedic Program',
            'category' => 'orthopedic',
            'diagnosis_type' => 'other',
            'is_active' => true,
        ]);

        $patientProfile = [
            'diagnosis' => 'Knee osteoarthritis',
            'medical_conditions' => ['joint pain'],
            'functional_limitations' => ['limited ROM'],
            'treatment_goals' => ['reduce pain'],
        ];

        $template = $this->personalizationService->selectAppropriateTemplate($patientProfile);

        $this->assertNotNull($template);
        $this->assertEquals('knee_osteoarthritis', $template->diagnosis_type);
    }

    public function test_personalize_program_integration()
    {
        // Create test data
        $patient = User::factory()->create([
            'birth_date' => now()->subYears(60),
            'gender' => 'female',
        ]);

        $diagnosis = Diagnosis::factory()->create([
            'patient_id' => $patient->id,
            'diagnosis_text' => 'Knee osteoarthritis with limited range of motion and joint pain',
        ]);

        $doctor = User::factory()->create();

        $program = HepProgram::create([
            'title' => 'Test Program',
            'description' => 'Test description',
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'diagnosis_id' => $diagnosis->id,
            'duration_weeks' => 4,
            'frequency_per_week' => 3,
            'goals' => ['improve strength'],
            'precautions' => ['basic precautions'],
            'status' => 'active',
        ]);

        // Create a test exercise
        $exercise = Exercise::create([
            'name' => 'Knee Extension',
            'category' => 'strength',
            'difficulty_level' => 'intermediate',
            'contraindications' => ['osteoarthritis'],
        ]);

        HepExercise::create([
            'hep_program_id' => $program->id,
            'exercise_id' => $exercise->id,
            'sets' => 3,
            'reps' => 12,
            'week_number' => 1,
            'order' => 1,
            'progression_notes' => '',
        ]);

        // Personalize the program
        $personalizedProgram = $this->personalizationService->personalizeProgram(
            $program,
            $diagnosis,
            $patient,
            []
        );

        // Verify personalization was applied
        $this->assertNotNull($personalizedProgram);
        $personalizedProgram->refresh();

        // Check that precautions were added
        $this->assertContains('Avoid exercises that cause joint pain', $personalizedProgram->precautions);

        // Check that goals were updated
        $this->assertContains('reduce pain', $personalizedProgram->goals);

        // Check that personalization metadata was stored
        $this->assertNotNull($personalizedProgram->personalization_metadata);
        $this->assertArrayHasKey('personalized_at', $personalizedProgram->personalization_metadata);
        $this->assertArrayHasKey('patient_profile', $personalizedProgram->personalization_metadata);
        $this->assertArrayHasKey('personalization_rules', $personalizedProgram->personalization_metadata);
        $this->assertArrayHasKey('customizations_applied', $personalizedProgram->personalization_metadata);
    }

    public function test_validate_personalization()
    {
        $program = HepProgram::factory()->create();

        $issues = $this->personalizationService->validatePersonalization($program);

        // Should return an array (may be empty if no issues)
        $this->assertIsArray($issues);
    }
}
