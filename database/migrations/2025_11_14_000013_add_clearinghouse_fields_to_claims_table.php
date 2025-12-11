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
        Schema::table('claims', function (Blueprint $table) {
            $table->foreignId('clearinghouse_submission_id')->nullable()->constrained('clearinghouse_submissions')->onDelete('set null');
            $table->string('clearinghouse_batch_id')->nullable(); // Reference to clearinghouse batch
            $table->string('clearinghouse_provider')->nullable(); // Provider name (availity, etc.)
            $table->string('clearinghouse_claim_id')->nullable(); // Claim ID assigned by clearinghouse
            $table->timestamp('clearinghouse_submitted_at')->nullable();
            $table->timestamp('clearinghouse_response_received_at')->nullable();

            $table->index(['clearinghouse_submission_id']);
            $table->index(['clearinghouse_provider', 'clearinghouse_submitted_at']);
            $table->index('clearinghouse_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['clearinghouse_submission_id']);
            $table->dropColumn([
                'clearinghouse_submission_id',
                'clearinghouse_batch_id',
                'clearinghouse_provider',
                'clearinghouse_claim_id',
                'clearinghouse_submitted_at',
                'clearinghouse_response_received_at'
            ]);
        });
    }
};
