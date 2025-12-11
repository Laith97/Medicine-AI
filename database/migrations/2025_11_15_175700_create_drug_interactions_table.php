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
        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('drug_1');
            $table->string('drug_2');
            $table->text('description');
            $table->enum('severity', ['mild', 'moderate', 'severe']);
            $table->text('clinical_consequence')->nullable();
            $table->text('recommendation')->nullable();
            $table->json('evidence_sources')->nullable();
            $table->timestamps();

            $table->index(['drug_1', 'drug_2']);
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_interactions');
    }
};
