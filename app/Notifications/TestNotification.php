<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database'];
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
}
