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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('category');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced']);
            $table->text('instructions');
            $table->string('video_url')->nullable();
            $table->string('image_url')->nullable();
            $table->text('contraindications')->nullable();
            $table->text('equipment_required')->nullable();
            $table->text('target_muscle_groups')->nullable();
            $table->integer('duration'); // in seconds
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
