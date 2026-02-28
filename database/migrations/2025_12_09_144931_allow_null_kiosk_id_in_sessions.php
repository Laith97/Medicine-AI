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
        DB::statement('ALTER TABLE kiosk_sessions MODIFY kiosk_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set any null values to a default before making the column non-null again
        DB::statement('UPDATE kiosk_sessions SET kiosk_id = 1 WHERE kiosk_id IS NULL');

        DB::statement('ALTER TABLE kiosk_sessions MODIFY kiosk_id BIGINT UNSIGNED NOT NULL');
    }
};
