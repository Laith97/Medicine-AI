<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\OpenAIUsage;
use App\Services\StripeService;
use Illuminate\Console\Command;
use Stripe\Stripe;

class TestStripeIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Stripe integration and billing system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Stripe Integration...');
        $this->newLine();

        // Test 1: Configuration
        $this->testConfiguration();
        
        // Test 2: Database Tables
        $this->testDatabaseTables();
        
        // Test 3: Models and Relationships
        $this->testModelsAndRelationships();
        
        // Test 4: Stripe Service
        $this->testStripeService();
        
        // Test 5: Routes
        $this->testRoutes();

        $this->newLine();
        $this->info('✅ All tests completed!');
    }

    private function testConfiguration()
    {
        $this->info('1. Testing Configuration...');
        
        $stripeKey = config('stripe.key');
        $stripeSecret = config('stripe.secret');
        $plans = config('stripe.plans');
        
        if (empty($stripeKey)) {
            $this->warn('   ⚠️  STRIPE_KEY not configured');
        } else {
            $this->info('   ✅ STRIPE_KEY configured');
        }
        
        if (empty($stripeSecret)) {
            $this->warn('   ⚠️  STRIPE_SECRET not configured');
        } else {
            $this->info('   ✅ STRIPE_SECRET configured');
        }
        
        if (empty($plans)) {
            $this->error('   ❌ Stripe plans not configured');
        } else {
            $this->info('   ✅ Stripe plans configured (' . count($plans) . ' plans)');
            foreach ($plans as $key => $plan) {
                $this->info("      - {$key}: {$plan['name']}");
            }
        }
    }

    private function testDatabaseTables()
    {
        $this->info('2. Testing Database Tables...');
        
        try {
            // Test subscriptions table
            \DB::table('subscriptions')->count();
            $this->info('   ✅ subscriptions table exists');
        } catch (\Exception $e) {
            $this->error('   ❌ subscriptions table missing');
        }
        
        try {
            // Test openai_usages table
            \DB::table('openai_usages')->count();
            $this->info('   ✅ openai_usages table exists');
        } catch (\Exception $e) {
            $this->error('   ❌ openai_usages table missing');
        }
        
        try {
            // Test users table has subscription fields
            $user = \DB::table('users')->first();
            if ($user && property_exists($user, 'current_plan')) {
                $this->info('   ✅ users table has subscription fields');
            } else {
                $this->warn('   ⚠️  users table missing subscription fields');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error checking users table');
        }
    }

    private function testModelsAndRelationships()
    {
        $this->info('3. Testing Models and Relationships...');
        
        try {
            // Test User model methods
            $user = new User();
            if (method_exists($user, 'subscriptions')) {
                $this->info('   ✅ User->subscriptions() relationship exists');
            } else {
                $this->error('   ❌ User->subscriptions() relationship missing');
            }
            
            if (method_exists($user, 'openaiUsages')) {
                $this->info('   ✅ User->openaiUsages() relationship exists');
            } else {
                $this->error('   ❌ User->openaiUsages() relationship missing');
            }
            
            if (method_exists($user, 'getPlanConfig')) {
                $this->info('   ✅ User->getPlanConfig() method exists');
            } else {
                $this->error('   ❌ User->getPlanConfig() method missing');
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error testing User model: ' . $e->getMessage());
        }
    }

    private function testStripeService()
    {
        $this->info('4. Testing Stripe Service...');
        
        try {
            $stripeService = app(StripeService::class);
            $this->info('   ✅ StripeService can be instantiated');
            
            if (method_exists($stripeService, 'createOrGetCustomer')) {
                $this->info('   ✅ StripeService->createOrGetCustomer() method exists');
            } else {
                $this->error('   ❌ StripeService->createOrGetCustomer() method missing');
            }
            
            if (method_exists($stripeService, 'createCheckoutSession')) {
                $this->info('   ✅ StripeService->createCheckoutSession() method exists');
            } else {
                $this->error('   ❌ StripeService->createCheckoutSession() method missing');
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error testing StripeService: ' . $e->getMessage());
        }
    }

    private function testRoutes()
    {
        $this->info('5. Testing Routes...');
        
        $routes = [
            'subscription.pricing' => 'Pricing page',
            'subscription.checkout' => 'Checkout endpoint',
            'subscription.success' => 'Success page',
            'subscription.manage' => 'Subscription management',
            'subscription.cancel' => 'Cancel subscription',
            'stripe.webhook' => 'Stripe webhook',
            'admin.billing' => 'Admin billing dashboard',
            'admin.usage-analytics' => 'Admin usage analytics',
        ];
        
        foreach ($routes as $routeName => $description) {
            try {
                $url = route($routeName);
                $this->info("   ✅ {$description}: {$url}");
            } catch (\Exception $e) {
                $this->error("   ❌ {$description} route missing");
            }
        }
    }
}
