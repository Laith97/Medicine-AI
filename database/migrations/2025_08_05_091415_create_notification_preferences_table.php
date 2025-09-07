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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Email preferences
            $table->boolean('email_enabled')->default(true);
            $table->boolean('email_appointment_reminders')->default(true);
            $table->boolean('email_diagnosis_updates')->default(true);
            $table->boolean('email_review_requests')->default(true);
            $table->boolean('email_system_alerts')->default(true);
            $table->boolean('email_marketing')->default(false);

            // SMS preferences
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('sms_appointment_reminders')->default(false);
            $table->boolean('sms_urgent_alerts')->default(true);

            // In-app preferences
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('in_app_sound')->default(true);
            $table->boolean('in_app_desktop')->default(true);
            $table->boolean('in_app_vibrate')->default(false);

            // Frequency settings
            $table->enum('frequency', ['immediate', 'hourly', 'daily', 'weekly'])->default('immediate');
            $table->time('quiet_hours_start')->default('22:00');
            $table->time('quiet_hours_end')->default('08:00');
            $table->boolean('respect_quiet_hours')->default(true);

            // Notification types
            $table->boolean('appointment_booked')->default(true);
            $table->boolean('appointment_reminder')->default(true);
            $table->boolean('diagnosis_submitted')->default(true);
            $table->boolean('review_submitted')->default(true);
            $table->boolean('voice_transcription_completed')->default(true);
            $table->boolean('system_alert')->default(true);

            // Ensure one user has only one preference record
            $table->unique(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
