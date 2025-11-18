<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HEPProgram>
 */
class HEPProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'doctor_id' => 1, // Will be overridden in tests
            'patient_id' => 1, // Will be overridden in tests
            'diagnosis_id' => 1, // Will be overridden in tests
            'appointment_id' => 1, // Will be overridden in tests
            'duration_weeks' => $this->faker->numberBetween(4, 12),
            'frequency_per_week' => $this->faker->numberBetween(1, 7),
            'goals' => $this->faker->paragraph(),
            'precautions' => $this->faker->paragraph(),
        ];
    }
}
