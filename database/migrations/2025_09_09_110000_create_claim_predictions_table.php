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
        Schema::create('claim_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->onDelete('cascade');
            $table->decimal('denial_risk', 5, 4); // e.g., 0.1234 for 12.34%
            $table->string('model_version', 50);
            $table->timestamps();

            $table->index(['claim_id', 'created_at']);
            $table->index('denial_risk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_predictions');
    }
};
