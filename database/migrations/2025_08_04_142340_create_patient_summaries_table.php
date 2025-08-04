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
        Schema::create('patient_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->text('summary')->nullable();
            $table->text('raw_data')->nullable();
            $table->timestamp('last_visit_date')->nullable();
            $table->integer('total_visits')->default(0);
            $table->boolean('is_ai_generated')->default(true);
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patient_data')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['patient_id', 'doctor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_summaries');
    }
};
