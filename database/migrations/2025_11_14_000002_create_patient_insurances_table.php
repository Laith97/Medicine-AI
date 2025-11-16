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
        Schema::create('patient_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patient_data')->onDelete('cascade');
            $table->foreignId('insurance_provider_id')->constrained('insurance_providers')->onDelete('cascade');
            $table->string('policy_number');
            $table->string('group_number')->nullable();
            $table->string('subscriber_id');
            $table->string('relationship_to_subscriber');
            $table->date('effective_date');
            $table->date('termination_date')->nullable();
            $table->json('copay_info')->nullable();
            $table->json('deductible_info')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'insurance_provider_id']);
            $table->index('policy_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_insurances');
    }
};
