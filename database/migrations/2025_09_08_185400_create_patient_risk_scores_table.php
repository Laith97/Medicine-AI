<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id');
            $table->decimal('no_show_risk', 3, 2)->nullable();
            $table->decimal('hospitalization_risk', 3, 2)->nullable();
            $table->timestamps();
            $table->foreign('patient_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_risk_scores');
    }
};
