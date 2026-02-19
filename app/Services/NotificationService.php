<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private static array $userPreferences = [];

    /**
     * Clear all user preferences (for testing)
     */
    public function clearAllPreferences(): void
    {
        self::$userPreferences = [];
    }
    /**
     * Create a new notification for a user
     */
    public function createNotification(User $user, array $data): \App\Models\Notification
    {
        $notification = new \App\Models\Notification();
        $notification->fill([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => get_class($this) . '@' . $data['type'] ?? 'general',
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'data' => [
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $data['type'] ?? 'info',
                'icon' => $data['icon'] ?? 'info',
                'link' => $data['action_url'] ?? $data['link'] ?? null,
                'link_text' => $data['link_text'] ?? 'View',
                'created_at' => now()->toDateTimeString(),
            ],
            'read_at' => null,
        ])->save();

        return $notification;
    }

    /**
     * Send email notification
     */
    public function sendEmailNotification(User $user, Notification $notification): void
    {
        try {
            // Send the actual email
            Mail::to($user->email)->send(
                new \App\Mail\NotificationMail($user, $notification)
            );

            Log::info('Email notification sent', [
                'user_id' => $user->id ?? null,
                'notification_id' => $notification->id ?? null,
                'title' => $notification->title ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $user->id ?? null,
                'notification_id' => $notification->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send subscription notification
     */
    public function sendSubscriptionNotification(User $user, string $type, array $data = []): Notification
    {
        $titles = [
            'subscription_created' => 'Subscription Created',
            'subscription_updated' => 'Subscription Updated',
            'subscription_cancelled' => 'Subscription Cancelled',
            'payment_failed' => 'Payment Failed',
        ];

        $messages = [
            'subscription_created' => 'Your subscription has been successfully created.',
            'subscription_updated' => 'Your subscription has been updated.',
            'subscription_cancelled' => 'Your subscription has been cancelled.',
            'payment_failed' => 'Your payment has failed. Please update your payment method.',
        ];

        return $this->createNotification($user, [
            'title' => $titles[$type] ?? 'Notification',
            'message' => $messages[$type] ?? 'You have a new notification.',
            'type' => $type === 'payment_failed' ? 'error' : 'info',
            'send_email' => true,
        ]);
    }

    /**
     * Send usage notification
     */
    public function sendUsageNotification(User $user, string $type, array $data = []): Notification
    {
        $titles = [
            'usage_limit_reached' => 'Usage Limit Reached',
            'usage_warning' => 'Usage Warning',
        ];

        $messages = [
            'usage_limit_reached' => 'You have reached your monthly usage limit.',
            'usage_warning' => 'You are approaching your monthly usage limit.',
        ];

        return $this->createNotification($user, [
            'title' => $titles[$type] ?? 'Usage Notification',
            'message' => $messages[$type] ?? 'Usage notification.',
            'type' => 'warning',
            'send_email' => true,
        ]);
    }

    /**
     * Send payment notification
     */
    public function sendPaymentNotification(User $user, string $type, array $data = []): Notification
    {
        $titles = [
            'payment_successful' => 'Payment Successful',
            'payment_failed' => 'Payment Failed',
            'invoice_created' => 'New Invoice',
        ];

        $messages = [
            'payment_successful' => 'Your payment has been processed successfully.',
            'payment_failed' => 'Your payment has failed. Please check your payment method.',
            'invoice_created' => 'A new invoice has been created for your account.',
        ];

        return $this->createNotification($user, [
            'title' => $titles[$type] ?? 'Payment Notification',
            'message' => $messages[$type] ?? 'Payment notification.',
            'type' => $type === 'payment_failed' ? 'error' : 'success',
            'send_email' => true,
        ]);
    }

    /**
     * Get notifications for a user
     */
    public function getNotifications(User $user, int $limit = 10)
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): bool
    {
        $notification->is_read = true;
        $notification->read_at = now();
        return $notification->save();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): bool
    {
        $updated = $user->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return $updated > 0;
    }

    /**
     * Delete notification
     */
    public function deleteNotification(Notification $notification): bool
    {
        return $notification->delete();
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(User $user, int $limit = 50)
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications(User $user)
    {
        return $user->notifications()
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Send notification (general method)
     */
    public function sendNotification(User $user, array $data): bool
    {
        try {
            // Check rate limiting (max 5 notifications per minute)
            $recentNotifications = $user->notifications()
                ->where('created_at', '>=', now()->subMinute())
                ->count();

            if ($recentNotifications >= 5) {
                Log::warning('Notification rate limit exceeded', [
                    'user_id' => $user->id,
                    'recent_count' => $recentNotifications,
                ]);
                return false;
            }

            $notification = $this->createNotification($user, $data);
            $preferences = $this->getNotificationPreferences($user);

            // Broadcast notification via Pusher for real-time delivery
            $this->broadcastNotification($user, $data);

            // Send via different channels based on data and user preferences
            if (isset($data['send_email']) && $data['send_email'] && $preferences['email_notifications']) {
                $this->sendEmailNotification($user, $notification);
            }

            if (isset($data['send_push']) && $data['send_push'] && $preferences['push_notifications']) {
                $this->sendPushNotification($user, $notification);
            }

            if (isset($data['send_sms']) && $data['send_sms'] && $preferences['sms_notifications']) {
                $this->sendSmsNotification($user, $notification);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Broadcast notification via Pusher for real-time delivery
     */
    public function broadcastNotification(User $user, array $notificationData): bool
    {
        try {
            event(new \App\Events\NotificationBroadcast($user->id, $notificationData));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send push notification
     */
    public function sendPushNotification(User $user, Notification $notification): void
    {
        try {
            $pushService = app(PushNotificationService::class);

            // Check if this is a critical notification
            $isCritical = $this->isCriticalNotification($notification);

            if ($isCritical) {
                $pushService->sendCriticalPushNotification($user, $notification);
            } elseif ($this->isAppointmentStatusNotification($notification)) {
                $pushService->sendAppointmentStatusPushNotification($user, $notification);
            } else {
                $pushService->sendPushNotification($user, $notification);
            }

            Log::info('Push notification sent', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'title' => $notification->title,
                'is_critical' => $isCritical,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if notification is critical
     */
    protected function isCriticalNotification(Notification $notification): bool
    {
        $criticalTypes = [
            'payment_failed',
            'system_alert',
            'appointment_cancelled',
            'appointment_no_show',
        ];

        $type = $notification->data['type'] ?? '';
        $newStatus = $notification->data['new_status'] ?? '';

        return in_array($type, $criticalTypes) ||
               in_array($newStatus, ['cancelled', 'no_show']);
    }

    /**
     * Check if notification is appointment status related
     */
    protected function isAppointmentStatusNotification(Notification $notification): bool
    {
        return ($notification->data['type'] ?? '') === 'appointment_status_changed' ||
               ($notification->data['related_type'] ?? '') === 'appointment';
    }

    /**
     * Send SMS notification
     */
    public function sendSmsNotification(User $user, Notification $notification): void
    {
        try {
            // Here you would integrate with SMS service
            // For now, we'll just log it
            Log::info('SMS notification sent', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'title' => $notification->title,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete old notifications
     */
    public function deleteOldNotifications(User $user, int $daysOld = 30): int
    {
        return $user->notifications()
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Send bulk notification to multiple users
     */
    public function sendBulkNotification(array $userIds, array $data): int
    {
        $sent = 0;
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user && $this->sendNotification($user, $data)) {
                $sent++;
            }
        }
        return $sent;
    }

    /**
     * Send scheduled notification
     */
    public function sendScheduledNotification(User $user, array $data, \DateTime $scheduledAt): bool
    {
        // For now, just send immediately if scheduled time has passed
        if ($scheduledAt <= new \DateTime()) {
            return $this->sendNotification($user, $data);
        }

        // In a real implementation, you would queue this for later
        Log::info('Notification scheduled', [
            'user_id' => $user->id,
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Schedule notification (alias for sendScheduledNotification)
     */
    public function scheduleNotification(User $user, array $data): bool
    {
        $scheduledAt = isset($data['scheduled_at']) ? new \DateTime($data['scheduled_at']) : new \DateTime();

        // Dispatch the job to be executed at the scheduled time
        \App\Jobs\SendScheduledNotification::dispatch($user, $data)
            ->delay($scheduledAt);

        return true;
    }

    /**
     * Get notification preferences for a user
     */
    public function getNotificationPreferences(User $user): array
    {
        // Return stored preferences or defaults
        return self::$userPreferences[$user->id] ?? [
            'email_notifications' => true,
            'push_notifications' => true,
            'sms_notifications' => false,
            'notification_frequency' => 'immediate',
        ];
    }

    /**
     * Update notification preferences for a user
     */
    public function updateNotificationPreferences(User $user, array $preferences): bool
    {
        // Store preferences in memory for testing
        $currentPreferences = $this->getNotificationPreferences($user);
        self::$userPreferences[$user->id] = array_merge($currentPreferences, $preferences);

        Log::info('Notification preferences updated', [
            'user_id' => $user->id,
            'preferences' => $preferences,
        ]);

        return true;
    }

    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder(User $user, array $appointmentData): bool
    {
        return $this->sendNotification($user, [
            'title' => 'Appointment Reminder',
            'message' => "You have an appointment with {$appointmentData['doctor_name']} on {$appointmentData['appointment_date']}",
            'type' => 'appointment_reminder',
            'send_email' => true,
        ]);
    }

    /**
     * Send subscription expiry warning
     */
    public function sendSubscriptionExpiryWarning(User $user, array $expiryData): bool
    {
        return $this->sendNotification($user, [
            'title' => 'Subscription Expiring Soon',
            'message' => "Your {$expiryData['plan_name']} subscription will expire on {$expiryData['expires_at']->format('M j, Y')}",
            'type' => 'subscription_expiry',
            'send_email' => true,
        ]);
    }

    /**
     * Send usage limit warning
     */
    public function sendUsageLimitWarning(User $user, array $usageData): bool
    {
        return $this->sendNotification($user, [
            'title' => 'Usage Limit Warning',
            'message' => "You have used " . ($usageData['current_usage'] ?? 0) . " of " . ($usageData['limit'] ?? 0) . " " . ($usageData['usage_type'] ?? 'tokens'),
            'type' => 'usage_warning',
            'send_email' => true,
        ]);
    }

    /**
     * Send payment failed notification
     */
    public function sendPaymentFailedNotification(User $user, array $paymentData): bool
    {
        return $this->sendNotification($user, [
            'title' => 'Payment Failed',
            'message' => "Your payment for {$paymentData['payment_method']} has failed. Please update your payment method.",
            'type' => 'payment_failed',
            'send_email' => true,
        ]);
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStatistics(User $user): array
    {
        $notifications = $user->notifications();

        return [
            'total_notifications' => $notifications->count(),
            'unread_notifications' => $notifications->where('is_read', false)->count(),
            'notifications_by_type' => [
                'info' => $notifications->where('type', 'info')->count(),
                'success' => $notifications->where('type', 'success')->count(),
                'warning' => $notifications->where('type', 'warning')->count(),
                'error' => $notifications->where('type', 'error')->count(),
            ],
        ];
    }

    /**
     * Render notification template
     */
    public function renderTemplate(string $template, array $data): string
    {
        $templates = [
            'appointment_reminder' => "Hello {$data['user_name']}, you have an appointment on {$data['appointment_date']}.",
            'subscription_expiry' => "Your subscription will expire soon.",
            'payment_failed' => "Your payment has failed. Please update your payment method.",
        ];

        return $templates[$template] ?? 'Notification message';
    }
    /**
     * Send grace period reminder notification
     */
    public function sendGracePeriodReminder(User $user, $setting): bool
    {
        return $this->sendNotification($user, [
            'title' => 'Grace Period Reminder',
            'message' => 'Your subscription is approaching its renewal date. Please update your payment method to avoid service interruption.',
            'type' => 'grace_period_reminder',
            'send_email' => true,
        ]);
    }

    /**
     * Send warning period reminder notification
     */
    public function sendWarningPeriodReminder(User $user, $setting): bool
    {
        return $this->sendNotification($user, [
            'title' => 'Subscription Renewal Warning',
            'message' => 'Your subscription will expire soon. Please update your payment method to continue using our services.',
            'type' => 'warning_period_reminder',
            'send_email' => true,
        ]);
    }

    /**
     * Send waitlist slot available notification
     */
    public function sendWaitlistSlotAvailableNotification(User $user, $waitlistEntry): bool
    {
        $preferences = $user->notificationPreferences;

        if (!$preferences || !$preferences->waitlist_slot_available) {
            return false;
        }

        // Check quiet hours
        if ($preferences->isQuietHoursActive()) {
            return false;
        }

        try {
            $user->notify(new \App\Notifications\WaitlistSlotAvailableNotification($waitlistEntry));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send waitlist slot available notification', [
                'user_id' => $user->id,
                'waitlist_entry_id' => $waitlistEntry->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send waitlist offer expiring notification
     */
    public function sendWaitlistOfferExpiringNotification(User $user, $waitlistEntry): bool
    {
        $preferences = $user->notificationPreferences;

        if (!$preferences || !$preferences->waitlist_offer_expiring) {
            return false;
        }

        // Check quiet hours
        if ($preferences->isQuietHoursActive()) {
            return false;
        }

        try {
            $user->notify(new \App\Notifications\WaitlistOfferExpiringNotification($waitlistEntry));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send waitlist offer expiring notification', [
                'user_id' => $user->id,
                'waitlist_entry_id' => $waitlistEntry->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send waitlist position update notification
     */
    public function sendWaitlistPositionUpdateNotification(User $user, $waitlistEntry, int $oldPosition, int $newPosition): bool
    {
        $preferences = $user->notificationPreferences;

        if (!$preferences || !$preferences->waitlist_position_update) {
            return false;
        }

        // Check quiet hours
        if ($preferences->isQuietHoursActive()) {
            return false;
        }

        try {
            $user->notify(new \App\Notifications\WaitlistPositionUpdateNotification($waitlistEntry, $oldPosition, $newPosition));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send waitlist position update notification', [
                'user_id' => $user->id,
                'waitlist_entry_id' => $waitlistEntry->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send waitlist auto-booked notification
     */
    public function sendWaitlistAutoBookedNotification(User $user, $appointment): bool
    {
        $preferences = $user->notificationPreferences;

        if (!$preferences || !$preferences->waitlist_auto_booked) {
            return false;
        }

        // Check quiet hours
        if ($preferences->isQuietHoursActive()) {
            return false;
        }

        try {
            $user->notify(new \App\Notifications\WaitlistAutoBookedNotification($appointment));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send waitlist auto-booked notification', [
                'user_id' => $user->id,
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send waitlist expired notification
     */
    public function sendWaitlistExpiredNotification(User $user, $waitlistEntry): bool
    {
        $preferences = $user->notificationPreferences;

        if (!$preferences || !$preferences->waitlist_expired) {
            return false;
        }

        // Check quiet hours
        if ($preferences->isQuietHoursActive()) {
            return false;
        }

        try {
            $user->notify(new \App\Notifications\WaitlistExpiredNotification($waitlistEntry));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send waitlist expired notification', [
                'user_id' => $user->id,
                'waitlist_entry_id' => $waitlistEntry->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
