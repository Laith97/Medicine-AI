<?php

namespace Tests\Unit\Services;

use App\Services\RealtimeStreamingService;
use App\Services\PusherConnectionPool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class RealtimeStreamingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $streamingService;
    protected $pusherPoolMock;
    protected $user;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pusherPoolMock = Mockery::mock(PusherConnectionPool::class);
        $this->streamingService = new RealtimeStreamingService($this->pusherPoolMock);

        $this->user = User::factory()->create(['role' => 'doctor']);
        $this->adminUser = User::factory()->create(['role' => 'admin']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_streaming_service_can_be_instantiated()
    {
        $this->assertInstanceOf(RealtimeStreamingService::class, $this->streamingService);
    }

    public function test_broadcast_kpi_update_with_active_subscriptions()
    {
        $kpiData = ['value' => 85.5, 'trend' => 'up'];

        // Mock cache to return active subscriptions
        Cache::shouldReceive('get')
            ->with('active_dashboard_subscriptions')
            ->andReturn([
                [
                    'user_id' => $this->user->id,
                    'dashboard_id' => 'analytics_dashboard',
                    'hospital_key' => 1,
                    'kpis' => ['patient_satisfaction']
                ]
            ]);

        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->with(
                ["dashboard.{$this->user->id}.analytics_dashboard"],
                'kpi.updated',
                Mockery::on(function ($data) {
                    return $data['kpi_name'] === 'patient_satisfaction' &&
                           $data['hospital_key'] === 1;
                })
            )
            ->andReturn(true);

        $result = $this->streamingService->broadcastKPIUpdate('patient_satisfaction', $kpiData, 1);

        $this->assertTrue($result);
    }

    public function test_broadcast_kpi_update_no_subscriptions()
    {
        $kpiData = ['value' => 75.0];

        // Mock cache to return empty subscriptions
        Cache::shouldReceive('get')
            ->with('active_dashboard_subscriptions')
            ->andReturn([]);

        // Should not call broadcast when no subscriptions
        $this->pusherPoolMock->shouldNotReceive('broadcast');

        $result = $this->streamingService->broadcastKPIUpdate('appointment_volume', $kpiData, 1);

        $this->assertTrue($result);
    }

    public function test_broadcast_alert_critical_level()
    {
        $alertData = [
            'alert_level' => 'critical',
            'message' => 'Server down',
            'metric' => 'system_health'
        ];

        // Mock user query for alert recipients
        $adminUsers = User::factory()->count(2)->create(['role' => 'admin']);

        // Mock the user query
        $userMock = Mockery::mock('overload:App\Models\User');
        $userMock->shouldReceive('whereHas')
            ->andReturnSelf();
        $userMock->shouldReceive('get')
            ->andReturn($adminUsers);

        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->andReturn(true);

        $result = $this->streamingService->broadcastAlert($alertData, 1);

        $this->assertTrue($result);
    }

    public function test_broadcast_alert_warning_level()
    {
        $alertData = [
            'alert_level' => 'warning',
            'message' => 'High load detected',
            'metric' => 'cpu_usage'
        ];

        // Mock user query for alert recipients
        $users = collect([
            User::factory()->create(['role' => 'admin']),
            User::factory()->create(['role' => 'manager'])
        ]);

        $userMock = Mockery::mock('overload:App\Models\User');
        $userMock->shouldReceive('whereHas')
            ->andReturnSelf();
        $userMock->shouldReceive('get')
            ->andReturn($users);

        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->andReturn(true);

        $result = $this->streamingService->broadcastAlert($alertData, 1);

        $this->assertTrue($result);
    }

    public function test_subscribe_to_dashboard()
    {
        $dashboardId = 'patient_metrics';

        Cache::shouldReceive('put')
            ->once()
            ->with(
                "dashboard_sub_{$this->user->id}_{$dashboardId}",
                Mockery::on(function ($subscription) {
                    return $subscription['user_id'] === 1 &&
                           $subscription['dashboard_id'] === 'patient_metrics' &&
                           isset($subscription['subscribed_at']);
                }),
                3600
            );

        $result = $this->streamingService->subscribeToDashboard($this->user, $dashboardId, ['wait_times', 'satisfaction']);

        $this->assertTrue($result);
    }

    public function test_unsubscribe_from_dashboard()
    {
        $dashboardId = 'appointment_board';

        Cache::shouldReceive('forget')
            ->once()
            ->with("dashboard_sub_{$this->user->id}_{$dashboardId}");

        $result = $this->streamingService->unsubscribeFromDashboard($this->user, $dashboardId);

        $this->assertTrue($result);
    }

    public function test_broadcast_dashboard_refresh()
    {
        $dashboardId = 'realtime_analytics';

        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->with(
                ["dashboard.{$dashboardId}", "dashboard.{$dashboardId}.hospital.1"],
                'dashboard.refresh',
                Mockery::on(function ($data) {
                    return $data['dashboard_id'] === 'realtime_analytics' &&
                           $data['hospital_key'] === 1;
                })
            )
            ->andReturn(true);

        $result = $this->streamingService->broadcastDashboardRefresh($dashboardId, 1);

        $this->assertTrue($result);
    }

    public function test_send_realtime_notification()
    {
        $notificationData = [
            'title' => 'System Alert',
            'message' => 'Database maintenance scheduled',
            'type' => 'info'
        ];

        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->with(
                ["notifications.{$this->user->id}"],
                'notification.received',
                Mockery::on(function ($data) {
                    return $data['notification']['title'] === 'System Alert' &&
                           $data['user_id'] === $this->user->id;
                })
            )
            ->andReturn(true);

        $result = $this->streamingService->sendRealtimeNotification($this->user, $notificationData);

        $this->assertTrue($result);
    }

    public function test_update_user_activity()
    {
        $dashboardId = 'kpi_dashboard';
        $subscription = [
            'user_id' => $this->user->id,
            'dashboard_id' => $dashboardId,
            'last_activity' => now()->subMinutes(10)
        ];

        Cache::shouldReceive('get')
            ->with("dashboard_sub_{$this->user->id}_{$dashboardId}")
            ->andReturn($subscription);

        Cache::shouldReceive('put')
            ->once()
            ->with(
                "dashboard_sub_{$this->user->id}_{$dashboardId}",
                Mockery::on(function ($updatedSubscription) {
                    return $updatedSubscription['last_activity'] > now()->subMinutes(1);
                }),
                3600
            );

        $this->streamingService->updateUserActivity($this->user, $dashboardId);
    }

    public function test_cleanup_inactive_subscriptions()
    {
        $inactiveSubscription = [
            'user_id' => 1,
            'dashboard_id' => 'old_dashboard',
            'last_activity' => now()->subHours(30)
        ];

        $activeSubscription = [
            'user_id' => 2,
            'dashboard_id' => 'active_dashboard',
            'last_activity' => now()->subHours(1)
        ];

        Cache::shouldReceive('get')
            ->with('active_dashboard_subscriptions')
            ->andReturn([
                'sub1' => $inactiveSubscription,
                'sub2' => $activeSubscription
            ]);

        Cache::shouldReceive('forget')
            ->once()
            ->with('sub1');

        Cache::shouldReceive('put')
            ->once()
            ->with('active_dashboard_subscriptions', ['sub2' => $activeSubscription]);

        $cleaned = $this->streamingService->cleanupInactiveSubscriptions(24);

        $this->assertEquals(1, $cleaned);
    }

    public function test_get_subscription_stats()
    {
        $subscriptions = [
            'sub1' => ['dashboard_id' => 'dashboard1', 'user_id' => 1],
            'sub2' => ['dashboard_id' => 'dashboard1', 'user_id' => 2],
            'sub3' => ['dashboard_id' => 'dashboard2', 'user_id' => 1]
        ];

        Cache::shouldReceive('get')
            ->with('active_dashboard_subscriptions')
            ->andReturn($subscriptions);

        $stats = $this->streamingService->getSubscriptionStats();

        $this->assertArrayHasKey('total_active_subscriptions', $stats);
        $this->assertArrayHasKey('subscriptions_by_dashboard', $stats);
        $this->assertArrayHasKey('subscriptions_by_user', $stats);
        $this->assertEquals(3, $stats['total_active_subscriptions']);
        $this->assertEquals(2, $stats['subscriptions_by_dashboard']['dashboard1']);
        $this->assertEquals(2, $stats['subscriptions_by_user'][1]);
    }

    public function test_health_check()
    {
        $pusherHealth = [
            'status' => 'healthy',
            'connections' => 5,
            'latency' => 45
        ];

        $this->pusherPoolMock->shouldReceive('healthCheck')
            ->once()
            ->andReturn($pusherHealth);

        $health = $this->streamingService->healthCheck();

        $this->assertArrayHasKey('service', $health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('pusher_connection_pool', $health);
        $this->assertArrayHasKey('active_subscriptions', $health);
        $this->assertEquals('realtime_streaming', $health['service']);
        $this->assertEquals('healthy', $health['status']);
        $this->assertEquals($pusherHealth, $health['pusher_connection_pool']);
    }

    public function test_broadcast_kpi_update_filters_by_kpi_list()
    {
        $kpiData = ['value' => 92.3];

        // User subscribed only to specific KPIs
        Cache::shouldReceive('get')
            ->with('active_dashboard_subscriptions')
            ->andReturn([
                [
                    'user_id' => $this->user->id,
                    'dashboard_id' => 'filtered_dashboard',
                    'hospital_key' => 1,
                    'kpis' => ['wait_times', 'no_show_rate'] // Not including patient_satisfaction
                ]
            ]);

        // Should not broadcast since KPI is not in subscribed list
        $this->pusherPoolMock->shouldNotReceive('broadcast');

        $result = $this->streamingService->broadcastKPIUpdate('patient_satisfaction', $kpiData, 1);

        $this->assertTrue($result);
    }

    public function test_broadcast_kpi_update_with_empty_kpi_filter()
    {
        $kpiData = ['value' => 88.7];

        // User subscribed to all KPIs (empty kpis array)
        Cache::shouldReceive('get')
            ->with('active_dashboard_subscriptions')
            ->andReturn([
                [
                    'user_id' => $this->user->id,
                    'dashboard_id' => 'all_kpis_dashboard',
                    'hospital_key' => 1,
                    'kpis' => [] // Empty means all KPIs
                ]
            ]);

        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->andReturn(true);

        $result = $this->streamingService->broadcastKPIUpdate('any_kpi', $kpiData, 1);

        $this->assertTrue($result);
    }

    public function test_broadcast_alert_handles_no_recipients()
    {
        $alertData = [
            'alert_level' => 'info',
            'message' => 'Minor notification'
        ];

        // Mock empty recipients
        $userMock = Mockery::mock('overload:App\Models\User');
        $userMock->shouldReceive('whereHas')
            ->andReturnSelf();
        $userMock->shouldReceive('get')
            ->andReturn(collect());

        // Should not broadcast if no recipients
        $this->pusherPoolMock->shouldNotReceive('broadcast');

        $result = $this->streamingService->broadcastAlert($alertData, 1);

        $this->assertTrue($result);
    }

    public function test_get_roles_for_alert_level_critical()
    {
        $reflection = new \ReflectionClass($this->streamingService);
        $method = $reflection->getMethod('getRolesForAlertLevel');
        $method->setAccessible(true);

        $criticalRoles = $method->invoke($this->streamingService, 'critical');

        $this->assertEquals(['admin', 'hospital_admin', 'manager'], $criticalRoles);
    }

    public function test_get_roles_for_alert_level_warning()
    {
        $reflection = new \ReflectionClass($this->streamingService);
        $method = $reflection->getMethod('getRolesForAlertLevel');
        $method->setAccessible(true);

        $warningRoles = $method->invoke($this->streamingService, 'warning');

        $this->assertEquals(['admin', 'hospital_admin', 'manager', 'supervisor'], $warningRoles);
    }

    public function test_get_roles_for_alert_level_info()
    {
        $reflection = new \ReflectionClass($this->streamingService);
        $method = $reflection->getMethod('getRolesForAlertLevel');
        $method->setAccessible(true);

        $infoRoles = $method->invoke($this->streamingService, 'info');

        $this->assertEquals(['admin', 'hospital_admin'], $infoRoles);
    }

    public function test_broadcast_handles_pusher_failure()
    {
        $kpiData = ['value' => 95.0];

        Cache::shouldReceive('get')
            ->with('active_dashboard_subscriptions')
            ->andReturn([
                [
                    'user_id' => $this->user->id,
                    'dashboard_id' => 'test_dashboard',
                    'hospital_key' => 1,
                    'kpis' => []
                ]
            ]);

        $this->pusherPoolMock->shouldReceive('broadcast')
            ->once()
            ->andReturn(false);

        $result = $this->streamingService->broadcastKPIUpdate('test_kpi', $kpiData, 1);

        $this->assertFalse($result);
    }
}
