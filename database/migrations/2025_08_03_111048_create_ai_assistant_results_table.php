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
        Schema::create('ai_assistant_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('diagnosis_id')->nullable()->constrained('diagnoses')->onDelete('cascade');
            $table->enum('source', ['ai_diagnosis', 'voice_assistant'])->default('ai_diagnosis');
            $table->text('ai_analysis'); // The AI analysis result
            $table->json('patient_data')->nullable(); // Patient information used for analysis
            $table->text('voice_transcript')->nullable(); // For voice assistant results
            $table->string('voice_file_path')->nullable(); // Path to original voice file
            $table->string('session_id')->nullable(); // For voice assistant sessions
            $table->json('usage_data')->nullable(); // OpenAI usage tracking
            $table->enum('status', ['pending', 'linked_to_diagnosis', 'archived'])->default('pending');
            $table->timestamps();

            $table->index(['doctor_id', 'created_at']);
            $table->index(['patient_id', 'created_at']);
            $table->index(['diagnosis_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_assistant_results');
    }
};
