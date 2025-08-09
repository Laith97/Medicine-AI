<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class TestNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;

        // Use realtime queue for instant processing
        $this->onQueue('realtime');
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->data['title'] ?? 'Test Notification',
            'message' => $this->data['message'] ?? 'This is a test notification',
            'icon' => $this->data['icon'] ?? 'info',
            'link' => $this->data['link'] ?? null,
            'type' => $this->data['type'] ?? 'general',
            'created_at' => now()->toDateTimeString(),
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->data['title'] ?? 'Test Notification',
            'message' => $this->data['message'] ?? 'This is a test notification',
            'icon' => $this->data['icon'] ?? 'info',
            'link' => $this->data['link'] ?? null,
            'type' => $this->data['type'] ?? 'general',
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => $this->data['type'] ?? 'test',
            'title' => $this->data['title'] ?? 'Test Notification',
            'message' => $this->data['message'] ?? 'This is a test notification',
            'body' => $this->data['message'] ?? 'This is a test notification',
            'icon' => $this->data['icon'] ?? 'info',
            'link' => $this->data['link'] ?? null,
            'created_at' => now()->toISOString()
        ]);
    }
}
