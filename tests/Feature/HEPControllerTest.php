<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;
use App\Models\HepProgram;
use App\Models\HepAssignment;
use App\Models\HepProgress;
use App\Models\Diagnosis;
use App\Models\Specialty;
use App\Jobs\GenerateHEPProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class HEPControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient;
    protected $diagnosis;
    protected $hepProgram;
    protected $hepAssignment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create specialty
        $specialty = Specialty::factory()->create();

        // Create doctor user
        $doctorUser = User::factory()->create(['role' => 'doctor']);

        // Create doctor
        $this->doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,
        ]);

        // Create patient
        $this->patient = User::factory()->create(['role' => 'patient']);

        // Create diagnosis
        $this->diagnosis = Diagnosis::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        // Create HEP program
        $this->hepProgram = HepProgram::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis_id' => $this->diagnosis->id,
        ]);

        // Create HEP assignment
        $this->hepAssignment = HepAssignment::factory()->create([
            'hep_program_id' => $this->hepProgram->id,
            'patient_id' => $this->patient->id,
            'assigned_by' => $this->doctor->id,
        ]);
    }

    public function test_generate_hep_program_background_processing()
    {
        $this->actingAs($this->doctor->user);
        Queue::fake();

        $response = $this->postJson('/api/hep/generate', [
            'diagnosis_id' => $this->diagnosis->id,
            'patient_id' => $this->patient->id,
            'use_background_processing' => true,
        ]);

        $response->assertStatus(202)
                ->assertJson([
                    'message' => 'HEP program generation started. You will be notified when complete.',
                    'status' => 'processing',
                ]);

        Queue::assertPushed(GenerateHEPProgram::class);
    }

    public function test_generate_hep_program_synchronous()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->postJson('/api/hep/generate', [
            'diagnosis_id' => $this->diagnosis->id,
            'patient_id' => $this->patient->id,
            'use_background_processing' => false,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'HEP program generated successfully'
                ])
                ->assertJsonStructure([
                    'message',
                    'program' => [
                        'id',
                        'patient',
                        'doctor',
                        'hepExercises'
                    ]
                ]);
    }

    public function test_generate_hep_program_validation_error()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->postJson('/api/hep/generate', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['diagnosis_id', 'patient_id']);
    }

    public function test_generate_hep_program_diagnosis_not_found()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->postJson('/api/hep/generate', [
            'diagnosis_id' => 99999,
            'patient_id' => $this->patient->id,
        ]);

        $response->assertStatus(500)
                ->assertJson([
                    'message' => 'Failed to generate HEP program'
                ]);
    }

    public function test_index_doctor_can_view_own_programs()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->getJson('/api/hep/programs');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'patient',
                            'diagnosis',
                            'status'
                        ]
                    ]
                ]);
    }

    public function test_index_patient_can_view_own_programs()
    {
        $this->actingAs($this->patient);

        $response = $this->getJson('/api/hep/programs');

        $response->assertStatus(200);
    }

    public function test_index_with_filters()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->getJson('/api/hep/programs?status=active&patient_id=' . $this->patient->id);

        $response->assertStatus(200);
    }

    public function test_show_program_doctor_can_view()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->getJson("/api/hep/programs/{$this->hepProgram->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'patient',
                    'doctor',
                    'diagnosis',
                    'hepExercises',
                    'hepAssignments'
                ]);
    }

    public function test_show_program_patient_can_view_own()
    {
        $this->actingAs($this->patient);

        $response = $this->getJson("/api/hep/programs/{$this->hepProgram->id}");

        $response->assertStatus(200);
    }

    public function test_show_program_unauthorized()
    {
        // Create another doctor
        $otherDoctor = User::factory()->create(['role' => 'doctor']);
        $this->actingAs($otherDoctor);

        $response = $this->getJson("/api/hep/programs/{$this->hepProgram->id}");

        $response->assertStatus(403);
    }

    public function test_update_program_status()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->putJson("/api/hep/programs/{$this->hepProgram->id}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'HEP program updated successfully'
                ]);

        $this->hepProgram->refresh();
        $this->assertEquals('completed', $this->hepProgram->status);
    }

    public function test_update_program_validation_error()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->putJson("/api/hep/programs/{$this->hepProgram->id}", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('status');
    }

    public function test_destroy_program()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->deleteJson("/api/hep/programs/{$this->hepProgram->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'HEP program deleted successfully'
                ]);

        $this->assertDatabaseMissing('hep_programs', [
            'id' => $this->hepProgram->id,
        ]);
    }

    public function test_generate_compliance_document()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->getJson("/api/hep/programs/{$this->hepProgram->id}/compliance-document");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'document',
                    'program_id'
                ]);
    }

    public function test_create_assignment()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->postJson('/api/hep/assignments', [
            'hep_program_id' => $this->hepProgram->id,
            'patient_id' => $this->patient->id,
            'due_date' => now()->addWeeks(4)->toDateString(),
            'patient_notes' => 'Please complete exercises daily',
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'HEP assignment created successfully'
                ])
                ->assertJsonStructure([
                    'message',
                    'assignment' => [
                        'id',
                        'hep_program_id',
                        'patient_id',
                        'completion_status'
                    ]
                ]);
    }

    public function test_create_assignment_validation_error()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->postJson('/api/hep/assignments', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['hep_program_id', 'patient_id']);
    }

    public function test_update_assignment()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->putJson("/api/hep/assignments/{$this->hepAssignment->id}", [
            'completion_status' => 'in_progress',
            'patient_notes' => 'Updated notes',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'HEP assignment updated successfully'
                ]);
    }

    public function test_get_assignments_doctor_view()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->getJson('/api/hep/assignments');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'hep_program_id',
                            'patient_id',
                            'completion_status'
                        ]
                    ]
                ]);
    }

    public function test_get_assignments_patient_view()
    {
        $this->actingAs($this->patient);

        $response = $this->getJson('/api/hep/assignments');

        $response->assertStatus(200);
    }

    public function test_get_assignments_with_filters()
    {
        $this->actingAs($this->doctor->user);

        $response = $this->getJson('/api/hep/assignments?status=pending&program_id=' . $this->hepProgram->id);

        $response->assertStatus(200);
    }

    public function test_update_progress()
    {
        $this->actingAs($this->patient);

        $progressData = [
            [
                'hep_exercise_id' => 1,
                'date' => now()->toDateString(),
                'completed_sets' => 3,
                'completed_reps' => 10,
                'pain_level' => 2,
                'difficulty_rating' => 3,
                'notes' => 'Exercise completed successfully',
            ]
        ];

        $response = $this->postJson("/api/hep/assignments/{$this->hepAssignment->id}/progress", [
            'progress_data' => $progressData,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Progress updated successfully'
                ]);

        $this->assertDatabaseHas('hep_progress', [
            'hep_assignment_id' => $this->hepAssignment->id,
            'completed_sets' => 3,
            'pain_level' => 2,
        ]);
    }

    public function test_update_progress_validation_error()
    {
        $this->actingAs($this->patient);

        $response = $this->postJson("/api/hep/assignments/{$this->hepAssignment->id}/progress", [
            'progress_data' => [], // Empty array
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors('progress_data');
    }

    public function test_get_progress()
    {
        $this->actingAs($this->patient);

        // Create some progress records
        HepProgress::factory()->create([
            'hep_assignment_id' => $this->hepAssignment->id,
            'date' => now()->toDateString(),
            'pain_level' => 3,
            'difficulty_rating' => 4,
        ]);

        $response = $this->getJson("/api/hep/assignments/{$this->hepAssignment->id}/progress");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'assignment',
                    'progress',
                    'summary' => [
                        'total_sessions',
                        'average_pain_level',
                        'average_difficulty',
                        'completion_percentage'
                    ]
                ]);
    }

    public function test_get_progress_unauthorized()
    {
        // Create another patient
        $otherPatient = User::factory()->create(['role' => 'patient']);
        $this->actingAs($otherPatient);

        $response = $this->getJson("/api/hep/assignments/{$this->hepAssignment->id}/progress");

        $response->assertStatus(403);
    }

    public function test_get_exercises()
    {
        $response = $this->getJson('/api/hep/exercises');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'category',
                            'difficulty_level'
                        ]
                    ]
                ]);
    }

    public function test_get_exercises_with_filters()
    {
        $response = $this->getJson('/api/hep/exercises?category=strength&difficulty_level=beginner&search=squat');

        $response->assertStatus(200);
    }

    public function test_get_exercises_pagination()
    {
        $response = $this->getJson('/api/hep/exercises?per_page=10');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'current_page',
                    'per_page',
                    'total'
                ]);
    }
}
