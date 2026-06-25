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
        $doctorName = $this->appointment->doctor?->user?->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient->name ?? $this->appointment->guest_name ?? 'Patient';
        $isDoctor = $notifiable->isDoctor();

        // Customize message based on notifiable type
        if ($isDoctor) {
            $message = "{$patientName} has booked a new appointment with you on {$this->appointment->appointment_date->format('M j, Y g:i A')}";
            $title = 'New Appointment Booked';
        } else {
            $message = "Your appointment with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')} has been confirmed";
            $title = 'Appointment Confirmed';
        }

        // Use doctor route if notifiable is a doctor, otherwise use patient route
        $link = $isDoctor
            ? route('doctor.appointments.show', $this->appointment->id)
            : route('appointments.show', $this->appointment->id);

        return [
            'type' => 'appointment_booked',
            'title' => $title,
            'message' => $message,
            'icon' => 'calendar',
            'link' => $link,
            'link_text' => 'View Appointment',
            'related_type' => 'appointment',
            'related_id' => $this->appointment->id,
            'data' => [
                'appointment_id' => $this->appointment->id,
                'doctor_name' => $doctorName,
                'patient_name' => $patientName,
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
        $doctorName = $this->appointment->doctor?->user?->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient->name ?? $this->appointment->guest_name ?? 'Patient';
        $isDoctor = $notifiable->isDoctor();

        if ($isDoctor) {
            $subject = 'New Appointment Booked';
            $message = "{$patientName} has booked a new appointment with you on {$this->appointment->appointment_date->format('M j, Y g:i A')}";
            $actionUrl = route('doctor.appointments.show', $this->appointment->id);
        } else {
            $subject = 'Appointment Confirmed';
            $message = "Your appointment with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')} has been confirmed";
            $actionUrl = route('appointments.show', $this->appointment->id);
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($message)
            ->line('Appointment Type: ' . $this->appointment->appointment_type)
            ->action('View Appointment', $actionUrl)
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $doctorName = $this->appointment->doctor?->user?->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient->name ?? $this->appointment->guest_name ?? 'Patient';
        $doctorId = $this->appointment->doctor->id ?? 0;
        $hospitalId = $this->appointment->doctor->hospital_id ?? 0;
        $isDoctor = $notifiable->isDoctor();

        if ($isDoctor) {
            $message = "{$patientName} has booked a new appointment with you on {$this->appointment->appointment_date->format('M j, Y g:i A')}. View: " . route('doctor.appointments.show', $this->appointment->id);
        } else {
            $message = "Your appointment with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')} has been confirmed. View: " . route('appointments.show', $this->appointment->id);
        }

        return [
            'message' => $message,
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
        $doctorName = $this->appointment->doctor?->user?->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient->name ?? $this->appointment->guest_name ?? 'Patient';
        $doctorId = $this->appointment->doctor->id ?? 0;
        $isDoctor = $notifiable->isDoctor();

        // Customize message based on notifiable type
        if ($isDoctor) {
            $title = 'New Appointment Booked';
            $message = "{$patientName} has booked a new appointment with you on {$this->appointment->appointment_date->format('M j, Y g:i A')}";
            $body = $message;
            $link = route('doctor.appointments.show', $this->appointment->id);
        } else {
            $title = 'Appointment Confirmed';
            $message = "Your appointment with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')} has been confirmed";
            $body = $message;
            $link = route('appointments.show', $this->appointment->id);
        }

        $payload = [
            'id' => $this->id,
            'type' => 'appointment_booked',
            'title' => $title,
            'message' => $message,
            'body' => $body,
            'icon' => 'calendar',
            'link' => $link,
            'link_text' => 'View Appointment',
            'data' => [
                'appointment_id' => $this->appointment->id,
                'doctor_name' => $doctorName,
                'patient_name' => $patientName,
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
    public function broadcastOn(): array
    {
        $userId = $this->notifiable?->id
            ?? $this->appointment->doctor?->user_id
            ?? $this->appointment->patient_id
            ?? 'default';

        return [new PrivateChannel('App.User.' . $userId)];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        // Using dot notation for Echo to listen with .listen('.appointment-booked')
        return 'appointment-booked';
    }
}
