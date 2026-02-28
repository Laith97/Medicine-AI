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
        Schema::create('hep_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hep_program_id')->constrained('hep_programs')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at');
            $table->date('due_date');
            $table->enum('completion_status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
            $table->text('patient_notes')->nullable();
            $table->text('clinician_feedback')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hep_assignments');
    }
};
