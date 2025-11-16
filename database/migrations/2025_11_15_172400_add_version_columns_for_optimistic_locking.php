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
        // Add version column to appointments table for optimistic locking
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('kiosk_id');
        });

        // Add version column to patient_insurances table for optimistic locking
        Schema::table('patient_insurances', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('deductible_info');
        });

        // Add version column to eligibility_checks table for optimistic locking
        Schema::table('eligibility_checks', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('checked_by');
        });

        // Add version column to claims table for optimistic locking
        Schema::table('claims', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('patient_insurances', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('eligibility_checks', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
