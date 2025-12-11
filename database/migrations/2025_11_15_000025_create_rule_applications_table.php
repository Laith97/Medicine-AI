<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('payer_rules')->onDelete('cascade');
            $table->foreignId('claim_id')->constrained('claims')->onDelete('cascade');
            $table->json('application_result')->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->index(['rule_id', 'claim_id']);
            $table->index('applied_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_applications');
    }
};
