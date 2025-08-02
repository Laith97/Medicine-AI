<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SimpleSubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing plans (handle foreign key constraints)
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        SubscriptionPlan::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $plans = [
            [
                'name' => 'Monthly Plan',
                'slug' => 'monthly',
                'description' => 'Monthly subscription plan',
                'price' => 99.00, // Default price, admin can change
                'billing_cycle' => 'monthly',
                'billing_period_months' => 1,
                'features' => [
                    'Unlimited AI consultations',
                    'Patient case management',
                    'Medical analysis reports',
                    'Email support'
                ],
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Yearly Plan',
                'slug' => 'yearly',
                'description' => 'Yearly subscription plan with savings',
                'price' => 950.00, // Default price, admin can change
                'billing_cycle' => 'yearly',
                'billing_period_months' => 12,
                'features' => [
                    'Unlimited AI consultations',
                    'Patient case management',
                    'Medical analysis reports',
                    'Email support',
                    'Annual savings'
                ],
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::create($planData);
        }
    }
}