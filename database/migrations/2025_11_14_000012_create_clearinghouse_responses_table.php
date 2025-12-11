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
        if (!Schema::hasTable('clearinghouse_responses')) {
            Schema::create('clearinghouse_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clearinghouse_account_id')->constrained('clearinghouse_accounts')->onDelete('cascade');
                $table->foreignId('clearinghouse_submission_id')->nullable()->constrained('clearinghouse_submissions')->onDelete('set null');
                $table->string('response_type'); // 277CA, 835, 999, etc.
                $table->string('transaction_set_id')->nullable(); // ISA13, etc.
                $table->string('batch_id')->nullable(); // Reference to batch if available
                $table->enum('status', [
                    'received', 'processed', 'error', 'acknowledged'
                ])->default('received');
                $table->longText('response_content'); // The EDI response content
                $table->json('parsed_data')->nullable(); // Parsed response data
                $table->integer('claim_count')->default(0); // Number of claims in response
                $table->decimal('total_paid_amount', 12, 2)->default(0); // For 835 responses
                $table->decimal('total_adjustment_amount', 12, 2)->default(0); // For 835 responses
                $table->timestamp('received_at');
                $table->timestamp('processed_at')->nullable();
                $table->text('processing_errors')->nullable();
                $table->json('metadata')->nullable(); // Additional response metadata
                $table->timestamps();

                $table->index(['clearinghouse_account_id', 'response_type'], 'ch_resp_account_type_idx');
                $table->index(['clearinghouse_submission_id', 'status']);
                $table->index(['status', 'received_at']);
                $table->index('transaction_set_id');
                $table->index('batch_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearinghouse_responses');
    }
};
