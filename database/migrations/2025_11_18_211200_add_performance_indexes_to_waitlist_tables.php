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
        // Performance indexes for waitlists table
        Schema::table('waitlists', function (Blueprint $table) {
            // Composite indexes for common queries
            $table->index(['doctor_id', 'status', 'priority_level'], 'waitlists_doctor_status_priority_idx');
            $table->index(['patient_id', 'status'], 'waitlists_patient_status_idx');
            $table->index(['service_type', 'status'], 'waitlists_service_status_idx');
            $table->index(['created_at', 'status'], 'waitlists_created_status_idx');
            $table->index(['updated_at'], 'waitlists_updated_at_idx');

            // Partial indexes for active waitlists
            $table->index(['doctor_id', 'created_at'], 'waitlists_active_doctor_created_idx')
                  ->where('status', 'active');
            $table->index(['priority_level', 'created_at'], 'waitlists_priority_created_idx')
                  ->where('status', 'active');
        });

        // Performance indexes for waitlist_entries table
        Schema::table('waitlist_entries', function (Blueprint $table) {
            // Composite indexes for common queries
            $table->index(['waitlist_id', 'status'], 'waitlist_entries_waitlist_status_idx');
            $table->index(['status', 'response_deadline'], 'waitlist_entries_status_deadline_idx');
            $table->index(['slot_date', 'slot_time'], 'waitlist_entries_slot_datetime_idx');
            $table->index(['offered_at'], 'waitlist_entries_offered_at_idx');
            $table->index(['created_at'], 'waitlist_entries_created_at_idx');

            // Partial indexes for pending/offered entries
            $table->index(['waitlist_id', 'created_at'], 'waitlist_entries_waitlist_created_idx')
                  ->whereIn('status', ['pending', 'offered']);
            $table->index(['response_deadline'], 'waitlist_entries_response_deadline_idx')
                  ->where('status', 'offered');
        });

        // Performance indexes for waitlist_patient_preferences table
        Schema::table('waitlist_patient_preferences', function (Blueprint $table) {
            // Composite indexes for common queries
            $table->index(['patient_id', 'doctor_id'], 'preferences_patient_doctor_idx');
            $table->index(['doctor_id', 'auto_accept_threshold'], 'preferences_doctor_threshold_idx');
            $table->index(['updated_at'], 'preferences_updated_at_idx');

            // Partial indexes for active preferences
            $table->index(['patient_id'], 'preferences_patient_idx')
                  ->whereNotNull('preferred_times');
            $table->index(['doctor_id'], 'preferences_doctor_idx')
                  ->whereNotNull('preferred_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from waitlists table
        Schema::table('waitlists', function (Blueprint $table) {
            $table->dropIndex('waitlists_doctor_status_priority_idx');
            $table->dropIndex('waitlists_patient_status_idx');
            $table->dropIndex('waitlists_service_status_idx');
            $table->dropIndex('waitlists_created_status_idx');
            $table->dropIndex('waitlists_updated_at_idx');
            $table->dropIndex('waitlists_active_doctor_created_idx');
            $table->dropIndex('waitlists_priority_created_idx');
        });

        // Drop indexes from waitlist_entries table
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropIndex('waitlist_entries_waitlist_status_idx');
            $table->dropIndex('waitlist_entries_status_deadline_idx');
            $table->dropIndex('waitlist_entries_slot_datetime_idx');
            $table->dropIndex('waitlist_entries_offered_at_idx');
            $table->dropIndex('waitlist_entries_created_at_idx');
            $table->dropIndex('waitlist_entries_waitlist_created_idx');
            $table->dropIndex('waitlist_entries_response_deadline_idx');
        });

        // Drop indexes from waitlist_patient_preferences table
        Schema::table('waitlist_patient_preferences', function (Blueprint $table) {
            $table->dropIndex('preferences_patient_doctor_idx');
            $table->dropIndex('preferences_doctor_threshold_idx');
            $table->dropIndex('preferences_updated_at_idx');
            $table->dropIndex('preferences_patient_idx');
            $table->dropIndex('preferences_doctor_idx');
        });
    }
};
