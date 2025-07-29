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
        Schema::table('doctor_notes', function (Blueprint $table) {
            $table->boolean('is_voice_note')->default(false);
            $table->integer('audio_duration')->nullable(); // in seconds
            $table->boolean('is_private')->default(false);
            $table->string('category')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->datetime('follow_up_date')->nullable();
            $table->boolean('shared_with_patient')->default(false);
            $table->datetime('shared_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_notes', function (Blueprint $table) {
            $table->dropColumn([
                'is_voice_note', 'audio_duration', 'is_private', 'category',
                'follow_up_required', 'follow_up_date', 'shared_with_patient', 'shared_at'
            ]);
        });
    }
};
