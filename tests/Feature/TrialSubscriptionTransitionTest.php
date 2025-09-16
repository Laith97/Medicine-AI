<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SystemSetting;
use App\Models\MonthlyInvoiceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialSubscriptionTransitionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_handles_trial_to_subscription_transition_with_remaining_days()
    {
        // Set up a 14-day trial
        SystemSetting::set('trial_days', 14, 'integer', 'Default trial duration');
        
        // Create a user and start their trial
        $user = User::factory()->create();
        $user->startTrial();
        
        // Simulate user being 2 days into their trial (12 days remaining)
        $user->trial_ends_at = now()->addDays(12);
        $user->save();
        
        // Verify user is in trial period
        $this->assertTrue($user->isInTrialPeriod());
        $this->assertEquals(12, $user->getTrialDaysRemaining());
        
        // Create monthly invoice setting for the user
        $monthlySetting = $user->getOrCreateMonthlyInvoiceSetting();
        
        // Simulate subscription creation during trial period
        $subscriptionData = [
            'id' => 'sub_test123',
            'customer' => 'cus_test123',
            'status' => 'active',
            'current_period_start' => now()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
        ];
        
        // Mock Stripe subscription
        $mockStripeSubscription = new \stdClass();
        $mockStripeSubscription->id = 'sub_test123';
        $mockStripeSubscription->customer = 'cus_test123';
        $mockStripeSubscription->status = 'active';
        $mockStripeSubscription->items = new \stdClass();
        $mockStripeSubscription->items->data = [
            new \stdClass()
        ];
        $mockStripeSubscription->items->data[0]->price = new \stdClass();
        $mockStripeSubscription->items->data[0]->price->recurring = new \stdClass();
        $mockStripeSubscription->items->data[0]->price->recurring->interval = 'month';
        $mockStripeSubscription->items->data[0]->price->unit_amount = 3000; // $30.00
        
        // Mock the StripeSubscription::retrieve method
        \Stripe\Subscription::shouldReceive('retrieve')
            ->with('sub_test123', ['expand' => ['items.data.price']])
            ->andReturn($mockStripeSubscription);
        
        // Mock Customer::retrieve
        \Stripe\Customer::shouldReceive('retrieve')
            ->with('cus_test123')
            ->andReturn((object)['id' => 'cus_test123']);
        
        // Create the StripeService instance and handle subscription
        $stripeService = new \App\Services\StripeService();
        
        // This should handle the trial-to-subscription transition
        $stripeService->handleSubscriptionCreated($subscriptionData);
        
        // Verify the transition
        $user->refresh();
        $monthlySetting->refresh();
        
        // Trial should be cleared (subscription started)
        $this->assertNull($user->trial_ends_at);
        $this->assertTrue($user->trial_used);
        
        // Subscription should start when trial ends (12 days from now)
        $expectedStartDate = now()->addDays(12);
        $this->assertEquals($expectedStartDate->format('Y-m-d H:i:s'), $monthlySetting->subscription_starts_at->format('Y-m-d H:i:s'));
        
        // Subscription should end 1 month after trial ends
        $expectedEndDate = now()->addDays(12)->addMonth();
        $this->assertEquals($expectedEndDate->format('Y-m-d H:i:s'), $monthlySetting->subscription_ends_at->format('Y-m-d H:i:s'));
        
        // Verify subscription is active
        $this->assertTrue($monthlySetting->is_active);
    }

    /** @test */
    public function it_handles_subscription_creation_without_active_trial()
    {
        // Create a user without trial
        $user = User::factory()->create();
        $this->assertFalse($user->isInTrialPeriod());
        
        // Create monthly invoice setting for the user
        $monthlySetting = $user->getOrCreateMonthlyInvoiceSetting();
        
        // Simulate subscription creation
        $subscriptionData = [
            'id' => 'sub_test456',
            'customer' => 'cus_test456',
            'status' => 'active',
            'current_period_start' => now()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
        ];
        
        // Mock Stripe subscription
        $mockStripeSubscription = new \stdClass();
        $mockStripeSubscription->id = 'sub_test456';
        $mockStripeSubscription->customer = 'cus_test456';
        $mockStripeSubscription->status = 'active';
        $mockStripeSubscription->items = new \stdClass();
        $mockStripeSubscription->items->data = [
            new \stdClass()
        ];
        $mockStripeSubscription->items->data[0]->price = new \stdClass();
        $mockStripeSubscription->items->data[0]->price->recurring = new \stdClass();
        $mockStripeSubscription->items->data[0]->price->recurring->interval = 'month';
        $mockStripeSubscription->items->data[0]->price->unit_amount = 3000; // $30.00
        
        // Mock the StripeSubscription::retrieve method
        \Stripe\Subscription::shouldReceive('retrieve')
            ->with('sub_test456', ['expand' => ['items.data.price']])
            ->andReturn($mockStripeSubscription);
        
        // Mock Customer::retrieve
        \Stripe\Customer::shouldReceive('retrieve')
            ->with('cus_test456')
            ->andReturn((object)['id' => 'cus_test456']);
        
        // Create the StripeService instance and handle subscription
        $stripeService = new \App\Services\StripeService();
        
        // This should handle normal subscription creation (no trial)
        $stripeService->handleSubscriptionCreated($subscriptionData);
        
        // Verify the subscription
        $user->refresh();
        $monthlySetting->refresh();
        
        // User should still not have a trial
        $this->assertFalse($user->isInTrialPeriod());
        
        // Subscription should use Stripe's dates directly
        $this->assertEquals(now()->format('Y-m-d H:i:s'), $monthlySetting->subscription_starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals(now()->addMonth()->format('Y-m-d H:i:s'), $monthlySetting->subscription_ends_at->format('Y-m-d H:i:s'));
        
        // Verify subscription is active
        $this->assertTrue($monthlySetting->is_active);
    }
}