<?php

namespace Tests\Unit\Services;

use App\Services\StripeService;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class StripeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $stripeService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Stripe configuration
        config([
            'stripe.key' => 'sk_test_123',
            'stripe.public_key' => 'pk_test_123',
            'stripe.webhook_secret' => 'whsec_test_123',
            'stripe.plans' => [
                'basic' => [
                    'price_id' => 'price_basic_123',
                    'token_limit' => 1000,
                    'price' => 10.00
                ],
                'premium' => [
                    'price_id' => 'price_premium_123',
                    'token_limit' => 5000,
                    'price' => 25.00
                ]
            ]
        ]);

        $this->user = User::factory()->create([
            'stripe_customer_id' => 'cus_test_123'
        ]);

        $this->stripeService = new StripeService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_stripe_service_can_be_instantiated()
    {
        $this->assertInstanceOf(StripeService::class, $this->stripeService);
    }

    public function test_get_available_plans()
    {
        $plans = $this->stripeService->getAvailablePlans();

        $this->assertIsArray($plans);
        $this->assertArrayHasKey('basic', $plans);
        $this->assertArrayHasKey('premium', $plans);
        $this->assertEquals(1000, $plans['basic']['token_limit']);
        $this->assertEquals(5000, $plans['premium']['token_limit']);
    }

    public function test_get_plan_config()
    {
        $basicPlan = $this->stripeService->getPlanConfig('basic');
        $this->assertEquals('price_basic_123', $basicPlan['price_id']);
        $this->assertEquals(1000, $basicPlan['token_limit']);

        $invalidPlan = $this->stripeService->getPlanConfig('invalid');
        $this->assertEquals([], $invalidPlan);
    }

    public function test_format_amount_for_stripe()
    {
        $this->assertEquals(1000, $this->stripeService->formatAmountForStripe(10.00));
        $this->assertEquals(2550, $this->stripeService->formatAmountForStripe(25.50));
        $this->assertEquals(100, $this->stripeService->formatAmountForStripe(1));
    }

    public function test_format_amount_from_stripe()
    {
        $this->assertEquals(10.00, $this->stripeService->formatAmountFromStripe(1000));
        $this->assertEquals(25.50, $this->stripeService->formatAmountFromStripe(2550));
        $this->assertEquals(1.00, $this->stripeService->formatAmountFromStripe(100));
    }

    public function test_validate_webhook_signature()
    {
        $payload = json_encode(['test' => 'data']);
        $signature = 'test_signature';

        // Mock the Stripe webhook signature validation
        $mockStripe = Mockery::mock('alias:Stripe\Webhook');
        $mockStripe->shouldReceive('constructEvent')
                  ->with($payload, $signature, 'whsec_test_123')
                  ->once()
                  ->andReturn(['type' => 'test.event']);

        $result = $this->stripeService->validateWebhookSignature($payload, $signature);

        $this->assertEquals(['type' => 'test.event'], $result);
    }

    public function test_validate_webhook_signature_invalid()
    {
        $payload = json_encode(['test' => 'data']);
        $signature = 'invalid_signature';

        // Mock the Stripe webhook signature validation to throw exception
        $mockStripe = Mockery::mock('alias:Stripe\Webhook');
        $mockStripe->shouldReceive('constructEvent')
                  ->with($payload, $signature, 'whsec_test_123')
                  ->once()
                  ->andThrow(new \Stripe\Exception\SignatureVerificationException('Invalid signature'));

        $result = $this->stripeService->validateWebhookSignature($payload, $signature);

        $this->assertNull($result);
    }

    public function test_create_customer()
    {
        $mockCustomer = Mockery::mock();
        $mockCustomer->id = 'cus_new_123';
        $mockCustomer->email = $this->user->email;

        $mockStripe = Mockery::mock('alias:Stripe\Customer');
        $mockStripe->shouldReceive('create')
                  ->with([
                      'email' => $this->user->email,
                      'name' => $this->user->name,
                      'metadata' => ['user_id' => $this->user->id]
                  ])
                  ->once()
                  ->andReturn($mockCustomer);

        $customer = $this->stripeService->createCustomer($this->user);

        $this->assertEquals('cus_new_123', $customer->id);
        $this->assertEquals($this->user->email, $customer->email);
    }

    public function test_get_customer()
    {
        $mockCustomer = Mockery::mock();
        $mockCustomer->id = 'cus_test_123';

        $mockStripe = Mockery::mock('alias:Stripe\Customer');
        $mockStripe->shouldReceive('retrieve')
                  ->with('cus_test_123')
                  ->once()
                  ->andReturn($mockCustomer);

        $customer = $this->stripeService->getCustomer('cus_test_123');

        $this->assertEquals('cus_test_123', $customer->id);
    }

    public function test_create_subscription()
    {
        $mockSubscription = Mockery::mock();
        $mockSubscription->id = 'sub_test_123';
        $mockSubscription->status = 'active';

        $mockStripe = Mockery::mock('alias:Stripe\Subscription');
        $mockStripe->shouldReceive('create')
                  ->with([
                      'customer' => 'cus_test_123',
                      'items' => [['price' => 'price_basic_123']],
                      'metadata' => ['user_id' => $this->user->id, 'plan' => 'basic']
                  ])
                  ->once()
                  ->andReturn($mockSubscription);

        $subscription = $this->stripeService->createSubscription($this->user, 'basic');

        $this->assertEquals('sub_test_123', $subscription->id);
        $this->assertEquals('active', $subscription->status);
    }

    public function test_cancel_subscription()
    {
        $mockSubscription = Mockery::mock();
        $mockSubscription->shouldReceive('cancel')
                        ->once()
                        ->andReturnSelf();

        $mockStripe = Mockery::mock('alias:Stripe\Subscription');
        $mockStripe->shouldReceive('retrieve')
                  ->with('sub_test_123')
                  ->once()
                  ->andReturn($mockSubscription);

        $result = $this->stripeService->cancelSubscription('sub_test_123');

        $this->assertEquals($mockSubscription, $result);
    }

    public function test_create_payment_intent()
    {
        $mockPaymentIntent = Mockery::mock();
        $mockPaymentIntent->id = 'pi_test_123';
        $mockPaymentIntent->client_secret = 'pi_test_123_secret';

        $mockStripe = Mockery::mock('alias:Stripe\PaymentIntent');
        $mockStripe->shouldReceive('create')
                  ->with([
                      'amount' => 1000,
                      'currency' => 'usd',
                      'customer' => 'cus_test_123',
                      'metadata' => ['user_id' => $this->user->id]
                  ])
                  ->once()
                  ->andReturn($mockPaymentIntent);

        $paymentIntent = $this->stripeService->createPaymentIntent(10.00, $this->user);

        $this->assertEquals('pi_test_123', $paymentIntent->id);
        $this->assertEquals('pi_test_123_secret', $paymentIntent->client_secret);
    }

    public function test_create_checkout_session()
    {
        $mockSession = Mockery::mock();
        $mockSession->id = 'cs_test_123';
        $mockSession->url = 'https://checkout.stripe.com/pay/cs_test_123';

        $mockStripe = Mockery::mock('alias:Stripe\Checkout\Session');
        $mockStripe->shouldReceive('create')
                  ->once()
                  ->andReturn($mockSession);

        $session = $this->stripeService->createCheckoutSession($this->user, 'basic', 'https://example.com/success', 'https://example.com/cancel');

        $this->assertEquals('cs_test_123', $session->id);
        $this->assertEquals('https://checkout.stripe.com/pay/cs_test_123', $session->url);
    }

    public function test_get_subscription()
    {
        $mockSubscription = Mockery::mock();
        $mockSubscription->id = 'sub_test_123';

        $mockStripe = Mockery::mock('alias:Stripe\Subscription');
        $mockStripe->shouldReceive('retrieve')
                  ->with('sub_test_123')
                  ->once()
                  ->andReturn($mockSubscription);

        $subscription = $this->stripeService->getSubscription('sub_test_123');

        $this->assertEquals('sub_test_123', $subscription->id);
    }

    public function test_update_subscription()
    {
        $mockSubscription = Mockery::mock();
        $mockSubscription->shouldReceive('save')
                        ->once()
                        ->andReturnSelf();

        $mockStripe = Mockery::mock('alias:Stripe\Subscription');
        $mockStripe->shouldReceive('retrieve')
                  ->with('sub_test_123')
                  ->once()
                  ->andReturn($mockSubscription);

        $result = $this->stripeService->updateSubscription('sub_test_123', ['metadata' => ['updated' => 'true']]);

        $this->assertEquals($mockSubscription, $result);
    }

    public function test_get_customer_subscriptions()
    {
        $mockSubscriptions = Mockery::mock();
        $mockSubscriptions->data = [
            (object)['id' => 'sub_1', 'status' => 'active'],
            (object)['id' => 'sub_2', 'status' => 'canceled']
        ];

        $mockStripe = Mockery::mock('alias:Stripe\Subscription');
        $mockStripe->shouldReceive('all')
                  ->with(['customer' => 'cus_test_123'])
                  ->once()
                  ->andReturn($mockSubscriptions);

        $subscriptions = $this->stripeService->getCustomerSubscriptions('cus_test_123');

        $this->assertCount(2, $subscriptions->data);
        $this->assertEquals('sub_1', $subscriptions->data[0]->id);
    }

    public function test_handle_stripe_exception()
    {
        $mockStripe = Mockery::mock('alias:Stripe\Customer');
        $mockStripe->shouldReceive('retrieve')
                  ->with('invalid_customer')
                  ->once()
                  ->andThrow(new \Stripe\Exception\InvalidRequestException('No such customer'));

        $result = $this->stripeService->getCustomer('invalid_customer');

        $this->assertNull($result);
    }

    public function test_sync_user_subscription_data()
    {
        $mockSubscription = Mockery::mock();
        $mockSubscription->id = 'sub_test_123';
        $mockSubscription->status = 'active';
        $mockSubscription->current_period_end = time() + 2592000; // 30 days from now
        $mockSubscription->items = (object)[
            'data' => [
                (object)['price' => (object)['id' => 'price_basic_123']]
            ]
        ];

        $this->stripeService->syncUserSubscriptionData($this->user, $mockSubscription);

        $this->user->refresh();
        $this->assertEquals('basic', $this->user->current_plan);
        $this->assertTrue($this->user->subscription_active);
        $this->assertNotNull($this->user->subscription_ends_at);
    }
}
