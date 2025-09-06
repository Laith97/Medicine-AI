<?php

namespace Tests\Unit\Services;

use App\Services\SubscriptionLifecycleService;
use App\Models\User;
use App\Models\Subscription;
use App\Models\MonthlyInvoiceSetting;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class SubscriptionLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $subscriptionService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptionService = new SubscriptionLifecycleService();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subscription_active' => true,
            'subscription_ends_at' => now()->addMonth(),
            'monthly_cost_limit' => 100.00
        ]);

        Notification::fake();
    }

    public function test_check_trial_expiration_for_active_trial()
    {
        $trialUser = User::factory()->create([
            'trial_ends_at' => now()->addDays(3),
            'trial_used' => false,
            'subscription_active' => false
        ]);

        $result = $this->subscriptionService->checkTrialExpiration($trialUser);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('days_remaining', $result);
        $this->assertEquals('active', $result['status']);
        $this->assertEquals(3, $result['days_remaining']);
    }

    public function test_check_trial_expiration_for_expired_trial()
    {
        $expiredTrialUser = User::factory()->create([
            'trial_ends_at' => now()->subDays(1),
            'trial_used' => true,
            'subscription_active' => false
        ]);

        $result = $this->subscriptionService->checkTrialExpiration($expiredTrialUser);

        $this->assertEquals('expired', $result['status']);
        $this->assertEquals(-1, $result['days_remaining']);
    }

    public function test_check_subscription_status_active()
    {
        $result = $this->subscriptionService->checkSubscriptionStatus($this->user);

        $this->assertEquals('active', $result['status']);
        $this->assertTrue($result['is_active']);
        $this->assertGreaterThan(0, $result['days_remaining']);
    }

    public function test_check_subscription_status_expired()
    {
        $expiredUser = User::factory()->create([
            'subscription_active' => false,
            'subscription_ends_at' => now()->subDays(5)
        ]);

        $result = $this->subscriptionService->checkSubscriptionStatus($expiredUser);

        $this->assertEquals('expired', $result['status']);
        $this->assertFalse($result['is_active']);
        $this->assertEquals(-5, $result['days_remaining']);
    }

    public function test_check_subscription_status_expiring_soon()
    {
        $expiringSoonUser = User::factory()->create([
            'subscription_active' => true,
            'subscription_ends_at' => now()->addDays(2)
        ]);

        $result = $this->subscriptionService->checkSubscriptionStatus($expiringSoonUser);

        $this->assertEquals('expiring_soon', $result['status']);
        $this->assertTrue($result['is_active']);
        $this->assertEquals(2, $result['days_remaining']);
    }

    public function test_process_subscription_renewal()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'current_period_end' => now()->addDays(1)
        ]);

        $result = $this->subscriptionService->processSubscriptionRenewal($subscription);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('new_period_end', $result);
    }

    public function test_handle_subscription_cancellation()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $result = $this->subscriptionService->handleSubscriptionCancellation($subscription);

        $subscription->refresh();
        $this->assertEquals('canceled', $subscription->status);
        $this->assertTrue($result['success']);
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_calculate_usage_overage()
    {
        $usageData = [
            'current_usage' => 150.00,
            'plan_limit' => 100.00,
            'overage_rate' => 0.10
        ];

        $result = $this->subscriptionService->calculateUsageOverage($usageData);

        $this->assertEquals(50.00, $result['overage_amount']);
        $this->assertEquals(5.00, $result['overage_cost']); // 50 * 0.10
        $this->assertTrue($result['has_overage']);
    }

    public function test_calculate_usage_overage_no_overage()
    {
        $usageData = [
            'current_usage' => 75.00,
            'plan_limit' => 100.00,
            'overage_rate' => 0.10
        ];

        $result = $this->subscriptionService->calculateUsageOverage($usageData);

        $this->assertEquals(0.00, $result['overage_amount']);
        $this->assertEquals(0.00, $result['overage_cost']);
        $this->assertFalse($result['has_overage']);
    }

    public function test_apply_grace_period()
    {
        $expiredUser = User::factory()->create([
            'subscription_active' => false,
            'subscription_ends_at' => now()->subDays(2)
        ]);

        $result = $this->subscriptionService->applyGracePeriod($expiredUser, 7);

        $expiredUser->refresh();
        $this->assertTrue($result['grace_period_applied']);
        $this->assertEquals(5, $result['grace_days_remaining']); // 7 - 2 days already expired
    }

    public function test_restrict_user_access()
    {
        $setting = MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => false
        ]);

        $result = $this->subscriptionService->restrictUserAccess($this->user, 'Subscription expired');

        $setting->refresh();
        $this->assertTrue($setting->is_restricted);
        $this->assertEquals('Subscription expired', $setting->restriction_reason);
        $this->assertTrue($result['success']);
    }

    public function test_restore_user_access()
    {
        $setting = MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true,
            'restriction_reason' => 'Payment overdue'
        ]);

        $result = $this->subscriptionService->restoreUserAccess($this->user);

        $setting->refresh();
        $this->assertFalse($setting->is_restricted);
        $this->assertNull($setting->restriction_reason);
        $this->assertTrue($result['success']);
    }

    public function test_send_expiration_warning()
    {
        $result = $this->subscriptionService->sendExpirationWarning($this->user, 3);

        $this->assertTrue($result['notification_sent']);
        $this->assertEquals(3, $result['days_until_expiration']);

        // Verify notification was sent
        Notification::assertSentTo($this->user, \App\Notifications\GracePeriodReminder::class);
    }

    public function test_process_failed_payment()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $paymentData = [
            'payment_intent_id' => 'pi_test_123',
            'amount' => 2999,
            'currency' => 'usd',
            'failure_reason' => 'insufficient_funds'
        ];

        $result = $this->subscriptionService->processFailedPayment($subscription, $paymentData);

        $this->assertTrue($result['payment_failed']);
        $this->assertEquals('insufficient_funds', $result['failure_reason']);
        $this->assertArrayHasKey('retry_date', $result);
    }

    public function test_upgrade_subscription_plan()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'stripe_price_id' => 'price_basic'
        ]);

        $newPlanData = [
            'stripe_price_id' => 'price_premium',
            'plan_name' => 'Premium',
            'monthly_limit' => 500.00
        ];

        $result = $this->subscriptionService->upgradeSubscriptionPlan($subscription, $newPlanData);

        $subscription->refresh();
        $this->assertTrue($result['success']);
        $this->assertEquals('price_premium', $subscription->stripe_price_id);
        $this->assertArrayHasKey('proration_amount', $result);
    }

    public function test_downgrade_subscription_plan()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'stripe_price_id' => 'price_premium'
        ]);

        $newPlanData = [
            'stripe_price_id' => 'price_basic',
            'plan_name' => 'Basic',
            'monthly_limit' => 100.00
        ];

        $result = $this->subscriptionService->downgradeSubscriptionPlan($subscription, $newPlanData);

        $this->assertTrue($result['success']);
        $this->assertEquals('scheduled', $result['change_status']);
        $this->assertArrayHasKey('effective_date', $result);
    }

    public function test_calculate_proration_amount()
    {
        $prorationData = [
            'old_plan_amount' => 2999, // $29.99
            'new_plan_amount' => 4999, // $49.99
            'days_remaining' => 15,
            'days_in_period' => 30
        ];

        $result = $this->subscriptionService->calculateProrationAmount($prorationData);

        $expectedProration = (4999 - 2999) * (15 / 30); // $10.00
        $this->assertEquals(1000, $result['proration_amount']); // in cents
        $this->assertEquals(15, $result['days_remaining']);
        $this->assertTrue($result['is_upgrade']);
    }

    public function test_get_subscription_metrics()
    {
        // Create test subscriptions
        Subscription::factory()->count(3)->create(['status' => 'active']);
        Subscription::factory()->count(2)->create(['status' => 'canceled']);
        Subscription::factory()->count(1)->create(['status' => 'past_due']);

        $metrics = $this->subscriptionService->getSubscriptionMetrics();

        $this->assertArrayHasKey('total_subscriptions', $metrics);
        $this->assertArrayHasKey('active_subscriptions', $metrics);
        $this->assertArrayHasKey('canceled_subscriptions', $metrics);
        $this->assertArrayHasKey('past_due_subscriptions', $metrics);
        $this->assertEquals(6, $metrics['total_subscriptions']);
        $this->assertEquals(3, $metrics['active_subscriptions']);
        $this->assertEquals(2, $metrics['canceled_subscriptions']);
        $this->assertEquals(1, $metrics['past_due_subscriptions']);
    }

    public function test_process_dunning_management()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'past_due'
        ]);

        $result = $this->subscriptionService->processDunningManagement($subscription);

        $this->assertArrayHasKey('dunning_stage', $result);
        $this->assertArrayHasKey('next_retry_date', $result);
        $this->assertArrayHasKey('notifications_sent', $result);
        $this->assertTrue($result['dunning_active']);
    }
}
