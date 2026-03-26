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
        Schema::create('waitlist_match_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waitlist_id')->constrained('waitlists')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->foreignId('availability_slot_id')->nullable()->constrained('availability_slots')->onDelete('set null');
            $table->decimal('match_score', 5, 4)->nullable(); // 0-1 confidence score
            $table->enum('status', ['pending', 'sent', 'accepted', 'declined', 'expired', 'booked'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->enum('patient_response', ['accept', 'decline'])->nullable();
            $table->text('decline_reason')->nullable();
            $table->timestamps();

            $table->index('waitlist_id');
            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('status');
        });

        Schema::create('waitlist_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->boolean('ai_matching_enabled')->default(false);
            $table->boolean('auto_send_offers')->default(false); // Auto-send without manual approval
            $table->integer('min_match_score')->default(0.7); // Minimum 70% match
            $table->integer('offer_expiry_minutes')->default(60);
            $table->integer('max_offers_per_slot')->default(3); // Offer to multiple patients per slot
            $table->boolean('priority_override_auto')->default(false); // Manual priority takes precedence
            $table->timestamps();

            $table->unique('doctor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_ai_settings');
        Schema::dropIfExists('waitlist_match_offers');
    }
};
