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
        Schema::create('workflow_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_type'); // claim_submission, appeal_followup, document_collection, etc.
            $table->morphs('taskable'); // claim, appeal_workflow, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('task_data')->nullable(); // Additional data for the task
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('reminders_sent')->nullable(); // Track reminder notifications
            $table->timestamps();

            $table->index(['task_type', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('due_date');
            $table->index(['taskable_type', 'taskable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_tasks');
    }
};
