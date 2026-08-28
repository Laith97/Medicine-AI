<?php

namespace Tests\Feature;

use App\Models\Diagnosis;
use App\Models\DoctorNote;
use App\Models\Exercise;
use App\Models\HepProgram;
use App\Models\User;
use App\Jobs\GenerateHEPProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HEPGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient;
    protected $diagnosis;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->patient = User::factory()->create(['role' => 'patient']);
        $this->patient->primary_doctor_id = $this->doctor->id;
        $this->patient->save();

        // Create test diagnosis
        $this->diagnosis = Diagnosis::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'diagnosis_text' => 'Low back pain with radiculopathy',
        ]);

        // Create some test exercises
        Exercise::factory()->create([
            'name' => 'Seated Leg Lifts',
            'category' => 'strength',
            'difficulty_level' => 'beginner',
        ]);

        Exercise::factory()->create([
            'name' => 'Gentle Walking',
            'category' => 'cardiovascular',
            'difficulty_level' => 'beginner',
        ]);
    }

    /** @test */
    public function doctor_can_generate_hep_program_synchronously()
    {
        $this->actingAs($this->doctor, 'web');

        $response = $this->postJson('/api/hep/generate', [
            'diagnosis_id' => $this->diagnosis->id,
            'patient_id' => $this->patient->id,
            'use_background_processing' => false,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'program' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'patient',
                        'doctor',
                        'hep_exercises',
                    ]
                ]);

        $this->assertDatabaseHas('hep_programs', [
            'diagnosis_id' => $this->diagnosis->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
        ]);
    }

    /** @test */
    public function doctor_can_generate_hep_program_asynchronously()
    {
        Queue::fake();

        $this->actingAs($this->doctor, 'web');

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

        Queue::assertPushed(GenerateHEPProgram::class, function ($job) {
            return $job->diagnosis->id === $this->diagnosis->id &&
                   $job->patient->id === $this->patient->id &&
                   $job->doctor->id === $this->doctor->id;
        });
    }

    /** @test */
    public function doctor_can_view_hep_programs()
    {
        $program = HepProgram::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'diagnosis_id' => $this->diagnosis->id,
        ]);

        $this->actingAs($this->doctor, 'web');

        $response = $this->getJson('/api/hep/programs');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'status',
                            'patient',
                            'diagnosis',
                        ]
                    ]
                ]);
    }

    /** @test */
    public function patient_can_view_their_hep_programs()
    {
        $program = HepProgram::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'diagnosis_id' => $this->diagnosis->id,
        ]);

        $this->actingAs($this->patient, 'web');

        $response = $this->getJson('/api/hep/programs');

        $response->assertStatus(200)
                ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function doctor_can_create_hep_assignment()
    {
        $program = HepProgram::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'diagnosis_id' => $this->diagnosis->id,
        ]);

        $this->actingAs($this->doctor, 'web');

        $response = $this->postJson('/api/hep/assignments', [
            'hep_program_id' => $program->id,
            'patient_id' => $this->patient->id,
            'due_date' => now()->addWeeks(4)->toDateString(),
            'patient_notes' => 'Please perform exercises 3 times per week',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'assignment' => [
                        'id',
                        'hep_program_id',
                        'patient_id',
                        'assigned_by',
                        'completion_status',
                        'patient_notes',
                    ]
                ]);

        $this->assertDatabaseHas('hep_assignments', [
            'hep_program_id' => $program->id,
            'patient_id' => $this->patient->id,
            'assigned_by' => $this->doctor->id,
            'patient_notes' => 'Please perform exercises 3 times per week',
        ]);
    }

    /** @test */
    public function patient_can_update_progress()
    {
        $program = HepProgram::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'diagnosis_id' => $this->diagnosis->id,
        ]);

        $assignment = $program->hepAssignments()->create([
            'patient_id' => $this->patient->id,
            'assigned_by' => $this->doctor->id,
            'assigned_at' => now(),
            'due_date' => now()->addWeeks(4),
            'completion_status' => 'pending',
        ]);

        $exercise = $program->hepExercises()->create([
            'exercise_id' => Exercise::factory()->create()->id,
            'sets' => 2,
            'reps' => 10,
            'week_number' => 1,
            'order' => 1,
        ]);

        $this->actingAs($this->patient, 'web');

        $response = $this->postJson("/api/hep/assignments/{$assignment->id}/progress", [
            'progress_data' => [
                [
                    'hep_exercise_id' => $exercise->id,
                    'date' => now()->toDateString(),
                    'completed_sets' => 2,
                    'completed_reps' => 10,
                    'pain_level' => 3,
                    'difficulty_rating' => 4,
                    'notes' => 'Exercise completed successfully',
                ]
            ]
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Progress updated successfully',
                ]);

        $this->assertDatabaseHas('hep_progress', [
            'hep_assignment_id' => $assignment->id,
            'hep_exercise_id' => $exercise->id,
            'completed_sets' => 2,
            'completed_reps' => 10,
            'pain_level' => 3,
            'difficulty_rating' => 4,
            'notes' => 'Exercise completed successfully',
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_generate_hep()
    {
        $otherDoctor = User::factory()->create(['role' => 'doctor']);

        $this->actingAs($otherDoctor, 'web');

        $response = $this->postJson('/api/hep/generate', [
            'diagnosis_id' => $this->diagnosis->id,
            'patient_id' => $this->patient->id,
        ]);

        $response->assertStatus(422); // Validation error due to authorization check
    }

    /** @test */
    public function invalid_diagnosis_id_returns_error()
    {
        $this->actingAs($this->doctor, 'web');

        $response = $this->postJson('/api/hep/generate', [
            'diagnosis_id' => 99999,
            'patient_id' => $this->patient->id,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['diagnosis_id']);
    }
}
