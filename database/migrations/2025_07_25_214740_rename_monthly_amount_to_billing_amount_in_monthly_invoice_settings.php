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
            $table->renameColumn('monthly_amount', 'billing_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_invoice_settings', function (Blueprint $table) {
            $table->renameColumn('billing_amount', 'monthly_amount');
        });
    }
};
