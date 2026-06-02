<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `appointment_id` foreign key to the `diagnoses` table.
 *
 * PR #30 changes `AIMedicalCopilotService::getPatientMedicalHistory()` to
 * query `Diagnosis::where('appointment_id', '!=', $appointment->id)`, but
 * the column does not exist in the original `create_diagnoses_table`
 * migration. Without this migration, every call to the service throws
 * "Unknown column 'appointment_id'" on a fresh install. The column is
 * nullable because diagnoses can be created without a linked appointment
 * (manual / voice-assistant flow).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->foreignId('appointment_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('appointments')
                ->onDelete('cascade');
            $table->index(['appointment_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropIndex(['appointment_id', 'created_at']);
            $table->dropColumn('appointment_id');
        });
    }
};
