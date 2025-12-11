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
        Schema::create('waitlist_patient_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->json('preferred_times')->nullable(); // ['morning', 'afternoon', 'evening']
            $table->json('preferred_days')->nullable(); // ['monday', 'tuesday', 'wednesday']
            $table->json('service_priorities')->nullable(); // {'consultation': 'high', 'follow_up': 'medium'}
            $table->json('notification_settings')->nullable(); // {'email': true, 'sms': true, 'push': false}
            $table->integer('auto_accept_threshold')->default(7); // days - auto-accept offers within this threshold
            $table->timestamps();

            // Indexes for performance
            $table->unique(['patient_id', 'doctor_id']); // One preference record per patient-doctor pair
            $table->index(['patient_id']);
            $table->index(['doctor_id']);
            $table->index('auto_accept_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_patient_preferences');
    }
};
