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
        try {
            // Drop foreign key constraints before modifying columns
            DB::statement('ALTER TABLE kiosk_checkins DROP FOREIGN KEY kiosk_checkins_kiosk_session_id_foreign');
        } catch (\Exception $e) {
            // Foreign key might not exist, continue
        }

        try {
            DB::statement('ALTER TABLE kiosk_payments DROP FOREIGN KEY kiosk_payments_kiosk_session_id_foreign');
        } catch (\Exception $e) {
            // Foreign key might not exist, continue
        }

        // Revert columns back to bigInteger
        DB::statement('ALTER TABLE kiosk_checkins MODIFY kiosk_session_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE kiosk_payments MODIFY kiosk_session_id BIGINT UNSIGNED NOT NULL');

        // Recreate foreign key constraints if the referenced columns exist
        try {
            DB::statement('ALTER TABLE kiosk_checkins ADD CONSTRAINT kiosk_checkins_kiosk_session_id_foreign FOREIGN KEY (kiosk_session_id) REFERENCES kiosk_sessions(id)');
        } catch (\Exception $e) {
            // Constraint might already exist or reference might not exist, continue
        }

        try {
            DB::statement('ALTER TABLE kiosk_payments ADD CONSTRAINT kiosk_payments_kiosk_session_id_foreign FOREIGN KEY (kiosk_session_id) REFERENCES kiosk_sessions(id)');
        } catch (\Exception $e) {
            // Constraint might already exist or reference might not exist, continue
        }
    }
};