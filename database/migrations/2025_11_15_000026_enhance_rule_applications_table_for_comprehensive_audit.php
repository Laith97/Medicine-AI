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
            $columnsToDrop = [];
            if (Schema::hasColumn('rule_applications', 'user_id')) {
                $columnsToDrop[] = 'user_id';
            }
            if (Schema::hasColumn('rule_applications', 'session_id')) {
                $columnsToDrop[] = 'session_id';
            }
            if (Schema::hasColumn('rule_applications', 'ip_address')) {
                $columnsToDrop[] = 'ip_address';
            }
            if (Schema::hasColumn('rule_applications', 'user_agent')) {
                $columnsToDrop[] = 'user_agent';
            }
            if (Schema::hasColumn('rule_applications', 'request_id')) {
                $columnsToDrop[] = 'request_id';
            }
            if (Schema::hasColumn('rule_applications', 'rule_conditions')) {
                $columnsToDrop[] = 'rule_conditions';
            }
            if (Schema::hasColumn('rule_applications', 'rule_actions')) {
                $columnsToDrop[] = 'rule_actions';
            }
            if (Schema::hasColumn('rule_applications', 'rule_triggered')) {
                $columnsToDrop[] = 'rule_triggered';
            }
            if (Schema::hasColumn('rule_applications', 'execution_time_ms')) {
                $columnsToDrop[] = 'execution_time_ms';
            }
            if (Schema::hasColumn('rule_applications', 'data_classification')) {
                $columnsToDrop[] = 'data_classification';
            }
            if (Schema::hasColumn('rule_applications', 'hipaa_compliance_flags')) {
                $columnsToDrop[] = 'hipaa_compliance_flags';
            }
            if (Schema::hasColumn('rule_applications', 'data_retention_until')) {
                $columnsToDrop[] = 'data_retention_until';
            }
            if (Schema::hasColumn('rule_applications', 'outcome_status')) {
                $columnsToDrop[] = 'outcome_status';
            }
            if (Schema::hasColumn('rule_applications', 'outcome_reason')) {
                $columnsToDrop[] = 'outcome_reason';
            }
            if (Schema::hasColumn('rule_applications', 'user_acknowledged')) {
                $columnsToDrop[] = 'user_acknowledged';
            }
            if (Schema::hasColumn('rule_applications', 'user_acknowledged_at')) {
                $columnsToDrop[] = 'user_acknowledged_at';
            }
            if (Schema::hasColumn('rule_applications', 'audit_metadata')) {
                $columnsToDrop[] = 'audit_metadata';
            }
            if (Schema::hasColumn('rule_applications', 'compliance_event_type')) {
                $columnsToDrop[] = 'compliance_event_type';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // Drop indexes if they exist using raw SQL
            $this->dropIndexIfExists('rule_applications', 'rule_applications_user_id_applied_at_index');
            $this->dropIndexIfExists('rule_applications', 'rule_applications_rule_triggered_applied_at_index');
            $this->dropIndexIfExists('rule_applications', 'rule_applications_outcome_status_applied_at_index');
            $this->dropIndexIfExists('rule_applications', 'rule_applications_data_classification_index');
            $this->dropIndexIfExists('rule_applications', 'rule_applications_compliance_event_type_index');
        });
    }

    /**
     * Drop an index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$table}` DROP INDEX IF EXISTS `{$indexName}`");
        } catch (\Exception $e) {
            // Index doesn't exist or another error occurred, continue silently
        }
    }
};
