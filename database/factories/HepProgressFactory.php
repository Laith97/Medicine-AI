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
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hep_assignment_id' => 1, // Will be overridden in tests
            'completion_percentage' => $this->faker->numberBetween(0, 100),
            'notes' => $this->faker->optional()->paragraph(),
            'completed_at' => $this->faker->optional(0.3)->dateTime(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
