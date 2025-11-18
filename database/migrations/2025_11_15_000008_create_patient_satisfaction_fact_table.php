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
        if (!Schema::hasTable('patient_satisfaction_fact')) {
            Schema::create('patient_satisfaction_fact', function (Blueprint $table) {
                $table->bigIncrements('outcome_key');
                $table->integer('date_key');
                $table->integer('patient_key');
                $table->integer('doctor_key');
                $table->integer('service_key')->nullable();
                $table->unsignedBigInteger('diagnosis_id');
                $table->date('outcome_date');
                $table->string('diagnosis_code', 20)->nullable();
                $table->string('procedure_code', 20)->nullable();
                $table->string('outcome_category', 100)->nullable(); // Successful, Complication, Readmission
                $table->decimal('outcome_score', 5, 2)->nullable(); // 0-1 scale
                $table->integer('length_of_stay_days')->nullable();
                $table->boolean('readmission_within_30_days')->default(false);
                $table->boolean('complication_occurred')->default(false);
                $table->decimal('patient_satisfaction', 3, 2)->nullable();
                $table->decimal('treatment_cost', 10, 2)->nullable();
                $table->boolean('follow_up_required')->default(false);
                $table->boolean('follow_up_completed')->default(false);
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();

                // Indexes
                $table->index(['date_key', 'patient_key', 'doctor_key']);
                $table->index('outcome_date');
                $table->index('outcome_category');

                // Only add foreign keys in non-testing environments
                if (app()->environment() !== 'testing') {
                    $table->foreign('date_key')->references('date_key')->on('dim_date');
                    $table->foreign('patient_key')->references('patient_key')->on('patient_dim');
                    $table->foreign('doctor_key')->references('doctor_key')->on('doctor_dim');
                    $table->foreign('service_key')->references('service_key')->on('service_dim');
                }
            });

            // Only apply partitioning in non-testing environments
            if (app()->environment() !== 'testing') {
                DB::statement('ALTER TABLE patient_satisfaction_fact PARTITION BY RANGE (YEAR(outcome_date)) (
                    PARTITION p2024 VALUES LESS THAN (2025),
                    PARTITION p2025 VALUES LESS THAN (2026),
                    PARTITION p2026 VALUES LESS THAN (2027)
                )');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_satisfaction_fact');
    }
};
