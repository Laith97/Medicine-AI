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
        if (Schema::hasTable('monthly_invoice_settings')) {
            Schema::table('monthly_invoice_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('monthly_invoice_settings', 'subscription_period_months')) {
                    $table->integer('subscription_period_months')->default(1)->after('monthly_amount');
                }
                if (!Schema::hasColumn('monthly_invoice_settings', 'subscription_starts_at')) {
                    $table->timestamp('subscription_starts_at')->nullable()->after('subscription_period_months');
                }
                if (!Schema::hasColumn('monthly_invoice_settings', 'subscription_ends_at')) {
                    $table->timestamp('subscription_ends_at')->nullable()->after('subscription_starts_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_invoice_settings', function (Blueprint $table) {
            $table->dropColumn(['subscription_period_months', 'subscription_starts_at', 'subscription_ends_at']);
        });
    }
};
