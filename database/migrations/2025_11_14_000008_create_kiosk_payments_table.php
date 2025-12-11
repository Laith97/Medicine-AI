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
        Schema::create('kiosk_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('kiosk_session_id')->constrained('kiosk_sessions', 'session_id')->onDelete('cascade');
            $table->string('stripe_payment_intent')->unique();
            $table->integer('amount'); // in cents
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->json('payment_metadata')->nullable(); // Store Stripe response data
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['appointment_id']); // One payment per appointment
            $table->index(['kiosk_session_id']);
            $table->index(['stripe_payment_intent']);
            $table->index(['status']);
            $table->index(['processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kiosk_payments');
    }
};
