<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SynchronizationQueueService
{
    protected int $queueTtl = 3600; // 1 hour
    protected int $maxQueueSize = 1000;
    protected int $processingBatchSize = 50;

    /**
     * Cache keys
     */
    const CACHE_KEY_SYNC_QUEUE = 'sync:queue:';
    const CACHE_KEY_PROCESSING_LOCK = 'sync:processing_lock:';
    const CACHE_KEY_QUEUE_STATS = 'sync:queue_stats:';

    /**
     * Queue a synchronization operation
     */
    public function queueOperation(array $operation): string
    {
        $operationId = uniqid('sync_op_', true);
        $operation['id'] = $operationId;
        $operation['queued_at'] = now();
        $operation['priority'] = $operation['priority'] ?? 'normal';
        $operation['status'] = 'queued';

        $queueKey = self::CACHE_KEY_SYNC_QUEUE . ($operation['user_id'] ?? 'global');

        $queue = Cache::get($queueKey, []);
        array_push($queue, $operation);

        // Maintain queue size limit
        if (count($queue) > $this->maxQueueSize) {
            array_shift($queue); // Remove oldest operation
        }

        // Sort by priority (high > normal > low)
        usort($queue, function ($a, $b) {
            $priorityOrder = ['high' => 3, 'normal' => 2, 'low' => 1];
            return ($priorityOrder[$b['priority']] ?? 2) <=> ($priorityOrder[$a['priority']] ?? 2);
        });

        Cache::put($queueKey, $queue, $this->queueTtl);

        Log::info('Synchronization operation queued', [
            'operation_id' => $operationId,
            'type' => $operation['type'],
            'user_id' => $operation['user_id'] ?? null,
            'device_id' => $operation['device_id'] ?? null
        ]);

        return $operationId;
    }

    /**
     * Process queued operations for a user
     */
    public function processQueue(int $userId): array
    {
        $lockKey = self::CACHE_KEY_PROCESSING_LOCK . $userId;

        // Acquire processing lock
        if (!Cache::add($lockKey, true, 300)) { // 5 minute lock
            Log::warning('Queue processing already in progress', ['user_id' => $userId]);
            return ['processed' => 0, 'errors' => 0, 'message' => 'Processing already in progress'];
        }

        try {
            $queueKey = self::CACHE_KEY_SYNC_QUEUE . $userId;
            $queue = Cache::get($queueKey, []);

            if (empty($queue)) {
                return ['processed' => 0, 'errors' => 0, 'message' => 'Queue is empty'];
            }

            $processed = 0;
            $errors = 0;
            $batch = array_slice($queue, 0, $this->processingBatchSize);

            foreach ($batch as $index => $operation) {
                try {
                    $result = $this->processOperation($operation);

                    if ($result['success']) {
                        $processed++;
                        $queue[$index]['status'] = 'completed';
                        $queue[$index]['completed_at'] = now();
                    } else {
                        $errors++;
                        $queue[$index]['status'] = 'failed';
                        $queue[$index]['error'] = $result['error'];
                        $queue[$index]['failed_at'] = now();
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $queue[$index]['status'] = 'failed';
                    $queue[$index]['error'] = $e->getMessage();
                    $queue[$index]['failed_at'] = now();

                    Log::error('Failed to process sync operation', [
                        'operation_id' => $operation['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Remove completed operations and keep failed ones for retry
            $queue = array_filter($queue, function ($op) {
                return $op['status'] !== 'completed';
            });

            Cache::put($queueKey, array_values($queue), $this->queueTtl);

            $this->updateQueueStats($userId, $processed, $errors);

            return [
                'processed' => $processed,
                'errors' => $errors,
                'remaining' => count($queue)
            ];

        } finally {
            Cache::forget($lockKey);
        }
    }

    /**
     * Process a single operation
     */
    protected function processOperation(array $operation): array
    {
        $type = $operation['type'];

        switch ($type) {
            case 'appointment_update':
                return $this->processAppointmentUpdate($operation);

            case 'conflict_resolution':
                return $this->processConflictResolution($operation);

            case 'device_sync':
                return $this->processDeviceSync($operation);

            case 'broadcast_notification':
                return $this->processBroadcastNotification($operation);

            default:
                return [
                    'success' => false,
                    'error' => "Unknown operation type: {$type}"
                ];
        }
    }

    /**
     * Process appointment update operation
     */
    protected function processAppointmentUpdate(array $operation): array
    {
        $syncService = app(DataSynchronizationService::class);

        $result = $syncService->handleDeviceAppointmentUpdate(
            User::find($operation['user_id']),
            $operation['device_id'],
            $operation['appointment_data'],
            $operation['expected_version']
        );

        return [
            'success' => $result['success'] ?? false,
            'error' => $result['error'] ?? null,
            'result' => $result
        ];
    }

    /**
     * Process conflict resolution operation
     */
    protected function processConflictResolution(array $operation): array
    {
        $syncService = app(DataSynchronizationService::class);

        $result = $syncService->resolveConflict(
            User::find($operation['user_id']),
            $operation['device_id'],
            $operation['appointment_id'],
            $operation['resolved_data'],
            $operation['resolution_strategy']
        );

        return [
            'success' => true,
            'result' => $result
        ];
    }

    /**
     * Process device sync operation
     */
    protected function processDeviceSync(array $operation): array
    {
        $syncService = app(DataSynchronizationService::class);

        $result = $syncService->synchronizeUserAppointments(
            User::find($operation['user_id']),
            $operation['device_id'],
            $operation['since_version'] ?? null
        );

        return [
            'success' => true,
            'result' => $result
        ];
    }

    /**
     * Process broadcast notification operation
     */
    protected function processBroadcastNotification(array $operation): array
    {
        $notificationService = app(DeviceNotificationService::class);

        $result = $notificationService->sendDeviceSpecificNotification(
            $operation['user_id'],
            $operation['device_ids'] ?? [],
            $operation['notification_data']
        );

        return [
            'success' => $result,
            'result' => ['sent' => $result]
        ];
    }

    /**
     * Get queue status for a user
     */
    public function getQueueStatus(int $userId): array
    {
        $queueKey = self::CACHE_KEY_SYNC_QUEUE . $userId;
        $queue = Cache::get($queueKey, []);

        $stats = [
            'total_operations' => count($queue),
            'pending_operations' => count(array_filter($queue, fn($op) => $op['status'] === 'queued')),
            'failed_operations' => count(array_filter($queue, fn($op) => $op['status'] === 'failed')),
            'processing_operations' => count(array_filter($queue, fn($op) => $op['status'] === 'processing')),
        ];

        // Group by priority
        $stats['by_priority'] = [
            'high' => count(array_filter($queue, fn($op) => $op['priority'] === 'high')),
            'normal' => count(array_filter($queue, fn($op) => $op['priority'] === 'normal')),
            'low' => count(array_filter($queue, fn($op) => $op['priority'] === 'low')),
        ];

        // Group by type
        $stats['by_type'] = [];
        foreach ($queue as $operation) {
            $type = $operation['type'];
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * Clear failed operations from queue
     */
    public function clearFailedOperations(int $userId): int
    {
        $queueKey = self::CACHE_KEY_SYNC_QUEUE . $userId;
        $queue = Cache::get($queueKey, []);

        $originalCount = count($queue);
        $queue = array_filter($queue, fn($op) => $op['status'] !== 'failed');

        Cache::put($queueKey, array_values($queue), $this->queueTtl);

        $cleared = $originalCount - count($queue);

        Log::info('Cleared failed operations from sync queue', [
            'user_id' => $userId,
            'cleared_count' => $cleared
        ]);

        return $cleared;
    }

    /**
     * Retry failed operations
     */
    public function retryFailedOperations(int $userId): int
    {
        $queueKey = self::CACHE_KEY_SYNC_QUEUE . $userId;
        $queue = Cache::get($queueKey, []);

        $retried = 0;
        foreach ($queue as &$operation) {
            if ($operation['status'] === 'failed') {
                $operation['status'] = 'queued';
                $operation['retry_count'] = ($operation['retry_count'] ?? 0) + 1;
                $operation['retried_at'] = now();
                $retried++;
            }
        }

        Cache::put($queueKey, $queue, $this->queueTtl);

        Log::info('Retried failed operations in sync queue', [
            'user_id' => $userId,
            'retried_count' => $retried
        ]);

        return $retried;
    }

    /**
     * Update queue statistics
     */
    protected function updateQueueStats(int $userId, int $processed, int $errors): void
    {
        $statsKey = self::CACHE_KEY_QUEUE_STATS . $userId;
        $stats = Cache::get($statsKey, [
            'total_processed' => 0,
            'total_errors' => 0,
            'last_processed_at' => null
        ]);

        $stats['total_processed'] += $processed;
        $stats['total_errors'] += $errors;
        $stats['last_processed_at'] = now();

        Cache::put($statsKey, $stats, $this->queueTtl * 24); // Keep stats longer
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats(int $userId): array
    {
        $statsKey = self::CACHE_KEY_QUEUE_STATS . $userId;
        return Cache::get($statsKey, [
            'total_processed' => 0,
            'total_errors' => 0,
            'last_processed_at' => null
        ]);
    }

    /**
     * Clean up old queues
     */
    public function cleanupOldQueues(int $olderThanHours = 24): int
    {
        // This would require iterating through all user queues
        // For now, rely on cache TTL expiration
        Log::info('Queue cleanup completed (TTL-based)', [
            'older_than_hours' => $olderThanHours
        ]);

        return 0;
    }
}
