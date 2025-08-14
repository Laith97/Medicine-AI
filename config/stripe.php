<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for Stripe payment processing.
    |
    */

    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'environment' => env('STRIPE_ENVIRONMENT', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define your subscription plans here. These should match your Stripe
    | product and price IDs.
    |
    */

    'plans' => [
        'basic' => [
            'name' => 'Basic Plan',
            'price_monthly' => 100, // $1.00 in cents (TEST MODE)
            'price_yearly' => 100, // $1.00 in cents (TEST MODE)
            'stripe_price_id_monthly' => env('STRIPE_BASIC_MONTHLY_PRICE_ID'),
            'stripe_price_id_yearly' => env('STRIPE_BASIC_YEARLY_PRICE_ID'),
            'features' => [
                '100 AI diagnoses per month',
                'Basic patient management',
                'Email support',
                'Standard security'
            ],
            'token_limit' => 50000, // Monthly token limit
        ],
        'pro' => [
            'name' => 'Professional Plan',
            'price_monthly' => 100, // $1.00 in cents (TEST MODE)
            'price_yearly' => 100, // $1.00 in cents (TEST MODE)
            'stripe_price_id_monthly' => env('STRIPE_PRO_MONTHLY_PRICE_ID'),
            'stripe_price_id_yearly' => env('STRIPE_PRO_YEARLY_PRICE_ID'),
            'features' => [
                '500 AI diagnoses per month',
                'Advanced patient management',
                'Priority email support',
                'Advanced analytics',
                'Export capabilities'
            ],
            'token_limit' => 250000, // Monthly token limit
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Token Pricing
    |--------------------------------------------------------------------------
    |
    | Define the cost per token for usage calculations
    |
    */

    'token_cost_per_1k' => 0.002, // $0.002 per 1,000 tokens
];