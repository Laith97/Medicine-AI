<?php

namespace App\Notifications;

use App\Models\Kiosk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;

class KioskOffline extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public Kiosk $kiosk
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'kiosk_offline',
            'kiosk_id' => $this->kiosk->id,
            'kiosk_name' => $this->kiosk->name,
            'kiosk_serial' => $this->kiosk->serial_number,
            'kiosk_location' => $this->kiosk->location,
            'title' => 'Kiosk Offline',
            'message' => "Kiosk {$this->kiosk->name} ({$this->kiosk->serial_number}) is offline.",
            'link' => "/admin/kiosks/{$this->kiosk->id}",
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $lastPing = $this->kiosk->last_ping ? $this->kiosk->last_ping->format('M j, Y g:i A') : 'Never';

        return (new MailMessage)
            ->subject('Kiosk Offline Alert')
            ->greeting('Kiosk Offline Alert')
            ->line('A kiosk has been detected as offline and may require attention.')
            ->line('**Kiosk Details:**')
            ->line("Name: {$this->kiosk->name}")
            ->line("Serial Number: {$this->kiosk->serial_number}")
            ->line("Location: " . ($this->kiosk->location ?? 'Not specified'))
            ->line("Status: {$this->kiosk->status}")
            ->line("Last Ping: {$lastPing}")
            ->action('View Kiosk Details', url("/admin/kiosks/{$this->kiosk->id}"))
            ->line('Please check the kiosk connectivity and ensure it is functioning properly.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kiosk_offline',
            'title' => 'Kiosk Offline',
            'message' => "Kiosk {$this->kiosk->name} ({$this->kiosk->serial_number}) is offline.",
            'data' => [
                'kiosk_id' => $this->kiosk->id,
                'kiosk_name' => $this->kiosk->name,
                'kiosk_serial' => $this->kiosk->serial_number,
                'kiosk_location' => $this->kiosk->location,
                'kiosk_status' => $this->kiosk->status,
                'last_ping' => $this->kiosk->last_ping?->toISOString(),
            ],
            'action_url' => "/admin/kiosks/{$this->kiosk->id}",
            'action_text' => 'View Kiosk',
        ];
    }

    /**
     * Get the channels the notification should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.User.' . ($this->notifiable?->id ?? 'default'))];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'kiosk-offline';
    }
}
