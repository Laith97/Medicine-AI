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

class WaitlistAutoBookedNotification extends Notification implements ShouldBroadcast
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
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';
        $doctorId = $this->appointment->doctor->id ?? 0;
        $hospitalId = $this->appointment->doctor->hospital_id ?? 0;

        return [
            'message' => "Your waitlisted appointment with Dr. {$doctorName} has been auto-booked for {$this->appointment->appointment_date->format('M j, Y g:i A')}. View: " . route('appointments.show', $this->appointment->id),
            'options' => [
                'doctor_id' => $doctorId,
                'hospital_id' => $hospitalId,
                'context' => 'appointment_booked',
                'context_id' => $this->appointment->id,
            ]
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';

        // Use doctor route if notifiable is a doctor, otherwise use patient route
        if ($notifiable->isDoctor()) {
            $link = route('doctor.appointments.show', $this->appointment->id);
        } else {
            $link = route('appointments.show', $this->appointment->id);
        }

        return [
            'type' => 'waitlist_auto_booked',
            'title' => 'Appointment Auto-Booked',
            'message' => "Your waitlisted appointment with Dr. {$doctorName} has been automatically booked",
            'icon' => 'magic',
            'link' => $link,
            'link_text' => 'View Appointment',
            'related_type' => 'appointment',
            'related_id' => $this->appointment->id,
            'data' => [
                'appointment_id' => $this->appointment->id,
                'doctor_name' => $doctorName,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
                'appointment_type' => $this->appointment->appointment_type,
                'auto_booked' => true,
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
            ->subject('Appointment Auto-Booked')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Great news! Your waitlisted appointment with Dr. {$doctorName} has been automatically booked.")
            ->line('Appointment Details:')
            ->line('Date & Time: ' . $this->appointment->appointment_date->format('M j, Y g:i A'))
            ->line('Type: ' . $this->appointment->appointment_type)
            ->action('View Appointment', route('appointments.show', $this->appointment->id))
            ->line('Thank you for your patience!');
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
            'type' => 'waitlist_auto_booked',
            'title' => 'Appointment Auto-Booked',
            'message' => "Your waitlisted appointment with Dr. {$doctorName} has been automatically booked",
            'body' => "Your waitlisted appointment with Dr. {$doctorName} has been automatically booked",
            'icon' => 'magic',
            'link' => route('appointments.show', $this->appointment->id),
            'link_text' => 'View Appointment',
            'data' => [
                'appointment_id' => $this->appointment->id,
                'doctor_name' => $doctorName,
                'doctor_id' => $doctorId,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
                'appointment_type' => $this->appointment->appointment_type,
                'auto_booked' => true,
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
        $userId = $this->appointment->patient_id;
        return [
            new PrivateChannel('App.User.' . $userId)
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'waitlist-auto-booked';
    }
}
