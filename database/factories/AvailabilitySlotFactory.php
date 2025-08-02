<?php

namespace Database\Factories;

use App\Models\AvailabilitySlot;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilitySlotFactory extends Factory
{
    protected $model = AvailabilitySlot::class;

    public function definition(): array
    {
        $startTime = fake()->time('H:i:s');
        $endTime = fake()->time('H:i:s', strtotime($startTime) + 3600); // 1 hour later

        return [
            'doctor_id' => Doctor::factory(),
            'day_of_week' => fake()->randomElement(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'slot_duration' => fake()->randomElement([30, 45, 60]), // minutes
            'max_bookings_per_slot' => fake()->numberBetween(1, 5),
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'effective_from' => fake()->optional()->date(),
            'effective_until' => fake()->optional()->date(),
            'created_at' => fake()->dateTimeThisMonth(),
            'updated_at' => fake()->dateTimeThisMonth(),
        ];
    }
}
