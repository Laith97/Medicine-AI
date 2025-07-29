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
            'stripe_customer_id' => 'cus_test_123',
            'current_plan' => 'basic',
            'subscription_active' => true
        ]);

        $this->mockStripeService = Mockery::mock(StripeService::class);
        $this->app->instance(StripeService::class, $this->mockStripeService);

        $this->controller = new SubscriptionController();
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_shows_subscription_page()
    {
        $this->mockStripeService
            ->shouldReceive('getAvailablePlans')
            ->once()
            ->andReturn([
                'basic' => ['price' => 10.00, 'token_limit' => 1000],
                'premium' => ['price' => 25.00, 'token_limit' => 5000]
            ]);

        $request = Request::create('/subscription', 'GET');

        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContains('subscription', $response->getContent());
    }

    public function test_create_subscription()
    {
        $mockSubscription = Mockery::mock();
        $mockSubscription->id = 'sub_test_123';
        $mockSubscription->status = 'active';

        $this->mockStripeService
            ->shouldReceive('createSubscription')
            ->once()
            ->with($this->user, 'premium')
            ->andReturn($mockSubscription);

        $request = Request::create('/subscription', 'POST', [
            'plan' => 'premium'
        ]);

        $response = $this->controller->create($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
    }

    public function test_create_subscription_validates_plan()
    {
        $request = Request::create('/subscription', 'POST', [
            'plan' => 'invalid_plan'
        ]);

        $response = $this->controller->create($request);

        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
    }

    public function test_create_subscription_handles_existing_subscription()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $request = Request::create('/subscription', 'POST', [
            'plan' => 'premium'
        ]);

        $response = $this->controller->create($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContains('existing subscription', $responseData['error']);
    }

    public function test_update_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'stripe_subscription_id' => 'sub_test_123',
            'status' => 'active'
        ]);

        $mockUpdatedSubscription = Mockery::mock();
        $mockUpdatedSubscription->id = 'sub_test_123';
        $mockUpdatedSubscription->status = 'active';

        $this->mockStripeService
            ->shouldReceive('updateSubscription')
            ->once()
            ->with('sub_test_123', Mockery::type('array'))
            ->andReturn($mockUpdatedSubscription);

        $request = Request::create('/subscription/update', 'PUT', [
            'plan' => 'premium'
        ]);

        $response = $this->controller->update($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
    }

    public function test_cancel_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'stripe_subscription_id' => 'sub_test_123',
            'status' => 'active'
        ]);

        $mockCancelledSubscription = Mockery::mock();
        $mockCancelledSubscription->id = 'sub_test_123';
        $mockCancelledSubscription->status = 'canceled';

        $this->mockStripeService
            ->shouldReceive('cancelSubscription')
            ->once()
            ->with('sub_test_123')
            ->andReturn($mockCancelledSubscription);

        $request = Request::create('/subscription/cancel', 'DELETE', [
            'reason' => 'No longer needed'
        ]);

        $response = $this->controller->cancel($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
    }

    public function test_cancel_subscription_requires_reason()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $request = Request::create('/subscription/cancel', 'DELETE');

        $response = $this->controller->cancel($request);

        $this->assertEquals(422, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
    }

    public function test_reactivate_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'stripe_subscription_id' => 'sub_test_123',
            'status' => 'canceled'
        ]);

        $mockReactivatedSubscription = Mockery::mock();
        $mockReactivatedSubscription->id = 'sub_test_123';
        $mockReactivatedSubscription->status = 'active';

        $this->mockStripeService
            ->shouldReceive('reactivateSubscription')
            ->once()
            ->with('sub_test_123')
            ->andReturn($mockReactivatedSubscription);

        $request = Request::create('/subscription/reactivate', 'POST');

        $response = $this->controller->reactivate($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
    }

    public function test_get_subscription_status()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth()->timestamp
        ]);

        $request = Request::create('/api/subscription/status', 'GET');

        $response = $this->controller->getStatus($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('current_plan', $responseData);
        $this->assertArrayHasKey('next_billing_date', $responseData);
        $this->assertEquals('active', $responseData['status']);
    }

    public function test_get_billing_history()
    {
        // Create some billing history
        Subscription::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $request = Request::create('/api/subscription/billing-history', 'GET');

        $response = $this->controller->getBillingHistory($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('billing_history', $responseData);
        $this->assertIsArray($responseData['billing_history']);
    }

    public function test_create_checkout_session()
    {
        $mockSession = Mockery::mock();
        $mockSession->id = 'cs_test_123';
        $mockSession->url = 'https://checkout.stripe.com/pay/cs_test_123';

        $this->mockStripeService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->with($this->user, 'premium', Mockery::type('string'), Mockery::type('string'))
            ->andReturn($mockSession);

        $request = Request::create('/subscription/checkout', 'POST', [
            'plan' => 'premium'
        ]);

        $response = $this->controller->createCheckoutSession($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('checkout_url', $responseData);
        $this->assertEquals('https://checkout.stripe.com/pay/cs_test_123', $responseData['checkout_url']);
    }

    public function test_handle_checkout_success()
    {
        $request = Request::create('/subscription/success', 'GET', [
            'session_id' => 'cs_test_123'
        ]);

        $response = $this->controller->handleCheckoutSuccess($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContains('success', $response->getContent());
    }

    public function test_handle_checkout_cancel()
    {
        $request = Request::create('/subscription/cancel', 'GET');

        $response = $this->controller->handleCheckoutCancel($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContains('cancelled', $response->getContent());
    }

    public function test_preview_subscription_change()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $this->mockStripeService
            ->shouldReceive('previewSubscriptionChange')
            ->once()
            ->with($this->user, 'premium')
            ->andReturn([
                'immediate_total' => 1500, // $15.00 prorated
                'next_invoice_total' => 2500 // $25.00 full amount
            ]);

        $request = Request::create('/api/subscription/preview', 'POST', [
            'plan' => 'premium'
        ]);

        $response = $this->controller->previewChange($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('immediate_total', $responseData);
        $this->assertArrayHasKey('next_invoice_total', $responseData);
    }

    public function test_get_usage_and_limits()
    {
        $request = Request::create('/api/subscription/usage', 'GET');

        $response = $this->controller->getUsageAndLimits($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('current_usage', $responseData);
        $this->assertArrayHasKey('limits', $responseData);
        $this->assertArrayHasKey('usage_percentage', $responseData);
    }

    public function test_download_invoice()
    {
        $this->mockStripeService
            ->shouldReceive('getInvoice')
            ->once()
            ->with('in_test_123')
            ->andReturn((object)[
                'id' => 'in_test_123',
                'invoice_pdf' => 'https://files.stripe.com/invoice.pdf'
            ]);

        $request = Request::create('/subscription/invoice/in_test_123/download', 'GET');

        $response = $this->controller->downloadInvoice($request, 'in_test_123');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://files.stripe.com/invoice.pdf', $response->headers->get('Location'));
    }

    public function test_update_payment_method()
    {
        $this->mockStripeService
            ->shouldReceive('updatePaymentMethod')
            ->once()
            ->with($this->user, 'pm_test_123')
            ->andReturn(true);

        $request = Request::create('/subscription/payment-method', 'PUT', [
            'payment_method_id' => 'pm_test_123'
        ]);

        $response = $this->controller->updatePaymentMethod($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
    }

    public function test_subscription_controller_handles_stripe_errors()
    {
        $this->mockStripeService
            ->shouldReceive('createSubscription')
            ->once()
            ->with($this->user, 'premium')
            ->andThrow(new \Stripe\Exception\CardException('Card declined'));

        $request = Request::create('/subscription', 'POST', [
            'plan' => 'premium'
        ]);

        $response = $this->controller->create($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContains('Card declined', $responseData['error']);
    }

    public function test_subscription_requires_authentication()
    {
        $this->app['auth']->logout();

        $request = Request::create('/subscription', 'GET');

        $response = $this->controller->index($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContains('login', $response->headers->get('Location'));
    }
}
