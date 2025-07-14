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
            // Patient History Fields
            $table->text('chief_complaint')->nullable();
            $table->string('symptom_duration')->nullable();
            $table->text('past_medical_history')->nullable();
            $table->text('medication_history')->nullable();
            $table->string('allergies')->nullable();
            $table->text('family_history')->nullable();
            $table->text('social_history')->nullable();

            // Pain and Visit Information
            $table->integer('pain_scale')->nullable();
            $table->enum('visit_type', ['Initial', 'Follow-up', 'Emergency'])->nullable();

            // Additional Vitals
            $table->integer('heart_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->integer('oxygen_saturation')->nullable();

            // Notes
            $table->text('physician_notes')->nullable();
            $table->text('additional_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_data', function (Blueprint $table) {
            $table->dropColumn([
                'chief_complaint',
                'symptom_duration',
                'past_medical_history',
                'medication_history',
                'allergies',
                'family_history',
                'social_history',
                'pain_scale',
                'visit_type',
                'heart_rate',
                'respiratory_rate',
                'oxygen_saturation',
                'physician_notes',
                'additional_notes'
            ]);
        });
    }
};
