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
        Schema::create('on_deck_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->comment('Associated appointment ID');
            $table->unsignedBigInteger('doctor_id')->comment('Doctor ID');
            $table->unsignedBigInteger('patient_id')->comment('Patient ID');
            $table->enum('status', ['waiting', 'ready', 'in-progress', 'completed', 'no-show'])->default('waiting')->comment('Current status');
            $table->integer('position')->comment('Queue position (1-based)');
            $table->integer('estimated_wait_minutes')->nullable()->comment('Estimated wait time in minutes');
            $table->float('risk_score', 3, 2)->nullable()->comment('Risk assessment score (0-1)');
            $table->json('risk_factors')->nullable()->comment('Risk factors as JSON array');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for performance
            $table->index(['doctor_id', 'status']);
            $table->index(['doctor_id', 'position']);
            $table->index(['appointment_id']);
            $table->index(['patient_id']);
            $table->index(['status']);
            $table->index(['risk_score']);

            // Ensure unique appointment_id (one on-deck entry per appointment)
            $table->unique('appointment_id', 'unique_appointment_on_deck');

            // Composite unique constraint for doctor + position (position must be unique per doctor)
            $table->unique(['doctor_id', 'position'], 'unique_doctor_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('on_deck_appointments');
    }
};
