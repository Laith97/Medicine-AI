<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class LoadBalancerService
{
    protected const CACHE_KEY_LOAD_STATS = 'load_balancer:stats';
    protected const CACHE_KEY_SERVER_HEALTH = 'load_balancer:server_health';
    protected const HEALTH_CHECK_INTERVAL = 30; // seconds
    protected const MAX_LOAD_THRESHOLD = 0.8; // 80% capacity

    protected array $servers = [];
    protected int $currentIndex = 0;

    public function __construct()
    {
        $this->initializeServers();
    }

    /**
     * Initialize available broadcasting servers
     */
    protected function initializeServers(): void
    {
        // Get server configurations from environment or config
        $this->servers = config('broadcasting.load_balancer.servers', [
            [
                'id' => 'primary',
                'host' => config('broadcasting.connections.pusher.options.host', 'api.pusherapp.com'),
                'weight' => 100,
                'enabled' => true,
                'max_connections' => 1000,
            ]
        ]);

        // Add additional servers if configured
        $additionalServers = config('broadcasting.load_balancer.additional_servers', []);
        foreach ($additionalServers as $server) {
            $this->servers[] = $server;
        }
    }

    /**
     * Get the best server for broadcasting based on load balancing strategy
     */
    public function getOptimalServer(array $channels = [], string $event = ''): array
    {
        $healthyServers = $this->getHealthyServers();

        if (empty($healthyServers)) {
            Log::warning('No healthy servers available for load balancing, using primary');
            return $this->getPrimaryServer();
        }

        // Use round-robin with weighted load balancing
        return $this->selectServerByWeightedRoundRobin($healthyServers);
    }

    /**
     * Select server using weighted round-robin algorithm
     */
    protected function selectServerByWeightedRoundRobin(array $servers): array
    {
        $totalWeight = array_sum(array_column($servers, 'weight'));

        if ($totalWeight === 0) {
            return $servers[0] ?? $this->getPrimaryServer();
        }

        $selectedWeight = 0;
        $targetWeight = mt_rand(1, $totalWeight);

        foreach ($servers as $server) {
            $selectedWeight += $server['weight'];
            if ($targetWeight <= $selectedWeight) {
                return $server;
            }
        }

        // Fallback to first server
        return $servers[0];
    }

    /**
     * Get healthy servers based on health checks
     */
    protected function getHealthyServers(): array
    {
        $healthData = Cache::get(self::CACHE_KEY_SERVER_HEALTH, []);
        $healthyServers = [];

        foreach ($this->servers as $server) {
            $serverId = $server['id'];
            $isHealthy = $this->isServerHealthy($server, $healthData[$serverId] ?? null);

            if ($isHealthy && $server['enabled']) {
                $healthyServers[] = $server;
            }
        }

        return $healthyServers;
    }

    /**
     * Check if a server is healthy
     */
    protected function isServerHealthy(array $server, ?array $healthData): bool
    {
        if (!$healthData) {
            // Perform health check
            return $this->performHealthCheck($server);
        }

        $lastCheck = $healthData['last_check'] ?? 0;
        $isHealthy = $healthData['healthy'] ?? false;

        // If health check is stale, perform new check
        if (time() - $lastCheck > self::HEALTH_CHECK_INTERVAL) {
            return $this->performHealthCheck($server);
        }

        return $isHealthy;
    }

    /**
     * Perform health check on a server
     */
    protected function performHealthCheck(array $server): bool
    {
        try {
            // Basic connectivity check - in production, this would ping the server
            $isHealthy = $this->checkServerConnectivity($server);

            // Update health cache
            $healthData = Cache::get(self::CACHE_KEY_SERVER_HEALTH, []);
            $healthData[$server['id']] = [
                'healthy' => $isHealthy,
                'last_check' => time(),
                'response_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            ];
            Cache::put(self::CACHE_KEY_SERVER_HEALTH, $healthData, 300); // 5 minutes

            Log::debug('Health check completed', [
                'server_id' => $server['id'],
                'healthy' => $isHealthy
            ]);

            return $isHealthy;
        } catch (Exception $e) {
            Log::warning('Health check failed', [
                'server_id' => $server['id'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check server connectivity (simplified for this implementation)
     */
    protected function checkServerConnectivity(array $server): bool
    {
        // In a real implementation, this would make an actual HTTP request
        // For now, we'll assume all configured servers are healthy
        return true;
    }

    /**
     * Get primary server as fallback
     */
    protected function getPrimaryServer(): array
    {
        foreach ($this->servers as $server) {
            if ($server['id'] === 'primary') {
                return $server;
            }
        }

        return $this->servers[0] ?? [
            'id' => 'fallback',
            'host' => 'api.pusherapp.com',
            'weight' => 100,
            'enabled' => true,
        ];
    }

    /**
     * Update server load statistics
     */
    public function updateServerLoad(string $serverId, int $activeConnections): void
    {
        $stats = Cache::get(self::CACHE_KEY_LOAD_STATS, []);

        $stats[$serverId] = [
            'active_connections' => $activeConnections,
            'last_updated' => time(),
            'load_percentage' => $this->calculateLoadPercentage($serverId, $activeConnections),
        ];

        Cache::put(self::CACHE_KEY_LOAD_STATS, $stats, 300); // 5 minutes
    }

    /**
     * Calculate load percentage for a server
     */
    protected function calculateLoadPercentage(string $serverId, int $activeConnections): float
    {
        $server = $this->findServerById($serverId);
        $maxConnections = $server['max_connections'] ?? 1000;

        return min(1.0, $activeConnections / $maxConnections);
    }

    /**
     * Find server by ID
     */
    protected function findServerById(string $serverId): ?array
    {
        foreach ($this->servers as $server) {
            if ($server['id'] === $serverId) {
                return $server;
            }
        }
        return null;
    }

    /**
     * Get load balancing statistics
     */
    public function getLoadStats(): array
    {
        $stats = Cache::get(self::CACHE_KEY_LOAD_STATS, []);
        $health = Cache::get(self::CACHE_KEY_SERVER_HEALTH, []);

        return [
            'servers' => $this->servers,
            'load_stats' => $stats,
            'health_stats' => $health,
            'healthy_server_count' => count($this->getHealthyServers()),
            'total_servers' => count($this->servers),
            'last_updated' => now()
        ];
    }

    /**
     * Check if load balancing is needed
     */
    public function shouldLoadBalance(): bool
    {
        $healthyServers = $this->getHealthyServers();
        return count($healthyServers) > 1;
    }

    /**
     * Get server distribution for analytics
     */
    public function getServerDistribution(): array
    {
        $distribution = [];
        $totalWeight = array_sum(array_column($this->servers, 'weight'));

        foreach ($this->servers as $server) {
            $distribution[$server['id']] = [
                'weight' => $server['weight'],
                'percentage' => $totalWeight > 0 ? ($server['weight'] / $totalWeight) * 100 : 0,
                'enabled' => $server['enabled'],
            ];
        }

        return $distribution;
    }
}
