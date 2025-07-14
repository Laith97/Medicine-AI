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
            // General Appearance
            $table->enum('consciousness_level', ['Alert', 'Drowsy', 'Unresponsive'])->nullable();
            $table->enum('mood_behavior', ['Calm', 'Anxious', 'Aggressive', 'Confused'])->nullable();
            $table->enum('speech_clarity', ['Clear', 'Slurred', 'Incoherent'])->nullable();
            $table->enum('hygiene_level', ['Good', 'Fair', 'Poor'])->nullable();

            // Head, Eyes, Ears, Nose, Mouth (HEENT)
            $table->string('scalp_condition')->nullable();
            $table->enum('pupil_reactivity', ['PERRLA', 'Unequal', 'Non-reactive'])->nullable();
            $table->boolean('vision_issues')->default(false);
            $table->boolean('hearing_issues')->default(false);
            $table->text('oral_findings')->nullable();

            // Neurological
            $table->enum('orientation_level', ['Oriented x4', 'Oriented x3', 'Oriented x2', 'Disoriented'])->nullable();
            $table->enum('limb_strength', ['Equal', 'Weak Left', 'Weak Right', 'Paralyzed'])->nullable();
            $table->enum('reflexes', ['Normal', 'Hyperreflexia', 'Hyporeflexia'])->nullable();
            $table->text('sensation_findings')->nullable();

            // Neck and Chest
            $table->enum('trachea_position', ['Midline', 'Deviated'])->nullable();
            $table->boolean('jvd_present')->default(false);
            $table->enum('lung_sounds', ['Clear', 'Crackles', 'Wheezes', 'Diminished'])->nullable();
            $table->enum('heart_sounds', ['Normal', 'Murmur', 'Irregular'])->nullable();
            $table->enum('capillary_refill_time', ['< 2s', '2–3s', '> 3s'])->nullable();

            // Abdomen
            $table->enum('abdominal_shape', ['Flat', 'Distended', 'Scarred'])->nullable();
            $table->enum('bowel_sounds', ['Normal', 'Hyperactive', 'Hypoactive', 'Absent'])->nullable();
            $table->boolean('abdominal_tenderness')->default(false);
            $table->boolean('nausea_or_vomiting')->default(false);
            $table->enum('appetite_level', ['Good', 'Poor', 'None'])->nullable();

            // Genitourinary
            $table->boolean('urination_issues')->default(false);
            $table->boolean('catheter_present')->default(false);
            $table->text('urine_characteristics')->nullable();

            // Musculoskeletal
            $table->enum('range_of_motion', ['Full', 'Limited', 'None'])->nullable();
            $table->enum('gait_stability', ['Stable', 'Unsteady', 'Requires assistance'])->nullable();
            $table->string('assistive_devices')->nullable();

            // Skin
            $table->enum('skin_color', ['Pink', 'Pale', 'Cyanotic', 'Jaundiced'])->nullable();
            $table->enum('skin_temperature', ['Warm', 'Cool', 'Cold'])->nullable();
            $table->text('skin_lesions')->nullable();
            $table->boolean('pressure_ulcers')->default(false);

            // Pain Assessment (additional to existing pain_scale)
            $table->text('pain_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_data', function (Blueprint $table) {
            $table->dropColumn([
                // General Appearance
                'consciousness_level', 'mood_behavior', 'speech_clarity', 'hygiene_level',
                // HEENT
                'scalp_condition', 'pupil_reactivity', 'vision_issues', 'hearing_issues', 'oral_findings',
                // Neurological
                'orientation_level', 'limb_strength', 'reflexes', 'sensation_findings',
                // Neck and Chest
                'trachea_position', 'jvd_present', 'lung_sounds', 'heart_sounds', 'capillary_refill_time',
                // Abdomen
                'abdominal_shape', 'bowel_sounds', 'abdominal_tenderness', 'nausea_or_vomiting', 'appetite_level',
                // Genitourinary
                'urination_issues', 'catheter_present', 'urine_characteristics',
                // Musculoskeletal
                'range_of_motion', 'gait_stability', 'assistive_devices',
                // Skin
                'skin_color', 'skin_temperature', 'skin_lesions', 'pressure_ulcers',
                // Pain Assessment
                'pain_description'
            ]);
        });
    }
};
