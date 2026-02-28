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
        Schema::create('hep_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hep_program_id')->constrained('hep_programs')->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained('exercises')->onDelete('cascade');
            $table->integer('sets');
            $table->integer('reps')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('rest_seconds');
            $table->string('frequency');
            $table->text('progression_notes')->nullable();
            $table->integer('week_number');
            $table->integer('order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hep_exercises');
    }
};
