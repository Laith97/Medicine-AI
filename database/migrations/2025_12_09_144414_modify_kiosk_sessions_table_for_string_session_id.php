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
        // First, drop foreign keys
        Schema::table('kiosk_checkins', function (Blueprint $table) {
            $table->dropForeign(['kiosk_session_id']);
        });
        Schema::table('kiosk_payments', function (Blueprint $table) {
            $table->dropForeign(['kiosk_session_id']);
        });

        // Modify the columns to VARCHAR(128)
        DB::statement('ALTER TABLE kiosk_sessions MODIFY session_id VARCHAR(128) NOT NULL');
        DB::statement('ALTER TABLE kiosk_checkins MODIFY kiosk_session_id VARCHAR(128) NOT NULL');
        DB::statement('ALTER TABLE kiosk_payments MODIFY kiosk_session_id VARCHAR(128) NOT NULL');

        // Re-add foreign keys
        Schema::table('kiosk_checkins', function (Blueprint $table) {
            $table->foreign('kiosk_session_id')->references('session_id')->on('kiosk_sessions');
        });
        Schema::table('kiosk_payments', function (Blueprint $table) {
            $table->foreign('kiosk_session_id')->references('session_id')->on('kiosk_sessions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is complex to revert as the session_id values are now strings
        // For safety, we'll just leave it as is since the system now expects string session IDs
    }
};
