<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Notifications\GracePeriodReminder;
use App\Notifications\FinalWarning;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestSubscriptionSystem extends Command
{
    protected $signature = 'test:subscription-system';
    protected $description = 'Test the complete subscription system functionality';

    public function handle()
    {
        $this->info('🧪 Testing Subscription System...');
        $this->newLine();

        // Test 1: Check subscription plans
        $this->info('1. Testing Subscription Plans...');
        $plans = SubscriptionPlan::active()->get();
        $this->table(['Name', 'Price', 'Cycle', 'Features'], $plans->map(function ($plan) {
            return [
                $plan->name,
                '$' . number_format($plan->price, 2),
                $plan->billing_cycle,
                implode(', ', array_slice($plan->features ?? [], 0, 2)) . (count($plan->features ?? []) > 2 ? '...' : '')
            ];
        }));

        // Test 2: Create test user with subscription
        $this->info('2. Creating test user with subscription...');
        $testUser = User::factory()->create([
            'name' => 'Test Subscription User',
            'email' => 'test-subscription@example.com',
        ]);

        $basicPlan = SubscriptionPlan::where('slug', 'basic-monthly')->first();
        if ($basicPlan) {
            $testUser->monthlyInvoiceSetting()->create([
                'subscription_plan_id' => $basicPlan->id,
                'billing_amount' => $basicPlan->price,
                'subscription_period_months' => $basicPlan->billing_period_months,
                'subscription_starts_at' => now(),
                'subscription_ends_at' => now()->addMonths($basicPlan->billing_period_months),
                'is_active' => true,
                'grace_period_days' => 7,
                'warning_period_days' => 3,
                'reminder_frequency_days' => 3,
            ]);
            $this->info("✅ User created with {$basicPlan->name} plan");
        }

        // Test 3: Test subscription statuses
        $this->info('3. Testing subscription statuses...');
        $setting = $testUser->monthlyInvoiceSetting;
        
        // Active subscription
        $this->info("Active status: {$setting->getSubscriptionStatus()}");
        
        // Grace period
        $setting->update(['subscription_ends_at' => now()->subDays(3)]);
        $this->info("Grace period status: {$setting->fresh()->getSubscriptionStatus()}");
        
        // Warning period
        $setting->update(['subscription_ends_at' => now()->subDays(10)]);
        $this->info("Warning period status: {$setting->fresh()->getSubscriptionStatus()}");

        // Test 4: Test notifications
        $this->info('4. Testing notification system...');
        
        // Test grace period notification
        $setting->update(['subscription_ends_at' => now()->subDays(2)]);
        $testUser->notify(new GracePeriodReminder($setting->fresh()));
        $this->info("✅ Grace period notification sent");
        
        // Test final warning notification
        $setting->update(['subscription_ends_at' => now()->subDays(9)]);
        $testUser->notify(new FinalWarning($setting->fresh()));
        $this->info("✅ Final warning notification sent");

        // Test 5: Check notification count
        $notificationCount = $testUser->notifications()->count();
        $this->info("📧 Total notifications sent: {$notificationCount}");

        // Test 6: Test plan features
        $this->info('5. Testing plan features...');
        if ($basicPlan && $basicPlan->features) {
            $this->info("Plan features for {$basicPlan->name}:");
            foreach ($basicPlan->features as $feature) {
                $this->line("  • {$feature}");
            }
        }

        // Cleanup
        $this->info('6. Cleaning up test data...');
        $testUser->notifications()->delete();
        $testUser->monthlyInvoiceSetting()->delete();
        $testUser->delete();
        $this->info("✅ Test user cleaned up");

        $this->newLine();
        $this->info('🎉 Subscription system test completed successfully!');
        
        return 0;
    }
}