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
            // WhatsApp preferences
            $table->boolean('whatsapp_enabled')->default(false);
            $table->boolean('whatsapp_appointment_reminders')->default(false);
            $table->boolean('whatsapp_urgent_alerts')->default(false);
            $table->boolean('whatsapp_diagnosis_updates')->default(false);
            $table->boolean('whatsapp_review_requests')->default(false);
            $table->boolean('whatsapp_system_alerts')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_enabled',
                'whatsapp_appointment_reminders',
                'whatsapp_urgent_alerts',
                'whatsapp_diagnosis_updates',
                'whatsapp_review_requests',
                'whatsapp_system_alerts',
            ]);
        });
    }
};
