<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\User;

class MemoryOptimizedNotificationProcessor
{
    private const BATCH_SIZE = 100; // Process notifications in batches
    private const MEMORY_THRESHOLD = 128 * 1024 * 1024; // 128MB threshold
    private const MAX_PROCESSING_TIME = 30; // 30 seconds max processing time

    /**
     * Process notifications in memory-optimized batches
     */
    public function processNotifications(Collection $notifications, callable $processor): Collection
    {
        $startTime = microtime(true);
        $results = collect();
        $processed = 0;

        Log::info('Starting memory-optimized notification processing', [
            'total_notifications' => $notifications->count(),
            'batch_size' => self::BATCH_SIZE
        ]);

        $notifications->chunk(self::BATCH_SIZE)->each(function ($batch) use (&$results, &$processed, $processor, $startTime) {
            // Check memory usage
            if ($this->isMemoryUsageHigh()) {
                Log::warning('High memory usage detected, forcing garbage collection');
                $this->forceGarbageCollection();
            }

            // Check processing time
            if ($this->isProcessingTimeExceeded($startTime)) {
                Log::warning('Processing time exceeded, stopping batch processing');
                return false; // Break out of chunk iteration
            }

            // Process batch
            $batchResults = $batch->map(function ($notification) use ($processor) {
                try {
                    return $processor($notification);
                } catch (\Exception $e) {
                    Log::error('Error processing notification', [
                        'notification_id' => $notification->id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            })->filter(); // Remove null results

            $results = $results->merge($batchResults);
            $processed += $batch->count();

            // Yield control to prevent blocking
            $this->yieldControl();
        });

        $processingTime = microtime(true) - $startTime;

        Log::info('Completed memory-optimized notification processing', [
            'processed_count' => $processed,
            'results_count' => $results->count(),
            'processing_time' => round($processingTime, 2) . 's',
            'memory_peak' => $this->getMemoryUsage()
        ]);

        return $results;
    }

    /**
     * Get user notifications with memory optimization
     */
    public function getUserNotifications(User $user, int $limit = 50, array $filters = []): LazyCollection
    {
        $query = $user->notifications();

        // Apply filters efficiently
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['read'])) {
            if ($filters['read']) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Use cursor pagination for better memory efficiency
        return $query->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->cursor()
                    ->map(function ($notification) {
                        // Only load necessary fields to save memory
                        return [
                            'id' => $notification->id,
                            'type' => $notification->type,
                            'data' => $this->optimizeNotificationData($notification->data),
                            'read_at' => $notification->read_at,
                            'created_at' => $notification->created_at,
                        ];
                    });
    }

    /**
     * Optimize notification data structure for memory efficiency
     */
    private function optimizeNotificationData(array $data): array
    {
        // Remove unnecessary fields and optimize structure
        $optimized = [];

        // Keep essential fields
        $essentialFields = ['title', 'message', 'type', 'icon', 'created_at'];
        foreach ($essentialFields as $field) {
            if (isset($data[$field])) {
                $optimized[$field] = $data[$field];
            }
        }

        // Optimize data array if it exists
        if (isset($data['data']) && is_array($data['data'])) {
            $optimized['data'] = array_slice($data['data'], 0, 10); // Limit to first 10 items
        }

        return $optimized;
    }

    /**
     * Bulk mark notifications as read with memory optimization
     */
    public function bulkMarkAsRead(Collection $notificationIds): int
    {
        $updated = 0;

        $notificationIds->chunk(self::BATCH_SIZE)->each(function ($batch) use (&$updated) {
            $count = DatabaseNotification::whereIn('id', $batch)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $updated += $count;

            // Yield control
            $this->yieldControl();
        });

        Log::info('Bulk marked notifications as read', [
            'total_requested' => $notificationIds->count(),
            'total_updated' => $updated
        ]);

        return $updated;
    }

    /**
     * Clean up old notifications with memory optimization
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        $deleted = 0;
        $batchSize = self::BATCH_SIZE;

        do {
            $oldNotifications = DatabaseNotification::where('created_at', '<', now()->subDays($daysOld))
                ->limit($batchSize)
                ->pluck('id');

            if ($oldNotifications->isEmpty()) {
                break;
            }

            $count = DatabaseNotification::whereIn('id', $oldNotifications)->delete();
            $deleted += $count;

            // Yield control
            $this->yieldControl();

        } while ($oldNotifications->count() === $batchSize);

        Log::info('Cleaned up old notifications', [
            'days_old' => $daysOld,
            'total_deleted' => $deleted
        ]);

        return $deleted;
    }

    /**
     * Check if memory usage is high
     */
    private function isMemoryUsageHigh(): bool
    {
        $memoryUsage = memory_get_usage(true);
        return $memoryUsage > self::MEMORY_THRESHOLD;
    }

    /**
     * Check if processing time is exceeded
     */
    private function isProcessingTimeExceeded(float $startTime): bool
    {
        $elapsed = microtime(true) - $startTime;
        return $elapsed > self::MAX_PROCESSING_TIME;
    }

    /**
     * Force garbage collection
     */
    private function forceGarbageCollection(): void
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Yield control to prevent blocking
     */
    private function yieldControl(): void
    {
        // Small delay to yield control
        usleep(1000); // 1ms
    }

    /**
     * Get current memory usage
     */
    private function getMemoryUsage(): string
    {
        $bytes = memory_get_peak_usage(true);
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get processing statistics
     */
    public function getProcessingStats(): array
    {
        return [
            'batch_size' => self::BATCH_SIZE,
            'memory_threshold' => self::MEMORY_THRESHOLD,
            'max_processing_time' => self::MAX_PROCESSING_TIME,
            'current_memory_usage' => $this->getMemoryUsage(),
            'memory_limit' => ini_get('memory_limit'),
        ];
    }
}
