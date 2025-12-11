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
        Schema::create('clearinghouse_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearinghouse_account_id')->constrained('clearinghouse_accounts')->onDelete('cascade');
            $table->string('batch_id')->unique(); // Unique batch identifier from clearinghouse
            $table->string('submission_type'); // 837P, 837I, 837D, etc.
            $table->enum('status', [
                'pending', 'submitted', 'accepted', 'rejected', 'partial_accept'
            ])->default('pending');
            $table->longText('edi_content'); // The EDI file content
            $table->string('file_name')->nullable(); // Original file name if uploaded
            $table->integer('claim_count')->default(0); // Number of claims in this batch
            $table->decimal('total_amount', 12, 2)->default(0); // Total billed amount
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('response_received_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional submission metadata
            $table->timestamps();

            $table->index(['clearinghouse_account_id', 'status']);
            $table->index(['status', 'submitted_at']);
            $table->index('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearinghouse_submissions');
    }
};
