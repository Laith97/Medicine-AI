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
        Schema::create('compliance_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // compliance_violation, compliance_rule_passed, data_access, etc.
            $table->string('event_category'); // audit, security, data_privacy, regulatory
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_role')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable(); // ID of the resource being accessed/modified
            $table->string('resource_type')->nullable(); // App\Models\User, App\Models\Claim, etc.
            $table->string('action_performed'); // create, update, delete, access, export, etc.
            $table->json('event_data')->nullable(); // Additional event-specific data
            $table->json('compliance_context')->nullable(); // HIPAA flags, data classification, etc.
            $table->string('severity_level')->default('low'); // low, medium, high, critical
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->string('request_id')->nullable();
            $table->timestamp('event_timestamp');
            $table->timestamps();

            // Indexes for performance
            $table->index(['event_type', 'event_timestamp']);
            $table->index(['user_id', 'event_timestamp']);
            $table->index(['resource_type', 'resource_id']);
            $table->index(['severity_level']);
            $table->index(['event_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_events');
    }
};
