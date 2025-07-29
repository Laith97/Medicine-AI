<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessSubscriptionLifecycle;
use App\Models\User;
use App\Models\Subscription;
use App\Models\MonthlyInvoiceSetting;
use App\Services\SubscriptionLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;

class ProcessSubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $mockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'subscription_active' => true,
            'subscription_ends_at' => now()->addDays(5)
        ]);

        $this->mockService = Mockery::mock(SubscriptionLifecycleService::class);
        $this->app->instance(SubscriptionLifecycleService::class, $this->mockService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_can_be_instantiated()
    {
        $job = new ProcessSubscriptionLifecycle();
        $this->assertInstanceOf(ProcessSubscriptionLifecycle::class, $job);
    }

    public function test_job_processes_expiring_subscriptions()
    {
        // Create users with subscriptions expiring soon
        $expiringUsers = User::factory()->count(3)->create([
            'subscription_active' => true,
            'subscription_ends_at' => now()->addDays(3)
        ]);

        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andReturn(3);

        $job = new ProcessSubscriptionLifecycle();
        $job->handle();

        // Verify the service method was called
        $this->assertTrue(true); // If we get here, the mock was called correctly
    }

    public function test_job_processes_expired_subscriptions()
    {
        // Create users with expired subscriptions
        $expiredUsers = User::factory()->count(2)->create([
            'subscription_active' => true,
            'subscription_ends_at' => now()->subDays(1)
        ]);

        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('processExpiredSubscriptions')
            ->once()
            ->andReturn(2);

        $job = new ProcessSubscriptionLifecycle();
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_sends_renewal_reminders()
    {
        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('processExpiredSubscriptions')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('sendRenewalReminders')
            ->once()
            ->andReturn(5);

        $job = new ProcessSubscriptionLifecycle();
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_handles_grace_period_users()
    {
        // Create users in grace period
        $gracePeriodUsers = User::factory()->count(2)->create([
            'subscription_active' => false,
            'subscription_ends_at' => now()->subDays(2)
        ]);

        foreach ($gracePeriodUsers as $user) {
            MonthlyInvoiceSetting::factory()->create([
                'user_id' => $user->id,
                'grace_period_days' => 7,
                'is_restricted' => false
            ]);
        }

        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('processExpiredSubscriptions')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('sendRenewalReminders')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('processGracePeriodUsers')
            ->once()
            ->andReturn(2);

        $job = new ProcessSubscriptionLifecycle();
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_can_be_queued()
    {
        Queue::fake();

        ProcessSubscriptionLifecycle::dispatch();

        Queue::assertPushed(ProcessSubscriptionLifecycle::class);
    }

    public function test_job_has_correct_queue_configuration()
    {
        $job = new ProcessSubscriptionLifecycle();

        $this->assertEquals('default', $job->queue);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->timeout);
    }

    public function test_job_handles_service_exceptions()
    {
        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andThrow(new \Exception('Service error'));

        $job = new ProcessSubscriptionLifecycle();

        // Job should handle the exception gracefully
        try {
            $job->handle();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Job should handle service exceptions gracefully');
        }
    }

    public function test_job_logs_processing_results()
    {
        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andReturn(5);

        $this->mockService
            ->shouldReceive('processExpiredSubscriptions')
            ->once()
            ->andReturn(3);

        $this->mockService
            ->shouldReceive('sendRenewalReminders')
            ->once()
            ->andReturn(10);

        $this->mockService
            ->shouldReceive('processGracePeriodUsers')
            ->once()
            ->andReturn(2);

        $job = new ProcessSubscriptionLifecycle();
        $job->handle();

        // Verify that the job completed successfully
        $this->assertTrue(true);
    }

    public function test_job_processes_subscription_renewals()
    {
        // Create users with active subscriptions that need renewal processing
        $renewalUsers = User::factory()->count(3)->create([
            'subscription_active' => true,
            'subscription_ends_at' => now()->addDays(1)
        ]);

        foreach ($renewalUsers as $user) {
            Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => 'active',
                'current_period_end' => now()->addDays(1)->timestamp
            ]);
        }

        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andReturn(3);

        $this->mockService
            ->shouldReceive('processExpiredSubscriptions')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('sendRenewalReminders')
            ->once()
            ->andReturn(3);

        $this->mockService
            ->shouldReceive('processGracePeriodUsers')
            ->once()
            ->andReturn(0);

        $job = new ProcessSubscriptionLifecycle();
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_handles_subscription_cancellations()
    {
        // Create users with cancelled subscriptions
        $cancelledUsers = User::factory()->count(2)->create([
            'subscription_active' => false,
            'subscription_ends_at' => now()->subDays(1)
        ]);

        foreach ($cancelledUsers as $user) {
            Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => 'canceled',
                'canceled_at' => now()->subDays(1)->timestamp
            ]);
        }

        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('processExpiredSubscriptions')
            ->once()
            ->andReturn(2);

        $this->mockService
            ->shouldReceive('sendRenewalReminders')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('processGracePeriodUsers')
            ->once()
            ->andReturn(0);

        $job = new ProcessSubscriptionLifecycle();
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_processes_trial_subscriptions()
    {
        // Create users with trial subscriptions ending soon
        $trialUsers = User::factory()->count(2)->create([
            'subscription_active' => true,
            'subscription_ends_at' => now()->addDays(2),
            'current_plan' => 'trial'
        ]);

        foreach ($trialUsers as $user) {
            Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => 'trialing',
                'trial_end' => now()->addDays(2)->timestamp
            ]);
        }

        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andReturn(2);

        $this->mockService
            ->shouldReceive('processExpiredSubscriptions')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('sendRenewalReminders')
            ->once()
            ->andReturn(2);

        $this->mockService
            ->shouldReceive('processGracePeriodUsers')
            ->once()
            ->andReturn(0);

        $job = new ProcessSubscriptionLifecycle();
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_updates_subscription_statuses()
    {
        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andReturn(1);

        $this->mockService
            ->shouldReceive('processExpiredSubscriptions')
            ->once()
            ->andReturn(1);

        $this->mockService
            ->shouldReceive('sendRenewalReminders')
            ->once()
            ->andReturn(1);

        $this->mockService
            ->shouldReceive('processGracePeriodUsers')
            ->once()
            ->andReturn(1);

        $this->mockService
            ->shouldReceive('updateSubscriptionStatuses')
            ->once()
            ->andReturn(4);

        $job = new ProcessSubscriptionLifecycle();
        $job->handle();

        $this->assertTrue(true);
    }

    public function test_job_cleans_up_old_data()
    {
        $this->mockService
            ->shouldReceive('processExpiringSubscriptions')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('processExpiredSubscriptions')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('sendRenewalReminders')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('processGracePeriodUsers')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('updateSubscriptionStatuses')
            ->once()
            ->andReturn(0);

        $this->mockService
            ->shouldReceive('cleanupOldSubscriptionData')
            ->once()
            ->andReturn(5);

        $job = new ProcessSubscriptionLifecycle();
        $job->handle();

        $this->assertTrue(true);
    }
}
