<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ambient_recording_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('doctor_id')->constrained('users');
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments');
            $table->string('session_uuid', 36)->unique();
            $table->enum('status', ['active', 'paused', 'completed', 'failed'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('audio_duration')->default(0);
            $table->string('audio_file_path', 255)->nullable();
            $table->longText('transcription')->nullable();
            $table->longText('ai_analysis')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambient_recording_sessions');
    }
};
