<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Appointment;

class AppointmentBookedNotification extends Notification
{
    use Queueable;

    protected $appointment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'sms'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';

        return [
            'type' => 'appointment_booked',
            'title' => 'New Appointment Booked',
            'message' => "A new appointment has been booked with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')}",
            'icon' => 'calendar',
            'link' => route('appointments.show', $this->appointment->id),
            'link_text' => 'View Appointment',
            'related_type' => 'appointment',
            'related_id' => $this->appointment->id,
            'data' => [
                'appointment_id' => $this->appointment->id,
                'doctor_name' => $doctorName,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
                'appointment_type' => $this->appointment->appointment_type,
            ]
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';

        return (new MailMessage)
            ->subject('New Appointment Booked')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("A new appointment has been booked with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')}")
            ->line('Appointment Type: ' . $this->appointment->appointment_type)
            ->action('View Appointment', route('appointments.show', $this->appointment->id))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';

        return "New appointment booked with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')}. View details: " . route('appointments.show', $this->appointment->id);
    }
}
