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
        Schema::table('patient_data', function (Blueprint $table) {
            $table->dropColumn('allergies');
            $table->json('allergies')->nullable();
            $table->json('past_medications')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_data', function (Blueprint $table) {
            $table->dropColumn(['allergies', 'past_medications']);
            $table->string('allergies')->nullable();
        });
    }
};
