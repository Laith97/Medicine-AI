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
        Schema::create('patient_dim', function (Blueprint $table) {
            $table->integer('patient_key')->primary();
            $table->unsignedBigInteger('patient_id');
            $table->string('patient_key_external', 100)->nullable(); // External patient identifier
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('ethnicity', 50)->nullable();
            $table->string('primary_language', 50)->nullable();
            $table->string('insurance_provider', 100)->nullable();
            $table->string('insurance_plan_type', 50)->nullable();
            $table->decimal('risk_score', 5, 2)->nullable();
            $table->json('chronic_conditions')->nullable(); // JSON array of conditions
            $table->json('allergies')->nullable(); // JSON array of allergies
            $table->unsignedBigInteger('primary_doctor_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->date('first_visit_date')->nullable();
            $table->date('last_visit_date')->nullable();
            $table->integer('total_visits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_dim');
    }
};
