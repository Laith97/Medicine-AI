<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Pusher\Pusher;
use Exception;
use App\Services\LoadBalancerService;

class PusherConnectionPool
{
    private const MAX_CONNECTIONS = 10;
    private const CONNECTION_TIMEOUT = 30; // seconds
    private const IDLE_TIMEOUT = 300; // 5 minutes

    private array $connections = [];
    private array $lastUsed = [];
    private array $connectionStats = [
        'created' => 0,
        'reused' => 0,
        'expired' => 0,
        'failed' => 0,
    ];
    private LoadBalancerService $loadBalancer;

    public function __construct(?LoadBalancerService $loadBalancer = null)
    {
        $this->loadBalancer = $loadBalancer ?? new LoadBalancerService();
    }

    /**
     * Get a Pusher connection from the pool
     */
    public function getConnection(): Pusher
    {
        // Try to reuse an existing connection
        $availableConnection = $this->getAvailableConnection();

        if ($availableConnection) {
            $this->connectionStats['reused']++;
            $this->lastUsed[spl_object_hash($availableConnection)] = time();
            Log::info('Reusing Pusher connection from pool', [
                'active_connections' => count($this->connections)
            ]);
            return $availableConnection;
        }

        // Create a new connection if under limit
        if (count($this->connections) < self::MAX_CONNECTIONS) {
            return $this->createNewConnection();
        }

        // Wait for an available connection or force cleanup
        $this->cleanupExpiredConnections();
        $availableConnection = $this->getAvailableConnection();

        if ($availableConnection) {
            $this->connectionStats['reused']++;
            $this->lastUsed[spl_object_hash($availableConnection)] = time();
            return $availableConnection;
        }

        // If still no connection available, create one anyway (emergency)
        Log::warning('Connection pool exhausted, creating emergency connection');
        return $this->createNewConnection();
    }

    /**
     * Create a new Pusher connection
     */
    private function createNewConnection(): Pusher
    {
        try {
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                config('broadcasting.connections.pusher.options', [])
            );

            $connectionHash = spl_object_hash($pusher);
            $this->connections[$connectionHash] = $pusher;
            $this->lastUsed[$connectionHash] = time();

            $this->connectionStats['created']++;

            Log::info('Created new Pusher connection', [
                'total_connections' => count($this->connections),
                'connection_hash' => $connectionHash
            ]);

            return $pusher;
        } catch (Exception $e) {
            $this->connectionStats['failed']++;
            Log::error('Failed to create Pusher connection', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get an available connection from the pool
     */
    private function getAvailableConnection(): ?Pusher
    {
        foreach ($this->connections as $hash => $connection) {
            $lastUsed = $this->lastUsed[$hash] ?? 0;

            // Check if connection is still valid
            if ($this->isConnectionValid($connection, $lastUsed)) {
                return $connection;
            } else {
                // Remove invalid connection
                unset($this->connections[$hash]);
                unset($this->lastUsed[$hash]);
                $this->connectionStats['expired']++;
            }
        }

        return null;
    }

    /**
     * Check if a connection is still valid
     */
    private function isConnectionValid(Pusher $connection, int $lastUsed): bool
    {
        // Check if connection has been idle too long
        if (time() - $lastUsed > self::IDLE_TIMEOUT) {
            return false;
        }

        // Basic connectivity check (ping)
        try {
            // We can't easily ping Pusher, so we'll rely on timeout
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clean up expired connections
     */
    private function cleanupExpiredConnections(): void
    {
        $now = time();
        $expired = [];

        foreach ($this->lastUsed as $hash => $lastUsed) {
            if ($now - $lastUsed > self::IDLE_TIMEOUT) {
                $expired[] = $hash;
            }
        }

        foreach ($expired as $hash) {
            unset($this->connections[$hash]);
            unset($this->lastUsed[$hash]);
            $this->connectionStats['expired']++;
        }

        if (!empty($expired)) {
            Log::info('Cleaned up expired Pusher connections', [
                'expired_count' => count($expired),
                'remaining_connections' => count($this->connections)
            ]);
        }
    }

    /**
     * Broadcast event using pooled connection with load balancing
     */
    public function broadcast(array $channels, string $event, array $data = []): bool
    {
        $driver = config('broadcasting.default');
        if ($driver !== 'pusher') {
            Log::warning('Broadcasting skipped because driver is ' . $driver, compact('channels', 'event'));
            return false;
        }

        if (empty($channels)) {
            Log::warning('Broadcast called with empty channels', ['event' => $event]);
            return false;
        }

        // Get optimal server for load balancing
        $server = $this->loadBalancer->getOptimalServer($channels, $event);
        $serverId = $server['id'] ?? 'primary';

        $pusher = $this->getConnection();

        try {
            // Handle compressed payloads
            $payload = $data;
            $isCompressed = isset($data['compressed']) && $data['compressed'];

            if ($isCompressed) {
                // For compressed payloads, send the compressed data directly
                $payload = $data['data'];
                $originalSize = $data['original_size'] ?? 0;
                $compressedSize = $data['compressed_size'] ?? 0;
            } else {
                $originalSize = strlen(json_encode($data));
                $compressedSize = $originalSize;
            }

            $result = $pusher->trigger($channels, $event, $payload);

            if ($result === true) {
                // Update load balancer with successful broadcast
                $this->loadBalancer->updateServerLoad($serverId, count($this->connections));

                Log::info('Successfully broadcast event via pooled connection', [
                    'channels' => $channels,
                    'event' => $event,
                    'server_id' => $serverId,
                    'original_size' => $originalSize,
                    'compressed_size' => $compressedSize,
                    'compression_ratio' => $originalSize > 0 ? round($compressedSize / $originalSize, 3) : 1.0,
                    'compressed' => $isCompressed,
                    'active_connections' => count($this->connections)
                ]);
                return true;
            } else {
                Log::warning('Failed to broadcast event via pooled connection', [
                    'channels' => $channels,
                    'event' => $event,
                    'server_id' => $serverId,
                    'result' => $result
                ]);
                return false;
            }
        } catch (Exception $e) {
            Log::error('Exception during broadcast via pooled connection', [
                'channels' => $channels,
                'event' => $event,
                'server_id' => $serverId,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e)
            ]);
            return false;
        }
    }

    /**
     * Get connection pool statistics
     */
    public function getPoolStats(): array
    {
        return [
            'active_connections' => count($this->connections),
            'max_connections' => self::MAX_CONNECTIONS,
            'idle_timeout' => self::IDLE_TIMEOUT,
            'connection_timeout' => self::CONNECTION_TIMEOUT,
            'stats' => $this->connectionStats,
            'connections' => array_map(function ($connection, $hash) {
                return [
                    'hash' => $hash,
                    'last_used' => $this->lastUsed[$hash] ?? null,
                    'age_seconds' => time() - ($this->lastUsed[$hash] ?? time()),
                ];
            }, $this->connections, array_keys($this->connections)),
        ];
    }

    /**
     * Force cleanup of all connections
     */
    public function forceCleanup(): void
    {
        $connectionCount = count($this->connections);

        $this->connections = [];
        $this->lastUsed = [];

        Log::info('Forced cleanup of all Pusher connections', [
            'connections_cleaned' => $connectionCount
        ]);
    }

    /**
     * Health check for the connection pool
     */
    public function healthCheck(): array
    {
        $this->cleanupExpiredConnections();

        $healthyConnections = 0;
        $unhealthyConnections = 0;

        foreach ($this->connections as $connection) {
            // Basic health check
            if ($this->isConnectionValid($connection, time())) {
                $healthyConnections++;
            } else {
                $unhealthyConnections++;
            }
        }

        return [
            'status' => $healthyConnections > 0 ? 'healthy' : 'degraded',
            'total_connections' => count($this->connections),
            'healthy_connections' => $healthyConnections,
            'unhealthy_connections' => $unhealthyConnections,
            'pool_utilization' => count($this->connections) / self::MAX_CONNECTIONS * 100,
        ];
    }
}
