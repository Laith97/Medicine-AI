<?php

namespace Database\Factories;

use App\Models\HepProgramTemplate;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HepProgramTemplate>
 */
class HepProgramTemplateFactory extends Factory
{
    protected $model = HepProgramTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' Template',
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(HepProgramTemplate::getCategories()),
            'diagnosis_type' => fake()->optional()->randomElement(HepProgramTemplate::getDiagnosisTypes()),
            'duration_weeks' => fake()->numberBetween(2, 12),
            'frequency_per_week' => fake()->numberBetween(2, 5),
            'goals' => [fake()->sentence(), fake()->sentence()],
            'precautions' => [fake()->sentence()],
            'is_active' => true,
            'created_by' => Admin::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => ['is_active' => false]);
    }

    public function orthopedic(): static
    {
        return $this->state(fn(array $attributes) => ['category' => 'orthopedic']);
    }
}
