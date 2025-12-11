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
        // Modify the columns to match the new session_id type
        DB::statement('ALTER TABLE kiosk_checkins MODIFY kiosk_session_id VARCHAR(128) NOT NULL');
        DB::statement('ALTER TABLE kiosk_payments MODIFY kiosk_session_id VARCHAR(128) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert columns back to bigInteger
        DB::statement('ALTER TABLE kiosk_checkins MODIFY kiosk_session_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE kiosk_payments MODIFY kiosk_session_id BIGINT UNSIGNED NOT NULL');
    }
};