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
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('event_type'); // compliance_violation, compliance_rule_passed, etc.
            $table->string('model_type')->nullable(); // App\Models\User, App\Models\Claim, etc.
            $table->json('conditions'); // JSON conditions for triggering the alert
            $table->json('severity_config'); // severity levels and thresholds
            $table->json('escalation_rules'); // escalation matrix configuration
            $table->json('notification_channels'); // email, sms, push, in_app
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(1); // 1-10, higher = more important
            $table->integer('cooldown_minutes')->default(60); // prevent alert spam
            $table->json('metadata')->nullable(); // additional configuration
            $table->timestamps();

            $table->index(['event_type', 'model_type']);
            $table->index('is_active');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
