<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PushNotificationService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('services.push_notifications', []);
    }

    /**
     * Send push notification to a user
     */
    public function sendPushNotification(User $user, Notification $notification, array $options = []): bool
    {
        try {
            // Check if user has push notification tokens/subscriptions
            $pushTokens = $this->getUserPushTokens($user);

            if (empty($pushTokens)) {
                Log::debug('No push tokens found for user', [
                    'user_id' => $user->id,
                    'notification_id' => $notification->id
                ]);
                return false;
            }

            $payload = $this->buildPushPayload($notification, $options);

            $successCount = 0;
            foreach ($pushTokens as $token) {
                if ($this->sendToProvider($token, $payload)) {
                    $successCount++;
                }
            }

            Log::info('Push notification sent', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'tokens_sent' => count($pushTokens),
                'successful_sends' => $successCount,
            ]);

            return $successCount > 0;
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send push notification for critical updates
     */
    public function sendCriticalPushNotification(User $user, Notification $notification): bool
    {
        return $this->sendPushNotification($user, $notification, [
            'priority' => 'high',
            'ttl' => 86400, // 24 hours
            'sound' => 'critical',
            'vibrate' => true,
        ]);
    }

    /**
     * Send push notification for appointment status changes
     */
    public function sendAppointmentStatusPushNotification(User $user, Notification $notification): bool
    {
        $isCritical = $this->isCriticalAppointmentStatus($notification);

        return $this->sendPushNotification($user, $notification, [
            'priority' => $isCritical ? 'high' : 'normal',
            'ttl' => $isCritical ? 86400 : 3600, // 24 hours for critical, 1 hour for normal
            'sound' => $isCritical ? 'appointment_critical' : 'appointment',
            'vibrate' => true,
            'category' => 'appointment',
        ]);
    }

    /**
     * Get user's push notification tokens
     */
    protected function getUserPushTokens(User $user): array
    {
        // This would typically come from a user_push_tokens table
        // For now, we'll check if the user has any stored tokens
        // In a real implementation, you'd have a relationship like $user->pushTokens

        $tokens = [];

        // Check for FCM tokens
        if (isset($user->fcm_token) && !empty($user->fcm_token)) {
            $tokens[] = [
                'token' => $user->fcm_token,
                'provider' => 'fcm',
            ];
        }

        // Check for APNS tokens (iOS)
        if (isset($user->apns_token) && !empty($user->apns_token)) {
            $tokens[] = [
                'token' => $user->apns_token,
                'provider' => 'apns',
            ];
        }

        // Check for Web Push subscriptions
        if (isset($user->web_push_subscription) && !empty($user->web_push_subscription)) {
            $tokens[] = [
                'token' => $user->web_push_subscription,
                'provider' => 'web_push',
            ];
        }

        return $tokens;
    }

    /**
     * Build push notification payload
     */
    protected function buildPushPayload(Notification $notification, array $options = []): array
    {
        $data = $notification->data;

        return [
            'title' => $data['title'] ?? 'Notification',
            'body' => $data['message'] ?? 'You have a new notification',
            'icon' => $this->getIconUrl($data['icon'] ?? 'bell'),
            'badge' => '/badge.png',
            'data' => [
                'notification_id' => $notification->id,
                'type' => $data['type'] ?? 'general',
                'link' => $data['link'] ?? null,
                'related_type' => $data['related_type'] ?? null,
                'related_id' => $data['related_id'] ?? null,
            ],
            'actions' => $this->getNotificationActions($notification),
            'priority' => $options['priority'] ?? 'normal',
            'ttl' => $options['ttl'] ?? 3600,
            'sound' => $options['sound'] ?? 'default',
            'vibrate' => $options['vibrate'] ?? false,
            'category' => $options['category'] ?? null,
        ];
    }

    /**
     * Get notification actions for interactive notifications
     */
    protected function getNotificationActions(Notification $notification): array
    {
        $actions = [];

        if ($notification->hasLink()) {
            $actions[] = [
                'action' => 'view',
                'title' => $notification->link_text,
                'icon' => '/icons/view.png',
            ];
        }

        // Add appointment-specific actions
        if (($notification->data['related_type'] ?? null) === 'appointment') {
            $actions[] = [
                'action' => 'reschedule',
                'title' => 'Reschedule',
                'icon' => '/icons/calendar.png',
            ];
            $actions[] = [
                'action' => 'cancel',
                'title' => 'Cancel',
                'icon' => '/icons/cancel.png',
            ];
        }

        return $actions;
    }

    /**
     * Get icon URL for the notification
     */
    protected function getIconUrl(string $icon): string
    {
        $iconMap = [
            'calendar' => '/icons/calendar.png',
            'calendar-check' => '/icons/calendar-check.png',
            'calendar-times' => '/icons/calendar-times.png',
            'check-circle' => '/icons/check-circle.png',
            'user-times' => '/icons/user-times.png',
            'clock' => '/icons/clock.png',
            'calendar-alt' => '/icons/calendar-alt.png',
            'bell' => '/icons/bell.png',
            'info' => '/icons/info.png',
            'warning' => '/icons/warning.png',
            'error' => '/icons/error.png',
            'success' => '/icons/success.png',
        ];

        return $iconMap[$icon] ?? '/icons/bell.png';
    }

    /**
     * Send notification to specific provider
     */
    protected function sendToProvider(array $token, array $payload): bool
    {
        try {
            switch ($token['provider']) {
                case 'fcm':
                    return $this->sendToFCM($token['token'], $payload);
                case 'apns':
                    return $this->sendToAPNS($token['token'], $payload);
                case 'web_push':
                    return $this->sendToWebPush($token['token'], $payload);
                default:
                    Log::warning('Unknown push provider', ['provider' => $token['provider']]);
                    return false;
            }
        } catch (\Exception $e) {
            Log::error('Failed to send to push provider', [
                'provider' => $token['provider'],
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send to Firebase Cloud Messaging
     */
    protected function sendToFCM(string $token, array $payload): bool
    {
        $serverKey = $this->config['fcm']['server_key'] ?? null;

        if (!$serverKey) {
            Log::warning('FCM server key not configured');
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $serverKey,  // Use proper Bearer token format
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $token,
            'notification' => [
                'title' => $payload['title'],
                'body' => $payload['body'],
                'icon' => $payload['icon'],
                'click_action' => $payload['data']['link'],
            ],
            'data' => $payload['data'],
        ]);

        return $response->successful();
    }

    /**
     * Send to Apple Push Notification Service
     *
     * NOTE: This method is not yet implemented.
     * TODO: Requires APNS certificate setup and proper payload formatting for iOS devices.
     *       When implementing, use apple push notification HTTP2 API with JWT authentication.
     */
    protected function sendToAPNS(string $token, array $payload): bool
    {
        // PLANNED: APNS implementation would go here
        // This requires specific APNS certificates and configuration
        Log::warning('APNS push notification not implemented - falling back to FCM', [
            'token' => substr($token, 0, 10) . '...',
            'title' => $payload['title'] ?? '',
        ]);

        return false; // Not implemented - indicates failure
    }

    /**
     * Send to Web Push
     *
     * NOTE: This method is not yet implemented.
     * TODO: Requires web push subscription endpoint and VAPID key configuration.
     *       When implementing, use web-push library with VAPID authentication.
     */
    protected function sendToWebPush(string $subscription, array $payload): bool
    {
        // PLANNED: Web Push implementation would go here
        Log::warning('Web Push notification not implemented', [
            'subscription' => substr($subscription, 0, 50) . '...',
            'title' => $payload['title'] ?? '',
        ]);

        return false; // Not implemented - indicates failure
    }

    /**
     * Check if appointment status notification is critical
     */
    protected function isCriticalAppointmentStatus(Notification $notification): bool
    {
        $criticalStatuses = ['cancelled', 'no_show'];
        $status = $notification->data['new_status'] ?? '';

        return in_array($status, $criticalStatuses);
    }

    /**
     * Register push token for user
     */
    public function registerPushToken(User $user, string $token, string $provider = 'fcm'): bool
    {
        try {
            // In a real implementation, you'd store this in a user_push_tokens table
            // For now, we'll just update the user model directly
            $field = $provider . '_token';
            $user->update([$field => $token]);

            Log::info('Push token registered', [
                'user_id' => $user->id,
                'provider' => $provider,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to register push token', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Unregister push token for user
     */
    public function unregisterPushToken(User $user, string $provider = 'fcm'): bool
    {
        try {
            $field = $provider . '_token';
            $user->update([$field => null]);

            Log::info('Push token unregistered', [
                'user_id' => $user->id,
                'provider' => $provider,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to unregister push token', [
                'user_id' => $user->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
