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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_session_id');
            $table->text('message');
            $table->enum('sender_type', ['visitor', 'doctor', 'bot'])->default('visitor');
            $table->boolean('is_read')->default(false);
            $table->json('metadata')->nullable(); // For storing additional data like bot response context
            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
            $table->index(['sender_type', 'is_read']);
        });

        // Add foreign key constraint only if chat_sessions table exists
        if (Schema::hasTable('chat_sessions')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->foreign('chat_session_id')->references('id')->on('chat_sessions')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
