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
        Schema::table('health_journals', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('health_medication_schedules', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('health_medication_logs', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_journals', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('health_medication_schedules', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('health_medication_logs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
