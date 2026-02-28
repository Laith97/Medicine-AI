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
        Schema::create('appeal_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->onDelete('cascade');
            $table->string('denial_category'); // normalized denial category
            $table->string('current_step')->default('initial_review');
            $table->json('workflow_steps'); // Defined steps for this denial type
            $table->json('completed_steps')->nullable(); // Track completed steps
            $table->date('deadline')->nullable(); // Appeal deadline
            $table->boolean('auto_appeal_eligible')->default(false);
            $table->decimal('appeal_probability', 5, 4)->nullable(); // AI predicted success rate
            $table->text('appeal_reason')->nullable();
            $table->json('required_documents')->nullable(); // Documents needed for appeal
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['claim_id', 'status']);
            $table->index(['denial_category', 'status']);
            $table->index('deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appeal_workflows');
    }
};
