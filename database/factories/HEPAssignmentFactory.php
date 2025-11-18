<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HEPAssignment>
 */
class HEPAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hep_program_id' => 1, // Will be overridden in tests
            'patient_id' => 1, // Will be overridden in tests
            'doctor_id' => 1, // Will be overridden in tests
            'assigned_date' => now(),
            'due_date' => now()->addWeeks(2),
            'notes' => $this->faker->optional()->paragraph(),
            'status' => 'assigned',
        ];
    }
}
