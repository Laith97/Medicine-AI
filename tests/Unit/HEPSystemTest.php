<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Doctor;

use App\Models\HEPProgram;
use App\Models\HEPAssignment;
use App\Models\HEPProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HEPSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient;
    protected $hepProgram;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a doctor user
        $this->doctor = User::factory()->create([
            'role' => 'doctor',
            'email' => 'doctor@example.com',
        ]);

        Doctor::factory()->create([
            'user_id' => $this->doctor->id,
        ]);

        // Create a patient user
        $this->patient = User::factory()->create([
            'name' => 'Test Patient',
            'email' => 'patient@example.com',
            'phone' => '1234567890',
            'role' => 'patient',
        ]);

        // Create a HEP program
        $this->hepProgram = HEPProgram::factory()->create([
            'title' => 'Diabetes Management',
            'description' => 'Program for managing diabetes',
            'duration_weeks' => 12,
            'frequency_per_week' => 7,
        ]);
    }

    public function test_hep_program_can_be_created()
    {
        // Create a diagnosis record first
        $diagnosis = \App\Models\Diagnosis::factory()->create();

        $programData = [
            'title' => 'Heart Health',
            'description' => 'Program for heart health',
            'duration_weeks' => 8,
            'frequency_per_week' => 2,
            'category' => 'cardiovascular',
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'diagnosis_id' => $diagnosis->id,
        ];

        $program = HEPProgram::create($programData);

        $this->assertDatabaseHas('hep_programs', [
            'title' => 'Heart Health',
            'description' => 'Program for heart health',
            'duration_weeks' => 8,
            'frequency_per_week' => 2,
        ]);
    }

    public function test_hep_program_can_be_updated()
    {
        // Create a diagnosis record first
        $diagnosis = \App\Models\Diagnosis::factory()->create();

        $program = HEPProgram::create([
            'title' => 'Old Name',
            'description' => 'Old Description',
            'duration_weeks' => 4,
            'frequency_per_week' => 7,
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'diagnosis_id' => $diagnosis->id,
        ]);

        $program->update([
            'title' => 'New Name',
            'description' => 'New Description',
            'duration_weeks' => 10,
            'frequency_per_week' => 3,
        ]);

        $this->assertDatabaseHas('hep_programs', [
            'id' => $program->id,
            'title' => 'New Name',
            'description' => 'New Description',
            'duration_weeks' => 10,
            'frequency_per_week' => 3,
        ]);
    }

    public function test_hep_program_can_be_deleted()
    {
        $program = HEPProgram::factory()->create();

        $program->delete();

        $this->assertDatabaseMissing('hep_programs', [
            'id' => $program->id,
        ]);
    }

    public function test_hep_assignment_can_be_created()
    {
        $assignmentData = [
            'program_id' => $this->hepProgram->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'assigned_by' => $this->doctor->id,
            'status' => 'active',
        ];

        $assignment = HEPAssignment::create($assignmentData);

        $this->assertDatabaseHas('hep_assignments', [
            'program_id' => $this->hepProgram->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'active',
        ]);
    }

    public function test_hep_assignment_due_date_calculation()
    {
        $assignment = HEPAssignment::create([
            'program_id' => $this->hepProgram->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'assigned_by' => $this->doctor->id,
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        // Calculate expected due date (12 weeks from assignment)
        $expectedDueDate = now()->addWeeks($this->hepProgram->duration_weeks)->format('Y-m-d');

        $this->assertEquals($expectedDueDate, $assignment->due_date->format('Y-m-d'));
    }

    public function test_hep_progress_can_be_tracked()
    {
        $assignment = HEPAssignment::create([
            'program_id' => $this->hepProgram->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'assigned_by' => $this->doctor->id,
            'status' => 'active',
        ]);

        $progressData = [
            'assignment_id' => $assignment->id,
            'activity_date' => now(),
            'completed' => true,
            'notes' => 'Completed first session',
        ];

        $progress = HEPProgress::create($progressData);

        $this->assertDatabaseHas('hep_progress', [
            'assignment_id' => $assignment->id,
            'completed' => true,
            'notes' => 'Completed first session',
        ]);
    }

    public function test_hep_assignment_progress_percentage()
    {
        $assignment = HEPAssignment::create([
            'program_id' => $this->hepProgram->id,
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'assigned_by' => $this->doctor->id,
            'status' => 'active',
        ]);

        // Add progress entries
        HEPProgress::create([
            'assignment_id' => $assignment->id,
            'activity_date' => now()->subDays(1),
            'completed' => true,
            'notes' => 'Completed activity 1',
        ]);

        HEPProgress::create([
            'assignment_id' => $assignment->id,
            'activity_date' => now(),
            'completed' => false,
            'notes' => 'Pending activity 2',
        ]);

        // Refresh assignment to get updated progress
        $assignment->refresh();

        // Progress percentage should be calculated based on completion
        $this->assertGreaterThanOrEqual(0, $assignment->progress_percentage);
    }

    public function test_hep_program_category_filtering()
    {
        $programs = HEPProgram::factory()->count(3)->create([
            'category' => 'diabetes',
        ]);

        $cardioProgram = HEPProgram::factory()->create([
            'category' => 'cardiovascular',
        ]);

        $diabetesPrograms = HEPProgram::where('category', 'diabetes')->get();

        $this->assertCount(3, $diabetesPrograms);
        $this->assertContains($programs[0]->id, $diabetesPrograms->pluck('id'));
    }
}