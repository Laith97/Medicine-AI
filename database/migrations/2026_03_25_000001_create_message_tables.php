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
        // message_threads table
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['general', 'follow_up'])->default('general');
            $table->foreignId('diagnosis_id')->nullable()->constrained('diagnoses')->onDelete('set null');
            $table->string('subject');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'last_message_at']);
            $table->index(['doctor_id', 'last_message_at']);
        });

        // ai_message_suggestions table (must be before messages since messages references it)
        Schema::create('ai_message_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('message_threads')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->text('suggested_reply');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });

        // messages table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('message_threads')->onDelete('cascade');
            $table->enum('sender_type', ['patient', 'doctor', 'ai']);
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->foreignId('ai_suggestion_id')->nullable()->constrained('ai_message_suggestions')->onDelete('set null');
            $table->boolean('is_sent')->default(true);
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
        });

        // message_attachments table
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('size_bytes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('ai_message_suggestions');
        Schema::dropIfExists('message_threads');
    }
};
