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
        Schema::create('batch_optimization_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('users')->onDelete('cascade');
            $table->string('rule_name');
            $table->text('description')->nullable();
            $table->string('clearinghouse_provider');
            $table->json('grouping_criteria'); // How to group claims (by payer, provider, amount, etc.)
            $table->integer('max_batch_size')->default(50);
            $table->integer('min_batch_size')->default(1);
            $table->decimal('max_total_amount', 12, 2)->nullable();
            $table->json('priority_rules')->nullable(); // Rules for claim priority
            $table->boolean('auto_create_batches')->default(true);
            $table->time('submission_cutoff_time')->nullable(); // Daily cutoff time
            $table->json('exclusion_rules')->nullable(); // Claims to exclude
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['hospital_id', 'clearinghouse_provider']);
            $table->index(['is_active', 'clearinghouse_provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_optimization_rules');
    }
};
