<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TelehealthAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;
    protected $appointmentId;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $message, int $appointmentId)
    {
        $this->message = $message;
        $this->appointmentId = $appointmentId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'sms'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Telehealth Alert')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->message)
            ->line('Appointment ID: ' . $this->appointmentId)
            ->action('View Appointment', route('appointments.show', $this->appointmentId))
            ->line('Please review the patient\'s condition.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return $this->message . ' Appointment ID: ' . $this->appointmentId . '. View: ' . route('appointments.show', $this->appointmentId);
    }
}
