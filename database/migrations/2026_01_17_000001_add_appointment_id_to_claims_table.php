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
        Schema::table('claims', function (Blueprint $table) {
            // Add appointment_id as nullable foreign key
            $table->foreignId('appointment_id')
                  ->nullable()
                  ->after('doctor_id')
                  ->constrained('appointments')
                  ->onDelete('set null');

            // Add index for faster lookups
            $table->index('appointment_id');
        });

        // Backfill existing claims: match claims to appointments by doctor_id, patient_id, and service_date
        // This uses a more precise matching: same doctor, same patient, service_date = appointment_date
        DB::statement("
            UPDATE claims
            SET appointment_id = (
                SELECT a.id FROM appointments a
                WHERE a.doctor_id = claims.doctor_id
                  AND a.patient_id = claims.patient_id
                  AND DATE(a.appointment_date) = claims.service_date
                  AND a.status IN ('completed', 'confirmed')
                ORDER BY a.appointment_date ASC
                LIMIT 1
            )
            WHERE appointment_id IS NULL
              AND service_date IS NOT NULL
              AND doctor_id IS NOT NULL
              AND patient_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropIndex(['appointment_id']);
            $table->dropColumn('appointment_id');
        });
    }
};
