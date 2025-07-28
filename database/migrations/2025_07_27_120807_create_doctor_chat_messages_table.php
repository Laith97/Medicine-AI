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
        Schema::create('doctor_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->string('session_id'); // To group messages in a conversation
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('visitor_phone')->nullable();
            $table->text('message');
            $table->enum('sender_type', ['visitor', 'bot', 'doctor'])->default('visitor');
            $table->boolean('is_read')->default(false);
            $table->json('metadata')->nullable(); // For storing IP, user agent, etc.
            $table->timestamps();

            $table->index(['doctor_id', 'session_id']);
            $table->index(['doctor_id', 'is_read']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_chat_messages');
    }
};
