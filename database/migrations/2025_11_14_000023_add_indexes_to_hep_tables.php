<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Exercises table indexes
        Schema::table('exercises', function (Blueprint $table) {
            $table->index('category');
            $table->index('difficulty_level');
        });

        // HEP Programs table indexes
        Schema::table('hep_programs', function (Blueprint $table) {
            $table->index('doctor_id');
            $table->index('patient_id');
            $table->index('diagnosis_id');
            $table->index('appointment_id');
            $table->index('status');
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
        });

        // HEP Exercises table indexes
        Schema::table('hep_exercises', function (Blueprint $table) {
            $table->index('hep_program_id');
            $table->index('exercise_id');
            $table->index('week_number');
            $table->index(['hep_program_id', 'week_number']);
        });

        // HEP Assignments table indexes
        Schema::table('hep_assignments', function (Blueprint $table) {
            $table->index('hep_program_id');
            $table->index('patient_id');
            $table->index('assigned_by');
            $table->index('completion_status');
            $table->index('due_date');
            $table->index(['patient_id', 'completion_status']);
            $table->index(['due_date', 'completion_status']);
        });

        // HEP Progress table indexes
        Schema::table('hep_progress', function (Blueprint $table) {
            $table->index('hep_assignment_id');
            $table->index('hep_exercise_id');
            $table->index('date');
            $table->index(['hep_assignment_id', 'date']);
            $table->index(['hep_exercise_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order with error handling
        $this->dropIndexIfExists('hep_progress', 'hep_progress_hep_exercise_id_date_index');
        $this->dropIndexIfExists('hep_progress', 'hep_progress_hep_assignment_id_date_index');
        $this->dropIndexIfExists('hep_progress', 'hep_progress_date_index');
        $this->dropIndexIfExists('hep_progress', 'hep_progress_hep_exercise_id_index');
        $this->dropIndexIfExists('hep_progress', 'hep_progress_hep_assignment_id_index');

        $this->dropIndexIfExists('hep_assignments', 'hep_assignments_due_date_completion_status_index');
        $this->dropIndexIfExists('hep_assignments', 'hep_assignments_patient_id_completion_status_index');
        $this->dropIndexIfExists('hep_assignments', 'hep_assignments_due_date_index');
        $this->dropIndexIfExists('hep_assignments', 'hep_assignments_completion_status_index');
        $this->dropIndexIfExists('hep_assignments', 'hep_assignments_assigned_by_index');
        $this->dropIndexIfExists('hep_assignments', 'hep_assignments_patient_id_index');
        $this->dropIndexIfExists('hep_assignments', 'hep_assignments_hep_program_id_index');

        $this->dropIndexIfExists('hep_exercises', 'hep_exercises_hep_program_id_week_number_index');
        $this->dropIndexIfExists('hep_exercises', 'hep_exercises_week_number_index');
        $this->dropIndexIfExists('hep_exercises', 'hep_exercises_exercise_id_index');
        $this->dropIndexIfExists('hep_exercises', 'hep_exercises_hep_program_id_index');

        $this->dropIndexIfExists('hep_programs', 'hep_programs_doctor_id_status_index');
        $this->dropIndexIfExists('hep_programs', 'hep_programs_patient_id_status_index');
        $this->dropIndexIfExists('hep_programs', 'hep_programs_status_index');
        $this->dropIndexIfExists('hep_programs', 'hep_programs_appointment_id_index');
        $this->dropIndexIfExists('hep_programs', 'hep_programs_diagnosis_id_index');
        $this->dropIndexIfExists('hep_programs', 'hep_programs_patient_id_index');
        $this->dropIndexIfExists('hep_programs', 'hep_programs_doctor_id_index');

        $this->dropIndexIfExists('exercises', 'exercises_difficulty_level_index');
        $this->dropIndexIfExists('exercises', 'exercises_category_index');
    }

    /**
     * Drop an index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            Schema::table($table, function ($table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Exception $e) {
            // Index doesn't exist, continue silently
        }
    }
};
