<?php

namespace App\Services;

use App\Services\PusherConnectionPool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Models\User;

class RealtimeStreamingService
{
    protected PusherConnectionPool $pusherPool;
    protected array $activeSubscriptions = [];
    protected int $cacheTtl = 3600; // 1 hour

    public function __construct(PusherConnectionPool $pusherPool)
    {
        $this->pusherPool = $pusherPool;
    }

    /**
     * Broadcast KPI update to subscribed dashboards
     */
    public function broadcastKPIUpdate(string $kpiName, array $data, int $hospitalKey = 1): bool
    {
        $channels = $this->getKPISubscriptionChannels($kpiName, $hospitalKey);

        if (empty($channels)) {
            Log::info('No active subscriptions for KPI update', [
                'kpi' => $kpiName,
                'hospital_key' => $hospitalKey
            ]);
            return true;
        }

        $eventData = [
            'type' => 'kpi_update',
            'kpi_name' => $kpiName,
            'data' => $data,
            'hospital_key' => $hospitalKey,
            'timestamp' => now()->toISOString(),
            'event_id' => uniqid('kpi_', true)
        ];

        return $this->pusherPool->broadcast($channels, 'kpi.updated', $eventData);
    }

    /**
     * Broadcast alert notification to relevant users
     */
    public function broadcastAlert(array $alertData, int $hospitalKey = 1): bool
    {
        $channels = $this->getAlertSubscriptionChannels($alertData['alert_level'], $hospitalKey);

        $eventData = [
            'type' => 'alert_notification',
            'alert' => $alertData,
            'hospital_key' => $hospitalKey,
            'timestamp' => now()->toISOString(),
            'event_id' => uniqid('alert_', true)
        ];

        return $this->pusherPool->broadcast($channels, 'alert.triggered', $eventData);
    }

    /**
     * Subscribe user to dashboard real-time updates
     */
    public function subscribeToDashboard(User $user, string $dashboardId, array $kpis = []): bool
    {
        $subscriptionKey = "dashboard_sub_{$user->id}_{$dashboardId}";

        $subscription = [
            'user_id' => $user->id,
            'dashboard_id' => $dashboardId,
            'kpis' => $kpis,
            'subscribed_at' => now(),
            'last_activity' => now()
        ];

        Cache::put($subscriptionKey, $subscription, $this->cacheTtl);

        // Add to active subscriptions
        $this->activeSubscriptions[$subscriptionKey] = $subscription;

        Log::info('User subscribed to dashboard', [
            'user_id' => $user->id,
            'dashboard_id' => $dashboardId,
            'kpis_count' => count($kpis)
        ]);

        return true;
    }

    /**
     * Unsubscribe user from dashboard updates
     */
    public function unsubscribeFromDashboard(User $user, string $dashboardId): bool
    {
        $subscriptionKey = "dashboard_sub_{$user->id}_{$dashboardId}";

        Cache::forget($subscriptionKey);

        if (isset($this->activeSubscriptions[$subscriptionKey])) {
            unset($this->activeSubscriptions[$subscriptionKey]);
        }

        Log::info('User unsubscribed from dashboard', [
            'user_id' => $user->id,
            'dashboard_id' => $dashboardId
        ]);

        return true;
    }

    /**
     * Get subscription channels for KPI updates
     */
    protected function getKPISubscriptionChannels(string $kpiName, int $hospitalKey): array
    {
        $channels = [];

        // Get all active dashboard subscriptions
        $subscriptions = Cache::get('active_dashboard_subscriptions', []);

        foreach ($subscriptions as $subscription) {
            if ($subscription['hospital_key'] == $hospitalKey &&
                (empty($subscription['kpis']) || in_array($kpiName, $subscription['kpis']))) {

                $channelName = "dashboard.{$subscription['user_id']}.{$subscription['dashboard_id']}";
                $channels[] = $channelName;
            }
        }

        return array_unique($channels);
    }

    /**
     * Get subscription channels for alerts based on user roles
     */
    protected function getAlertSubscriptionChannels(string $alertLevel, int $hospitalKey): array
    {
        $channels = [];

        // Get users who should receive alerts for this level
        $recipients = $this->getAlertRecipients($alertLevel, $hospitalKey);

        foreach ($recipients as $user) {
            $channels[] = "alerts.{$user->id}";
            $channels[] = "notifications.{$user->id}";
        }

        return array_unique($channels);
    }

    /**
     * Get users who should receive alerts for a given level
     */
    protected function getAlertRecipients(string $alertLevel, int $hospitalKey): Collection
    {
        $roles = $this->getRolesForAlertLevel($alertLevel);

        return User::whereHas('roles', function($query) use ($roles) {
            $query->whereIn('name', $roles);
        })->get();
    }

    /**
     * Get roles that should receive alerts for each level
     */
    protected function getRolesForAlertLevel(string $alertLevel): array
    {
        switch ($alertLevel) {
            case 'critical':
                return ['admin', 'hospital_admin', 'manager'];
            case 'warning':
                return ['admin', 'hospital_admin', 'manager', 'supervisor'];
            default:
                return ['admin', 'hospital_admin'];
        }
    }

    /**
     * Broadcast dashboard refresh signal
     */
    public function broadcastDashboardRefresh(string $dashboardId, int $hospitalKey = 1): bool
    {
        $channels = ["dashboard.{$dashboardId}", "dashboard.{$dashboardId}.hospital.{$hospitalKey}"];

        $eventData = [
            'type' => 'dashboard_refresh',
            'dashboard_id' => $dashboardId,
            'hospital_key' => $hospitalKey,
            'timestamp' => now()->toISOString(),
            'event_id' => uniqid('refresh_', true)
        ];

        return $this->pusherPool->broadcast($channels, 'dashboard.refresh', $eventData);
    }

    /**
     * Send real-time notification to user
     */
    public function sendRealtimeNotification(User $user, array $notificationData): bool
    {
        $channel = "notifications.{$user->id}";

        $eventData = [
            'type' => 'notification',
            'notification' => $notificationData,
            'user_id' => $user->id,
            'timestamp' => now()->toISOString(),
            'event_id' => uniqid('notification_', true)
        ];

        return $this->pusherPool->broadcast([$channel], 'notification.received', $eventData);
    }

    /**
     * Update user activity timestamp for subscription management
     */
    public function updateUserActivity(User $user, string $dashboardId): void
    {
        $subscriptionKey = "dashboard_sub_{$user->id}_{$dashboardId}";

        $subscription = Cache::get($subscriptionKey);
        if ($subscription) {
            $subscription['last_activity'] = now();
            Cache::put($subscriptionKey, $subscription, $this->cacheTtl);
        }
    }

    /**
     * Clean up inactive subscriptions
     */
    public function cleanupInactiveSubscriptions(int $inactiveHours = 24): int
    {
        $inactiveTime = now()->subHours($inactiveHours);
        $cleaned = 0;

        $subscriptions = Cache::get('active_dashboard_subscriptions', []);

        foreach ($subscriptions as $key => $subscription) {
            if ($subscription['last_activity'] < $inactiveTime) {
                Cache::forget($key);
                unset($subscriptions[$key]);
                $cleaned++;
            }
        }

        Cache::put('active_dashboard_subscriptions', $subscriptions, $this->cacheTtl);

        Log::info('Cleaned up inactive subscriptions', [
            'cleaned_count' => $cleaned,
            'inactive_hours' => $inactiveHours
        ]);

        return $cleaned;
    }

    /**
     * Get active subscription statistics
     */
    public function getSubscriptionStats(): array
    {
        $subscriptions = Cache::get('active_dashboard_subscriptions', []);

        return [
            'total_active_subscriptions' => count($subscriptions),
            'subscriptions_by_dashboard' => collect($subscriptions)->groupBy('dashboard_id')->map->count(),
            'subscriptions_by_user' => collect($subscriptions)->groupBy('user_id')->map->count(),
            'cache_ttl' => $this->cacheTtl,
            'last_updated' => now()
        ];
    }

    /**
     * Health check for real-time streaming service
     */
    public function healthCheck(): array
    {
        $pusherHealth = $this->pusherPool->healthCheck();

        return [
            'service' => 'realtime_streaming',
            'status' => $pusherHealth['status'],
            'pusher_connection_pool' => $pusherHealth,
            'active_subscriptions' => count($this->activeSubscriptions),
            'cache_status' => Cache::store()->getStore() ? 'connected' : 'disconnected',
            'timestamp' => now()
        ];
    }
}
