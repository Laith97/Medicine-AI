<?php

namespace Database\Factories;

use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WaitlistEntry>
 */
class WaitlistEntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WaitlistEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'waitlist_id' => Waitlist::factory(),
            'slot_date' => fake()->dateTimeBetween('+1 day', '+1 week')->format('Y-m-d'),
            'slot_time' => fake()->time('H:i:s'),
            'status' => fake()->randomElement(['pending', 'offered', 'accepted', 'declined', 'expired']),
        ];
    }

    /**
     * Indicate that the entry is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the entry is offered.
     */
    public function offered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offered',
            'offered_at' => now(),
            'response_deadline' => now()->addHours(24),
        ]);
    }
}