<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments_fact', function (Blueprint $table) {
            $table->bigIncrements('appointment_key');
            $table->integer('date_key');
            $table->integer('time_key');
            $table->integer('patient_key');
            $table->integer('doctor_key');
            $table->integer('hospital_key')->nullable();
            $table->integer('service_key')->nullable();
            $table->unsignedBigInteger('appointment_id');
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->time('actual_start_time')->nullable();
            $table->time('actual_end_time')->nullable();
            $table->string('status', 50); // Scheduled, Completed, Cancelled, No-show
            $table->string('appointment_type', 50)->nullable();
            $table->string('booking_method', 50)->nullable(); // Online, Phone, Walk-in
            $table->integer('wait_time_minutes')->nullable();
            $table->integer('consultation_duration_minutes')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->boolean('follow_up_scheduled')->default(false);
            $table->decimal('patient_satisfaction_score', 3, 2)->nullable();
            $table->text('doctor_notes')->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->decimal('insurance_covered_amount', 10, 2)->nullable();
            $table->decimal('patient_paid_amount', 10, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('date_key')->references('date_key')->on('dim_date');
            $table->foreign('time_key')->references('time_key')->on('time_dim');
            $table->foreign('patient_key')->references('patient_key')->on('patient_dim');
            $table->foreign('doctor_key')->references('doctor_key')->on('doctor_dim');
            $table->foreign('service_key')->references('service_key')->on('service_dim');

            // Indexes
            $table->index(['date_key', 'patient_key', 'doctor_key']);
            $table->index('scheduled_date');
            $table->index('status');
        });

        // Partitioning by year
        DB::statement('ALTER TABLE appointments_fact PARTITION BY RANGE (YEAR(scheduled_date)) (
            PARTITION p2024 VALUES LESS THAN (2025),
            PARTITION p2025 VALUES LESS THAN (2026),
            PARTITION p2026 VALUES LESS THAN (2027)
        )');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments_fact');
    }
};
