<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

class CreateTestPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:create-test-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create $1 test prices for all subscription plans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating $1 test prices for all plans...');
        $this->newLine();

        // Initialize Stripe
        $stripeSecret = config('stripe.secret');
        if (!$stripeSecret || $stripeSecret === 'sk_test_your_secret_key_here') {
            $this->error('Stripe secret key is not configured. Please set STRIPE_SECRET in your .env file.');
            return 1;
        }

        Stripe::setApiKey($stripeSecret);

        $plans = [
            'basic' => 'Basic Plan - Test',
            'pro' => 'Professional Plan - Test', 
            'enterprise' => 'Enterprise Plan - Test'
        ];

        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($plans as $planKey => $planName) {
            $this->info("Creating $1 test prices for {$planName}...");

            try {
                // Create or get existing product
                $product = Product::create([
                    'name' => $planName,
                    'description' => 'Test version of ' . $planName . ' - $1 for testing',
                    'metadata' => [
                        'plan_key' => $planKey,
                        'test_mode' => 'true',
                    ],
                ]);

                $this->line("  ✅ Product created: {$product->id}");

                // Create $1 monthly price
                $monthlyPrice = Price::create([
                    'product' => $product->id,
                    'unit_amount' => 100, // $1.00 in cents
                    'currency' => 'usd',
                    'recurring' => [
                        'interval' => 'month',
                    ],
                    'metadata' => [
                        'plan_key' => $planKey,
                        'billing_cycle' => 'monthly',
                        'test_mode' => 'true',
                    ],
                ]);

                $this->line("  ✅ Monthly $1 price created: {$monthlyPrice->id}");

                // Create $1 yearly price
                $yearlyPrice = Price::create([
                    'product' => $product->id,
                    'unit_amount' => 100, // $1.00 in cents
                    'currency' => 'usd',
                    'recurring' => [
                        'interval' => 'year',
                    ],
                    'metadata' => [
                        'plan_key' => $planKey,
                        'billing_cycle' => 'yearly',
                        'test_mode' => 'true',
                    ],
                ]);

                $this->line("  ✅ Yearly $1 price created: {$yearlyPrice->id}");

                // Update .env file with new price IDs
                $monthlyEnvKey = 'STRIPE_' . strtoupper($planKey) . '_MONTHLY_PRICE_ID';
                $yearlyEnvKey = 'STRIPE_' . strtoupper($planKey) . '_YEARLY_PRICE_ID';

                $envContent = preg_replace(
                    "/^{$monthlyEnvKey}=.*/m",
                    "{$monthlyEnvKey}={$monthlyPrice->id}",
                    $envContent
                );

                $envContent = preg_replace(
                    "/^{$yearlyEnvKey}=.*/m",
                    "{$yearlyEnvKey}={$yearlyPrice->id}",
                    $envContent
                );

                $this->line("  ✅ Environment variables updated");
                $this->newLine();

            } catch (\Exception $e) {
                $this->error("  ❌ Failed to create {$planName}: " . $e->getMessage());
                return 1;
            }
        }

        // Save updated .env file
        file_put_contents($envPath, $envContent);

        $this->info('🎉 All $1 test prices have been created successfully!');
        $this->newLine();
        
        $this->info('Updated price IDs in .env file:');
        $this->line('- All plans now cost $1.00 for testing');
        $this->line('- Both monthly and yearly subscriptions are $1.00');
        $this->newLine();
        
        $this->info('Next steps:');
        $this->line('1. Run: php artisan config:clear');
        $this->line('2. Test the subscription checkout');
        $this->newLine();
        
        $this->warn('Note: These are $1 test prices. Remember to update for production!');

        return 0;
    }
}