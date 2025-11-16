<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class DeviceNotificationService
{
    protected NotificationService $notificationService;
    protected ConnectionManagementService $connectionManager;

    protected int $notificationTtl = 3600; // 1 hour

    /**
     * Cache keys
     */
    const CACHE_KEY_DEVICE_NOTIFICATIONS = 'device:notifications:';
    const CACHE_KEY_DEVICE_PREFERENCES = 'device:preferences:';

    public function __construct(
        NotificationService $notificationService,
        ConnectionManagementService $connectionManager
    ) {
        $this->notificationService = $notificationService;
        $this->connectionManager = $connectionManager;
    }

    /**
     * Send device-specific notification
     */
    public function sendDeviceSpecificNotification(int $userId, array $deviceIds, array $notificationData): bool
    {
        $user = User::find($userId);
        if (!$user) {
            Log::error('User not found for device notification', ['user_id' => $userId]);
            return false;
        }

        $sent = 0;
        $total = count($deviceIds);

        foreach ($deviceIds as $deviceId) {
            if ($this->sendToDevice($user, $deviceId, $notificationData)) {
                $sent++;
            }
        }

        // Log notification delivery
        $this->logDeviceNotification($userId, $deviceIds, $notificationData, $sent, $total);

        return $sent > 0;
    }

    /**
     * Send notification to specific device
     */
    protected function sendToDevice(User $user, string $deviceId, array $notificationData): bool
    {
        // Get device preferences
        $preferences = $this->getDeviceNotificationPreferences($user->id, $deviceId);

        // Check if device should receive this type of notification
        if (!$this->shouldSendToDevice($notificationData['type'], $preferences)) {
            return false;
        }

        // Get active connections for this device
        $connections = $this->connectionManager->getUserActiveConnections($user->id)
            ->filter(function ($connection) use ($deviceId) {
                return ($connection['device_id'] ?? null) === $deviceId;
            });

        if ($connections->isEmpty()) {
            // Device not currently connected, store for later delivery
            $this->storeOfflineNotification($user->id, $deviceId, $notificationData);
            return true; // Consider it "sent" for offline delivery
        }

        // Send via different channels based on connection type
        $sent = false;
        foreach ($connections as $connection) {
            $channelType = $connection['type'] ?? 'websocket';

            switch ($channelType) {
                case 'websocket':
                    $sent = $sent || $this->sendViaWebSocket($connection, $notificationData);
                    break;
                case 'push':
                    $sent = $sent || $this->sendViaPush($user, $deviceId, $notificationData);
                    break;
                case 'sms':
                    $sent = $sent || $this->sendViaSms($user, $notificationData);
                    break;
            }
        }

        return $sent;
    }

    /**
     * Send notification via WebSocket
     */
    protected function sendViaWebSocket(array $connection, array $notificationData): bool
    {
        try {
            $channel = "device.{$connection['user_id']}.{$connection['device_id']}";

            $eventData = [
                'type' => 'device_notification',
                'notification' => $notificationData,
                'timestamp' => now()->toISOString(),
                'connection_id' => $connection['id']
            ];

            return app(PusherConnectionPool::class)->broadcast([$channel], 'device.notification', $eventData);
        } catch (\Exception $e) {
            Log::error('Failed to send WebSocket notification', [
                'connection_id' => $connection['id'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification via push notification
     */
    protected function sendViaPush(User $user, string $deviceId, array $notificationData): bool
    {
        try {
            // Get device token for push notifications
            $deviceToken = $this->getDevicePushToken($user->id, $deviceId);

            if (!$deviceToken) {
                return false;
            }

            // Here you would integrate with push notification service (FCM, APNS, etc.)
            // For now, we'll simulate sending
            Log::info('Push notification sent', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'title' => $notificationData['title'],
                'type' => $notificationData['type']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification via SMS
     */
    protected function sendViaSms(User $user, array $notificationData): bool
    {
        try {
            return $this->notificationService->sendSmsNotification($user, [
                'title' => $notificationData['title'],
                'message' => $notificationData['message']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Store notification for offline delivery
     */
    protected function storeOfflineNotification(int $userId, string $deviceId, array $notificationData): void
    {
        $key = self::CACHE_KEY_DEVICE_NOTIFICATIONS . $userId . ':' . $deviceId;
        $notifications = Cache::get($key, []);

        $notificationData['stored_at'] = now();
        $notificationData['id'] = uniqid('offline_notif_', true);

        array_push($notifications, $notificationData);

        // Keep only last 50 notifications per device
        if (count($notifications) > 50) {
            array_shift($notifications);
        }

        Cache::put($key, $notifications, $this->notificationTtl);
    }

    /**
     * Deliver stored offline notifications when device comes online
     */
    public function deliverOfflineNotifications(int $userId, string $deviceId): int
    {
        $key = self::CACHE_KEY_DEVICE_NOTIFICATIONS . $userId . ':' . $deviceId;
        $notifications = Cache::get($key, []);

        if (empty($notifications)) {
            return 0;
        }

        $delivered = 0;
        foreach ($notifications as $notification) {
            if ($this->sendToDevice(User::find($userId), $deviceId, $notification)) {
                $delivered++;
            }
        }

        // Clear delivered notifications
        Cache::forget($key);

        Log::info('Delivered offline notifications', [
            'user_id' => $userId,
            'device_id' => $deviceId,
            'delivered_count' => $delivered
        ]);

        return $delivered;
    }

    /**
     * Get device notification preferences
     */
    public function getDeviceNotificationPreferences(int $userId, string $deviceId): array
    {
        $key = self::CACHE_KEY_DEVICE_PREFERENCES . $userId . ':' . $deviceId;
        return Cache::get($key, $this->getDefaultDevicePreferences());
    }

    /**
     * Update device notification preferences
     */
    public function updateDeviceNotificationPreferences(int $userId, string $deviceId, array $preferences): bool
    {
        $key = self::CACHE_KEY_DEVICE_PREFERENCES . $userId . ':' . $deviceId;

        $currentPreferences = $this->getDeviceNotificationPreferences($userId, $deviceId);
        $updatedPreferences = array_merge($currentPreferences, $preferences);

        Cache::put($key, $updatedPreferences, $this->notificationTtl);

        Log::info('Device notification preferences updated', [
            'user_id' => $userId,
            'device_id' => $deviceId,
            'preferences' => $preferences
        ]);

        return true;
    }

    /**
     * Get default device preferences
     */
    protected function getDefaultDevicePreferences(): array
    {
        return [
            'appointment_updates' => true,
            'conflict_alerts' => true,
            'sync_notifications' => true,
            'error_alerts' => true,
            'channels' => [
                'websocket' => true,
                'push' => false,
                'sms' => false,
                'email' => true
            ],
            'quiet_hours' => [
                'enabled' => false,
                'start' => '22:00',
                'end' => '08:00'
            ]
        ];
    }

    /**
     * Check if notification should be sent to device based on preferences
     */
    protected function shouldSendToDevice(string $notificationType, array $preferences): bool
    {
        // Check if notification type is enabled
        $typeEnabled = match($notificationType) {
            'appointment_update' => $preferences['appointment_updates'] ?? true,
            'conflict_alert' => $preferences['conflict_alerts'] ?? true,
            'sync_complete' => $preferences['sync_notifications'] ?? true,
            'sync_error' => $preferences['error_alerts'] ?? true,
            default => true
        };

        if (!$typeEnabled) {
            return false;
        }

        // Check quiet hours
        if (($preferences['quiet_hours']['enabled'] ?? false)) {
            $now = now()->format('H:i');
            $start = $preferences['quiet_hours']['start'];
            $end = $preferences['quiet_hours']['end'];

            if ($this->isTimeInRange($now, $start, $end)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if current time is within quiet hours range
     */
    protected function isTimeInRange(string $currentTime, string $startTime, string $endTime): bool
    {
        $current = strtotime($currentTime);
        $start = strtotime($startTime);
        $end = strtotime($endTime);

        if ($start <= $end) {
            return ($current >= $start && $current <= $end);
        } else {
            // Handle overnight range (e.g., 22:00 to 08:00)
            return ($current >= $start || $current <= $end);
        }
    }

    /**
     * Get device push token
     */
    protected function getDevicePushToken(int $userId, string $deviceId): ?string
    {
        // This would typically be stored in a database table
        // For now, return null (push notifications disabled)
        return null;
    }

    /**
     * Log device notification delivery
     */
    protected function logDeviceNotification(int $userId, array $deviceIds, array $notificationData, int $sent, int $total): void
    {
        Log::info('Device notification delivery completed', [
            'user_id' => $userId,
            'device_ids' => $deviceIds,
            'notification_type' => $notificationData['type'],
            'sent_count' => $sent,
            'total_count' => $total,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0
        ]);
    }

    /**
     * Send synchronization conflict alert
     */
    public function sendSyncConflictAlert(User $user, string $deviceId, array $conflictData): bool
    {
        $notificationData = [
            'type' => 'conflict_alert',
            'title' => 'Synchronization Conflict Detected',
            'message' => 'A conflict was detected while synchronizing data. Please review and resolve.',
            'conflict_data' => $conflictData,
            'action_required' => true,
            'priority' => 'high'
        ];

        return $this->sendDeviceSpecificNotification($user->id, [$deviceId], $notificationData);
    }

    /**
     * Send synchronization completion notification
     */
    public function sendSyncCompletionNotification(User $user, string $deviceId, array $syncStats): bool
    {
        $notificationData = [
            'type' => 'sync_complete',
            'title' => 'Synchronization Complete',
            'message' => "Data synchronization completed successfully. {$syncStats['changes_count']} changes applied.",
            'sync_stats' => $syncStats,
            'priority' => 'normal'
        ];

        return $this->sendDeviceSpecificNotification($user->id, [$deviceId], $notificationData);
    }

    /**
     * Send synchronization error notification
     */
    public function sendSyncErrorNotification(User $user, string $deviceId, string $error): bool
    {
        $notificationData = [
            'type' => 'sync_error',
            'title' => 'Synchronization Error',
            'message' => 'An error occurred during data synchronization: ' . $error,
            'error' => $error,
            'priority' => 'high'
        ];

        return $this->sendDeviceSpecificNotification($user->id, [$deviceId], $notificationData);
    }

    /**
     * Broadcast to all user devices
     */
    public function broadcastToAllUserDevices(User $user, array $notificationData): int
    {
        $connections = $this->connectionManager->getUserActiveConnections($user->id);
        $deviceIds = $connections->pluck('device_id')->unique()->filter()->values()->all();

        if (empty($deviceIds)) {
            return 0;
        }

        $this->sendDeviceSpecificNotification($user->id, $deviceIds, $notificationData);
        return count($deviceIds);
    }
}
