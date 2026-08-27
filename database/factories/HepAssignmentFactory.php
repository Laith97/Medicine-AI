<?php

namespace Database\Factories;

use App\Models\HepAssignment;
use App\Models\User;
use App\Models\HepProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HepAssignment>
 */
class HepAssignmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = HepAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hep_program_id' => HepProgram::factory(),
            'patient_id' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'due_date' => now()->addWeeks(2),
            'completion_status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'patient_notes' => $this->faker->optional()->paragraph(),
            'clinician_feedback' => $this->faker->optional()->paragraph(),
        ];
    }
}
