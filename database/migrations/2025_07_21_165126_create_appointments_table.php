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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->string('appointment_number')->unique();
            $table->datetime('appointment_date');
            $table->datetime('appointment_end');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('pending');
            $table->text('reason')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->text('patient_notes')->nullable();
            $table->integer('consultation_fee')->nullable(); // in cents
            $table->enum('appointment_type', ['in_person', 'video_call', 'phone_call'])->default('in_person');
            $table->string('meeting_link')->nullable(); // for video calls
            $table->datetime('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable(); // 'doctor' or 'patient'
            $table->text('cancellation_reason')->nullable();
            $table->datetime('confirmed_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->boolean('follow_up_required')->default(false);
            $table->timestamps();

            $table->index(['doctor_id', 'appointment_date']);
            $table->index(['patient_id', 'appointment_date']);
            $table->index(['status', 'appointment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
