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
        Schema::create('voice_transcriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('session_id')->index(); // To group related transcriptions
            $table->longText('raw_transcription'); // Full voice transcription
            $table->json('extracted_data')->nullable(); // Structured medical data
            $table->longText('ai_analysis')->nullable(); // AI-generated analysis
            $table->json('structured_chart')->nullable(); // Auto-filled chart data
            $table->boolean('is_confirmed')->default(false); // Doctor confirmation
            $table->boolean('is_final')->default(false); // Final submission
            $table->string('status')->default('active'); // active, paused, completed
            $table->timestamp('session_started_at')->nullable();
            $table->timestamp('session_ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_transcriptions');
    }
};
