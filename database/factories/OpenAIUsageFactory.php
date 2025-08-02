<?php

namespace Database\Factories;

use App\Models\OpenAIUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpenAIUsageFactory extends Factory
{
    protected $model = OpenAIUsage::class;

    public function definition(): array
    {
        $promptTokens = fake()->numberBetween(10, 1000);
        $completionTokens = fake()->numberBetween(10, 500);

        return [
            'user_id' => User::factory(),
            'request_type' => fake()->randomElement(['chat', 'completion', 'analysis']),
            'model_used' => fake()->randomElement(['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo']),
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
            'cost_estimate' => fake()->randomFloat(4, 0.01, 10.00),
            'request_metadata' => [
                'endpoint' => fake()->randomElement(['/chat/completions', '/completions']),
                'temperature' => fake()->randomFloat(1, 0, 1),
            ],
            'created_at' => fake()->dateTimeThisMonth(),
            'updated_at' => fake()->dateTimeThisMonth(),
        ];
    }
}
