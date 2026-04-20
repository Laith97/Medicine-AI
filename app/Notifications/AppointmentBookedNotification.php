<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use App\Models\Appointment;
use App\Services\NotificationCompressionService;

class AppointmentBookedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $appointment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;

        // Use realtime queue for instant processing
        $this->onQueue('realtime');

        // Ensure notification is broadcast immediately
        $this->delay(0);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail', 'sms'];
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
    public function toSms(object $notifiable): array
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';
        $doctorId = $this->appointment->doctor->id ?? 0;
        $hospitalId = $this->appointment->doctor->hospital_id ?? 0;

        return [
            'message' => "New appointment booked with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')}. View details: " . route('appointments.show', $this->appointment->id),
            'options' => [
                'doctor_id' => $doctorId,
                'hospital_id' => $hospitalId,
                'context' => 'appointment_booked',
                'context_id' => $this->appointment->id,
            ]
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';
        $doctorId = $this->appointment->doctor->id ?? 0;

        $payload = [
            'id' => $this->id,
            'type' => 'appointment_booked',
            'title' => 'New Appointment Booked',
            'message' => "A new appointment has been booked with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')}",
            'body' => "A new appointment has been booked with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')}",
            'icon' => 'calendar',
            'link' => route('appointments.show', $this->appointment->id),
            'link_text' => 'View Appointment',
            'data' => [
                'appointment_id' => $this->appointment->id,
                'doctor_name' => $doctorName,
                'doctor_id' => $doctorId,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
                'appointment_type' => $this->appointment->appointment_type,
            ],
            'created_at' => now()->toISOString()
        ];

        // Compress payload if beneficial
        $compressionService = app(NotificationCompressionService::class);
        $compressedPayload = $compressionService->compressPayload($payload);

        return new BroadcastMessage($compressedPayload);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        $doctorId = $this->appointment->doctor->id;
        // 确保使用正确的频道名称格式
        return [
            new PrivateChannel('doctor.' . $doctorId),
            new PrivateChannel('App.User.' . $doctorId)
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'appointment-booked';
    }
}
