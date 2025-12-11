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
        Schema::table('appointments', function (Blueprint $table) {
            // Add indexes optimized for real-time appointment queries
            // These indexes support fast queries for today's appointments with status/doctor filtering

            // Index for appointment_date queries (today's appointments)
            $table->index('appointment_date', 'idx_appointments_date');

            // Index for status queries (filtering by appointment status)
            $table->index('status', 'idx_appointments_status');

            // Index for doctor_id queries (filtering by doctor)
            $table->index('doctor_id', 'idx_appointments_doctor');

            // Composite index for real-time dashboard queries (date + status)
            $table->index(['appointment_date', 'status'], 'idx_appointments_date_status');

            // Composite index for doctor's appointments (doctor + date)
            $table->index(['doctor_id', 'appointment_date'], 'idx_appointments_doctor_date');

            // Composite index for status filtering by doctor (doctor + status + date)
            $table->index(['doctor_id', 'status', 'appointment_date'], 'idx_appointments_doctor_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop the indexes in reverse order
            $table->dropIndex('idx_appointments_doctor_status_date');
            $table->dropIndex('idx_appointments_doctor_date');
            $table->dropIndex('idx_appointments_date_status');
            $table->dropIndex('idx_appointments_doctor');
            $table->dropIndex('idx_appointments_status');
            $table->dropIndex('idx_appointments_date');
        });
    }
};
