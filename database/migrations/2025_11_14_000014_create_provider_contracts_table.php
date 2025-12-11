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
        Schema::create('provider_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_provider_id')->constrained('insurance_providers')->onDelete('cascade');
            $table->foreignId('hospital_id')->constrained('users')->onDelete('cascade');
            $table->string('contract_number')->unique();
            $table->string('clearinghouse_provider'); // availity, change_healthcare, trizetto
            $table->json('routing_rules'); // Rules for claim routing
            $table->json('fee_schedule')->nullable(); // Fee schedule data
            $table->decimal('contract_rate', 5, 4)->default(1.0); // Contract rate multiplier
            $table->boolean('auto_submit')->default(true);
            $table->integer('batch_size_limit')->default(50);
            $table->json('supported_claim_types')->nullable(); // 837P, 837I, etc.
            $table->date('effective_date');
            $table->date('expiration_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['insurance_provider_id', 'hospital_id']);
            $table->index(['clearinghouse_provider', 'is_active']);
            $table->index('contract_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_contracts');
    }
};
