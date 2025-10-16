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
        Schema::create('billing_underpayment_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->onDelete('cascade');
            $table->decimal('expected_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2);
            $table->decimal('variance', 10, 2); // expected - paid
            $table->decimal('threshold_percentage', 5, 2); // e.g., 10.00 for 10%
            $table->timestamp('flagged_at');
            $table->enum('status', ['active', 'resolved', 'dismissed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['claim_id', 'status']);
            $table->index('flagged_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_underpayment_alerts');
    }
};
