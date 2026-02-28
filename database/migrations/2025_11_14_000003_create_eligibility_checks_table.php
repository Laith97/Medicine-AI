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
        Schema::create('eligibility_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_insurance_id')->constrained('patient_insurances')->onDelete('cascade');
            $table->timestamp('check_date');
            $table->string('service_type');
            $table->enum('eligibility_status', ['eligible', 'ineligible', 'pending', 'error']);
            $table->json('response_data')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('checked_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['patient_insurance_id', 'check_date']);
            $table->index('eligibility_status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eligibility_checks');
    }
};
