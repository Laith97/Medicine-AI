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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Basic Monthly", "Pro Yearly"
            $table->string('slug')->unique(); // e.g., "basic-monthly", "pro-yearly"
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // Price amount
            $table->enum('billing_cycle', ['monthly', 'yearly']); // Billing frequency
            $table->integer('billing_period_months'); // 1 for monthly, 12 for yearly
            $table->json('features')->nullable(); // JSON array of features
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};