<?php

namespace Database\Factories;

use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exercise>
 */
class ExerciseFactory extends Factory
{
    protected $model = Exercise::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' Exercise',
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(Exercise::getCategories()),
            'difficulty_level' => fake()->randomElement(Exercise::getDifficultyLevels()),
            'instructions' => fake()->paragraphs(3, true),
            'video_url' => fake()->optional()->url(),
            'image_url' => fake()->optional()->imageUrl(),
            'contraindications' => fake()->optional()->randomElements(['knee injury', 'shoulder impingement', 'back pain', 'hypertension'], fake()->numberBetween(0,2)),
            'equipment_required' => fake()->optional()->randomElements(['resistance band', 'dumbbells', 'chair', 'mat'], fake()->numberBetween(0,2)),
            'target_muscle_groups' => fake()->randomElements(['quadriceps', 'hamstrings', 'glutes', 'core', 'shoulders', 'chest'], fake()->numberBetween(1,3)),
            'duration' => fake()->optional()->numberBetween(30, 300),
        ];
    }

    public function beginner(): static
    {
        return $this->state(fn(array $attributes) => ['difficulty_level' => 'beginner']);
    }

    public function withMedia(): static
    {
        return $this->state(fn(array $attributes) => [
            'video_url' => fake()->url(),
            'image_url' => fake()->imageUrl(),
        ]);
    }

    public function withoutMedia(): static
    {
        return $this->state(fn(array $attributes) => [
            'video_url' => null,
            'image_url' => null,
        ]);
    }
}
