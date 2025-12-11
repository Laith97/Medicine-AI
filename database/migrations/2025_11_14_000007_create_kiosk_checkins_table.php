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
        Schema::create('kiosk_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('kiosk_session_id')->constrained('kiosk_sessions', 'session_id')->onDelete('cascade');
            $table->timestamp('checkin_time');
            $table->enum('verification_method', ['qr_code', 'id_card', 'biometric', 'manual'])->default('qr_code');
            $table->json('verification_data')->nullable(); // Store verification details
            $table->timestamps();

            $table->unique(['appointment_id']); // One checkin per appointment
            $table->index(['kiosk_session_id']);
            $table->index(['checkin_time']);
            $table->index(['verification_method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kiosk_checkins');
    }
};
