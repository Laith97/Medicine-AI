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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model'); // Model class name
            $table->unsignedBigInteger('model_id'); // Model instance ID
            $table->string('action'); // created, updated, deleted, accessed
            $table->unsignedBigInteger('user_id')->nullable(); // User who performed action
            $table->string('ip_address')->nullable(); // IP address of request
            $table->text('user_agent')->nullable(); // User agent string
            $table->timestamp('timestamp'); // When the action occurred
            $table->json('data')->nullable(); // Additional data about the action
            $table->timestamps();

            // Indexes for performance
            $table->index(['model', 'model_id']);
            $table->index('action');
            $table->index('user_id');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
