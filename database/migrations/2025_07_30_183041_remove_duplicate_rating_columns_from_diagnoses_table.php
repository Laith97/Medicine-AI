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
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropColumn(['patient_rating', 'patient_review_text', 'patient_rated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->tinyInteger('patient_rating')->nullable();
            $table->text('patient_review_text')->nullable();
            $table->timestamp('patient_rated_at')->nullable();
        });
    }
};
