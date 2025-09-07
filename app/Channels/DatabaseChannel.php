<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Models\Notification as NotificationModel;

class DatabaseChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $notificationData = $notification->toDatabase($notifiable);

        NotificationModel::create([
            'user_id' => $notifiable->id,
            'type' => get_class($notification),
            'title' => $notificationData['title'] ?? 'Notification',
            'message' => $notificationData['message'] ?? $notificationData['body'] ?? 'You have a new notification',
            'data' => $notificationData,
            'read_at' => null,
            'created_at' => now(),
        ]);
    }
}
