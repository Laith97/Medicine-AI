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
        Schema::table('notification_preferences', function (Blueprint $table) {
            // Appointment status change preferences
            $table->boolean('appointment_status_changed')->default(true)->after('appointment_reminder');
            $table->boolean('appointment_confirmed')->default(true)->after('appointment_status_changed');
            $table->boolean('appointment_cancelled')->default(true)->after('appointment_confirmed');
            $table->boolean('appointment_completed')->default(true)->after('appointment_cancelled');
            $table->boolean('appointment_no_show')->default(true)->after('appointment_completed');

            // Real-time preferences
            $table->boolean('realtime_appointment_updates')->default(true)->after('system_alert');
            $table->boolean('realtime_critical_alerts')->default(true)->after('realtime_appointment_updates');
            $table->boolean('push_appointment_status')->default(true)->after('realtime_critical_alerts');
            $table->boolean('push_critical_updates')->default(true)->after('push_appointment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'appointment_status_changed',
                'appointment_confirmed',
                'appointment_cancelled',
                'appointment_completed',
                'appointment_no_show',
                'realtime_appointment_updates',
                'realtime_critical_alerts',
                'push_appointment_status',
                'push_critical_updates',
            ]);
        });
    }
};
