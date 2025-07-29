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
        if (Schema::hasTable('monthly_invoice_settings') && Schema::hasColumn('monthly_invoice_settings', 'monthly_amount')) {
            Schema::table('monthly_invoice_settings', function (Blueprint $table) {
                $table->renameColumn('monthly_amount', 'billing_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('monthly_invoice_settings') && Schema::hasColumn('monthly_invoice_settings', 'billing_amount')) {
            Schema::table('monthly_invoice_settings', function (Blueprint $table) {
                $table->renameColumn('billing_amount', 'monthly_amount');
            });
        }
    }
};
