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

class AppointmentStatusChangedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $appointment;
    protected $oldStatus;
    protected $newStatus;
    protected $changedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment, string $oldStatus, string $newStatus, $changedBy = null)
    {
        $this->appointment = $appointment;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;

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
        $doctorName = $this->appointment->doctor ? $this->appointment->doctor->user->name ?? 'Unknown Doctor' : 'Unknown Doctor';
        $patientName = $this->appointment->patient_name;

        $title = $this->getStatusChangeTitle();
        $message = $this->getStatusChangeMessage($doctorName, $patientName);
        $icon = $this->getStatusChangeIcon();

        return [
            'type' => 'appointment_status_changed',
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'link' => route('appointments.show', $this->appointment->id),
            'link_text' => 'View Appointment',
            'related_type' => 'appointment',
            'related_id' => $this->appointment->id,
            'data' => [
                'appointment_id' => $this->appointment->id,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
                'doctor_name' => $doctorName,
                'patient_name' => $patientName,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
                'appointment_type' => $this->appointment->appointment_type,
                'changed_by' => $this->changedBy,
            ]
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $doctorName = $this->appointment->doctor ? $this->appointment->doctor->user->name ?? 'Unknown Doctor' : 'Unknown Doctor';
        $patientName = $this->appointment->patient_name;

        $subject = $this->getStatusChangeTitle();
        $message = $this->getStatusChangeMessage($doctorName, $patientName);

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($message)
            ->line('Appointment Details:')
            ->line('Date: ' . $this->appointment->appointment_date->format('M j, Y g:i A'))
            ->line('Type: ' . $this->appointment->appointment_type)
            ->line('Status changed from: ' . ucfirst($this->oldStatus) . ' to ' . ucfirst($this->newStatus))
            ->action('View Appointment', route('appointments.show', $this->appointment->id))
            ->line('Thank you for using our platform!');

        return $mail;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $doctorName = $this->appointment->doctor ? $this->appointment->doctor->user->name ?? 'Unknown Doctor' : 'Unknown Doctor';
        $patientName = $this->appointment->patient_name;

        $message = $this->getStatusChangeMessage($doctorName, $patientName);

        return $message . ' View details: ' . route('appointments.show', $this->appointment->id);
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $doctorName = $this->appointment->doctor ? $this->appointment->doctor->user->name ?? 'Unknown Doctor' : 'Unknown Doctor';
        $patientName = $this->appointment->patient_name;
        $doctorId = $this->appointment->doctor ? $this->appointment->doctor->id : 0;

        $payload = [
            'id' => $this->id,
            'type' => 'appointment_status_changed',
            'title' => $this->getStatusChangeTitle(),
            'message' => $this->getStatusChangeMessage($doctorName, $patientName),
            'body' => $this->getStatusChangeMessage($doctorName, $patientName),
            'icon' => $this->getStatusChangeIcon(),
            'link' => route('appointments.show', $this->appointment->id),
            'link_text' => 'View Appointment',
            'data' => [
                'appointment_id' => $this->appointment->id,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
                'doctor_name' => $doctorName,
                'doctor_id' => $doctorId,
                'patient_name' => $patientName,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
                'appointment_type' => $this->appointment->appointment_type,
                'changed_by' => $this->changedBy,
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
        $channels = [];

        // Doctor's channel
        if ($this->appointment->doctor) {
            $channels[] = new PrivateChannel('doctor.' . $this->appointment->doctor->id);
            $channels[] = new PrivateChannel('App.User.' . $this->appointment->doctor->id);
        }

        // Patient's channel (if registered patient)
        if ($this->appointment->patient_id) {
            $channels[] = new PrivateChannel('App.User.' . $this->appointment->patient_id);
        }

        // Admin channels
        $channels[] = new PrivateChannel('admin');
        $channels[] = new PrivateChannel('clinic-staff');

        // Appointment-specific channel
        $channels[] = new PrivateChannel('appointment.' . $this->appointment->id);

        return $channels;
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'appointment-status-changed';
    }

    /**
     * Get the title based on status change
     */
    protected function getStatusChangeTitle(): string
    {
        return match($this->newStatus) {
            'confirmed' => 'Appointment Confirmed',
            'cancelled' => 'Appointment Cancelled',
            'completed' => 'Appointment Completed',
            'no_show' => 'Appointment No-Show',
            'pending' => 'Appointment Status Updated',
            default => 'Appointment Status Changed'
        };
    }

    /**
     * Get the message based on status change
     */
    protected function getStatusChangeMessage(string $doctorName, string $patientName): string
    {
        $date = $this->appointment->appointment_date->format('M j, Y g:i A');

        return match($this->newStatus) {
            'confirmed' => "Your appointment with {$patientName} on {$date} has been confirmed.",
            'cancelled' => "The appointment with {$patientName} on {$date} has been cancelled.",
            'completed' => "The appointment with {$patientName} on {$date} has been completed.",
            'no_show' => "Patient {$patientName} did not show up for their appointment on {$date}.",
            'pending' => "The appointment with {$patientName} on {$date} status has been updated to pending.",
            default => "The appointment with {$patientName} on {$date} status has changed from {$this->oldStatus} to {$this->newStatus}."
        };
    }

    /**
     * Get the icon based on status change
     */
    protected function getStatusChangeIcon(): string
    {
        return match($this->newStatus) {
            'confirmed' => 'calendar-check',
            'cancelled' => 'calendar-times',
            'completed' => 'check-circle',
            'no_show' => 'user-times',
            'pending' => 'clock',
            default => 'calendar-alt'
        };
    }
}
