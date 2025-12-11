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
        // Add performance indexes to kiosks table
        Schema::table('kiosks', function (Blueprint $table) {
            $table->index(['status', 'last_ping']); // For online/offline status queries
            $table->index(['location']); // For location-based queries
        });

        // Add performance indexes to kiosk_sessions table
        Schema::table('kiosk_sessions', function (Blueprint $table) {
            $table->index(['kiosk_id', 'start_time']); // For session history queries
            $table->index(['kiosk_id', 'status', 'start_time']); // For active session queries
            $table->index(['end_time']); // For cleanup queries
            $table->index(['start_time', 'end_time']); // For duration calculations
        });

        // Add performance indexes to kiosk_checkins table
        Schema::table('kiosk_checkins', function (Blueprint $table) {
            $table->index(['kiosk_session_id', 'checkin_time']); // For session checkin queries
            $table->index(['appointment_id', 'checkin_time']); // For appointment checkin history
            $table->index(['verification_method', 'checkin_time']); // For verification method analytics
        });

        // Add performance indexes to kiosk_payments table
        Schema::table('kiosk_payments', function (Blueprint $table) {
            $table->index(['kiosk_session_id', 'status']); // For session payment queries
            $table->index(['appointment_id', 'status']); // For appointment payment status
            $table->index(['status', 'processed_at']); // For payment processing analytics
            $table->index(['amount']); // For payment amount analytics
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove performance indexes from kiosks table
        Schema::table('kiosks', function (Blueprint $table) {
            $table->dropIndex(['status', 'last_ping']);
            $table->dropIndex(['location']);
        });

        // Remove performance indexes from kiosk_sessions table
        Schema::table('kiosk_sessions', function (Blueprint $table) {
            $table->dropIndex(['kiosk_id', 'start_time']);
            $table->dropIndex(['kiosk_id', 'status', 'start_time']);
            $table->dropIndex(['end_time']);
            $table->dropIndex(['start_time', 'end_time']);
        });

        // Remove performance indexes from kiosk_checkins table
        Schema::table('kiosk_checkins', function (Blueprint $table) {
            $table->dropIndex(['kiosk_session_id', 'checkin_time']);
            $table->dropIndex(['appointment_id', 'checkin_time']);
            $table->dropIndex(['verification_method', 'checkin_time']);
        });

        // Remove performance indexes from kiosk_payments table
        Schema::table('kiosk_payments', function (Blueprint $table) {
            $table->dropIndex(['kiosk_session_id', 'status']);
            $table->dropIndex(['appointment_id', 'status']);
            $table->dropIndex(['status', 'processed_at']);
            $table->dropIndex(['amount']);
        });
    }
};
