<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\SubscriptionController;
use App\Models\User;
use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Mockery;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;
    protected $mockStripeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'stripe_customer_id' => 'cus_test_123'
        ]);

        $this->mockStripeService = Mockery::mock(StripeService::class);
        $this->app->instance(StripeService::class, $this->mockStripeService);

        // Use Laravel's container to resolve the controller with its dependencies
        $this->controller = $this->app->make(SubscriptionController::class);
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_pricing_returns_view()
    {
        $response = $this->controller->pricing();

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertStringContainsString('pricing', $response->getName());
    }

    public function test_checkout_validates_plan_type()
    {
        $request = Request::create('/subscription/checkout', 'POST', [
            'plan_type' => 'invalid_plan'
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->controller->checkout($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * @group skipped
     * @markTestSkipped('Stripe Session type mocking is complex - requires actual Stripe library integration')
     */
    public function test_checkout_creates_checkout_session()
    {
        $this->markTestSkipped('Stripe Session type mocking is complex - requires actual Stripe library integration');
    }

    public function test_success_redirects_after_checkout()
    {
        $request = Request::create('/subscription/success', 'GET', [
            'session_id' => 'cs_test_123'
        ]);

        $response = $this->controller->success($request);

        // Success redirects to dashboard or manage page
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function test_manage_returns_view()
    {
        $response = $this->controller->manage();

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    }

    public function test_cancel_redirects_when_no_active_subscription()
    {
        // User has no active subscription
        $request = Request::create('/subscription/cancel', 'POST');

        $response = $this->controller->cancel($request);

        // Should redirect back with error since no active subscription
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function test_customer_portal_redirects_without_stripe_customer_id()
    {
        // User without stripe_customer_id
        $userWithoutStripe = User::factory()->create([
            'stripe_customer_id' => null
        ]);
        $this->actingAs($userWithoutStripe);

        // Need to re-resolve controller with new user context
        $controller = $this->app->make(SubscriptionController::class);

        $request = Request::create('/subscription/portal', 'GET');

        $response = $controller->customerPortal($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }
}
