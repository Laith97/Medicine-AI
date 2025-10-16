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
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_id')->unique(); // Unique claim identifier
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->text('diagnosis_text')->nullable();
            $table->text('procedure_text')->nullable();
            $table->json('icd10_codes')->nullable(); // Array of ICD-10 codes
            $table->json('cpt_codes')->nullable(); // Array of CPT codes
            $table->string('payer')->nullable(); // Insurance payer name
            $table->enum('claim_status', [
                'submitted', 'pending', 'approved', 'denied', 'paid', 'partially_paid', 'rejected'
            ])->default('submitted');
            $table->text('denial_reason')->nullable();
            $table->string('raw_denial_code')->nullable(); // Original denial code from payer
            $table->enum('normalized_denial_category', [
                'documentation_missing',
                'coding_error',
                'coverage_issue',
                'medical_necessity',
                'timely_filing',
                'duplicate_claim',
                'other'
            ])->nullable();
            $table->decimal('expected_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('payment_difference', 10, 2)->default(0); // expected - paid
            $table->json('era_eob_data')->nullable(); // Store parsed ERA/EOB data
            $table->date('service_date')->nullable();
            $table->date('submission_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'claim_status']);
            $table->index(['claim_status', 'submission_date']);
            $table->index('payer');
            $table->index('normalized_denial_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
