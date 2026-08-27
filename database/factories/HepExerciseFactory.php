<?php

namespace Database\Factories;

use App\Models\HepExercise;
use App\Models\HepProgram;
use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HepExercise>
 */
class HepExerciseFactory extends Factory
{
    protected $model = HepExercise::class;

    public function definition(): array
    {
        return [
            'hep_program_id' => HepProgram::factory(),
            'exercise_id' => Exercise::factory(),
            'sets' => fake()->numberBetween(1, 4),
            'reps' => fake()->numberBetween(8, 15),
            'duration_seconds' => fake()->optional()->numberBetween(30, 120),
            'rest_seconds' => fake()->numberBetween(30, 90),
            'frequency' => fake()->randomElement(['Daily', '3x/week']),
            'progression_notes' => fake()->optional()->sentence(),
            'week_number' => fake()->numberBetween(1, 4),
            'order' => fake()->numberBetween(0, 5),
        ];
    }
}
