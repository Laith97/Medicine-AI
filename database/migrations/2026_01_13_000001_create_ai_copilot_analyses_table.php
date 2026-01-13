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
        Schema::create('ai_copilot_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->json('analysis_data');
            $table->timestamp('generated_at');
            $table->text('summary');
            $table->json('considerations')->nullable();
            $table->json('questions')->nullable();
            $table->json('red_flags')->nullable();
            $table->enum('status', ['active', 'archived', 'deleted'])->default('active');
            $table->foreignId('reviewed_by_doctor')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('doctor_notes')->nullable();
            // Guest patient fields for appointments without registered patients
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->date('guest_date_of_birth')->nullable();
            $table->string('guest_gender')->nullable();
            $table->text('guest_address')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('appointment_id');
            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('status');
            $table->index('generated_at');
            $table->index('reviewed_at');
            $table->index(['patient_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_copilot_analyses');
    }
};