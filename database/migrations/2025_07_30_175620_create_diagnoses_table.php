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
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['manual', 'ai'])->default('manual');
            $table->text('diagnosis_text');
            $table->text('voice_transcript')->nullable(); // For voice input transcript
            $table->string('voice_file_path')->nullable(); // Path to original voice file
            $table->json('patient_data')->nullable(); // Store patient information used for diagnosis
            $table->text('ai_response')->nullable(); // AI response for AI diagnoses
            $table->integer('follow_up_count')->default(0); // Track follow-up questions
            $table->boolean('patient_notified')->default(false);
            $table->timestamp('patient_viewed_at')->nullable();
            $table->boolean('patient_reviewed')->default(false);
            $table->timestamps();

            $table->index(['doctor_id', 'created_at']);
            $table->index(['patient_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
