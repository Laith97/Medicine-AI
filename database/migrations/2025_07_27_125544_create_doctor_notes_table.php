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
        Schema::create('doctor_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->enum('note_type', ['text', 'voice'])->default('text');
            $table->text('note_text');
            $table->text('transcript')->nullable(); // For voice notes transcription
            $table->string('audio_file_path')->nullable(); // Store audio file path if needed
            $table->date('appointment_date')->nullable(); // Optional appointment date reference
            $table->string('title')->nullable(); // Optional note title
            $table->text('tags')->nullable(); // JSON field for tags/categories
            $table->softDeletes(); // Soft delete for security
            $table->timestamps();

            // Indexes for better performance
            $table->index(['doctor_id', 'created_at']);
            $table->index(['patient_id', 'created_at']);
            $table->index('note_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_notes');
    }
};
