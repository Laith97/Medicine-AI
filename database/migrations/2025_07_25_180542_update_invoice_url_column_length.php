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
            // Change invoice_url column to TEXT to accommodate long Stripe URLs
            $table->text('invoice_url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stripe_invoices', function (Blueprint $table) {
            // Revert back to string if needed
            $table->string('invoice_url', 255)->nullable()->change();
        });
    }
};
