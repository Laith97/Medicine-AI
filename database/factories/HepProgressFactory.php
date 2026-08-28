<?php

namespace Database\Factories;

use App\Models\HepProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HepProgress>
 */
class HepProgressFactory extends Factory
{
    protected $model = HepProgress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hep_assignment_id' => \App\Models\HepAssignment::factory(),
            'hep_exercise_id' => \App\Models\HepExercise::factory(),
            'date' => fake()->date(),
            'completed_sets' => fake()->numberBetween(1, 4),
            'completed_reps' => fake()->numberBetween(8, 15),
            'duration_completed' => fake()->numberBetween(30, 120),
            'pain_level' => fake()->numberBetween(0, 10),
            'difficulty_rating' => fake()->numberBetween(1, 10),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
