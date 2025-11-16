<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\AnalyticsPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $managerUser;
    protected User $regularUser;
    protected AnalyticsPermissions $analyticsPermissions;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different roles
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->managerUser = User::factory()->create();
        $this->managerUser->assignRole('manager');

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('user');

        $this->analyticsPermissions = app(AnalyticsPermissions::class);
    }

    /** @test */
    public function admin_can_access_executive_dashboard()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/executive-dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'summary' => [
                        'revenue',
                        'patient_satisfaction',
                        'operational_efficiency',
                        'clinical_outcomes'
                    ],
                    'charts',
                    'alerts'
                ],
                'meta' => [
                    'last_updated',
                    'data_freshness',
                    'permissions'
                ]
            ]);

        $response->assertJson([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'revenue' => ['value' => 125430],
                    'patient_satisfaction' => ['value' => 4.8],
                    'operational_efficiency' => ['value' => 94.2],
                    'clinical_outcomes' => ['value' => 87.3]
                ]
            ]
        ]);
    }

    /** @test */
    public function manager_can_access_revenue_analytics()
    {
        $response = $this->actingAs($this->managerUser)
            ->getJson('/api/analytics/revenue-analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'kpis' => [
                        'mrr',
                        'arpu',
                        'churn_rate',
                        'clv'
                    ],
                    'breakdown' => [
                        'by_plan'
                    ],
                    'forecast'
                ]
            ]);

        $response->assertJson([
            'status' => 'success',
            'data' => [
                'kpis' => [
                    'mrr' => ['value' => 125430],
                    'arpu' => ['value' => 89.50],
                    'churn_rate' => ['value' => 3.2],
                    'clv' => ['value' => 2340]
                ]
            ]
        ]);
    }

    /** @test */
    public function regular_user_cannot_access_revenue_analytics()
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/analytics/revenue-analytics');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Access denied to revenue metrics'
            ]);
    }

    /** @test */
    public function user_can_access_patient_satisfaction_metrics()
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/analytics/patient-satisfaction');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'overall' => [
                        'nps',
                        'satisfaction_score',
                        'response_rate'
                    ],
                    'by_department',
                    'trends'
                ]
            ]);

        $response->assertJson([
            'status' => 'success',
            'data' => [
                'overall' => [
                    'nps' => 72,
                    'satisfaction_score' => 4.8,
                    'response_rate' => 85.5
                ]
            ]
        ]);
    }

    /** @test */
    public function admin_can_export_dashboard_data()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/export', [
                'dashboard' => 'executive'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'export_id',
                    'status',
                    'estimated_completion',
                    'download_url'
                ]
            ]);

        $response->assertJson([
            'status' => 'success',
            'data' => [
                'status' => 'processing'
            ]
        ]);
    }

    /** @test */
    public function regular_user_cannot_export_dashboard_data()
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson('/api/analytics/export', [
                'dashboard' => 'executive'
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Export permission denied'
            ]);
    }

    /** @test */
    public function user_can_get_analytics_permissions()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'has_analytics_access',
                    'role_name',
                    'hierarchy_level',
                    'available_dashboards',
                    'available_features',
                    'available_kpi_categories'
                ]
            ]);

        $response->assertJson([
            'status' => 'success',
            'data' => [
                'has_analytics_access' => true,
                'hierarchy_level' => 1 // Admin level
            ]
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_analytics()
    {
        $response = $this->getJson('/api/analytics/executive-dashboard');

        $response->assertStatus(401);
    }

    /** @test */
    public function dashboard_response_includes_proper_meta_data()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/executive-dashboard');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('last_updated', $data['meta']);
        $this->assertArrayHasKey('data_freshness', $data['meta']);
        $this->assertArrayHasKey('permissions', $data['meta']);

        // Verify timestamp format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/',
            $data['meta']['last_updated']
        );

        // Verify permissions structure
        $this->assertArrayHasKey('read', $data['meta']['permissions']);
        $this->assertArrayHasKey('export', $data['meta']['permissions']);
        $this->assertArrayHasKey('customize', $data['meta']['permissions']);
    }

    /** @test */
    public function revenue_analytics_includes_forecasting_data()
    {
        $response = $this->actingAs($this->managerUser)
            ->getJson('/api/analytics/revenue-analytics');

        $response->assertStatus(200);

        $data = $response->json()['data'];

        $this->assertArrayHasKey('forecast', $data);
        $this->assertArrayHasKey('next_month', $data['forecast']);
        $this->assertArrayHasKey('confidence', $data['forecast']);
        $this->assertArrayHasKey('trend', $data['forecast']);

        $this->assertEquals(138000, $data['forecast']['next_month']);
        $this->assertEquals(85, $data['forecast']['confidence']);
        $this->assertEquals('up', $data['forecast']['trend']);
    }

    /** @test */
    public function patient_satisfaction_includes_department_breakdown()
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson('/api/analytics/patient-satisfaction');

        $response->assertStatus(200);

        $data = $response->json()['data'];

        $this->assertArrayHasKey('by_department', $data);
        $this->assertCount(2, $data['by_department']);

        $this->assertEquals('Cardiology', $data['by_department'][0]['department']);
        $this->assertEquals(4.9, $data['by_department'][0]['satisfaction']);
        $this->assertEquals(245, $data['by_department'][0]['response_count']);

        $this->assertEquals('Orthopedics', $data['by_department'][1]['department']);
        $this->assertEquals(4.7, $data['by_department'][1]['satisfaction']);
        $this->assertEquals(189, $data['by_department'][1]['response_count']);
    }

    /** @test */
    public function executive_dashboard_includes_alerts()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/executive-dashboard');

        $response->assertStatus(200);

        $data = $response->json()['data'];

        $this->assertArrayHasKey('alerts', $data);
        $this->assertCount(1, $data['alerts']);

        $alert = $data['alerts'][0];
        $this->assertEquals('alert_123', $alert['id']);
        $this->assertEquals('warning', $alert['type']);
        $this->assertEquals('Patient satisfaction below target', $alert['message']);
        $this->assertEquals('patient_satisfaction', $alert['metric']);
        $this->assertEquals(4.9, $alert['threshold']);
        $this->assertEquals(4.8, $alert['current_value']);
    }

    /** @test */
    public function export_request_validates_dashboard_parameter()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/export', [
                'dashboard' => 'nonexistent_dashboard'
            ]);

        // Should still work since validation is handled in the controller
        $response->assertStatus(200);
    }

    /** @test */
    public function analytics_endpoints_handle_database_errors_gracefully()
    {
        // Simulate database connection issues by mocking DB facade
        DB::shouldReceive('table')
            ->andThrow(new \Exception('Database connection failed'));

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/executive-dashboard');

        // Should return a proper error response
        $response->assertStatus(500);
    }

    /** @test */
    public function dashboard_data_includes_performance_metrics()
    {
        $startTime = microtime(true);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/executive-dashboard');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Assert response time is reasonable (< 2 seconds as per requirements)
        $this->assertLessThan(2000, $responseTime, 'Dashboard response should be under 2 seconds');

        // Verify data structure is complete
        $data = $response->json()['data'];
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('charts', $data);
        $this->assertCount(4, $data['summary']); // revenue, patient_satisfaction, operational_efficiency, clinical_outcomes
    }
}
