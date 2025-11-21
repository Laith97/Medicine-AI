<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            // Convert single voice file/transcript to JSON arrays
            $table->json('voice_files')->nullable()->after('voice_file_path');
            $table->json('voice_transcripts')->nullable()->after('voice_transcript');
        });

        // Migrate existing data
        DB::statement('
            UPDATE diagnoses
            SET voice_files = JSON_ARRAY(COALESCE(voice_file_path, "")),
                voice_transcripts = JSON_ARRAY(COALESCE(voice_transcript, ""))
            WHERE voice_file_path IS NOT NULL OR voice_transcript IS NOT NULL
        ');

        // Drop old columns
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropColumn(['voice_file_path', 'voice_transcript']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            // Recreate old columns
            $table->text('voice_transcript')->nullable()->after('diagnosis_text');
            $table->string('voice_file_path')->nullable()->after('voice_transcript');
        });

        // Migrate data back
        DB::statement('
            UPDATE diagnoses
            SET voice_file_path = JSON_UNQUOTE(JSON_EXTRACT(voice_files, "$[0]")),
                voice_transcript = JSON_UNQUOTE(JSON_EXTRACT(voice_transcripts, "$[0]"))
            WHERE JSON_LENGTH(voice_files) > 0 OR JSON_LENGTH(voice_transcripts) > 0
        ');

        // Drop new columns
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropColumn(['voice_files', 'voice_transcripts']);
        });
    }
};
