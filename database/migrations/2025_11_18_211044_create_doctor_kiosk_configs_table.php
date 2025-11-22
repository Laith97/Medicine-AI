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
        Schema::create('doctor_kiosk_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->string('clinic_name', 255);
            $table->text('clinic_address');
            $table->string('contact_phone', 20);
            $table->string('kiosk_display_name', 255);
            $table->string('primary_color', 7)->default('#2563eb');
            $table->string('secondary_color', 7)->default('#6b7280');
            $table->boolean('auto_approve_appointments')->default(false);
            $table->boolean('require_payment_upfront')->default(false);
            $table->boolean('voice_instructions_enabled')->default(true);
            $table->boolean('high_contrast_mode')->default(false);
            $table->string('kiosk_token', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->json('additional_settings')->nullable(); // For future extensibility
            $table->timestamps();

            // Indexes for performance
            $table->unique(['doctor_id']); // One config per doctor
            $table->index(['is_active']);
            $table->index(['kiosk_token']);
            $table->index(['doctor_id', 'is_active']); // For active configs lookup
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_kiosk_configs');
    }
};
