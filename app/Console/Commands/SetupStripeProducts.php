<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

class SetupStripeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:setup-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Stripe products and prices for the subscription plans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up Stripe products and prices...');
        $this->newLine();

        // Initialize Stripe
        $stripeSecret = config('stripe.secret');
        if (!$stripeSecret || $stripeSecret === 'sk_test_your_secret_key_here') {
            $this->error('Stripe secret key is not configured. Please set STRIPE_SECRET in your .env file.');
            return 1;
        }

        Stripe::setApiKey($stripeSecret);

        $plans = config('stripe.plans');
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($plans as $planKey => $planConfig) {
            $this->info("Creating product for {$planConfig['name']}...");

            try {
                // Create product
                $product = Product::create([
                    'name' => $planConfig['name'],
                    'description' => 'MedCura AI ' . $planConfig['name'] . ' - ' . implode(', ', array_slice($planConfig['features'], 0, 3)),
                    'metadata' => [
                        'plan_key' => $planKey,
                        'token_limit' => $planConfig['token_limit'],
                    ],
                ]);

                $this->line("  ✅ Product created: {$product->id}");

                // Create monthly price
                $monthlyPrice = Price::create([
                    'product' => $product->id,
                    'unit_amount' => $planConfig['price_monthly'],
                    'currency' => 'usd',
                    'recurring' => [
                        'interval' => 'month',
                    ],
                    'metadata' => [
                        'plan_key' => $planKey,
                        'billing_cycle' => 'monthly',
                    ],
                ]);

                $this->line("  ✅ Monthly price created: {$monthlyPrice->id}");

                // Create yearly price
                $yearlyPrice = Price::create([
                    'product' => $product->id,
                    'unit_amount' => $planConfig['price_yearly'],
                    'currency' => 'usd',
                    'recurring' => [
                        'interval' => 'year',
                    ],
                    'metadata' => [
                        'plan_key' => $planKey,
                        'billing_cycle' => 'yearly',
                    ],
                ]);

                $this->line("  ✅ Yearly price created: {$yearlyPrice->id}");

                // Update .env file with actual price IDs
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
                $this->error("  ❌ Failed to create {$planConfig['name']}: " . $e->getMessage());
                return 1;
            }
        }

        // Save updated .env file
        file_put_contents($envPath, $envContent);

        $this->info('🎉 All Stripe products and prices have been created successfully!');
        $this->newLine();
        
        $this->info('Next steps:');
        $this->line('1. Run: php artisan config:clear');
        $this->line('2. Test the subscription checkout');
        $this->newLine();
        
        $this->warn('Note: These are test products. For production, create products in live mode.');

        return 0;
    }
}
