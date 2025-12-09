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
        Schema::table('voice_transcriptions', function (Blueprint $table) {
            $table->string('audio_file')->nullable()->after('raw_transcription');
            $table->string('audio_format')->nullable()->after('audio_file');
            $table->unsignedInteger('audio_duration')->nullable()->after('audio_format'); // Duration in seconds
            $table->unsignedBigInteger('audio_file_size')->nullable()->after('audio_duration'); // File size in bytes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voice_transcriptions', function (Blueprint $table) {
            $table->dropColumn(['audio_file', 'audio_format', 'audio_duration', 'audio_file_size']);
        });
    }
};
