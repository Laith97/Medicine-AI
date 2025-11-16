<?php

namespace App\Listeners;

use App\Events\AppointmentStatusChangedEvent;
use App\Notifications\AppointmentStatusChangedNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendAppointmentStatusChangeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

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
        try {
            // Get users who should be notified
            $usersToNotify = $this->getUsersToNotify($event);

            foreach ($usersToNotify as $user) {
                // Check user preferences before sending notification
                if ($this->shouldSendNotification($user, $event)) {
                    // Send the notification
                    $notification = $user->notify(new AppointmentStatusChangedNotification(
                        $event->appointment,
                        $event->oldStatus,
                        $event->newStatus,
                        $event->changedBy
                    ));

                    // Send push notification for critical updates
                    if ($this->isCriticalStatusChange($event->oldStatus, $event->newStatus)) {
                        $this->sendPushNotification($user, $notification);
                    }

                    Log::info('Appointment status change notification sent', [
                        'appointment_id' => $event->appointment->id,
                        'user_id' => $user->id,
                        'old_status' => $event->oldStatus,
                        'new_status' => $event->newStatus,
                        'is_critical' => $this->isCriticalStatusChange($event->oldStatus, $event->newStatus),
                    ]);
                }
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
            $users = array_merge($users, $adminUsers->toArray());
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
    protected function sendPushNotification($user, $notification): void
    {
        try {
            $pushService = app(\App\Services\PushNotificationService::class);
            $pushService->sendCriticalPushNotification($user, $notification);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send critical push notification', [
                'user_id' => $user->id,
                'notification_id' => $notification->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
