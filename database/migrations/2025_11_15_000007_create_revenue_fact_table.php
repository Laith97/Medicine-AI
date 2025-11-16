<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('revenue_fact', function (Blueprint $table) {
            $table->bigIncrements('transaction_key');
            $table->integer('date_key');
            $table->integer('patient_key');
            $table->integer('doctor_key')->nullable();
            $table->integer('hospital_key')->nullable();
            $table->unsignedBigInteger('transaction_id');
            $table->date('transaction_date');
            $table->string('transaction_type', 50); // Payment, Refund, Adjustment
            $table->string('payment_method', 50)->nullable(); // Credit Card, Insurance, Cash
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('insurance_adjustment', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->unsignedBigInteger('claim_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 50); // Pending, Completed, Failed
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('date_key')->references('date_key')->on('dim_date');
            $table->foreign('patient_key')->references('patient_key')->on('patient_dim');
            $table->foreign('doctor_key')->references('doctor_key')->on('doctor_dim');

            // Indexes
            $table->index(['date_key', 'patient_key']);
            $table->index('transaction_date');
            $table->index('transaction_type');
            $table->index('status');
        });

        // Partitioning by year
        DB::statement('ALTER TABLE revenue_fact PARTITION BY RANGE (YEAR(transaction_date)) (
            PARTITION p2024 VALUES LESS THAN (2025),
            PARTITION p2025 VALUES LESS THAN (2026),
            PARTITION p2026 VALUES LESS THAN (2027)
        )');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_fact');
    }
};
