<?php

namespace App\Listeners;

use App\Events\AppointmentStatusChangedEvent;
use App\Notifications\AppointmentStatusChangedNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendAppointmentStatusChangeNotification
{
    protected $notificationService;

    // Track recently processed events to prevent duplicate notifications
    protected static array $processedEvents = [];
    protected static int $dedupeWindowSeconds = 2;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(AppointmentStatusChangedEvent $event): void
    {
        // Deduplication: Create a unique key for this event
        $eventKey = $event->appointment->id . '-' . $event->oldStatus . '-' . $event->newStatus;
        $now = time();

        // Clean old entries
        foreach (self::$processedEvents as $key => $timestamp) {
            if (($now - $timestamp) > self::$dedupeWindowSeconds) {
                unset(self::$processedEvents[$key]);
            }
        }

        // Skip if already processed recently
        if (isset(self::$processedEvents[$eventKey])) {
            Log::info('Duplicate event detected, skipping', [
                'appointment_id' => $event->appointment->id,
                'event_key' => $eventKey,
            ]);
            return;
        }

        // Mark as processed
        self::$processedEvents[$eventKey] = $now;

        try {
            // Get users who should be notified
            $usersToNotify = $this->getUsersToNotify($event);

            foreach ($usersToNotify as $user) {
                // NOTE: We no longer check user preferences here because:
                // 1. This notification uses 'database' channel only (see via())
                // 2. User preferences should control real-time/Pusher channels, not database persistence
                // 3. Users expect to see ALL notifications in their notification center, not just ones they "opted in" to
                // 4. If a doctor misses a notification because preferences were off, they lose important clinical information
                // Send the notification (always persisted to database)
                $notificationInstance = new AppointmentStatusChangedNotification(
                    $event->appointment,
                    $event->oldStatus,
                    $event->newStatus,
                    $event->changedBy
                );

                $user->notify($notificationInstance);

                Log::info('Appointment status change notification sent', [
                    'appointment_id' => $event->appointment->id,
                    'user_id' => $user->id,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                    'notification_class' => get_class($notificationInstance),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send appointment status change notification', [
                'appointment_id' => $event->appointment->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get users who should be notified about this status change
     */
    protected function getUsersToNotify(AppointmentStatusChangedEvent $event): array
    {
        $users = [];

        // Always notify the doctor if they exist
        if ($event->appointment->doctor && $event->appointment->doctor->user) {
            $users[] = $event->appointment->doctor->user;
        }

        // Notify the patient if they are a registered user
        if ($event->appointment->patient_id && $event->appointment->patient) {
            $users[] = $event->appointment->patient;
        }

        // For critical status changes, also notify admins
        if ($this->isCriticalStatusChange($event->oldStatus, $event->newStatus)) {
            $adminUsers = \App\Models\User::whereIn('role', ['admin', 'hospital_admin'])->get();
            // Merge User collections properly - use toArray() to get array but keep User objects
            $users = array_merge($users, $adminUsers->all());
        }

        // Remove duplicates
        $uniqueUsers = [];
        $userIds = [];
        foreach ($users as $user) {
            if (!in_array($user->id, $userIds)) {
                $uniqueUsers[] = $user;
                $userIds[] = $user->id;
            }
        }

        return $uniqueUsers;
    }

    /**
     * Check if user preferences allow this notification
     */
    protected function shouldSendNotification($user, AppointmentStatusChangedEvent $event): bool
    {
        // Get user notification preferences
        $preferences = $user->notificationPreferences;

        if (!$preferences) {
            // Default to true if no preferences set
            return true;
        }

        // Check if real-time notifications are enabled
        if (!$preferences->in_app_enabled) {
            return false;
        }

        // Check quiet hours
        if ($preferences->isQuietHoursActive()) {
            return false;
        }

        // Check specific preferences based on status change
        $statusPreference = $this->getStatusPreference($event->newStatus);
        if ($statusPreference && !$preferences->$statusPreference) {
            return false;
        }

        // For critical status changes, check critical alerts preference
        if ($this->isCriticalStatusChange($event->oldStatus, $event->newStatus)) {
            return $preferences->realtime_critical_alerts && $preferences->push_critical_updates;
        }

        // Check real-time appointment updates preference
        return $preferences->realtime_appointment_updates;
    }

    /**
     * Get the preference field name for a specific status
     */
    protected function getStatusPreference(string $newStatus): ?string
    {
        return match($newStatus) {
            'confirmed' => 'appointment_confirmed',
            'cancelled' => 'appointment_cancelled',
            'completed' => 'appointment_completed',
            'no_show' => 'appointment_no_show',
            default => 'appointment_status_changed'
        };
    }

    /**
     * Determine if this is a critical status change that should always be notified
     */
    protected function isCriticalStatusChange(string $oldStatus, string $newStatus): bool
    {
        $criticalStatuses = ['cancelled', 'no_show'];

        return in_array($newStatus, $criticalStatuses) ||
               ($oldStatus === 'confirmed' && $newStatus === 'cancelled');
    }

    /**
     * Send push notification for critical updates
     */
    protected function sendPushNotification($user, $notificationInstance): void
    {
        try {
            // Skip push notifications for now since we need the database notification model
            // The push notification service expects App\Models\Notification, not the notification class
            \Illuminate\Support\Facades\Log::info('Push notification skipped - requires database notification model', [
                'user_id' => $user->id,
                'notification_class' => get_class($notificationInstance),
            ]);
            
            // TODO: Implement proper push notification after notification is saved to database
            // $pushService = app(\App\Services\PushNotificationService::class);
            // $pushService->sendCriticalPushNotification($user, $databaseNotification);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send critical push notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
