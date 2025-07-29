<?php

namespace Database\Factories;

use App\Models\MonthlyInvoiceSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonthlyInvoiceSettingFactory extends Factory
{
    protected $model = MonthlyInvoiceSetting::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'billing_amount' => fake()->randomFloat(2, 10, 500),
            'grace_period_days' => fake()->numberBetween(3, 30),
            'reminder_frequency_days' => fake()->numberBetween(1, 7),
            'is_restricted' => fake()->boolean(20), // 20% chance of being restricted
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'created_at' => fake()->dateTimeThisMonth(),
            'updated_at' => fake()->dateTimeThisMonth(),
        ];
    }
}
