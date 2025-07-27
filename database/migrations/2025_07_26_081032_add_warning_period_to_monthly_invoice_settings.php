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
            $table->integer('warning_period_days')->default(3)->after('grace_period_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_invoice_settings', function (Blueprint $table) {
            $table->dropColumn('warning_period_days');
        });
    }
};
