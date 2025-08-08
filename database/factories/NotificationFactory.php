<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'title' => $this->faker->sentence(3),
            'message' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['info', 'success', 'warning', 'error']),
            'action_url' => $this->faker->optional()->url(),
            'is_read' => $this->faker->boolean(30), // 30% chance of being read
            'data' => $this->faker->optional()->randomElement([
                ['key' => 'value'],
                ['appointment_id' => $this->faker->numberBetween(1, 100)],
                ['payment_amount' => $this->faker->randomFloat(2, 10, 500)],
            ]),
        ];
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
    }

    /**
     * Indicate that the notification is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    /**
     * Create a notification of a specific type.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
