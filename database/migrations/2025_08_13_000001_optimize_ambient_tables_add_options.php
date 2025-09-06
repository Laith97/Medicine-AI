<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add helpful indexes
        Schema::table('ambient_recording_chunks', function (Blueprint $table) {
            $table->index(['session_id', 'recorded_at'], 'arc_session_recorded_idx');
        });

        Schema::table('ambient_recording_sessions', function (Blueprint $table) {
            $table->index('doctor_id', 'ars_doctor_idx');
            $table->index(['patient_id', 'started_at'], 'ars_patient_started_idx');
            // Options for ASR
            $table->string('language', 8)->nullable()->after('ai_analysis');
            $table->boolean('diarization_enabled')->default(false)->after('language');
        });

        Schema::table('real_time_insights', function (Blueprint $table) {
            $table->index(['session_id', 'timestamp'], 'rti_session_timestamp_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ambient_recording_chunks', function (Blueprint $table) {
            $table->dropIndex('arc_session_recorded_idx');
        });
        Schema::table('ambient_recording_sessions', function (Blueprint $table) {
            $table->dropIndex('ars_doctor_idx');
            $table->dropIndex('ars_patient_started_idx');
            $table->dropColumn(['language', 'diarization_enabled']);
        });
        Schema::table('real_time_insights', function (Blueprint $table) {
            $table->dropIndex('rti_session_timestamp_idx');
        });
    }
};
