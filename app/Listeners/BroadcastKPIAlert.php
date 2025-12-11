<?php

namespace App\Listeners;

use App\Events\KPIAlertTriggered;
use App\Services\RealtimeStreamingService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class BroadcastKPIAlert implements ShouldQueue
{
    use InteractsWithQueue;

    protected RealtimeStreamingService $streamingService;
    protected NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(
        RealtimeStreamingService $streamingService,
        NotificationService $notificationService
    ) {
        $this->streamingService = $streamingService;
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(KPIAlertTriggered $event): void
    {
        try {
            // Broadcast the alert via WebSocket
            $success = $this->streamingService->broadcastAlert(
                $event->alertData,
                $event->hospitalKey
            );

            if ($success) {
                Log::info('KPI alert broadcast successfully', [
                    'alert_id' => $event->alertData['alert_id'] ?? null,
                    'kpi_name' => $event->alertData['kpi_name'] ?? null,
                    'alert_level' => $event->alertData['alert_level'] ?? null,
                    'hospital_key' => $event->hospitalKey,
                    'event_id' => $event->eventId
                ]);
            } else {
                Log::warning('Failed to broadcast KPI alert', [
                    'alert_id' => $event->alertData['alert_id'] ?? null,
                    'kpi_name' => $event->alertData['kpi_name'] ?? null,
                    'hospital_key' => $event->hospitalKey,
                    'event_id' => $event->eventId
                ]);
            }

            // Send real-time notifications to relevant users
            $this->sendRealtimeNotifications($event);

            // Send email/SMS notifications for critical alerts
            if ($this->shouldSendPersistentNotification($event->alertData)) {
                $this->sendPersistentNotifications($event);
            }

        } catch (\Exception $e) {
            Log::error('Error broadcasting KPI alert', [
                'alert_id' => $event->alertData['alert_id'] ?? null,
                'kpi_name' => $event->alertData['kpi_name'] ?? null,
                'hospital_key' => $event->hospitalKey,
                'event_id' => $event->eventId,
                'error' => $e->getMessage()
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Send real-time notifications to relevant users
     */
    protected function sendRealtimeNotifications(KPIAlertTriggered $event): void
    {
        $recipients = $this->getAlertRecipients($event->alertData['alert_level'], $event->hospitalKey);

        $notificationData = [
            'title' => $this->getAlertTitle($event->alertData),
            'message' => $this->getAlertMessage($event->alertData),
            'type' => $this->getNotificationType($event->alertData['alert_level']),
            'alert_id' => $event->alertData['alert_id'],
            'kpi_name' => $event->alertData['kpi_name'],
            'current_value' => $event->alertData['current_value'],
            'threshold_breached' => $event->alertData['threshold_breached'],
            'recommended_actions' => $event->alertData['recommended_actions'],
            'send_push' => true,
        ];

        foreach ($recipients as $user) {
            try {
                $this->streamingService->sendRealtimeNotification($user, $notificationData);

                Log::info('Real-time alert notification sent', [
                    'user_id' => $user->id,
                    'alert_id' => $event->alertData['alert_id'],
                    'alert_level' => $event->alertData['alert_level']
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send real-time alert notification', [
                    'user_id' => $user->id,
                    'alert_id' => $event->alertData['alert_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Send persistent notifications (email/SMS) for critical alerts
     */
    protected function sendPersistentNotifications(KPIAlertTriggered $event): void
    {
        $recipients = $this->getAlertRecipients($event->alertData['alert_level'], $event->hospitalKey);

        foreach ($recipients as $user) {
            try {
                $this->notificationService->sendNotification($user, [
                    'title' => $this->getAlertTitle($event->alertData),
                    'message' => $this->getAlertMessage($event->alertData),
                    'type' => $this->getNotificationType($event->alertData['alert_level']),
                    'send_email' => true,
                    'send_sms' => $event->alertData['alert_level'] === 'critical',
                    'alert_data' => $event->alertData
                ]);

                Log::info('Persistent alert notification sent', [
                    'user_id' => $user->id,
                    'alert_id' => $event->alertData['alert_id'],
                    'alert_level' => $event->alertData['alert_level'],
                    'channels' => ['email', 'sms']
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send persistent alert notification', [
                    'user_id' => $user->id,
                    'alert_id' => $event->alertData['alert_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Determine if persistent notification should be sent
     */
    protected function shouldSendPersistentNotification(array $alertData): bool
    {
        return in_array($alertData['alert_level'], ['critical', 'warning']);
    }

    /**
     * Get alert recipients based on level and hospital
     */
    protected function getAlertRecipients(string $alertLevel, int $hospitalKey): \Illuminate\Database\Eloquent\Collection
    {
        $roles = $this->getRolesForAlertLevel($alertLevel);

        return \App\Models\User::whereHas('roles', function($query) use ($roles) {
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
     * Get alert title
     */
    protected function getAlertTitle(array $alertData): string
    {
        $kpiName = ucwords(str_replace('_', ' ', $alertData['kpi_name']));
        $level = ucfirst($alertData['alert_level']);

        return "{$level} Alert: {$kpiName}";
    }

    /**
     * Get alert message
     */
    protected function getAlertMessage(array $alertData): string
    {
        $kpiName = ucwords(str_replace('_', ' ', $alertData['kpi_name']));
        $value = number_format($alertData['current_value'], 2);
        $threshold = $alertData['threshold_breached'];

        return "{$kpiName} is at {$value} (breached {$threshold} threshold). " .
               "Recommended actions: " . implode(', ', $alertData['recommended_actions']);
    }

    /**
     * Get notification type based on alert level
     */
    protected function getNotificationType(string $alertLevel): string
    {
        return match($alertLevel) {
            'critical' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            default => 'info'
        };
    }

    /**
     * Get the queue name for the job
     */
    public function viaQueue(): string
    {
        return 'alerts';
    }

    /**
     * Get the middleware for the job
     */
    public function middleware(): array
    {
        return [
            // Add rate limiting for alert notifications
            \Illuminate\Queue\Middleware\RateLimited::class,
        ];
    }
}
