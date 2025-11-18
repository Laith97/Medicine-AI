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
        // Only run this for SQLite to fix enum compatibility
        if (DB::getDriverName() === 'sqlite') {
            // Skip the problematic migration for SQLite
            // The enum column already exists from previous migrations
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to do anything as this is a compatibility fix
    }
};
