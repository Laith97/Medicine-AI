<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConnectionManagementService
{
    protected int $sessionTtl = 3600; // 1 hour
    protected int $heartbeatInterval = 30; // 30 seconds
    protected int $maxConnectionsPerUser = 5;

    /**
     * Cache keys
     */
    const CACHE_KEY_ACTIVE_CONNECTIONS = 'realtime:active_connections';
    const CACHE_KEY_USER_CONNECTIONS = 'realtime:user_connections:';
    const CACHE_KEY_CONNECTION_HEARTBEATS = 'realtime:connection_heartbeats';

    /**
     * Register a new WebSocket connection
     */
    public function registerConnection(User $user, string $socketId, array $metadata = []): string
    {
        $connectionId = $this->generateConnectionId($user, $socketId);

        $connectionData = [
            'id' => $connectionId,
            'user_id' => $user->id,
            'socket_id' => $socketId,
            'connected_at' => now(),
            'last_heartbeat' => now(),
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        // Store connection data
        $this->storeConnection($connectionId, $connectionData);

        // Add to user's active connections
        $this->addUserConnection($user->id, $connectionId);

        // Update global active connections count
        $this->incrementActiveConnections();

        Log::info('WebSocket connection registered', [
            'connection_id' => $connectionId,
            'user_id' => $user->id,
            'socket_id' => $socketId,
        ]);

        return $connectionId;
    }

    /**
     * Unregister a WebSocket connection
     */
    public function unregisterConnection(string $connectionId): bool
    {
        $connectionData = $this->getConnection($connectionId);

        if (!$connectionData) {
            Log::warning('Attempted to unregister non-existent connection', [
                'connection_id' => $connectionId
            ]);
            return false;
        }

        $userId = $connectionData['user_id'];

        // Remove connection data
        $this->removeConnection($connectionId);

        // Remove from user's active connections
        $this->removeUserConnection($userId, $connectionId);

        // Update global active connections count
        $this->decrementActiveConnections();

        Log::info('WebSocket connection unregistered', [
            'connection_id' => $connectionId,
            'user_id' => $userId,
            'duration' => now()->diffInSeconds($connectionData['connected_at']),
        ]);

        return true;
    }

    /**
     * Update connection heartbeat
     */
    public function updateHeartbeat(string $connectionId): bool
    {
        $connectionData = $this->getConnection($connectionId);

        if (!$connectionData) {
            return false;
        }

        $connectionData['last_heartbeat'] = now();
        $this->storeConnection($connectionId, $connectionData);

        return true;
    }

    /**
     * Get active connections for a user
     */
    public function getUserActiveConnections(int $userId): Collection
    {
        $userConnectionsKey = self::CACHE_KEY_USER_CONNECTIONS . $userId;
        $connectionIds = Cache::get($userConnectionsKey, []);

        $activeConnections = [];
        foreach ($connectionIds as $connectionId) {
            $connectionData = $this->getConnection($connectionId);
            if ($connectionData) {
                $activeConnections[] = $connectionData;
            }
        }

        return collect($activeConnections);
    }

    /**
     * Check if user has active connections
     */
    public function userHasActiveConnections(int $userId): bool
    {
        return $this->getUserActiveConnections($userId)->isNotEmpty();
    }

    /**
     * Disconnect all connections for a user
     */
    public function disconnectUser(int $userId): int
    {
        $connections = $this->getUserActiveConnections($userId);
        $disconnectedCount = 0;

        foreach ($connections as $connection) {
            $this->unregisterConnection($connection['id']);
            $disconnectedCount++;
        }

        Log::info('User connections forcibly disconnected', [
            'user_id' => $userId,
            'connections_disconnected' => $disconnectedCount,
        ]);

        return $disconnectedCount;
    }

    /**
     * Clean up stale connections
     */
    public function cleanupStaleConnections(int $staleThresholdSeconds = 60): int
    {
        $allConnections = $this->getAllConnections();
        $cleanedCount = 0;
        $now = now();

        foreach ($allConnections as $connectionId => $connectionData) {
            $lastHeartbeat = $connectionData['last_heartbeat'];

            if ($now->diffInSeconds($lastHeartbeat) > $staleThresholdSeconds) {
                $this->unregisterConnection($connectionId);
                $cleanedCount++;
            }
        }

        if ($cleanedCount > 0) {
            Log::info('Cleaned up stale connections', [
                'cleaned_count' => $cleanedCount,
                'stale_threshold_seconds' => $staleThresholdSeconds,
            ]);
        }

        return $cleanedCount;
    }

    /**
     * Get connection statistics
     */
    public function getConnectionStats(): array
    {
        $allConnections = $this->getAllConnections();
        $activeConnections = count($allConnections);

        $userConnectionCounts = [];
        $deviceTypes = [];
        $connectionDurations = [];

        foreach ($allConnections as $connection) {
            $userId = $connection['user_id'];
            $userConnectionCounts[$userId] = ($userConnectionCounts[$userId] ?? 0) + 1;

            if (isset($connection['metadata']['device_type'])) {
                $deviceType = $connection['metadata']['device_type'];
                $deviceTypes[$deviceType] = ($deviceTypes[$deviceType] ?? 0) + 1;
            }

            $duration = now()->diffInMinutes($connection['connected_at']);
            $connectionDurations[] = $duration;
        }

        return [
            'total_active_connections' => $activeConnections,
            'unique_users_connected' => count($userConnectionCounts),
            'average_connections_per_user' => $activeConnections > 0 ? $activeConnections / count($userConnectionCounts) : 0,
            'max_connections_per_user' => !empty($userConnectionCounts) ? max($userConnectionCounts) : 0,
            'device_types' => $deviceTypes,
            'average_connection_duration_minutes' => !empty($connectionDurations) ? array_sum($connectionDurations) / count($connectionDurations) : 0,
            'session_ttl' => $this->sessionTtl,
            'heartbeat_interval' => $this->heartbeatInterval,
            'last_updated' => now(),
        ];
    }

    /**
     * Broadcast to user's active connections only
     */
    public function broadcastToUserConnections(int $userId, array $channels, string $event, array $data): bool
    {
        $connections = $this->getUserActiveConnections($userId);

        if ($connections->isEmpty()) {
            Log::info('No active connections for user, skipping broadcast', [
                'user_id' => $userId,
                'event' => $event,
            ]);
            return false;
        }

        // Add connection-specific channels
        $userChannels = array_merge($channels, [
            "user.{$userId}",
            "private-user.{$userId}",
        ]);

        // Use PusherConnectionPool for broadcasting
        $pusherPool = app(PusherConnectionPool::class);
        return $pusherPool->broadcast($userChannels, $event, $data);
    }

    /**
     * Handle connection migration (e.g., when user switches devices)
     */
    public function migrateConnection(string $oldConnectionId, string $newSocketId, array $newMetadata = []): ?string
    {
        $oldConnectionData = $this->getConnection($oldConnectionId);

        if (!$oldConnectionData) {
            return null;
        }

        // Unregister old connection
        $this->unregisterConnection($oldConnectionId);

        // Register new connection with same user
        $user = User::find($oldConnectionData['user_id']);
        if (!$user) {
            return null;
        }

        return $this->registerConnection($user, $newSocketId, $newMetadata);
    }

    /**
     * Generate unique connection ID
     */
    protected function generateConnectionId(User $user, string $socketId): string
    {
        return "conn_{$user->id}_{$socketId}_" . Str::random(8);
    }

    /**
     * Store connection data in cache
     */
    protected function storeConnection(string $connectionId, array $data): void
    {
        $key = "realtime:connection:{$connectionId}";
        Cache::put($key, $data, $this->sessionTtl);
    }

    /**
     * Get connection data from cache
     */
    protected function getConnection(string $connectionId): ?array
    {
        $key = "realtime:connection:{$connectionId}";
        return Cache::get($key);
    }

    /**
     * Remove connection data from cache
     */
    protected function removeConnection(string $connectionId): void
    {
        $key = "realtime:connection:{$connectionId}";
        Cache::forget($key);
    }

    /**
     * Add connection to user's active connections
     */
    protected function addUserConnection(int $userId, string $connectionId): void
    {
        $key = self::CACHE_KEY_USER_CONNECTIONS . $userId;
        $connections = Cache::get($key, []);
        $connections[] = $connectionId;

        // Enforce max connections per user
        if (count($connections) > $this->maxConnectionsPerUser) {
            // Remove oldest connections
            $connections = array_slice($connections, -$this->maxConnectionsPerUser);
        }

        Cache::put($key, $connections, $this->sessionTtl);
    }

    /**
     * Remove connection from user's active connections
     */
    protected function removeUserConnection(int $userId, string $connectionId): void
    {
        $key = self::CACHE_KEY_USER_CONNECTIONS . $userId;
        $connections = Cache::get($key, []);

        $connections = array_filter($connections, function ($connId) use ($connectionId) {
            return $connId !== $connectionId;
        });

        if (empty($connections)) {
            Cache::forget($key);
        } else {
            Cache::put($key, array_values($connections), $this->sessionTtl);
        }
    }

    /**
     * Get all active connections
     */
    protected function getAllConnections(): array
    {
        // This is a simplified implementation - in production, you might want to
        // maintain a separate index of all connection IDs
        $connections = [];

        // Get all cache keys that match connection pattern
        // Note: This is a limitation of the cache system - in Redis, you could use SCAN
        // For now, we'll rely on the user connections index

        $userConnectionsKeys = Cache::get('realtime:user_connections_keys', []);
        foreach ($userConnectionsKeys as $userKey) {
            $connectionIds = Cache::get($userKey, []);
            foreach ($connectionIds as $connectionId) {
                $connectionData = $this->getConnection($connectionId);
                if ($connectionData) {
                    $connections[$connectionId] = $connectionData;
                }
            }
        }

        return $connections;
    }

    /**
     * Increment active connections counter
     */
    protected function incrementActiveConnections(): void
    {
        $count = Cache::get(self::CACHE_KEY_ACTIVE_CONNECTIONS, 0);
        Cache::put(self::CACHE_KEY_ACTIVE_CONNECTIONS, $count + 1, $this->sessionTtl);
    }

    /**
     * Decrement active connections counter
     */
    protected function decrementActiveConnections(): void
    {
        $count = Cache::get(self::CACHE_KEY_ACTIVE_CONNECTIONS, 0);
        $newCount = max(0, $count - 1);
        Cache::put(self::CACHE_KEY_ACTIVE_CONNECTIONS, $newCount, $this->sessionTtl);
    }
}
