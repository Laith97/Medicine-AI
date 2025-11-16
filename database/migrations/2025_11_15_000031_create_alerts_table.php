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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_id')->unique(); // UUID for external reference
            $table->foreignId('alert_rule_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('severity'); // critical, high, medium, low, info
            $table->string('status')->default('active'); // active, acknowledged, resolved, escalated
            $table->string('event_type');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('event_data'); // original event data that triggered the alert
            $table->json('context_data')->nullable(); // additional context
            $table->decimal('priority_score', 5, 2)->default(0); // ML-based priority score
            $table->json('escalation_history')->nullable(); // track escalation steps
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->json('notification_history')->nullable(); // track all notifications sent
            $table->timestamp('next_escalation_at')->nullable();
            $table->integer('escalation_level')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['alert_rule_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index('priority_score');
            $table->index('next_escalation_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
