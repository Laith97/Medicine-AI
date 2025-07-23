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
        Schema::create('stripe_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('stripe_invoice_id')->unique();
            $table->decimal('amount_due', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('status')->default('draft'); // draft, open, paid, void, uncollectible
            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('invoice_url')->nullable();
            $table->string('invoice_pdf')->nullable();
            $table->string('currency', 3)->default('usd');
            $table->text('description')->nullable();
            $table->json('line_items')->nullable(); // Store invoice line items
            $table->json('metadata')->nullable(); // Additional Stripe metadata
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'due_date']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_invoices');
    }
};
