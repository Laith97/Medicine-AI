<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic Monthly',
                'slug' => 'basic-monthly',
                'description' => 'Perfect for individual practitioners who need AI assistance on a monthly basis.',
                'price' => 99.00,
                'billing_cycle' => 'monthly',
                'billing_period_months' => 1,
                'features' => [
                    'Unlimited AI consultations',
                    'Patient case management',
                    'Medical analysis reports',
                    'Email support',
                    'Basic dashboard analytics'
                ],
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic Yearly',
                'slug' => 'basic-yearly',
                'description' => 'Save 20% with our annual Basic plan - ideal for committed practitioners.',
                'price' => 950.00, // 20% discount from monthly (99 * 12 = 1188, 20% off = 950)
                'billing_cycle' => 'yearly',
                'billing_period_months' => 12,
                'features' => [
                    'Unlimited AI consultations',
                    'Patient case management',
                    'Medical analysis reports',
                    'Priority email support',
                    'Advanced dashboard analytics',
                    'Annual usage reports',
                    '20% savings vs monthly'
                ],
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Professional Monthly',
                'slug' => 'professional-monthly',
                'description' => 'Enhanced features for busy medical professionals and small clinics.',
                'price' => 199.00,
                'billing_cycle' => 'monthly',
                'billing_period_months' => 1,
                'features' => [
                    'Everything in Basic',
                    'Advanced AI models',
                    'Bulk patient processing',
                    'Custom report templates',
                    'Phone support',
                    'Integration capabilities',
                    'Team collaboration tools'
                ],
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Professional Yearly',
                'slug' => 'professional-yearly',
                'description' => 'Our most popular plan - Professional features with 25% annual savings.',
                'price' => 1790.00, // 25% discount from monthly (199 * 12 = 2388, 25% off = 1791)
                'billing_cycle' => 'yearly',
                'billing_period_months' => 12,
                'features' => [
                    'Everything in Basic',
                    'Advanced AI models',
                    'Bulk patient processing',
                    'Custom report templates',
                    'Priority phone support',
                    'Advanced integrations',
                    'Team collaboration tools',
                    'Quarterly business reviews',
                    '25% savings vs monthly'
                ],
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 4,
            ]
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}