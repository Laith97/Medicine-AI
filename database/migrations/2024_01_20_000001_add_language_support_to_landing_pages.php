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
        Schema::table('doctor_landing_pages', function (Blueprint $table) {
            $table->string('default_language', 5)->default('en')->after('seo_settings');
            $table->json('translations')->nullable()->after('default_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_landing_pages', function (Blueprint $table) {
            $table->dropColumn(['default_language', 'translations']);
        });
    }
};
