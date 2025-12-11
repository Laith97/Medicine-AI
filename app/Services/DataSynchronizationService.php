<?php

namespace App\Services;

use App\Models\User;
use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DataSynchronizationService
{
    protected ConnectionManagementService $connectionManager;
    protected RealtimeCacheService $cacheService;
    protected AppointmentBroadcastService $broadcastService;

    protected int $syncTtl = 3600; // 1 hour
    protected int $maxSyncVersions = 50; // Max versions to keep per user

    /**
     * Cache keys
     */
    const CACHE_KEY_USER_SYNC_STATE = 'sync:user_state:';
    const CACHE_KEY_APPOINTMENT_VERSIONS = 'sync:appointment_versions:';
    const CACHE_KEY_DEVICE_SYNC_LOG = 'sync:device_log:';

    public function __construct(
        ConnectionManagementService $connectionManager,
        RealtimeCacheService $cacheService,
        AppointmentBroadcastService $broadcastService
    ) {
        $this->connectionManager = $connectionManager;
        $this->cacheService = $cacheService;
        $this->broadcastService = $broadcastService;
    }

    /**
     * Initialize synchronization for a user device
     */
    public function initializeDeviceSync(User $user, string $deviceId, array $metadata = []): array
    {
        $syncState = [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'last_sync_timestamp' => now(),
            'last_sync_version' => $this->getLatestVersionForUser($user->id),
            'metadata' => $metadata,
            'sync_token' => $this->generateSyncToken(),
        ];

        $cacheKey = self::CACHE_KEY_USER_SYNC_STATE . $user->id . ':' . $deviceId;
        Cache::put($cacheKey, $syncState, $this->syncTtl);

        Log::info('Device sync initialized', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'sync_token' => $syncState['sync_token']
        ]);

        return $syncState;
    }

    /**
     * Synchronize appointment data for a user across devices
     */
    public function synchronizeUserAppointments(User $user, string $deviceId, int $sinceVersion = null): array
    {
        $syncState = $this->getDeviceSyncState($user->id, $deviceId);

        if (!$syncState) {
            throw new \Exception('Device not synchronized. Call initializeDeviceSync first.');
        }

        $latestVersion = $this->getLatestVersionForUser($user->id);
        $sinceVersion = $sinceVersion ?? $syncState['last_sync_version'];

        // Get changes since the last sync
        $changes = $this->getAppointmentChangesSinceVersion($user->id, $sinceVersion);

        // Update sync state
        $syncState['last_sync_timestamp'] = now();
        $syncState['last_sync_version'] = $latestVersion;
        $this->updateDeviceSyncState($user->id, $deviceId, $syncState);

        // Log sync operation
        $this->logDeviceSync($user->id, $deviceId, [
            'changes_count' => count($changes),
            'since_version' => $sinceVersion,
            'to_version' => $latestVersion
        ]);

        return [
            'sync_token' => $syncState['sync_token'],
            'current_version' => $latestVersion,
            'changes' => $changes,
            'has_more_changes' => false, // For future pagination
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Handle appointment update from a device (conflict resolution)
     */
    public function handleDeviceAppointmentUpdate(User $user, string $deviceId, array $appointmentData, int $expectedVersion): array
    {
        $currentVersion = $this->getLatestVersionForUser($user->id);

        // Check for version conflict
        if ($expectedVersion < $currentVersion) {
            // There's a conflict - get the conflicting changes
            $conflictingChanges = $this->getAppointmentChangesSinceVersion($user->id, $expectedVersion);

            return [
                'conflict' => true,
                'current_version' => $currentVersion,
                'expected_version' => $expectedVersion,
                'conflicting_changes' => $conflictingChanges,
                'resolution_required' => true
            ];
        }

        // No conflict - proceed with update
        try {
            $appointment = Appointment::findOrFail($appointmentData['id']);

            // Check if user has permission to update this appointment
            if (!$this->userCanUpdateAppointment($user, $appointment)) {
                throw new \Exception('User does not have permission to update this appointment');
            }

            // Apply the update
            $oldStatus = $appointment->status;
            $appointment->update($appointmentData);

            // Create new version
            $newVersion = $this->createAppointmentVersion($appointment, $user->id, $deviceId, 'update');

            // Broadcast the change
            $this->broadcastService->broadcastAppointmentUpdated($appointment, array_keys($appointmentData));

            // Update cache
            $this->cacheService->updateAppointmentInCache($appointment);

            return [
                'conflict' => false,
                'success' => true,
                'new_version' => $newVersion,
                'appointment' => $appointment
            ];

        } catch (\Exception $e) {
            Log::error('Failed to handle device appointment update', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'appointment_id' => $appointmentData['id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'conflict' => false,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Resolve synchronization conflict
     */
    public function resolveConflict(User $user, string $deviceId, int $appointmentId, array $resolvedData, string $resolutionStrategy): array
    {
        $appointment = Appointment::findOrFail($appointmentId);

        Log::info('Resolving sync conflict', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'appointment_id' => $appointmentId,
            'strategy' => $resolutionStrategy
        ]);

        // Apply resolved data
        $appointment->update($resolvedData);

        // Create version with conflict resolution note
        $newVersion = $this->createAppointmentVersion($appointment, $user->id, $deviceId, 'conflict_resolution', [
            'resolution_strategy' => $resolutionStrategy
        ]);

        // Broadcast resolution
        $this->broadcastService->broadcastAppointmentUpdated($appointment, array_keys($resolvedData));

        // Update cache
        $this->cacheService->updateAppointmentInCache($appointment);

        return [
            'success' => true,
            'new_version' => $newVersion,
            'appointment' => $appointment
        ];
    }

    /**
     * Broadcast synchronization state to all user devices
     */
    public function broadcastSyncStateUpdate(User $user, array $syncData): bool
    {
        $connections = $this->connectionManager->getUserActiveConnections($user->id);

        if ($connections->isEmpty()) {
            return true; // No connections to broadcast to
        }

        $channels = $connections->map(function ($connection) {
            return "sync.{$connection['user_id']}";
        })->unique()->values()->all();

        $eventData = [
            'type' => 'sync_state_update',
            'user_id' => $user->id,
            'sync_data' => $syncData,
            'timestamp' => now()->toISOString(),
            'event_id' => uniqid('sync_', true)
        ];

        return app(PusherConnectionPool::class)->broadcast($channels, 'sync.updated', $eventData);
    }

    /**
     * Get synchronization statistics for a user
     */
    public function getUserSyncStats(int $userId): array
    {
        $devices = $this->getUserSyncedDevices($userId);
        $latestVersion = $this->getLatestVersionForUser($userId);

        $deviceStats = [];
        foreach ($devices as $deviceId => $syncState) {
            $deviceStats[$deviceId] = [
                'last_sync' => $syncState['last_sync_timestamp'],
                'version' => $syncState['last_sync_version'],
                'behind_by' => $latestVersion - $syncState['last_sync_version'],
                'metadata' => $syncState['metadata'] ?? []
            ];
        }

        return [
            'user_id' => $userId,
            'latest_version' => $latestVersion,
            'device_count' => count($devices),
            'devices' => $deviceStats,
            'sync_logs' => $this->getDeviceSyncLogs($userId, 10) // Last 10 sync operations
        ];
    }

    /**
     * Clean up old synchronization data
     */
    public function cleanupOldSyncData(int $olderThanHours = 24): int
    {
        $cutoffTime = now()->subHours($olderThanHours);
        $cleaned = 0;

        // This would need implementation based on your cache backend
        // For now, we'll rely on cache TTL expiration

        Log::info('Cleaned up old sync data', [
            'older_than_hours' => $olderThanHours,
            'cleaned_count' => $cleaned
        ]);

        return $cleaned;
    }

    /**
     * Get appointment changes since a specific version
     */
    public function getAppointmentChangesSinceVersion(int $userId, int $sinceVersion): array
    {
        $versions = $this->getAppointmentVersionsForUser($userId);

        $changes = [];
        foreach ($versions as $version) {
            if ($version['version'] > $sinceVersion) {
                $changes[] = $version;
            }
        }

        return array_slice($changes, 0, 100); // Limit to prevent huge payloads
    }

    /**
     * Create a new appointment version
     */
    protected function createAppointmentVersion(Appointment $appointment, int $userId, string $deviceId, string $operation, array $metadata = []): int
    {
        $version = $this->incrementUserVersion($userId);

        $versionData = [
            'version' => $version,
            'appointment_id' => $appointment->id,
            'user_id' => $userId,
            'device_id' => $deviceId,
            'operation' => $operation,
            'appointment_data' => $appointment->toArray(),
            'timestamp' => now(),
            'metadata' => $metadata
        ];

        $versionsKey = self::CACHE_KEY_APPOINTMENT_VERSIONS . $userId;
        $versions = Cache::get($versionsKey, []);

        // Keep only the most recent versions
        array_unshift($versions, $versionData);
        $versions = array_slice($versions, 0, $this->maxSyncVersions);

        Cache::put($versionsKey, $versions, $this->syncTtl);

        return $version;
    }

    /**
     * Get appointment versions for a user
     */
    protected function getAppointmentVersionsForUser(int $userId): array
    {
        $versionsKey = self::CACHE_KEY_APPOINTMENT_VERSIONS . $userId;
        return Cache::get($versionsKey, []);
    }

    /**
     * Get latest version number for a user
     */
    public function getLatestVersionForUser(int $userId): int
    {
        $versions = $this->getAppointmentVersionsForUser($userId);
        return $versions ? $versions[0]['version'] : 0;
    }

    /**
     * Increment user version counter
     */
    protected function incrementUserVersion(int $userId): int
    {
        $versionKey = "sync:user_version:{$userId}";
        $currentVersion = Cache::get($versionKey, 0);
        $newVersion = $currentVersion + 1;

        Cache::put($versionKey, $newVersion, $this->syncTtl * 24); // Keep version counter longer

        return $newVersion;
    }

    /**
     * Get device sync state
     */
    protected function getDeviceSyncState(int $userId, string $deviceId): ?array
    {
        $cacheKey = self::CACHE_KEY_USER_SYNC_STATE . $userId . ':' . $deviceId;
        return Cache::get($cacheKey);
    }

    /**
     * Update device sync state
     */
    protected function updateDeviceSyncState(int $userId, string $deviceId, array $syncState): void
    {
        $cacheKey = self::CACHE_KEY_USER_SYNC_STATE . $userId . ':' . $deviceId;
        Cache::put($cacheKey, $syncState, $this->syncTtl);
    }

    /**
     * Get all synced devices for a user
     */
    protected function getUserSyncedDevices(int $userId): array
    {
        // This is a simplified implementation - in production, you'd want to maintain
        // a separate index of device IDs per user
        $devices = [];

        // Pattern matching cache keys would be needed here
        // For now, return empty array as this requires more complex cache operations

        return $devices;
    }

    /**
     * Log device synchronization operation
     */
    protected function logDeviceSync(int $userId, string $deviceId, array $syncData): void
    {
        $logKey = self::CACHE_KEY_DEVICE_SYNC_LOG . $userId . ':' . $deviceId;
        $logs = Cache::get($logKey, []);

        array_unshift($logs, [
            'timestamp' => now(),
            'data' => $syncData
        ]);

        // Keep only last 50 logs per device
        $logs = array_slice($logs, 0, 50);

        Cache::put($logKey, $logs, $this->syncTtl);
    }

    /**
     * Get device sync logs
     */
    protected function getDeviceSyncLogs(int $userId, int $limit = 10): array
    {
        // This would aggregate logs from all user devices
        // Simplified implementation
        return [];
    }

    /**
     * Check if user can update an appointment
     */
    protected function userCanUpdateAppointment(User $user, Appointment $appointment): bool
    {
        // Role-based permissions
        if (in_array($user->role, ['admin', 'hospital_admin'])) {
            return true;
        }

        if ($user->role === 'doctor' && $appointment->doctor_id === $user->doctor->id) {
            return true;
        }

        if ($appointment->patient_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Generate a unique sync token
     */
    protected function generateSyncToken(): string
    {
        return 'sync_' . Str::random(32) . '_' . time();
    }
}
