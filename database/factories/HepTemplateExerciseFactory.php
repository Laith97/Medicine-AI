<?php

namespace Database\Factories;

use App\Models\HepTemplateExercise;
use App\Models\HepProgramTemplate;
use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HepTemplateExercise>
 */
class HepTemplateExerciseFactory extends Factory
{
    protected $model = HepTemplateExercise::class;

    public function definition(): array
    {
        return [
            'hep_program_template_id' => HepProgramTemplate::factory(),
            'exercise_id' => Exercise::factory(),
            'sets' => fake()->numberBetween(1, 4),
            'reps' => fake()->numberBetween(8, 15),
            'duration_seconds' => fake()->optional()->numberBetween(30, 120),
            'rest_seconds' => fake()->optional()->numberBetween(30, 90),
            'frequency' => fake()->randomElement(['Daily', '3x/week', '2x/day']),
            'progression_notes' => fake()->optional()->sentence(),
            'week_number' => fake()->numberBetween(1, 4),
            'order' => fake()->numberBetween(0, 5),
        ];
    }
}
