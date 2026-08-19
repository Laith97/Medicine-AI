<?php

namespace Database\Factories;

use App\Models\PatientData;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientDataFactory extends Factory
{
    protected $model = PatientData::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'age' => $this->faker->numberBetween(18, 80),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'weight' => $this->faker->numberBetween(50, 120),
            'height' => $this->faker->numberBetween(150, 200),
            'temperature' => $this->faker->optional()->randomFloat(1, 36.0, 40.0),
            'blood_pressure' => $this->faker->optional()->randomElement(['120/80', '130/85', '140/90']),
            'blood_sugar' => $this->faker->optional()->numberBetween(70, 200),
            'symptoms' => $this->faker->randomElements(['fever', 'headache', 'cough', 'nausea', 'fatigue', 'pain'], $this->faker->numberBetween(0, 3)),
            'test_results' => $this->faker->optional()->sentence(),
            'preliminary_diagnosis' => $this->faker->optional()->sentence(),
            'ai_response' => $this->faker->optional()->paragraph(),
            'user_id' => User::factory(),
            'assigned_patient_id' => User::factory(),
            'previous_record_id' => null,
            'visit_number' => 1,
            'patient_key' => $this->faker->uuid(),
            'chief_complaint' => $this->faker->optional()->sentence(),
            'symptom_duration' => $this->faker->optional()->randomElement(['1 day', '3 days', '1 week', '2 weeks']),
            'past_medical_history' => $this->faker->optional()->sentence(),
            'medication_history' => $this->faker->optional()->sentence(),
            'allergies' => $this->faker->randomElements(['penicillin', 'sulfa drugs', 'aspirin', 'codeine'], $this->faker->numberBetween(0, 2)),
            'past_medications' => $this->faker->randomElements(['ibuprofen', 'amoxicillin', 'lisinopril', 'metformin'], $this->faker->numberBetween(0, 3)),
            'family_history' => $this->faker->optional()->sentence(),
            'social_history' => $this->faker->optional()->sentence(),
            'pain_scale' => $this->faker->optional()->numberBetween(1, 10),
            'visit_type' => $this->faker->randomElement(['Initial', 'Follow-up', 'Emergency']),
            'heart_rate' => $this->faker->optional()->numberBetween(60, 120),
            'respiratory_rate' => $this->faker->optional()->numberBetween(12, 24),
            'oxygen_saturation' => $this->faker->optional()->numberBetween(95, 100),
            'physician_notes' => $this->faker->optional()->paragraph(),
            'additional_notes' => $this->faker->optional()->paragraph(),
            'consciousness_level' => $this->faker->randomElement(['Alert', 'Drowsy', 'Unresponsive']),
            'mood_behavior' => $this->faker->randomElement(['Calm', 'Anxious', 'Aggressive', 'Confused']),
            'speech_clarity' => $this->faker->randomElement(['Clear', 'Slurred', 'Incoherent']),
            'hygiene_level' => $this->faker->randomElement(['Good', 'Fair', 'Poor']),
            'scalp_condition' => $this->faker->optional()->word(),
            'pupil_reactivity' => $this->faker->randomElement(['PERRLA', 'Unequal', 'Non-reactive']),
            'vision_issues' => $this->faker->boolean(),
            'hearing_issues' => $this->faker->boolean(),
            'oral_findings' => $this->faker->optional()->sentence(),
            'orientation_level' => $this->faker->randomElement(['Oriented x4', 'Oriented x3', 'Oriented x2', 'Disoriented']),
            'limb_strength' => $this->faker->randomElement(['Equal', 'Weak Left', 'Weak Right', 'Paralyzed']),
            'reflexes' => $this->faker->randomElement(['Normal', 'Hyperreflexia', 'Hyporeflexia']),
            'sensation_findings' => $this->faker->optional()->sentence(),
            'trachea_position' => $this->faker->randomElement(['Midline', 'Deviated']),
            'jvd_present' => $this->faker->boolean(),
            'lung_sounds' => $this->faker->randomElement(['Clear', 'Crackles', 'Wheezes', 'Diminished']),
            'heart_sounds' => $this->faker->randomElement(['Normal', 'Murmur', 'Irregular']),
            'capillary_refill_time' => $this->faker->randomElement(['< 2s', '2–3s', '> 3s']),
            'abdominal_shape' => $this->faker->randomElement(['Flat', 'Distended', 'Scarred']),
            'bowel_sounds' => $this->faker->randomElement(['Normal', 'Hyperactive', 'Hypoactive', 'Absent']),
            'abdominal_tenderness' => $this->faker->boolean(),
            'nausea_or_vomiting' => $this->faker->boolean(),
            'appetite_level' => $this->faker->randomElement(['Good', 'Poor', 'None']),
            'urination_issues' => $this->faker->boolean(),
            'catheter_present' => $this->faker->boolean(),
            'urine_characteristics' => $this->faker->optional()->word(),
            'range_of_motion' => $this->faker->randomElement(['Full', 'Limited', 'None']),
            'gait_stability' => $this->faker->randomElement(['Stable', 'Unsteady', 'Requires assistance']),
            'assistive_devices' => $this->faker->optional()->randomElement(['Cane', 'Walker', 'Wheelchair']),
            'skin_color' => $this->faker->randomElement(['Pink', 'Pale', 'Cyanotic', 'Jaundiced']),
            'skin_temperature' => $this->faker->randomElement(['Warm', 'Cool', 'Cold']),
            'skin_lesions' => $this->faker->optional()->sentence(),
            'pressure_ulcers' => $this->faker->boolean(),
            'pain_description' => $this->faker->optional()->sentence(),
        ];
    }
}