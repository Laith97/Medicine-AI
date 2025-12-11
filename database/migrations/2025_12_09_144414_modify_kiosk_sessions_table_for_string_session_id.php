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
        // First, let's temporarily disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop the primary key constraint and auto_increment property
        DB::statement('ALTER TABLE kiosk_sessions DROP PRIMARY KEY, MODIFY session_id VARCHAR(128) NOT NULL, ADD PRIMARY KEY (session_id)');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
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
