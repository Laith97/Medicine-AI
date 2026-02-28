<?php

namespace Database\Factories;

use App\Models\Waitlist;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Waitlist>
 */
class WaitlistFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Waitlist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => User::factory(),
            'doctor_id' => Doctor::factory(),
            'service_type' => $this->faker->randomElement(['consultation', 'follow_up', 'procedure', 'emergency']),
            'priority_level' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'preferred_time_slots' => $this->faker->randomElements(['morning', 'afternoon', 'evening'], 2),
            'preferred_days' => $this->faker->randomElements(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], 3),
            'max_wait_days' => $this->faker->numberBetween(7, 30),
            'notification_channels' => $this->faker->randomElements(['email', 'sms', 'push'], 2),
            'status' => $this->faker->randomElement(['active', 'paused', 'fulfilled']),
        ];
    }

    /**
     * Indicate that the waitlist is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the waitlist is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    /**
     * Indicate that the waitlist is fulfilled.
     */
    public function fulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'fulfilled',
        ]);
    }

    /**
     * Indicate that the waitlist is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority_level' => $this->faker->randomElement(['high', 'urgent']),
            'max_wait_days' => $this->faker->numberBetween(1, 7),
        ]);
    }

    /**
     * Indicate that the waitlist is low priority.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority_level' => 'low',
            'max_wait_days' => $this->faker->numberBetween(14, 30),
        ]);
    }
}
