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
        Schema::create('hep_template_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hep_program_template_id')->constrained('hep_program_templates')->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained('exercises')->onDelete('cascade');
            $table->integer('sets')->nullable();
            $table->integer('reps')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('rest_seconds')->nullable();
            $table->string('frequency')->nullable();
            $table->text('progression_notes')->nullable();
            $table->integer('week_number');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('hep_program_template_id');
            $table->index('exercise_id');
            $table->index('week_number');
            $table->index(['hep_program_template_id', 'week_number']);
            $table->index(['hep_program_template_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hep_template_exercises');
    }
};
