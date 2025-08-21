<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $title;
    protected $message;
    protected $type;
    protected $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, string $type = 'warning', array $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_alert',
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->getIcon(),
            'link' => $this->data['link'] ?? null,
            'link_text' => $this->data['link_text'] ?? 'View Details',
            'related_type' => $this->data['related_type'] ?? null,
            'related_id' => $this->data['related_id'] ?? null,
            'data' => array_merge($this->data, [
                'alert_type' => $this->type,
                'created_at' => now()->format('Y-m-d H:i:s'),
            ])
        ];
    }

    /**
     * Get the appropriate icon based on alert type.
     */
    private function getIcon(): string
    {
        return match($this->type) {
            'error' => 'exclamation-circle',
            'warning' => 'exclamation-triangle',
            'success' => 'check-circle',
            'info' => 'info-circle',
            default => 'bell',
        };
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->message)
            ->action('View Details', $this->data['link'] ?? route('notifications.index'))
            ->line('This is an important system alert.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "System Alert: {$this->title}. {$this->message}. View details: " . ($this->data['link'] ?? route('notifications.index'));
    }

    public function toBroadcast(object $notifiable)
    {
        return [
            'id' => $this->id,
            'type' => 'system_alert',
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->getIcon(),
            'link' => $this->data['link'] ?? null,
            'link_text' => $this->data['link_text'] ?? 'View Details',
            'related_type' => $this->data['related_type'] ?? null,
            'related_id' => $this->data['related_id'] ?? null,
            'data' => array_merge($this->data, [
                'alert_type' => $this->type,
                'created_at' => now()->toISOString(),
            ])
        ];
    }
}
