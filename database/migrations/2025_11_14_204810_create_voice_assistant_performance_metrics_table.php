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
        Schema::create('voice_assistant_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('session_id');
            $table->string('processing_type')->default('hybrid'); // 'live', 'server', 'hybrid'

            // Success/Failure tracking
            $table->boolean('live_transcription_success')->default(false);
            $table->boolean('server_processing_success')->default(false);
            $table->boolean('medical_extraction_success')->default(false);
            $table->boolean('ai_analysis_success')->default(false);
            $table->boolean('overall_success')->default(false);

            // Performance metrics
            $table->decimal('live_transcription_time', 8, 3)->nullable(); // seconds
            $table->decimal('server_processing_time', 8, 3)->nullable(); // seconds
            $table->decimal('medical_extraction_time', 8, 3)->nullable(); // seconds
            $table->decimal('ai_analysis_time', 8, 3)->nullable(); // seconds
            $table->decimal('total_processing_time', 8, 3)->nullable(); // seconds

            // Audio quality metrics
            $table->integer('audio_file_size')->nullable(); // bytes
            $table->decimal('audio_duration', 8, 3)->nullable(); // seconds
            $table->string('audio_format')->nullable(); // wav, mp3, webm, etc.
            $table->decimal('audio_sample_rate', 8, 1)->nullable(); // Hz
            $table->integer('audio_channels')->nullable(); // 1 or 2
            $table->decimal('average_audio_level', 5, 2)->nullable(); // 0-100

            // Transcription quality
            $table->integer('live_transcript_length')->nullable(); // characters
            $table->integer('server_transcript_length')->nullable(); // characters
            $table->decimal('transcript_improvement_ratio', 5, 2)->nullable(); // server/live ratio
            $table->boolean('server_better_than_live')->nullable();

            // Medical data extraction
            $table->integer('extracted_symptoms_count')->nullable();
            $table->integer('extracted_medical_history_count')->nullable();
            $table->integer('extracted_physical_findings_count')->nullable();
            $table->integer('extracted_medications_count')->nullable();
            $table->integer('extracted_vital_signs_count')->nullable();

            // Error tracking
            $table->string('error_type')->nullable(); // 'audio_upload', 'transcription', 'extraction', 'analysis'
            $table->text('error_message')->nullable();

            // User feedback (to be collected later)
            $table->integer('user_satisfaction_rating')->nullable(); // 1-5 scale
            $table->text('user_feedback')->nullable();

            // Metadata
            $table->string('browser_info')->nullable();
            $table->string('device_type')->nullable();
            $table->string('network_type')->nullable();
            $table->decimal('connection_speed', 8, 2)->nullable(); // Mbps

            $table->timestamps();

            // Indexes
            $table->index(['doctor_id', 'created_at'], 'vapm_doctor_date_idx');
            $table->index(['processing_type', 'overall_success'], 'vapm_type_success_idx');
            $table->index('session_id', 'vapm_session_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_assistant_performance_metrics');
    }
};
