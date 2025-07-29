<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $planName = $this->faker->randomElement(['basic', 'premium', 'enterprise']);
        $status = $this->faker->randomElement(['active', 'canceled', 'past_due', 'trialing']);

        return [
            'user_id' => User::factory(),
            'stripe_subscription_id' => 'sub_' . $this->faker->randomNumber(8),
            'stripe_customer_id' => 'cus_' . $this->faker->randomNumber(8),
            'plan_name' => $planName,
            'billing_cycle' => $this->faker->randomElement(['monthly', 'yearly']),
            'status' => $status,
            'amount' => $this->getAmountForPlan($planName),
            'current_period_start' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'current_period_end' => $this->faker->dateTimeBetween('now', '+1 month'),
            'canceled_at' => $status === 'canceled' ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'trial_ends_at' => $status === 'trialing' ? $this->faker->dateTimeBetween('now', '+2 weeks') : null,
            'metadata' => [
                'created_via' => 'api',
                'source' => 'web',
            ],
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'canceled_at' => null,
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is canceled.
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'canceled',
            'canceled_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the subscription is in trial.
     */
    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trialing',
            'trial_ends_at' => $this->faker->dateTimeBetween('now', '+2 weeks'),
            'canceled_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is past due.
     */
    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'past_due',
            'canceled_at' => null,
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Set the subscription to basic plan.
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_name' => 'basic',
            'amount' => 29.99,
        ]);
    }

    /**
     * Set the subscription to premium plan.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_name' => 'premium',
            'amount' => 59.99,
        ]);
    }

    /**
     * Set the subscription to enterprise plan.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_name' => 'enterprise',
            'amount' => 99.99,
        ]);
    }

    /**
     * Get amount for a given plan.
     */
    private function getAmountForPlan(string $planName): float
    {
        return match($planName) {
            'basic' => 29.99,
            'premium' => 59.99,
            'enterprise' => 99.99,
            default => 29.99,
        };
    }
}
