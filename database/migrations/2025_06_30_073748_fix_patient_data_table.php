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
        // Check if columns exist before adding them
        if (!Schema::hasColumn('patient_data', 'visit_number')) {
            Schema::table('patient_data', function (Blueprint $table) {
                $table->unsignedInteger('visit_number')->nullable()->after('previous_record_id');
            });
        }
        
        if (!Schema::hasColumn('patient_data', 'patient_key')) {
            Schema::table('patient_data', function (Blueprint $table) {
                $table->string('patient_key')->nullable()->after('visit_number')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if columns exist before dropping them
        if (Schema::hasColumn('patient_data', 'visit_number')) {
            Schema::table('patient_data', function (Blueprint $table) {
                $table->dropColumn('visit_number');
            });
        }
        
        if (Schema::hasColumn('patient_data', 'patient_key')) {
            Schema::table('patient_data', function (Blueprint $table) {
                $table->dropColumn('patient_key');
            });
        }
    }
};
