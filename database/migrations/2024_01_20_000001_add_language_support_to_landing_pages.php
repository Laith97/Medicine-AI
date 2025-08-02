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
        // Check if table exists and columns don't already exist
        if (Schema::hasTable('doctor_landing_pages')) {
            Schema::table('doctor_landing_pages', function (Blueprint $table) {
                if (!Schema::hasColumn('doctor_landing_pages', 'default_language')) {
                    $table->string('default_language', 5)->default('en')->after('seo_settings');
                }
                if (!Schema::hasColumn('doctor_landing_pages', 'translations')) {
                    $table->json('translations')->nullable()->after('default_language');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('doctor_landing_pages')) {
            Schema::table('doctor_landing_pages', function (Blueprint $table) {
                if (Schema::hasColumn('doctor_landing_pages', 'default_language')) {
                    $table->dropColumn('default_language');
                }
                if (Schema::hasColumn('doctor_landing_pages', 'translations')) {
                    $table->dropColumn('translations');
                }
            });
        }
    }
};
