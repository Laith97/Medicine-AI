<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StripeService;
use App\Models\User;

class TestStripeConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:test-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Stripe configuration and connectivity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Stripe Configuration...');
        $this->newLine();

        // Test basic configuration
        $this->testBasicConfig();
        
        // Test plan configuration
        $this->testPlanConfig();
        
        // Test Stripe connectivity (if keys are real)
        $this->testStripeConnectivity();

        $this->newLine();
        $this->info('Stripe configuration test completed!');
    }

    private function testBasicConfig()
    {
        $this->info('1. Testing basic configuration...');
        
        $stripeKey = config('stripe.key');
        $stripeSecret = config('stripe.secret');
        $webhookSecret = config('stripe.webhook_secret');
        
        if (!$stripeKey || $stripeKey === 'pk_test_your_publishable_key_here') {
            $this->error('   ❌ STRIPE_KEY is not configured');
        } else {
            $this->info('   ✅ STRIPE_KEY is set');
        }
        
        if (!$stripeSecret || $stripeSecret === 'sk_test_your_secret_key_here') {
            $this->error('   ❌ STRIPE_SECRET is not configured');
        } else {
            $this->info('   ✅ STRIPE_SECRET is set');
        }
        
        if (!$webhookSecret || $webhookSecret === 'whsec_your_webhook_secret_here') {
            $this->warn('   ⚠️  STRIPE_WEBHOOK_SECRET is not configured (optional)');
        } else {
            $this->info('   ✅ STRIPE_WEBHOOK_SECRET is set');
        }
    }

    private function testPlanConfig()
    {
        $this->info('2. Testing plan configuration...');
        
        $plans = config('stripe.plans', []);
        
        if (empty($plans)) {
            $this->error('   ❌ No plans configured');
            return;
        }
        
        foreach ($plans as $planName => $planConfig) {
            $this->info("   Testing {$planName} plan:");
            
            $monthlyPriceId = $planConfig['stripe_price_id_monthly'] ?? null;
            $yearlyPriceId = $planConfig['stripe_price_id_yearly'] ?? null;
            
            if (!$monthlyPriceId || strpos($monthlyPriceId, 'your_') !== false || $monthlyPriceId === "price_{$planName}_monthly_id") {
                $this->error("     ❌ Monthly price ID not configured");
            } else {
                $this->info("     ✅ Monthly price ID: {$monthlyPriceId}");
            }
            
            if (!$yearlyPriceId || strpos($yearlyPriceId, 'your_') !== false || $yearlyPriceId === "price_{$planName}_yearly_id") {
                $this->error("     ❌ Yearly price ID not configured");
            } else {
                $this->info("     ✅ Yearly price ID: {$yearlyPriceId}");
            }
        }
    }

    private function testStripeConnectivity()
    {
        $this->info('3. Testing Stripe connectivity...');
        
        $stripeSecret = config('stripe.secret');
        
        if (!$stripeSecret || $stripeSecret === 'sk_test_your_secret_key_here') {
            $this->warn('   ⚠️  Skipping connectivity test - no valid API key');
            return;
        }
        
        try {
            \Stripe\Stripe::setApiKey($stripeSecret);
            
            // Try to retrieve account information
            $account = \Stripe\Account::retrieve();
            
            $this->info('   ✅ Successfully connected to Stripe');
            $this->info("   📧 Account email: {$account->email}");
            $this->info("   🏢 Business name: " . ($account->business_profile->name ?? 'Not set'));
            $this->info("   🌍 Country: {$account->country}");
            
        } catch (\Exception $e) {
            $this->error('   ❌ Failed to connect to Stripe: ' . $e->getMessage());
        }
    }
}