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
        Schema::create('hep_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hep_assignment_id')->constrained('hep_assignments')->onDelete('cascade');
            $table->foreignId('hep_exercise_id')->constrained('hep_exercises')->onDelete('cascade');
            $table->date('date');
            $table->integer('completed_sets');
            $table->integer('completed_reps');
            $table->integer('duration_completed'); // in seconds
            $table->integer('pain_level'); // 1-10
            $table->integer('difficulty_rating'); // 1-10
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hep_progress');
    }
};
