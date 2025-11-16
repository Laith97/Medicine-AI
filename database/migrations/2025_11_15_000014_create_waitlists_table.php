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
        Schema::create('waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->string('service_type'); // e.g., 'consultation', 'follow_up', 'emergency'
            $table->enum('priority_level', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->json('preferred_time_slots')->nullable(); // ['09:00-12:00', '14:00-17:00']
            $table->json('preferred_days')->nullable(); // ['monday', 'wednesday', 'friday']
            $table->integer('max_wait_days')->default(30);
            $table->json('notification_channels')->nullable(); // ['email', 'sms', 'push']
            $table->enum('status', ['active', 'paused', 'cancelled', 'fulfilled'])->default('active');
            $table->timestamps();

            // Indexes for performance
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
            $table->index(['priority_level', 'status']);
            $table->index(['service_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlists');
    }
};
