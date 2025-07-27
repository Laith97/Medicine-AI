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
        Schema::table('stripe_invoices', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('stripe_invoices', 'invoice_type')) {
                $table->string('invoice_type')->default('subscription')->after('stripe_invoice_id');
            }
            if (!Schema::hasColumn('stripe_invoices', 'invoice_month')) {
                $table->integer('invoice_month')->nullable()->after('invoice_type');
            }
            if (!Schema::hasColumn('stripe_invoices', 'invoice_year')) {
                $table->integer('invoice_year')->nullable()->after('invoice_month');
            }
            if (!Schema::hasColumn('stripe_invoices', 'grace_period_ends_at')) {
                $table->timestamp('grace_period_ends_at')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('stripe_invoices', 'reminder_count')) {
                $table->integer('reminder_count')->default(0)->after('grace_period_ends_at');
            }
            if (!Schema::hasColumn('stripe_invoices', 'last_reminder_sent_at')) {
                $table->timestamp('last_reminder_sent_at')->nullable()->after('reminder_count');
            }
            if (!Schema::hasColumn('stripe_invoices', 'auto_generated')) {
                $table->boolean('auto_generated')->default(false)->after('last_reminder_sent_at');
            }
        });
        
        // Add indexes (will be skipped if they already exist)
        try {
            Schema::table('stripe_invoices', function (Blueprint $table) {
                $table->index(['invoice_type', 'status'], 'idx_invoice_type_status');
                $table->index(['invoice_month', 'invoice_year'], 'idx_invoice_month_year');
                $table->index(['user_id', 'invoice_type', 'invoice_month', 'invoice_year'], 'idx_user_invoice_period');
            });
        } catch (\Exception $e) {
            // Indexes might already exist, ignore the error
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stripe_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_type_status');
            $table->dropIndex('idx_invoice_month_year');
            $table->dropIndex('idx_user_invoice_period');
            
            $table->dropColumn([
                'invoice_type',
                'invoice_month', 
                'invoice_year',
                'grace_period_ends_at',
                'reminder_count',
                'last_reminder_sent_at',
                'auto_generated'
            ]);
        });
    }
};