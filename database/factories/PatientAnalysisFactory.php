<?php

namespace Database\Factories;

use App\Models\PatientAnalysis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientAnalysisFactory extends Factory
{
    protected $model = PatientAnalysis::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'age' => $this->faker->numberBetween(18, 80),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'weight' => $this->faker->numberBetween(50, 120),
            'height' => $this->faker->numberBetween(150, 200),
            'temperature' => $this->faker->randomFloat(1, 36.0, 39.0),
            'blood_pressure' => $this->faker->randomElement(['120/80', '130/85', '140/90', '110/70']),
            'blood_sugar' => $this->faker->numberBetween(80, 200),
            'symptoms' => $this->faker->sentence(10),
            'test_results' => $this->faker->paragraph(),
            'preliminary_diagnosis' => $this->faker->sentence(),
            'ai_response' => $this->faker->paragraph(3),
            'user_id' => User::factory(),
            'visit_number' => $this->faker->numberBetween(1, 10),
            'patient_key' => $this->faker->md5(),
            'chief_complaint' => $this->faker->sentence(),
            'symptom_duration' => $this->faker->randomElement(['1 day', '3 days', '1 week', '2 weeks']),
            'past_medical_history' => $this->faker->paragraph(),
            'medication_history' => $this->faker->paragraph(),
            'allergies' => $this->faker->words(3, true),
            'family_history' => $this->faker->paragraph(),
            'social_history' => $this->faker->paragraph(),
            'pain_scale' => $this->faker->numberBetween(0, 10),
            'visit_type' => $this->faker->randomElement(['Initial', 'Follow-up', 'Emergency']),
            'heart_rate' => $this->faker->numberBetween(60, 100),
            'respiratory_rate' => $this->faker->numberBetween(12, 20),
            'oxygen_saturation' => $this->faker->numberBetween(95, 100),
            'physician_notes' => $this->faker->paragraph(),
            'additional_notes' => $this->faker->paragraph(),
        ];
    }
}
