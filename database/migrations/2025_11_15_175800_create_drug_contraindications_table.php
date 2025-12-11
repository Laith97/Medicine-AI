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
        Schema::create('drug_contraindications', function (Blueprint $table) {
            $table->id();
            $table->string('drug_name');
            $table->string('condition');
            $table->text('reason');
            $table->enum('severity', ['mild', 'moderate', 'severe']);
            $table->text('alternative_options')->nullable();
            $table->text('monitoring_required')->nullable();
            $table->json('evidence_sources')->nullable();
            $table->timestamps();

            $table->index(['drug_name', 'condition']);
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_contraindications');
    }
};
