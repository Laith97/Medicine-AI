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
            $table->foreignId('chat_session_id')->constrained('chat_sessions')->onDelete('cascade');
            $table->text('message');
            $table->enum('sender_type', ['visitor', 'doctor', 'bot'])->default('visitor');
            $table->boolean('is_read')->default(false);
            $table->json('metadata')->nullable(); // For storing additional data like bot response context
            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
            $table->index(['sender_type', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
