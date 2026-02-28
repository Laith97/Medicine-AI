<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rule_applications', function (Blueprint $table) {
            // Enhanced audit metadata
            $table->string('user_id')->nullable()->after('claim_id'); // User who triggered the rule application
            $table->string('session_id')->nullable()->after('user_id'); // Session identifier for tracking
            $table->string('ip_address')->nullable()->after('session_id'); // IP address for audit trail
            $table->string('user_agent')->nullable()->after('ip_address'); // User agent for device tracking
            $table->string('request_id')->nullable()->after('user_agent'); // Request ID for correlation

            // Rule execution details
            $table->json('rule_conditions')->nullable()->after('application_result'); // Conditions that were evaluated
            $table->json('rule_actions')->nullable()->after('rule_conditions'); // Actions that were executed
            $table->boolean('rule_triggered')->default(false)->after('rule_actions'); // Whether rule was triggered
            $table->decimal('execution_time_ms', 8, 2)->nullable()->after('rule_triggered'); // Execution time in milliseconds

            // Compliance and HIPAA tracking
            $table->string('data_classification')->default('internal')->after('execution_time_ms'); // Data sensitivity level
            $table->json('hipaa_compliance_flags')->nullable()->after('data_classification'); // HIPAA compliance markers
            $table->timestamp('data_retention_until')->nullable()->after('hipaa_compliance_flags'); // When data can be deleted

            // Effectiveness tracking
            $table->string('outcome_status')->nullable()->after('data_retention_until'); // success, warning, denial, etc.
            $table->text('outcome_reason')->nullable()->after('outcome_status'); // Reason for the outcome
            $table->boolean('user_acknowledged')->default(false)->after('outcome_reason'); // Whether user acknowledged the rule
            $table->timestamp('user_acknowledged_at')->nullable()->after('user_acknowledged');

            // Audit trail
            $table->json('audit_metadata')->nullable()->after('user_acknowledged_at'); // Additional audit information
            $table->string('compliance_event_type')->nullable()->after('audit_metadata'); // Type of compliance event

            // Indexes for performance
            $table->index(['user_id', 'applied_at']);
            $table->index(['rule_triggered', 'applied_at']);
            $table->index(['outcome_status', 'applied_at']);
            $table->index(['data_classification']);
            $table->index(['compliance_event_type']);
        });
    }

    public function down(): void
    {
        Schema::table('rule_applications', function (Blueprint $table) {
            $table->dropColumn([
                'user_id',
                'session_id',
                'ip_address',
                'user_agent',
                'request_id',
                'rule_conditions',
                'rule_actions',
                'rule_triggered',
                'execution_time_ms',
                'data_classification',
                'hipaa_compliance_flags',
                'data_retention_until',
                'outcome_status',
                'outcome_reason',
                'user_acknowledged',
                'user_acknowledged_at',
                'audit_metadata',
                'compliance_event_type',
            ]);

            // Drop indexes
            $table->dropIndex(['user_id', 'applied_at']);
            $table->dropIndex(['rule_triggered', 'applied_at']);
            $table->dropIndex(['outcome_status', 'applied_at']);
            $table->dropIndex(['data_classification']);
            $table->dropIndex(['compliance_event_type']);
        });
    }
};
