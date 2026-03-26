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
        Schema::create('rtm_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->string('session_type'); // initial, follow_up, monitoring
            $table->enum('status', ['active', 'paused', 'completed', 'discharged'])->default('active');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('target_days')->default(30); // Target monitoring period
            $table->json('monitoring_parameters')->nullable(); // What to track
            $table->text('clinical_notes')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('status');
        });

        Schema::create('rtm_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rtm_session_id')->constrained('rtm_sessions')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->string('metric_type'); // pain_level, function_score, adherence, symptom
            $table->decimal('value', 8, 2);
            $table->string('unit')->nullable(); // scale 1-10, percentage, etc
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index('rtm_session_id');
            $table->index('patient_id');
            $table->index('metric_type');
            $table->index('recorded_at');
        });

        Schema::create('rtm_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rtm_session_id')->constrained('rtm_sessions')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->enum('alert_type', ['threshold_breach', 'pattern_change', 'adherence_drop', 'deterioration'])->default('threshold_breach');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('metric_type')->nullable();
            $table->decimal('trigger_value', 8, 2)->nullable();
            $table->decimal('threshold_value', 8, 2)->nullable();
            $table->text('message');
            $table->text('recommended_action')->nullable();
            $table->enum('status', ['active', 'acknowledged', 'resolved', 'dismissed'])->default('active');
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('rtm_session_id');
            $table->index('patient_id');
            $table->index('severity');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rtm_alerts');
        Schema::dropIfExists('rtm_metrics');
        Schema::dropIfExists('rtm_sessions');
    }
};
