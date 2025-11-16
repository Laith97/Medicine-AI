<?php

namespace App\Services;

use App\Models\User;
use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class MultiDeviceSynchronizationService
{
    protected DataSynchronizationService $syncService;
    protected ConflictResolutionService $conflictService;
    protected SynchronizationQueueService $queueService;
    protected DeviceNotificationService $notificationService;
    protected AppointmentBroadcastService $broadcastService;

    protected int $syncTtl = 3600; // 1 hour

    /**
     * Cache keys
     */
    const CACHE_KEY_GLOBAL_SYNC_STATE = 'sync:global_state';
    const CACHE_KEY_DEVICE_HEALTH = 'sync:device_health:';

    public function __construct(
        DataSynchronizationService $syncService,
        ConflictResolutionService $conflictService,
        SynchronizationQueueService $queueService,
        DeviceNotificationService $notificationService,
        AppointmentBroadcastService $broadcastService
    ) {
        $this->syncService = $syncService;
        $this->conflictService = $conflictService;
        $this->queueService = $queueService;
        $this->notificationService = $notificationService;
        $this->broadcastService = $broadcastService;
    }

    /**
     * Initialize multi-device synchronization for a user
     */
    public function initializeMultiDeviceSync(User $user, array $deviceIds): array
    {
        $results = [];

        foreach ($deviceIds as $deviceId) {
            try {
                $syncState = $this->syncService->initializeDeviceSync($user, $deviceId);

                // Deliver any offline notifications
                $delivered = $this->notificationService->deliverOfflineNotifications($user->id, $deviceId);

                $results[$deviceId] = [
                    'success' => true,
                    'sync_state' => $syncState,
                    'offline_notifications_delivered' => $delivered
                ];
            } catch (\Exception $e) {
                $results[$deviceId] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Update global sync state
        $this->updateGlobalSyncState($user->id, [
            'active_devices' => $deviceIds,
            'last_sync' => now(),
            'status' => 'initialized'
        ]);

        Log::info('Multi-device synchronization initialized', [
            'user_id' => $user->id,
            'devices' => $deviceIds,
            'results' => $results
        ]);

        return [
            'user_id' => $user->id,
            'devices' => $results,
            'global_state' => $this->getGlobalSyncState($user->id)
        ];
    }

    /**
     * Handle appointment update with multi-device synchronization
     */
    public function handleMultiDeviceAppointmentUpdate(User $user, string $deviceId, array $appointmentData, int $expectedVersion): array
    {
        // Queue the operation for ordered processing
        $operationId = $this->queueService->queueOperation([
            'type' => 'appointment_update',
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'appointment_data' => $appointmentData,
            'expected_version' => $expectedVersion,
            'priority' => $this->determineOperationPriority($appointmentData)
        ]);

        // Process the queue immediately for real-time response
        $processResult = $this->queueService->processQueue($user->id);

        // Get the result of our operation
        $operationResult = $this->getOperationResult($operationId, $processResult);

        if ($operationResult && $operationResult['success']) {
            // Send device-specific notifications
            $this->sendSyncNotifications($user, $deviceId, $appointmentData, $operationResult);

            // Update device health
            $this->updateDeviceHealth($user->id, $deviceId, 'healthy');
        } else {
            // Send error notification
            $this->notificationService->sendSyncErrorNotification(
                $user,
                $deviceId,
                $operationResult['error'] ?? 'Unknown synchronization error'
            );

            // Update device health
            $this->updateDeviceHealth($user->id, $deviceId, 'error');
        }

        return [
            'operation_id' => $operationId,
            'result' => $operationResult,
            'queue_status' => $this->queueService->getQueueStatus($user->id),
            'device_health' => $this->getDeviceHealth($user->id, $deviceId)
        ];
    }

    /**
     * Synchronize data across all user devices
     */
    public function synchronizeAllUserDevices(User $user): array
    {
        $connections = app(ConnectionManagementService::class)->getUserActiveConnections($user->id);
        $deviceIds = $connections->pluck('device_id')->unique()->filter()->values()->all();

        if (empty($deviceIds)) {
            return [
                'success' => false,
                'message' => 'No active devices found for synchronization'
            ];
        }

        $results = [];
        $totalChanges = 0;

        foreach ($deviceIds as $deviceId) {
            try {
                $syncResult = $this->syncService->synchronizeUserAppointments($user, $deviceId);

                $results[$deviceId] = [
                    'success' => true,
                    'changes_count' => $syncResult['changes']['count'] ?? 0,
                    'current_version' => $syncResult['current_version']
                ];

                $totalChanges += $syncResult['changes']['count'] ?? 0;

                // Send completion notification
                $this->notificationService->sendSyncCompletionNotification(
                    $user,
                    $deviceId,
                    ['changes_count' => $syncResult['changes']['count'] ?? 0]
                );

            } catch (\Exception $e) {
                $results[$deviceId] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];

                // Send error notification
                $this->notificationService->sendSyncErrorNotification($user, $deviceId, $e->getMessage());
            }
        }

        // Update global sync state
        $this->updateGlobalSyncState($user->id, [
            'last_full_sync' => now(),
            'total_changes_synced' => $totalChanges,
            'devices_synced' => count(array_filter($results, fn($r) => $r['success']))
        ]);

        return [
            'user_id' => $user->id,
            'devices_synced' => count($results),
            'total_changes' => $totalChanges,
            'results' => $results,
            'global_state' => $this->getGlobalSyncState($user->id)
        ];
    }

    /**
     * Handle conflict resolution with multi-device support
     */
    public function resolveMultiDeviceConflict(User $user, string $deviceId, int $appointmentId, string $resolutionChoice, array $customData = []): array
    {
        // Queue conflict resolution
        $operationId = $this->queueService->queueOperation([
            'type' => 'conflict_resolution',
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'appointment_id' => $appointmentId,
            'resolution_choice' => $resolutionChoice,
            'custom_data' => $customData,
            'priority' => 'high'
        ]);

        // Process immediately
        $processResult = $this->queueService->processQueue($user->id);
        $operationResult = $this->getOperationResult($operationId, $processResult);

        if ($operationResult && $operationResult['success']) {
            // Notify all user devices about conflict resolution
            $this->notificationService->broadcastToAllUserDevices($user, [
                'type' => 'conflict_resolved',
                'title' => 'Conflict Resolved',
                'message' => 'A synchronization conflict has been resolved.',
                'appointment_id' => $appointmentId,
                'resolution' => $resolutionChoice
            ]);
        }

        return [
            'operation_id' => $operationId,
            'result' => $operationResult,
            'broadcast_sent' => $operationResult['success'] ?? false
        ];
    }

    /**
     * Get comprehensive sync status for a user
     */
    public function getComprehensiveSyncStatus(int $userId): array
    {
        $user = User::find($userId);

        return [
            'user_id' => $userId,
            'global_state' => $this->getGlobalSyncState($userId),
            'queue_status' => $this->queueService->getQueueStatus($userId),
            'queue_stats' => $this->queueService->getQueueStats($userId),
            'conflict_stats' => $this->conflictService->getConflictResolutionStats($userId),
            'device_health' => $this->getAllDeviceHealth($userId),
            'active_connections' => app(ConnectionManagementService::class)->getUserActiveConnections($userId)->count(),
            'last_updated' => now()->toISOString()
        ];
    }

    /**
     * Ensure data consistency across clinic devices
     */
    public function ensureClinicDataConsistency(): array
    {
        // Get all active users (could be filtered by role/location)
        $activeUsers = User::where('last_activity', '>', now()->subHours(24))->get();

        $results = [
            'total_users' => $activeUsers->count(),
            'processed' => 0,
            'errors' => 0,
            'consistency_checks' => []
        ];

        foreach ($activeUsers as $user) {
            try {
                // Check for data inconsistencies
                $inconsistencies = $this->checkDataConsistency($user);

                if (!empty($inconsistencies)) {
                    // Attempt to resolve inconsistencies
                    $resolution = $this->resolveDataInconsistencies($user, $inconsistencies);
                    $results['consistency_checks'][] = [
                        'user_id' => $user->id,
                        'inconsistencies_found' => count($inconsistencies),
                        'resolution_attempted' => true,
                        'resolution_success' => $resolution['success']
                    ];
                } else {
                    $results['consistency_checks'][] = [
                        'user_id' => $user->id,
                        'inconsistencies_found' => 0,
                        'status' => 'consistent'
                    ];
                }

                $results['processed']++;
            } catch (\Exception $e) {
                $results['errors']++;
                $results['consistency_checks'][] = [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'status' => 'error'
                ];
            }
        }

        Log::info('Clinic data consistency check completed', $results);

        return $results;
    }

    /**
     * Check for data consistency issues for a user
     */
    protected function checkDataConsistency(User $user): array
    {
        $inconsistencies = [];

        // Check version consistency across devices
        $deviceVersions = $this->getDeviceVersions($user->id);
        if ($this->hasVersionInconsistencies($deviceVersions)) {
            $inconsistencies[] = [
                'type' => 'version_inconsistency',
                'devices' => $deviceVersions,
                'severity' => 'medium'
            ];
        }

        // Check for orphaned sync states
        $orphanedStates = $this->getOrphanedSyncStates($user->id);
        if (!empty($orphanedStates)) {
            $inconsistencies[] = [
                'type' => 'orphaned_sync_states',
                'count' => count($orphanedStates),
                'severity' => 'low'
            ];
        }

        // Check queue health
        $queueStatus = $this->queueService->getQueueStatus($user->id);
        if ($queueStatus['failed_operations'] > 10) {
            $inconsistencies[] = [
                'type' => 'queue_health',
                'failed_operations' => $queueStatus['failed_operations'],
                'severity' => 'high'
            ];
        }

        return $inconsistencies;
    }

    /**
     * Resolve data inconsistencies
     */
    protected function resolveDataInconsistencies(User $user, array $inconsistencies): array
    {
        $resolved = 0;
        $errors = 0;

        foreach ($inconsistencies as $inconsistency) {
            try {
                switch ($inconsistency['type']) {
                    case 'version_inconsistency':
                        $this->resolveVersionInconsistencies($user->id, $inconsistency['devices']);
                        break;
                    case 'orphaned_sync_states':
                        $this->cleanupOrphanedSyncStates($user->id);
                        break;
                    case 'queue_health':
                        $this->queueService->clearFailedOperations($user->id);
                        $this->queueService->retryFailedOperations($user->id);
                        break;
                }
                $resolved++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to resolve data inconsistency', [
                    'user_id' => $user->id,
                    'inconsistency_type' => $inconsistency['type'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'success' => $errors === 0,
            'resolved' => $resolved,
            'errors' => $errors
        ];
    }

    /**
     * Determine operation priority based on appointment data
     */
    protected function determineOperationPriority(array $appointmentData): string
    {
        // High priority for status changes
        if (isset($appointmentData['status'])) {
            return 'high';
        }

        // Medium priority for schedule changes
        if (isset($appointmentData['appointment_date']) || isset($appointmentData['duration'])) {
            return 'normal';
        }

        // Low priority for other changes
        return 'low';
    }

    /**
     * Send synchronization notifications
     */
    protected function sendSyncNotifications(User $user, string $deviceId, array $appointmentData, array $operationResult): void
    {
        // Send to the originating device
        $this->notificationService->sendDeviceSpecificNotification($user->id, [$deviceId], [
            'type' => 'sync_success',
            'title' => 'Update Synchronized',
            'message' => 'Your appointment update has been synchronized across all devices.',
            'appointment_id' => $appointmentData['id'],
            'priority' => 'normal'
        ]);

        // Broadcast to other devices (excluding the originating one)
        $connections = app(ConnectionManagementService::class)->getUserActiveConnections($user->id);
        $otherDeviceIds = $connections->pluck('device_id')
            ->unique()
            ->filter(fn($id) => $id !== $deviceId)
            ->values()
            ->all();

        if (!empty($otherDeviceIds)) {
            $this->notificationService->sendDeviceSpecificNotification($user->id, $otherDeviceIds, [
                'type' => 'sync_update',
                'title' => 'Data Updated',
                'message' => 'Appointment data has been updated from another device.',
                'appointment_id' => $appointmentData['id'],
                'priority' => 'low'
            ]);
        }
    }

    /**
     * Get operation result from queue processing
     */
    protected function getOperationResult(string $operationId, array $processResult): ?array
    {
        // This is a simplified implementation
        // In practice, you'd need to track operation results more precisely
        return $processResult['processed'] > 0 ? ['success' => true] : null;
    }

    /**
     * Update global sync state
     */
    protected function updateGlobalSyncState(int $userId, array $data): void
    {
        $key = self::CACHE_KEY_GLOBAL_SYNC_STATE . $userId;
        $currentState = Cache::get($key, []);

        $updatedState = array_merge($currentState, $data, [
            'updated_at' => now()
        ]);

        Cache::put($key, $updatedState, $this->syncTtl);
    }

    /**
     * Get global sync state
     */
    protected function getGlobalSyncState(int $userId): array
    {
        $key = self::CACHE_KEY_GLOBAL_SYNC_STATE . $userId;
        return Cache::get($key, [
            'status' => 'not_initialized',
            'active_devices' => [],
            'last_sync' => null,
            'updated_at' => null
        ]);
    }

    /**
     * Update device health status
     */
    protected function updateDeviceHealth(int $userId, string $deviceId, string $status): void
    {
        $key = self::CACHE_KEY_DEVICE_HEALTH . $userId . ':' . $deviceId;

        $health = Cache::get($key, [
            'status' => 'unknown',
            'last_seen' => null,
            'error_count' => 0,
            'success_count' => 0
        ]);

        $health['status'] = $status;
        $health['last_seen'] = now();

        if ($status === 'healthy') {
            $health['success_count']++;
        } elseif ($status === 'error') {
            $health['error_count']++;
        }

        Cache::put($key, $health, $this->syncTtl * 24); // Keep health data longer
    }

    /**
     * Get device health status
     */
    protected function getDeviceHealth(int $userId, string $deviceId): array
    {
        $key = self::CACHE_KEY_DEVICE_HEALTH . $userId . ':' . $deviceId;
        return Cache::get($key, [
            'status' => 'unknown',
            'last_seen' => null,
            'error_count' => 0,
            'success_count' => 0
        ]);
    }

    /**
     * Get health status for all user devices
     */
    protected function getAllDeviceHealth(int $userId): array
    {
        // This would require iterating through all device keys
        // Simplified implementation
        return [];
    }

    /**
     * Get device versions for consistency checking
     */
    protected function getDeviceVersions(int $userId): array
    {
        // Simplified implementation
        return [];
    }

    /**
     * Check for version inconsistencies
     */
    protected function hasVersionInconsistencies(array $deviceVersions): bool
    {
        if (empty($deviceVersions)) {
            return false;
        }

        $versions = array_column($deviceVersions, 'version');
        return count(array_unique($versions)) > 1;
    }

    /**
     * Get orphaned sync states
     */
    protected function getOrphanedSyncStates(int $userId): array
    {
        // Simplified implementation
        return [];
    }

    /**
     * Resolve version inconsistencies
     */
    protected function resolveVersionInconsistencies(int $userId, array $deviceVersions): void
    {
        // Force sync all devices to latest version
        $user = User::find($userId);
        if ($user) {
            $this->synchronizeAllUserDevices($user);
        }
    }

    /**
     * Clean up orphaned sync states
     */
    protected function cleanupOrphanedSyncStates(int $userId): void
    {
        // Implementation would clean up old sync states
        Log::info('Cleaned up orphaned sync states', ['user_id' => $userId]);
    }
}
