<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\DataWarehouse\KPICalculationService;
use App\Services\RealtimeStreamingService;
use App\Services\PusherConnectionPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsWorkflowEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected KPICalculationService $kpiService;
    protected RealtimeStreamingService $streamingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->kpiService = app(KPICalculationService::class);
        $this->streamingService = app(RealtimeStreamingService::class);
    }

    /** @test */
    public function complete_analytics_workflow_from_data_to_dashboard()
    {
        // Step 1: Simulate data warehouse population
        $this->populateTestDataWarehouse();

        // Step 2: Calculate KPIs
        $date = Carbon::create(2024, 11, 15);
        $kpiResults = $this->kpiService->calculateDailyKPIs($date, 1);

        // Step 3: Verify KPI calculations
        $this->assertIsArray($kpiResults);
        $this->assertArrayHasKey('total_revenue', $kpiResults);
        $this->assertArrayHasKey('patient_satisfaction_score', $kpiResults);
        $this->assertArrayHasKey('total_appointments', $kpiResults);

        // Step 4: Test real-time broadcasting
        $broadcastResult = $this->streamingService->broadcastKPIUpdate(
            'revenue',
            ['value' => $kpiResults['total_revenue'], 'change' => 12.5],
            1
        );
        $this->assertTrue($broadcastResult);

        // Step 5: Test dashboard API access
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/executive-dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'summary',
                    'charts',
                    'alerts'
                ]
            ]);

        // Step 6: Verify dashboard data includes calculated KPIs
        $dashboardData = $response->json()['data'];
        $this->assertArrayHasKey('summary', $dashboardData);
        $this->assertArrayHasKey('revenue', $dashboardData['summary']);
    }

    /** @test */
    public function user_journey_through_analytics_features()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        // Step 1: User logs in and accesses permissions
        $permissionsResponse = $this->actingAs($user)
            ->getJson('/api/analytics/permissions');

        $permissionsResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'has_analytics_access' => true
                ]
            ]);

        // Step 2: User accesses executive dashboard
        $dashboardResponse = $this->actingAs($user)
            ->getJson('/api/analytics/executive-dashboard');

        $dashboardResponse->assertStatus(200);

        // Step 3: User accesses revenue analytics
        $revenueResponse = $this->actingAs($user)
            ->getJson('/api/analytics/revenue-analytics');

        $revenueResponse->assertStatus(200);

        // Step 4: User exports dashboard data
        $exportResponse = $this->actingAs($user)
            ->postJson('/api/analytics/export', [
                'dashboard' => 'executive'
            ]);

        $exportResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'status' => 'processing'
                ]
            ]);
    }

    /** @test */
    public function real_time_updates_workflow()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Step 1: User subscribes to dashboard updates
        $subscribeResult = $this->streamingService->subscribeToDashboard(
            $user,
            'executive',
            ['revenue', 'patients']
        );

        $this->assertTrue($subscribeResult);

        // Step 2: Simulate KPI update and broadcast
        $broadcastResult = $this->streamingService->broadcastKPIUpdate(
            'revenue',
            ['value' => 150000, 'change' => 15.2],
            1
        );

        $this->assertTrue($broadcastResult);

        // Step 3: Send real-time notification
        $notificationResult = $this->streamingService->sendRealtimeNotification(
            $user,
            [
                'title' => 'Revenue Target Achieved',
                'message' => 'Monthly revenue target has been exceeded',
                'type' => 'success'
            ]
        );

        $this->assertTrue($notificationResult);

        // Step 4: Broadcast dashboard refresh
        $refreshResult = $this->streamingService->broadcastDashboardRefresh('executive', 1);
        $this->assertTrue($refreshResult);

        // Step 5: Check subscription stats
        $stats = $this->streamingService->getSubscriptionStats();
        $this->assertGreaterThan(0, $stats['total_active_subscriptions']);
    }

    /** @test */
    public function alert_system_end_to_end_workflow()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        // Step 1: Trigger critical alert
        $alertData = [
            'alert_level' => 'critical',
            'message' => 'System performance degraded',
            'metric' => 'response_time',
            'threshold' => 2000,
            'current_value' => 3500
        ];

        $alertResult = $this->streamingService->broadcastAlert($alertData, 1);
        $this->assertTrue($alertResult);

        // Step 2: Verify alert reaches appropriate users
        // (In real implementation, this would check notification delivery)

        // Step 3: Check system health
        $health = $this->streamingService->healthCheck();
        $this->assertEquals('realtime_streaming', $health['service']);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('active_subscriptions', $health);
    }

    /** @test */
    public function data_consistency_across_analytics_components()
    {
        // Step 1: Populate consistent test data
        $this->populateConsistentTestData();

        // Step 2: Calculate KPIs
        $date = Carbon::create(2024, 11, 15);
        $kpis = $this->kpiService->calculateDailyKPIs($date, 1);

        // Step 3: Access dashboard data
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/executive-dashboard');

        $response->assertStatus(200);
        $dashboardData = $response->json()['data'];

        // Step 4: Verify data consistency
        // Revenue should match between KPI calculation and dashboard
        $this->assertEquals($kpis['total_revenue'], $dashboardData['summary']['revenue']['value']);

        // Step 5: Access detailed revenue analytics
        $revenueResponse = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/revenue-analytics');

        $revenueResponse->assertStatus(200);
        $revenueData = $revenueResponse->json()['data'];

        // Step 6: Verify revenue consistency across endpoints
        $this->assertEquals(
            $dashboardData['summary']['revenue']['value'],
            $revenueData['kpis']['mrr']['value']
        );
    }

    /** @test */
    public function performance_under_load_simulation()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Step 1: Simulate multiple concurrent dashboard accesses
        $startTime = microtime(true);

        $promises = [];
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)
                ->getJson('/api/analytics/executive-dashboard');

            $response->assertStatus(200);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // milliseconds

        // Step 2: Verify performance requirements (< 2 seconds per request on average)
        $averageResponseTime = $totalTime / 10;
        $this->assertLessThan(2000, $averageResponseTime,
            "Average response time should be under 2 seconds, got {$averageResponseTime}ms");

        // Step 3: Test caching effectiveness
        $cacheKey = "daily_kpis_1_" . Carbon::yesterday()->format('Ymd');
        $this->assertTrue(Cache::has($cacheKey), 'KPI data should be cached');
    }

    /** @test */
    public function subscription_management_workflow()
    {
        $user = User::factory()->create();

        // Step 1: Subscribe to multiple dashboards
        $this->streamingService->subscribeToDashboard($user, 'executive', ['revenue']);
        $this->streamingService->subscribeToDashboard($user, 'revenue', ['arpu']);

        // Step 2: Verify subscriptions
        $stats = $this->streamingService->getSubscriptionStats();
        $this->assertEquals(2, $stats['total_active_subscriptions']);

        // Step 3: Update activity
        $this->streamingService->updateUserActivity($user, 'executive');

        // Step 4: Clean up inactive subscriptions
        $cleaned = $this->streamingService->cleanupInactiveSubscriptions(0); // Clean immediately
        $this->assertEquals(0, $cleaned); // Should not clean active subscriptions

        // Step 5: Unsubscribe from one dashboard
        $this->streamingService->unsubscribeFromDashboard($user, 'executive');

        $statsAfter = $this->streamingService->getSubscriptionStats();
        $this->assertEquals(1, $statsAfter['total_active_subscriptions']);
    }

    /** @test */
    public function error_handling_and_recovery_workflow()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Step 1: Test with invalid data
        $response = $this->actingAs($user)
            ->postJson('/api/analytics/export', [
                'dashboard' => 'invalid_dashboard_type'
            ]);

        $response->assertStatus(200); // Should handle gracefully

        // Step 2: Test unauthenticated access
        $unauthResponse = $this->getJson('/api/analytics/executive-dashboard');
        $unauthResponse->assertStatus(401);

        // Step 3: Test insufficient permissions
        $regularUser = User::factory()->create();
        $regularUser->assignRole('user');

        $permissionResponse = $this->actingAs($regularUser)
            ->getJson('/api/analytics/revenue-analytics');

        $permissionResponse->assertStatus(403);

        // Step 4: Test system recovery - access should still work for authorized users
        $recoveryResponse = $this->actingAs($user)
            ->getJson('/api/analytics/executive-dashboard');

        $recoveryResponse->assertStatus(200);
    }

    // Helper methods

    private function populateTestDataWarehouse()
    {
        // Create basic test data structure
        // In a real scenario, this would populate actual fact and dimension tables
        DB::table('fact_financial_transactions')->insert([
            ['date_key' => 20241115, 'hospital_key' => 1, 'transaction_type' => 'Payment', 'amount' => 1000, 'payment_method' => 'Insurance'],
            ['date_key' => 20241115, 'hospital_key' => 1, 'transaction_type' => 'Payment', 'amount' => 500, 'payment_method' => 'Credit Card'],
        ]);

        DB::table('fact_appointments')->insert([
            ['date_key' => 20241115, 'hospital_key' => 1, 'status' => 'Completed', 'patient_satisfaction_score' => 4.5],
            ['date_key' => 20241115, 'hospital_key' => 1, 'status' => 'Completed', 'patient_satisfaction_score' => 4.8],
        ]);
    }

    private function populateConsistentTestData()
    {
        // Populate data that will result in predictable KPI calculations
        DB::table('fact_financial_transactions')->insert([
            ['date_key' => 20241115, 'hospital_key' => 1, 'transaction_type' => 'Payment', 'amount' => 125430, 'payment_method' => 'Insurance'],
        ]);

        DB::table('fact_appointments')->insert([
            ['date_key' => 20241115, 'hospital_key' => 1, 'status' => 'Completed', 'patient_satisfaction_score' => 4.8],
        ]);
    }
}
