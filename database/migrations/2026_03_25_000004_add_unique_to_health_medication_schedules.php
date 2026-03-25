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
        Schema::table('health_medication_schedules', function (Blueprint $table) {
            $table->unique(['user_id', 'medication_name', 'start_date'], 'health_med_sched_user_med_start_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_medication_schedules', function (Blueprint $table) {
            $table->dropUnique('health_med_sched_user_med_start_unique');
        });
    }
};
