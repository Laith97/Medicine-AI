<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AlertNotificationService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send notifications for an alert
     */
    public function sendAlertNotifications(Alert $alert, string $notificationType = 'initial'): void
    {
        $channels = $this->getNotificationChannels($alert, $notificationType);
        $recipients = $this->getAlertRecipients($alert, $notificationType);

        foreach ($channels as $channel) {
            try {
                $this->sendChannelNotification($alert, $channel, $recipients, $notificationType);
            } catch (\Exception $e) {
                Log::error("Failed to send alert notification via {$channel}", [
                    'alert_id' => $alert->id,
                    'channel' => $channel,
                    'notification_type' => $notificationType,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Record notification history
        $alert->addNotificationHistory('bulk', [
            'channels' => $channels,
            'recipients_count' => $recipients->count(),
            'notification_type' => $notificationType,
        ]);
    }

    /**
     * Get notification channels for alert
     */
    protected function getNotificationChannels(Alert $alert, string $notificationType): array
    {
        $ruleChannels = $alert->alertRule->notification_channels ?? [];

        // Add escalation-specific channels
        if ($notificationType === 'escalation') {
            $escalationRules = $alert->alertRule->getEscalationRules($alert->severity);
            if (isset($escalationRules[$alert->escalation_level]['channels'])) {
                $ruleChannels = array_merge($ruleChannels, $escalationRules[$alert->escalation_level]['channels']);
            }
        }

        // Default channels if none specified
        if (empty($ruleChannels)) {
            $ruleChannels = ['in_app'];
            if (in_array($alert->severity, ['critical', 'high'])) {
                $ruleChannels[] = 'email';
            }
        }

        return array_unique($ruleChannels);
    }

    /**
     * Get recipients for alert notification
     */
    protected function getAlertRecipients(Alert $alert, string $notificationType): Collection
    {
        $recipients = collect();

        // Get base recipients based on alert rule configuration
        $baseRecipients = $this->getBaseRecipients($alert);
        $recipients = $recipients->merge($baseRecipients);

        // Add escalation-specific recipients
        if ($notificationType === 'escalation') {
            $escalationRecipients = $this->getEscalationRecipients($alert);
            $recipients = $recipients->merge($escalationRecipients);
        }

        // Add acknowledgement/resolution recipients
        if (in_array($notificationType, ['acknowledged', 'resolved'])) {
            $statusRecipients = $this->getStatusUpdateRecipients($alert);
            $recipients = $recipients->merge($statusRecipients);
        }

        return $recipients->unique('id');
    }

    /**
     * Get base recipients for alert
     */
    protected function getBaseRecipients(Alert $alert): Collection
    {
        $recipients = collect();

        // Get recipients from alert rule metadata
        $ruleRecipients = $alert->alertRule->metadata['recipients'] ?? [];

        if (!empty($ruleRecipients)) {
            foreach ($ruleRecipients as $recipientConfig) {
                $users = $this->resolveRecipientConfig($recipientConfig);
                $recipients = $recipients->merge($users);
            }
        }

        // Default to admin users if no specific recipients
        if ($recipients->isEmpty()) {
            $adminUsers = User::where('role', 'admin')->get();
            $recipients = $recipients->merge($adminUsers);

            // Add hospital admins for high-severity alerts
            if (in_array($alert->severity, ['critical', 'high'])) {
                $hospitalAdmins = User::where('role', 'hospital_admin')->get();
                $recipients = $recipients->merge($hospitalAdmins);
            }
        }

        return $recipients;
    }

    /**
     * Get escalation recipients
     */
    protected function getEscalationRecipients(Alert $alert): Collection
    {
        $recipients = collect();

        $escalationRules = $alert->alertRule->getEscalationRules($alert->severity);
        $currentLevel = $escalationRules[$alert->escalation_level] ?? [];

        if (isset($currentLevel['recipients'])) {
            foreach ($currentLevel['recipients'] as $recipientConfig) {
                $users = $this->resolveRecipientConfig($recipientConfig);
                $recipients = $recipients->merge($users);
            }
        }

        // Add senior management for high escalation levels
        if ($alert->escalation_level >= 2) {
            $seniorUsers = User::whereIn('role', ['admin', 'hospital_admin'])
                ->where('is_senior_management', true)
                ->get();
            $recipients = $recipients->merge($seniorUsers);
        }

        return $recipients;
    }

    /**
     * Get recipients for status updates
     */
    protected function getStatusUpdateRecipients(Alert $alert): Collection
    {
        $recipients = collect();

        // Notify the user who triggered the alert (if different from resolver/acknowledger)
        if ($alert->model && method_exists($alert->model, 'getOwner')) {
            $owner = $alert->model->getOwner();
            if ($owner && $owner->id !== ($alert->acknowledged_by ?? $alert->resolved_by)) {
                $recipients->push($owner);
            }
        }

        // Notify escalation subscribers
        $escalationSubscribers = $this->getEscalationSubscribers($alert);
        $recipients = $recipients->merge($escalationSubscribers);

        return $recipients;
    }

    /**
     * Resolve recipient configuration to users
     */
    protected function resolveRecipientConfig(array $config): Collection
    {
        $type = $config['type'] ?? 'role';
        $value = $config['value'] ?? null;

        switch ($type) {
            case 'role':
                return User::where('role', $value)->get();

            case 'user_id':
                $user = User::find($value);
                return $user ? collect([$user]) : collect();

            case 'department':
                return User::where('department', $value)->get();

            case 'user_ids':
                return User::whereIn('id', (array)$value)->get();

            default:
                return collect();
        }
    }

    /**
     * Get escalation subscribers
     */
    protected function getEscalationSubscribers(Alert $alert): Collection
    {
        // Get users who have subscribed to escalation notifications
        return User::where('alert_escalation_subscriber', true)->get();
    }

    /**
     * Send notification via specific channel
     */
    protected function sendChannelNotification(
        Alert $alert,
        string $channel,
        Collection $recipients,
        string $notificationType
    ): void {
        $notificationData = $this->buildNotificationData($alert, $notificationType);

        foreach ($recipients as $user) {
            try {
                $this->sendToUser($user, $channel, $notificationData, $alert);
            } catch (\Exception $e) {
                Log::error("Failed to send alert notification to user", [
                    'alert_id' => $alert->id,
                    'user_id' => $user->id,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build notification data for alert
     */
    protected function buildNotificationData(Alert $alert, string $notificationType): array
    {
        $baseData = [
            'title' => $this->getNotificationTitle($alert, $notificationType),
            'message' => $this->getNotificationMessage($alert, $notificationType),
            'type' => $this->getNotificationType($alert, $notificationType),
            'alert_id' => $alert->id,
            'alert_severity' => $alert->severity,
            'alert_priority_score' => $alert->priority_score,
        ];

        // Add channel-specific data
        switch ($notificationType) {
            case 'escalation':
                $baseData['escalation_level'] = $alert->escalation_level;
                break;
            case 'acknowledged':
                $baseData['acknowledged_by'] = $alert->acknowledgedBy?->name;
                break;
            case 'resolved':
                $baseData['resolved_by'] = $alert->resolvedBy?->name;
                break;
        }

        return $baseData;
    }

    /**
     * Get notification title
     */
    protected function getNotificationTitle(Alert $alert, string $notificationType): string
    {
        $prefix = match ($notificationType) {
            'escalation' => '🚨 ESCALATED: ',
            'acknowledged' => '✅ ACKNOWLEDGED: ',
            'resolved' => '✅ RESOLVED: ',
            default => '',
        };

        return $prefix . $alert->title;
    }

    /**
     * Get notification message
     */
    protected function getNotificationMessage(Alert $alert, string $notificationType): string
    {
        $baseMessage = $alert->message;

        switch ($notificationType) {
            case 'escalation':
                return $baseMessage . " (Escalated to level {$alert->escalation_level})";
            case 'acknowledged':
                return $baseMessage . " (Acknowledged by {$alert->acknowledgedBy?->name})";
            case 'resolved':
                return $baseMessage . " (Resolved by {$alert->resolvedBy?->name})";
            default:
                return $baseMessage;
        }
    }

    /**
     * Get notification type for UI
     */
    protected function getNotificationType(Alert $alert, string $notificationType): string
    {
        if ($notificationType === 'escalation') {
            return 'alert_escalation';
        }

        return match ($alert->severity) {
            'critical' => 'alert_critical',
            'high' => 'alert_high',
            'medium' => 'alert_medium',
            'low' => 'alert_low',
            'info' => 'alert_info',
            default => 'alert',
        };
    }

    /**
     * Send notification to specific user via channel
     */
    protected function sendToUser(User $user, string $channel, array $data, Alert $alert): void
    {
        $channelData = $data;

        // Add channel-specific flags
        switch ($channel) {
            case 'email':
                $channelData['send_email'] = true;
                break;
            case 'sms':
                $channelData['send_sms'] = true;
                break;
            case 'push':
                $channelData['send_push'] = true;
                break;
            case 'in_app':
                // In-app notifications are always sent
                break;
        }

        // Add action URL for web interface
        $channelData['action_url'] = route('alerts.show', $alert->id);
        $channelData['link_text'] = 'View Alert Details';

        $success = $this->notificationService->sendNotification($user, $channelData);

        if ($success) {
            Log::info('Alert notification sent', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
                'channel' => $channel,
                'notification_type' => $data['type'] ?? 'unknown',
            ]);
        }
    }

    /**
     * Send bulk notifications for multiple alerts
     */
    public function sendBulkNotifications(Collection $alerts, string $notificationType = 'initial'): void
    {
        foreach ($alerts as $alert) {
            try {
                $this->sendAlertNotifications($alert, $notificationType);
            } catch (\Exception $e) {
                Log::error('Failed to send bulk alert notification', [
                    'alert_id' => $alert->id,
                    'notification_type' => $notificationType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(User $user, array $testData = []): bool
    {
        $testAlertData = array_merge([
            'title' => 'Test Alert Notification',
            'message' => 'This is a test alert notification to verify the system is working correctly.',
            'severity' => 'info',
            'type' => 'test_alert',
        ], $testData);

        return $this->notificationService->sendNotification($user, $testAlertData);
    }
}
