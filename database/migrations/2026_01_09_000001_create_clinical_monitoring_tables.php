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
        // 1. patient_monitoring_sessions: Track active monitoring periods
        Schema::create('clinical_monitoring_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('active'); // active, completed, paused
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });

        // 2. monitoring_devices: Connect IoT devices to patient records
        Schema::create('clinical_monitoring_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();
            $table->string('device_type'); // wearable, bedside_monitor, etc.
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('status')->default('online'); // online, offline, maintenance
            $table->foreignId('current_patient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_sync_at')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();

            $table->index(['device_type', 'status']);
        });

        // 3. clinical_indicators: Store real-time vital signs and lab values
        Schema::create('clinical_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('clinical_monitoring_sessions')->onDelete('set null');
            $table->foreignId('device_id')->nullable()->constrained('clinical_monitoring_devices')->onDelete('set null');
            $table->string('type'); // vital_sign, lab_result, clinical_note
            $table->string('name'); // heart_rate, creatinine, etc.
            $table->string('value');
            $table->string('unit')->nullable();
            $table->timestamp('measured_at')->useCurrent();
            $table->json('metadata')->nullable(); // e.g., sentiment for notes
            $table->timestamps();

            $table->index(['patient_id', 'type', 'name']);
            $table->index('measured_at');
        });

        // 4. early_warning_scores: Store calculated risk scores over time
        Schema::create('clinical_early_warning_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->string('algorithm_type'); // news2, sepsis, cardiac, stroke
            $table->decimal('score', 8, 2);
            $table->string('risk_level'); // low, medium, high, critical
            $table->json('contributing_factors'); // breakdown of what led to this score
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();

            $table->index(['patient_id', 'algorithm_type', 'calculated_at'], 'clinical_ews_patient_algo_time_idx');
        });

        // 5. clinical_alert_rules: Configurable rules for different alert types
        Schema::create('clinical_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('algorithm_type');
            $table->string('severity'); // green, yellow, orange, red
            $table->decimal('threshold_min', 8, 2)->nullable();
            $table->decimal('threshold_max', 8, 2)->nullable();
            $table->json('notification_channels'); // role-based distribution
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. clinical_alerts: Track triggered alerts and responses
        Schema::create('clinical_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('rule_id')->constrained('clinical_alert_rules')->onDelete('cascade');
            $table->string('severity'); // green, yellow, orange, red
            $table->string('status')->default('triggered'); // triggered, acknowledged, escalated, resolved
            $table->text('message');
            $table->json('trigger_data');
            $table->timestamp('triggered_at')->useCurrent();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status', 'severity'], 'clinical_alerts_patient_status_sev_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_alerts');
        Schema::dropIfExists('clinical_alert_rules');
        Schema::dropIfExists('clinical_early_warning_scores');
        Schema::dropIfExists('clinical_indicators');
        Schema::dropIfExists('clinical_monitoring_devices');
        Schema::dropIfExists('clinical_monitoring_sessions');
    }
};
