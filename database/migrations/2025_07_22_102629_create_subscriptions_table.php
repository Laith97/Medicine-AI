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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('stripe_subscription_id')->unique();
            $table->string('stripe_customer_id');
            $table->string('plan_name'); // basic, pro, enterprise
            $table->string('billing_cycle'); // monthly, yearly
            $table->string('status'); // active, canceled, past_due, etc.
            $table->decimal('amount', 10, 2); // Subscription amount
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->json('metadata')->nullable(); // Store additional Stripe data
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('stripe_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
