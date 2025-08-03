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
        Schema::table('monthly_invoice_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('monthly_invoice_settings', 'monthly_price')) {
                $table->decimal('monthly_price', 10, 2)->default(0)->after('billing_amount');
            }
            if (!Schema::hasColumn('monthly_invoice_settings', 'yearly_price')) {
                $table->decimal('yearly_price', 10, 2)->default(0)->after('monthly_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_invoice_settings', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_invoice_settings', 'monthly_price')) {
                $table->dropColumn('monthly_price');
            }
            if (Schema::hasColumn('monthly_invoice_settings', 'yearly_price')) {
                $table->dropColumn('yearly_price');
            }
        });
    }
};
