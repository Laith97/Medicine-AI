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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('document_type'); // 'claim', 'prescription', 'diagnosis', 'custom'
            $table->string('status')->default('draft'); // 'draft', 'submitted', 'under_review', 'approved', 'rejected', 'archived'
            $table->string('workflow_state')->default('created'); // State machine state
            $table->json('metadata')->nullable(); // Additional document metadata
            $table->json('compliance_data')->nullable(); // Compliance-related data
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'status']);
            $table->index('workflow_state');
            $table->index('created_by');

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
