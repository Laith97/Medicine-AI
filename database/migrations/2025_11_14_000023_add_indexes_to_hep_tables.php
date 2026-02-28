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
        // Drop indexes in reverse order
        Schema::table('hep_progress', function (Blueprint $table) {
            $table->dropIndex(['hep_exercise_id', 'date']);
            $table->dropIndex(['hep_assignment_id', 'date']);
            $table->dropIndex('date');
            $table->dropIndex('hep_exercise_id');
            $table->dropIndex('hep_assignment_id');
        });

        Schema::table('hep_assignments', function (Blueprint $table) {
            $table->dropIndex(['due_date', 'completion_status']);
            $table->dropIndex(['patient_id', 'completion_status']);
            $table->dropIndex('due_date');
            $table->dropIndex('completion_status');
            $table->dropIndex('assigned_by');
            $table->dropIndex('patient_id');
            $table->dropIndex('hep_program_id');
        });

        Schema::table('hep_exercises', function (Blueprint $table) {
            $table->dropIndex(['hep_program_id', 'week_number']);
            $table->dropIndex('week_number');
            $table->dropIndex('exercise_id');
            $table->dropIndex('hep_program_id');
        });

        Schema::table('hep_programs', function (Blueprint $table) {
            $table->dropIndex(['doctor_id', 'status']);
            $table->dropIndex(['patient_id', 'status']);
            $table->dropIndex('status');
            $table->dropIndex('appointment_id');
            $table->dropIndex('diagnosis_id');
            $table->dropIndex('patient_id');
            $table->dropIndex('doctor_id');
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->dropIndex('difficulty_level');
            $table->dropIndex('category');
        });
    }
};
