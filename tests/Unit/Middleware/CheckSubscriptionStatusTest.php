<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CheckSubscriptionStatusTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CheckSubscriptionStatus();
        $this->user = User::factory()->create([
            'subscription_active' => true,
            'subscription_ends_at' => now()->addMonth()
        ]);
    }

    public function test_middleware_allows_active_subscription()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth()->timestamp
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_middleware_blocks_expired_subscription()
    {
        $this->user->subscription_active = false;
        $this->user->subscription_ends_at = now()->subDays(5);
        $this->user->save();

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'canceled',
            'current_period_end' => now()->subDays(5)->timestamp
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_allows_trial_subscription()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'trialing',
            'trial_end' => now()->addWeek()->timestamp
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_blocks_expired_trial()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'trialing',
            'trial_end' => now()->subDays(2)->timestamp
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_allows_grace_period()
    {
        $this->user->subscription_active = false;
        $this->user->subscription_ends_at = now()->subDays(2); // Expired 2 days ago
        $this->user->save();

        // Configure grace period of 7 days
        config(['subscription.grace_period_days' => 7]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success with Grace Period', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_blocks_after_grace_period()
    {
        $this->user->subscription_active = false;
        $this->user->subscription_ends_at = now()->subDays(10); // Expired 10 days ago
        $this->user->save();

        // Configure grace period of 7 days
        config(['subscription.grace_period_days' => 7]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_allows_admin_users()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subscription_active' => false
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Admin Access', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_allows_guest_users_to_public_routes()
    {
        $request = Request::create('/login', 'GET');
        $request->setUserResolver(function () {
            return null; // Guest user
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Login Page', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_redirects_to_subscription_page()
    {
        $this->user->subscription_active = false;
        $this->user->subscription_ends_at = now()->subDays(10);
        $this->user->save();

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContains('subscription', $response->headers->get('Location'));
    }

    public function test_middleware_returns_json_for_api_requests()
    {
        $this->user->subscription_active = false;
        $this->user->subscription_ends_at = now()->subDays(10);
        $this->user->save();

        $request = Request::create('/api/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertArrayHasKey('subscription_required', $responseData);
    }

    public function test_middleware_handles_ajax_requests()
    {
        $this->user->subscription_active = false;
        $this->user->subscription_ends_at = now()->subDays(10);
        $this->user->save();

        $request = Request::create('/dashboard', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function test_middleware_allows_subscription_management_routes()
    {
        $this->user->subscription_active = false;
        $this->user->subscription_ends_at = now()->subDays(10);
        $this->user->save();

        $allowedRoutes = [
            '/subscription',
            '/subscription/create',
            '/subscription/cancel',
            '/billing',
            '/profile'
        ];

        foreach ($allowedRoutes as $route) {
            $request = Request::create($route, 'GET');
            $request->setUserResolver(function () {
                return $this->user;
            });

            $response = $this->middleware->handle($request, function ($req) {
                return new Response('Allowed', 200);
            });

            $this->assertEquals(200, $response->getStatusCode(), "Route {$route} should be allowed");
        }
    }

    public function test_middleware_checks_subscription_status_in_database()
    {
        // User has active subscription in user table but canceled in Stripe
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'canceled',
            'current_period_end' => now()->subDays(1)->timestamp
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_handles_incomplete_subscription()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'incomplete',
            'current_period_end' => now()->addMonth()->timestamp
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContains('subscription', $response->headers->get('Location'));
    }

    public function test_middleware_handles_past_due_subscription()
    {
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'past_due',
            'current_period_end' => now()->addMonth()->timestamp
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        // Past due subscriptions should be allowed with a warning
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_logs_subscription_access_attempts()
    {
        $this->user->subscription_active = false;
        $this->user->subscription_ends_at = now()->subDays(10);
        $this->user->save();

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Mock the logger to verify logging
        $this->expectsEvents(\Illuminate\Log\Events\MessageLogged::class);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_handles_multiple_subscriptions()
    {
        // User has multiple subscriptions, one active
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'canceled',
            'current_period_end' => now()->subDays(5)->timestamp
        ]);

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'current_period_end' => now()->addMonth()->timestamp
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
