<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckAccessRestrictions;
use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CheckAccessRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CheckAccessRestrictions();
        $this->user = User::factory()->create();
    }

    public function test_middleware_allows_unrestricted_user()
    {
        // Create unrestricted user setting
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => false
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

    public function test_middleware_blocks_restricted_user()
    {
        // Create restricted user setting
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true,
            'restriction_reason' => 'Overdue payment'
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

    public function test_middleware_allows_access_to_allowed_routes_for_restricted_user()
    {
        // Create restricted user setting
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true,
            'allowed_routes' => ['billing', 'profile', 'logout']
        ]);

        $request = Request::create('/billing', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Billing Page', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Billing Page', $response->getContent());
    }

    public function test_middleware_blocks_restricted_routes_for_restricted_user()
    {
        // Create restricted user setting
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true,
            'allowed_routes' => ['billing', 'profile']
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Dashboard', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_allows_guest_users()
    {
        $request = Request::create('/login', 'GET');
        $request->setUserResolver(function () {
            return null; // Guest user
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Login Page', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Login Page', $response->getContent());
    }

    public function test_middleware_allows_user_without_monthly_invoice_setting()
    {
        // User without MonthlyInvoiceSetting should be allowed
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Dashboard', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Dashboard', $response->getContent());
    }

    public function test_middleware_handles_api_routes()
    {
        // Create restricted user setting
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true
        ]);

        $request = Request::create('/api/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('API Response', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());

        // For API routes, should return JSON response
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertArrayHasKey('message', $responseData);
    }

    public function test_middleware_allows_ajax_requests_to_allowed_routes()
    {
        // Create restricted user setting
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true,
            'allowed_routes' => ['billing']
        ]);

        $request = Request::create('/billing', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Billing Data', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_blocks_ajax_requests_to_restricted_routes()
    {
        // Create restricted user setting
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true,
            'allowed_routes' => ['billing']
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Dashboard Data', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());

        // Should return JSON response for AJAX
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function test_middleware_checks_route_patterns()
    {
        // Create restricted user setting
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true,
            'allowed_routes' => ['billing*', 'profile*']
        ]);

        // Test that billing sub-routes are allowed
        $request = Request::create('/billing/invoices', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Billing Invoices', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_handles_grace_period()
    {
        // Create user in grace period
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => false,
            'grace_period_days' => 7,
            'subscription_ends_at' => now()->subDays(2) // Expired but in grace period
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Dashboard with Grace Period Warning', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_restricts_after_grace_period()
    {
        // Create user past grace period
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true,
            'grace_period_days' => 7,
            'subscription_ends_at' => now()->subDays(10) // Past grace period
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Dashboard', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_logs_restriction_attempts()
    {
        // Create restricted user setting
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        // Mock the logger to verify logging
        $this->expectsEvents(\Illuminate\Log\Events\MessageLogged::class);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Dashboard', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_middleware_handles_different_restriction_levels()
    {
        // Create user with partial restrictions
        MonthlyInvoiceSetting::factory()->create([
            'user_id' => $this->user->id,
            'is_restricted' => true,
            'restriction_level' => 'partial',
            'allowed_routes' => ['dashboard', 'profile', 'billing']
        ]);

        // Test allowed route
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Dashboard', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());

        // Test restricted route
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Admin', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }
}
