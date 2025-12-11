<?php

namespace App\Notifications;

use App\Models\KioskSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KioskSessionTimeout extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public KioskSession $session
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kiosk Session Timeout Alert')
            ->greeting('Kiosk Session Timeout')
            ->line('A kiosk session has timed out and was automatically ended.')
            ->line('**Session Details:**')
            ->line("Session ID: {$this->session->session_id}")
            ->line("Kiosk: {$this->session->kiosk->name} ({$this->session->kiosk->serial_number})")
            ->line("Location: " . ($this->session->kiosk->location ?? 'Not specified'))
            ->line("Started: {$this->session->start_time->format('M j, Y g:i A')}")
            ->line("Duration: {$this->session->getDurationInMinutes()} minutes")
            ->line("Check-ins: {$this->session->checkins->count()}")
            ->line("Payments: {$this->session->payments->count()}")
            ->action('View Kiosk Details', url("/admin/kiosks/{$this->session->kiosk->id}"))
            ->line('Please check the kiosk to ensure it is functioning properly.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kiosk_session_timeout',
            'title' => 'Kiosk Session Timeout',
            'message' => "Session {$this->session->session_id} on kiosk {$this->session->kiosk->name} has timed out.",
            'data' => [
                'session_id' => $this->session->session_id,
                'kiosk_id' => $this->session->kiosk_id,
                'kiosk_name' => $this->session->kiosk->name,
                'kiosk_serial' => $this->session->kiosk->serial_number,
                'start_time' => $this->session->start_time->toISOString(),
                'duration_minutes' => $this->session->getDurationInMinutes(),
                'checkin_count' => $this->session->checkins->count(),
                'payment_count' => $this->session->payments->count(),
            ],
            'action_url' => "/admin/kiosks/{$this->session->kiosk->id}",
            'action_text' => 'View Kiosk',
        ];
    }
}
