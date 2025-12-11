<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Services\RealtimePerformanceMonitoringService;
use App\Services\LoadBalancerService;
use App\Services\PusherConnectionPool;

class RealtimePerformanceDashboardController extends Controller
{
    protected RealtimePerformanceMonitoringService $performanceService;
    protected LoadBalancerService $loadBalancer;
    protected PusherConnectionPool $connectionPool;

    public function __construct(
        RealtimePerformanceMonitoringService $performanceService,
        LoadBalancerService $loadBalancer,
        PusherConnectionPool $connectionPool
    ) {
        $this->performanceService = $performanceService;
        $this->loadBalancer = $loadBalancer;
        $this->connectionPool = $connectionPool;
    }

    /**
     * Display the performance dashboard
     */
    public function index(): View
    {
        $metrics = $this->performanceService->getMetrics();
        $healthStatus = $this->performanceService->getHealthStatus();
        $loadStats = $this->loadBalancer->getLoadStats();
        $poolStats = $this->connectionPool->getPoolStats();

        return view('admin.realtime-performance-dashboard', compact(
            'metrics',
            'healthStatus',
            'loadStats',
            'poolStats'
        ));
    }

    /**
     * Get real-time performance metrics via AJAX
     */
    public function getMetrics(Request $request): JsonResponse
    {
        $metrics = $this->performanceService->getMetrics();
        $healthStatus = $this->performanceService->getHealthStatus();

        return response()->json([
            'metrics' => $metrics,
            'health' => $healthStatus,
            'timestamp' => now()
        ]);
    }

    /**
     * Get performance analytics data
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $hours = $request->get('hours', 24);
        $analytics = $this->performanceService->getAnalytics($hours);

        return response()->json($analytics);
    }

    /**
     * Get load balancer statistics
     */
    public function getLoadStats(): JsonResponse
    {
        $loadStats = $this->loadBalancer->getLoadStats();

        return response()->json($loadStats);
    }

    /**
     * Get connection pool statistics
     */
    public function getConnectionStats(): JsonResponse
    {
        $poolStats = $this->connectionPool->getPoolStats();

        return response()->json($poolStats);
    }

    /**
     * Clear performance metrics (admin only)
     */
    public function clearMetrics(Request $request): JsonResponse
    {
        // Check if user is admin
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'hospital_admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->performanceService->clearMetrics();

        return response()->json([
            'message' => 'Performance metrics cleared successfully',
            'timestamp' => now()
        ]);
    }

    /**
     * Export performance data
     */
    public function exportData(Request $request): JsonResponse
    {
        $type = $request->get('type', 'metrics');
        $hours = $request->get('hours', 24);

        switch ($type) {
            case 'analytics':
                $data = $this->performanceService->getAnalytics($hours);
                break;
            case 'load_stats':
                $data = $this->loadBalancer->getLoadStats();
                break;
            case 'connection_stats':
                $data = $this->connectionPool->getPoolStats();
                break;
            default:
                $data = $this->performanceService->getMetrics();
        }

        return response()->json([
            'export' => $data,
            'exported_at' => now(),
            'type' => $type
        ]);
    }

    /**
     * Get performance alerts
     */
    public function getAlerts(): JsonResponse
    {
        $metrics = $this->performanceService->getMetrics();

        return response()->json([
            'alerts' => $metrics['alerts'] ?? [],
            'total_alerts' => count($metrics['alerts'] ?? []),
            'timestamp' => now()
        ]);
    }

    /**
     * Get system health overview
     */
    public function getHealthOverview(): JsonResponse
    {
        $healthStatus = $this->performanceService->getHealthStatus();
        $loadStats = $this->loadBalancer->getLoadStats();
        $poolStats = $this->connectionPool->getPoolStats();

        $overview = [
            'overall_status' => $this->calculateOverallStatus($healthStatus, $loadStats, $poolStats),
            'components' => [
                'performance_monitoring' => [
                    'status' => $healthStatus['status'],
                    'issues' => $healthStatus['issues']
                ],
                'load_balancer' => [
                    'status' => $this->calculateLoadBalancerStatus($loadStats),
                    'healthy_servers' => $loadStats['healthy_server_count'] ?? 0,
                    'total_servers' => $loadStats['total_servers'] ?? 0
                ],
                'connection_pool' => [
                    'status' => $this->calculateConnectionPoolStatus($poolStats),
                    'active_connections' => $poolStats['active_connections'] ?? 0,
                    'pool_utilization' => $poolStats['pool_utilization'] ?? 0
                ]
            ],
            'last_updated' => now()
        ];

        return response()->json($overview);
    }

    /**
     * Calculate overall system status
     */
    protected function calculateOverallStatus(array $healthStatus, array $loadStats, array $poolStats): string
    {
        $statuses = [
            $healthStatus['status'],
            $this->calculateLoadBalancerStatus($loadStats),
            $this->calculateConnectionPoolStatus($poolStats)
        ];

        if (in_array('critical', $statuses)) {
            return 'critical';
        }

        if (in_array('warning', $statuses)) {
            return 'warning';
        }

        if (in_array('degraded', $statuses)) {
            return 'degraded';
        }

        return 'healthy';
    }

    /**
     * Calculate load balancer status
     */
    protected function calculateLoadBalancerStatus(array $loadStats): string
    {
        $healthyServers = $loadStats['healthy_server_count'] ?? 0;
        $totalServers = $loadStats['total_servers'] ?? 1;

        $healthRatio = $healthyServers / $totalServers;

        if ($healthRatio < 0.5) {
            return 'critical';
        }

        if ($healthRatio < 0.8) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Calculate connection pool status
     */
    protected function calculateConnectionPoolStatus(array $poolStats): string
    {
        $utilization = $poolStats['pool_utilization'] ?? 0;

        if ($utilization > 0.95) {
            return 'critical';
        }

        if ($utilization > 0.8) {
            return 'warning';
        }

        return 'healthy';
    }
}
