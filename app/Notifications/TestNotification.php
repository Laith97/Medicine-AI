<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class TestNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $data;

    /**
     * Create a new notification instance.
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->data['title'] ?? 'Test Notification',
            'message' => $this->data['message'] ?? 'This is a test notification',
            'icon' => $this->data['icon'] ?? 'info',
            'link' => $this->data['link'] ?? null,
            'type' => $this->data['type'] ?? 'test',
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        Log::info('Broadcasting notification', [
            'notification_id' => $this->id,
            'notifiable_id' => $notifiable->id,
            'notifiable_type' => get_class($notifiable),
            'channel' => 'App.User.' . $notifiable->id,
            'data' => $this->data
        ]);

        return new BroadcastMessage([
            'id' => $this->id,
            'type' => $this->data['type'] ?? 'test',
            'title' => $this->data['title'] ?? 'Test Notification',
            'message' => $this->data['message'] ?? 'This is a test notification',
            'icon' => $this->data['icon'] ?? 'info',
            'link' => $this->data['link'] ?? null,
            'created_at' => now()->toISOString()
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->data['title'] ?? 'Test Notification',
            'message' => $this->data['message'] ?? 'This is a test notification',
            'icon' => $this->data['icon'] ?? 'info',
            'link' => $this->data['link'] ?? null,
            'type' => $this->data['type'] ?? 'test',
        ];
    }
}
