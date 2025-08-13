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
        if (Schema::hasTable('landing_page_visits')) {
            Schema::table('landing_page_visits', function (Blueprint $table) {
                // Check if visitor_ip column exists and rename it to ip_address
                if (Schema::hasColumn('landing_page_visits', 'visitor_ip') &&
                    !Schema::hasColumn('landing_page_visits', 'ip_address')) {
                    $table->renameColumn('visitor_ip', 'ip_address');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('landing_page_visits')) {
            Schema::table('landing_page_visits', function (Blueprint $table) {
                // Revert the column name change
                if (Schema::hasColumn('landing_page_visits', 'ip_address') &&
                    !Schema::hasColumn('landing_page_visits', 'visitor_ip')) {
                    $table->renameColumn('ip_address', 'visitor_ip');
                }
            });
        }
    }
};
