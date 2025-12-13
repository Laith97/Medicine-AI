<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('medical_audio_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->text('audio_file_path')->nullable(); // NULL for real-time streaming
            $table->json('transcript_json')->nullable();
            $table->foreignId('soap_note_draft_id')->nullable()->constrained('soap_notes')->nullOnDelete();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('auto_deleted_at')->nullable(); // Auto-delete trigger
            $table->timestamps();
        });

        Schema::create('transcript_speaker_segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('audio_session_id')->constrained('medical_audio_sessions')->cascadeOnDelete();
            $table->string('speaker_tag', 20)->nullable();
            $table->text('transcript_text')->nullable();
            $table->decimal('start_time', 10, 3)->nullable();
            $table->decimal('end_time', 10, 3)->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->json('medical_entities')->nullable();
            $table->string('soap_section', 20)->nullable(); // 'subjective','objective','assessment','plan'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transcript_speaker_segments');
        Schema::dropIfExists('medical_audio_sessions');
    }
};
