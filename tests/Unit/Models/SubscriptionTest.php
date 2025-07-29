<?php

namespace Tests\Unit\Models;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected $subscription;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'stripe_customer_id' => 'cus_test_123'
        ]);

        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'stripe_subscription_id' => 'sub_test_123',
            'status' => 'active',
            'current_period_start' => now()->startOfMonth()->timestamp,
            'current_period_end' => now()->endOfMonth()->timestamp,
            'plan_id' => 'price_premium_123',
            'plan_name' => 'Premium Plan',
            'plan_amount' => 2500, // $25.00
            'trial_end' => null,
            'canceled_at' => null
        ]);
    }

    public function test_subscription_can_be_created()
    {
        $this->assertInstanceOf(Subscription::class, $this->subscription);
        $this->assertEquals($this->user->id, $this->subscription->user_id);
        $this->assertEquals('sub_test_123', $this->subscription->stripe_subscription_id);
        $this->assertEquals('active', $this->subscription->status);
    }

    public function test_subscription_has_fillable_attributes()
    {
        $fillable = [
            'user_id', 'stripe_subscription_id', 'status', 'current_period_start',
            'current_period_end', 'plan_id', 'plan_name', 'plan_amount',
            'trial_end', 'trial_days', 'canceled_at', 'ends_at', 'metadata'
        ];

        $this->assertEquals($fillable, $this->subscription->getFillable());
    }

    public function test_subscription_casts_attributes_correctly()
    {
        $this->assertIsInt($this->subscription->current_period_start);
        $this->assertIsInt($this->subscription->current_period_end);
        $this->assertIsInt($this->subscription->plan_amount);

        if ($this->subscription->metadata) {
            $this->assertIsArray($this->subscription->metadata);
        }
    }

    public function test_subscription_user_relationship()
    {
        $this->assertInstanceOf(User::class, $this->subscription->user);
        $this->assertEquals($this->user->id, $this->subscription->user->id);
    }

    public function test_subscription_active_scope()
    {
        $activeSubscription = Subscription::factory()->create(['status' => 'active']);
        $canceledSubscription = Subscription::factory()->create(['status' => 'canceled']);

        $activeSubscriptions = Subscription::active()->get();

        $this->assertTrue($activeSubscriptions->contains($activeSubscription));
        $this->assertFalse($activeSubscriptions->contains($canceledSubscription));
    }

    public function test_subscription_canceled_scope()
    {
        $canceledSubscription = Subscription::factory()->create(['status' => 'canceled']);
        $activeSubscription = Subscription::factory()->create(['status' => 'active']);

        $canceledSubscriptions = Subscription::canceled()->get();

        $this->assertTrue($canceledSubscriptions->contains($canceledSubscription));
        $this->assertFalse($canceledSubscriptions->contains($activeSubscription));
    }

    public function test_subscription_trialing_scope()
    {
        $trialingSubscription = Subscription::factory()->create([
            'status' => 'trialing',
            'trial_end' => now()->addWeek()->timestamp
        ]);
        $activeSubscription = Subscription::factory()->create(['status' => 'active']);

        $trialingSubscriptions = Subscription::trialing()->get();

        $this->assertTrue($trialingSubscriptions->contains($trialingSubscription));
        $this->assertFalse($trialingSubscriptions->contains($activeSubscription));
    }

    public function test_subscription_past_due_scope()
    {
        $pastDueSubscription = Subscription::factory()->create(['status' => 'past_due']);
        $activeSubscription = Subscription::factory()->create(['status' => 'active']);

        $pastDueSubscriptions = Subscription::pastDue()->get();

        $this->assertTrue($pastDueSubscriptions->contains($pastDueSubscription));
        $this->assertFalse($pastDueSubscriptions->contains($activeSubscription));
    }

    public function test_subscription_expiring_soon_scope()
    {
        $expiringSoonSubscription = Subscription::factory()->create([
            'current_period_end' => now()->addDays(3)->timestamp
        ]);
        $notExpiringSoonSubscription = Subscription::factory()->create([
            'current_period_end' => now()->addMonth()->timestamp
        ]);

        $expiringSoonSubscriptions = Subscription::expiringSoon(7)->get(); // Within 7 days

        $this->assertTrue($expiringSoonSubscriptions->contains($expiringSoonSubscription));
        $this->assertFalse($expiringSoonSubscriptions->contains($notExpiringSoonSubscription));
    }

    public function test_subscription_get_plan_amount_dollars_attribute()
    {
        $this->assertEquals(25.0, $this->subscription->plan_amount_dollars);
    }

    public function test_subscription_get_current_period_start_date_attribute()
    {
        $expectedDate = Carbon::createFromTimestamp($this->subscription->current_period_start);
        $this->assertEquals($expectedDate->toDateString(), $this->subscription->current_period_start_date->toDateString());
    }

    public function test_subscription_get_current_period_end_date_attribute()
    {
        $expectedDate = Carbon::createFromTimestamp($this->subscription->current_period_end);
        $this->assertEquals($expectedDate->toDateString(), $this->subscription->current_period_end_date->toDateString());
    }

    public function test_subscription_get_trial_end_date_attribute()
    {
        $trialSubscription = Subscription::factory()->create([
            'trial_end' => now()->addWeek()->timestamp
        ]);

        $expectedDate = Carbon::createFromTimestamp($trialSubscription->trial_end);
        $this->assertEquals($expectedDate->toDateString(), $trialSubscription->trial_end_date->toDateString());
    }

    public function test_subscription_get_days_until_expiry_attribute()
    {
        $this->subscription->current_period_end = now()->addDays(10)->timestamp;
        $this->subscription->save();

        $this->assertEquals(10, $this->subscription->days_until_expiry);
    }

    public function test_subscription_get_status_color_attribute()
    {
        $this->subscription->status = 'active';
        $this->assertEquals('success', $this->subscription->status_color);

        $this->subscription->status = 'trialing';
        $this->assertEquals('info', $this->subscription->status_color);

        $this->subscription->status = 'past_due';
        $this->assertEquals('warning', $this->subscription->status_color);

        $this->subscription->status = 'canceled';
        $this->assertEquals('danger', $this->subscription->status_color);

        $this->subscription->status = 'incomplete';
        $this->assertEquals('secondary', $this->subscription->status_color);
    }

    public function test_subscription_is_active_method()
    {
        $this->assertTrue($this->subscription->isActive());

        $this->subscription->status = 'canceled';
        $this->assertFalse($this->subscription->isActive());
    }

    public function test_subscription_is_canceled_method()
    {
        $this->assertFalse($this->subscription->isCanceled());

        $this->subscription->status = 'canceled';
        $this->assertTrue($this->subscription->isCanceled());
    }

    public function test_subscription_is_trialing_method()
    {
        $this->assertFalse($this->subscription->isTrialing());

        $this->subscription->status = 'trialing';
        $this->subscription->trial_end = now()->addWeek()->timestamp;
        $this->assertTrue($this->subscription->isTrialing());
    }

    public function test_subscription_is_past_due_method()
    {
        $this->assertFalse($this->subscription->isPastDue());

        $this->subscription->status = 'past_due';
        $this->assertTrue($this->subscription->isPastDue());
    }

    public function test_subscription_is_incomplete_method()
    {
        $this->assertFalse($this->subscription->isIncomplete());

        $this->subscription->status = 'incomplete';
        $this->assertTrue($this->subscription->isIncomplete());
    }

    public function test_subscription_is_expired_method()
    {
        $this->subscription->current_period_end = now()->subDay()->timestamp;
        $this->subscription->status = 'canceled';
        $this->assertTrue($this->subscription->isExpired());

        $this->subscription->current_period_end = now()->addDay()->timestamp;
        $this->assertFalse($this->subscription->isExpired());
    }

    public function test_subscription_is_expiring_soon_method()
    {
        $this->subscription->current_period_end = now()->addDays(3)->timestamp;
        $this->assertTrue($this->subscription->isExpiringSoon(7)); // Within 7 days

        $this->subscription->current_period_end = now()->addDays(10)->timestamp;
        $this->assertFalse($this->subscription->isExpiringSoon(7));
    }

    public function test_subscription_has_trial_method()
    {
        $this->assertFalse($this->subscription->hasTrial());

        $this->subscription->trial_end = now()->addWeek()->timestamp;
        $this->assertTrue($this->subscription->hasTrial());
    }

    public function test_subscription_trial_expired_method()
    {
        $this->subscription->trial_end = now()->subDay()->timestamp;
        $this->assertTrue($this->subscription->trialExpired());

        $this->subscription->trial_end = now()->addDay()->timestamp;
        $this->assertFalse($this->subscription->trialExpired());

        $this->subscription->trial_end = null;
        $this->assertFalse($this->subscription->trialExpired());
    }

    public function test_subscription_days_left_in_trial_method()
    {
        $this->subscription->trial_end = now()->addDays(5)->timestamp;
        $this->assertEquals(5, $this->subscription->daysLeftInTrial());

        $this->subscription->trial_end = now()->subDay()->timestamp;
        $this->assertEquals(0, $this->subscription->daysLeftInTrial());

        $this->subscription->trial_end = null;
        $this->assertEquals(0, $this->subscription->daysLeftInTrial());
    }

    public function test_subscription_cancel_method()
    {
        $this->subscription->cancel();

        $this->assertEquals('canceled', $this->subscription->status);
        $this->assertNotNull($this->subscription->canceled_at);
    }

    public function test_subscription_cancel_at_period_end_method()
    {
        $this->subscription->cancelAtPeriodEnd();

        $this->assertEquals('active', $this->subscription->status); // Still active until period end
        $this->assertNotNull($this->subscription->canceled_at);
        $this->assertNotNull($this->subscription->ends_at);
    }

    public function test_subscription_resume_method()
    {
        $this->subscription->status = 'canceled';
        $this->subscription->canceled_at = now()->timestamp;
        $this->subscription->save();

        $this->subscription->resume();

        $this->assertEquals('active', $this->subscription->status);
        $this->assertNull($this->subscription->canceled_at);
        $this->assertNull($this->subscription->ends_at);
    }

    public function test_subscription_swap_plan_method()
    {
        $newPlanId = 'price_basic_123';
        $newPlanName = 'Basic Plan';
        $newPlanAmount = 1000; // $10.00

        $this->subscription->swapPlan($newPlanId, $newPlanName, $newPlanAmount);

        $this->assertEquals($newPlanId, $this->subscription->plan_id);
        $this->assertEquals($newPlanName, $this->subscription->plan_name);
        $this->assertEquals($newPlanAmount, $this->subscription->plan_amount);
    }

    public function test_subscription_extend_trial_method()
    {
        $this->subscription->trial_end = now()->addDays(5)->timestamp;
        $this->subscription->save();

        $this->subscription->extendTrial(10); // Extend by 10 days

        $expectedTrialEnd = now()->addDays(15)->timestamp;
        $this->assertEquals($expectedTrialEnd, $this->subscription->trial_end);
    }

    public function test_subscription_get_billing_cycle_method()
    {
        // Assuming monthly billing based on period dates
        $this->subscription->current_period_start = now()->startOfMonth()->timestamp;
        $this->subscription->current_period_end = now()->endOfMonth()->timestamp;

        $billingCycle = $this->subscription->getBillingCycle();
        $this->assertEquals('monthly', $billingCycle);
    }

    public function test_subscription_get_next_billing_date_method()
    {
        $nextBillingDate = $this->subscription->getNextBillingDate();
        $expectedDate = Carbon::createFromTimestamp($this->subscription->current_period_end);

        $this->assertEquals($expectedDate->toDateString(), $nextBillingDate->toDateString());
    }

    public function test_subscription_get_usage_period_method()
    {
        $usagePeriod = $this->subscription->getUsagePeriod();

        $this->assertIsArray($usagePeriod);
        $this->assertArrayHasKey('start', $usagePeriod);
        $this->assertArrayHasKey('end', $usagePeriod);
    }

    public function test_subscription_can_be_upgraded_method()
    {
        $this->assertTrue($this->subscription->canBeUpgraded());

        $this->subscription->status = 'canceled';
        $this->assertFalse($this->subscription->canBeUpgraded());

        $this->subscription->status = 'incomplete';
        $this->assertFalse($this->subscription->canBeUpgraded());
    }

    public function test_subscription_can_be_downgraded_method()
    {
        $this->assertTrue($this->subscription->canBeDowngraded());

        $this->subscription->status = 'canceled';
        $this->assertFalse($this->subscription->canBeDowngraded());
    }

    public function test_subscription_get_proration_amount_method()
    {
        $newPlanAmount = 5000; // $50.00
        $prorationAmount = $this->subscription->getProrationAmount($newPlanAmount);

        $this->assertIsFloat($prorationAmount);
        $this->assertGreaterThanOrEqual(0, $prorationAmount);
    }

    public function test_subscription_add_metadata_method()
    {
        $this->subscription->addMetadata('custom_field', 'custom_value');

        $this->assertArrayHasKey('custom_field', $this->subscription->metadata);
        $this->assertEquals('custom_value', $this->subscription->metadata['custom_field']);
    }

    public function test_subscription_remove_metadata_method()
    {
        $this->subscription->metadata = ['field1' => 'value1', 'field2' => 'value2'];
        $this->subscription->save();

        $this->subscription->removeMetadata('field1');

        $this->assertArrayNotHasKey('field1', $this->subscription->metadata);
        $this->assertArrayHasKey('field2', $this->subscription->metadata);
    }

    public function test_subscription_get_metadata_method()
    {
        $this->subscription->metadata = ['test_field' => 'test_value'];
        $this->subscription->save();

        $value = $this->subscription->getMetadata('test_field');
        $this->assertEquals('test_value', $value);

        $defaultValue = $this->subscription->getMetadata('non_existent_field', 'default');
        $this->assertEquals('default', $defaultValue);
    }
}
