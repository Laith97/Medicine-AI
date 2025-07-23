<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestSubscriptionFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:subscription-flow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete subscription flow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing subscription flow...');
        $this->newLine();

        // Test 1: Check if users exist
        $users = User::where('is_admin', false)->get();
        $this->info("Found {$users->count()} non-admin users");
        
        if ($users->isEmpty()) {
            $this->error('No non-admin users found. Creating a test user...');
            $user = User::create([
                'name' => 'Test Subscription User',
                'email' => 'subscription.test@example.com',
                'password' => bcrypt('password'),
                'is_admin' => false,
            ]);
            $this->info("Created test user: {$user->name}");
        } else {
            $user = $users->first();
            $this->info("Using existing user: {$user->name}");
        }

        // Test 2: Check Stripe configuration
        $this->info('Checking Stripe configuration...');
        $stripeSecret = config('stripe.secret');
        $stripeKey = config('stripe.key');
        
        if (!$stripeSecret || $stripeSecret === 'sk_test_your_secret_key_here') {
            $this->error('❌ Stripe secret key not configured');
            return 1;
        }
        
        if (!$stripeKey || $stripeKey === 'pk_test_your_publishable_key_here') {
            $this->error('❌ Stripe publishable key not configured');
            return 1;
        }
        
        $this->info('✅ Stripe keys configured');

        // Test 3: Check price IDs
        $this->info('Checking price IDs...');
        $plans = config('stripe.plans');
        
        foreach ($plans as $planKey => $planConfig) {
            $monthlyPrice = $planConfig['stripe_price_id_monthly'];
            $yearlyPrice = $planConfig['stripe_price_id_yearly'];
            
            if (!$monthlyPrice || $monthlyPrice === '1' || strpos($monthlyPrice, 'demo') !== false) {
                $this->error("❌ {$planKey} monthly price ID not configured: {$monthlyPrice}");
                return 1;
            }
            
            if (!$yearlyPrice || $yearlyPrice === '1' || strpos($yearlyPrice, 'demo') !== false) {
                $this->error("❌ {$planKey} yearly price ID not configured: {$yearlyPrice}");
                return 1;
            }
            
            $this->info("✅ {$planKey}: monthly={$monthlyPrice}, yearly={$yearlyPrice}");
        }

        // Test 4: Test subscription creation
        $this->info('Testing subscription creation...');
        
        try {
            $stripeService = new \App\Services\StripeService();
            $session = $stripeService->createCheckoutSession($user, 'basic', 'monthly');
            $this->info("✅ Checkout session created successfully");
            $this->info("   Session ID: {$session->id}");
            $this->info("   URL: {$session->url}");
        } catch (\Exception $e) {
            $this->error("❌ Failed to create checkout session: " . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('🎉 All tests passed! Subscription flow is working correctly.');
        $this->newLine();
        
        $this->warn('If users are still getting errors, the issue is likely:');
        $this->line('1. Users are not logged in when clicking "Get Started"');
        $this->line('2. CSRF token issues in the browser');
        $this->line('3. JavaScript errors in the browser console');
        $this->newLine();
        
        $this->info('Next steps:');
        $this->line('1. Check browser console for JavaScript errors');
        $this->line('2. Ensure users are logged in before testing');
        $this->line('3. Check network tab for actual request/response');

        return 0;
    }
}
