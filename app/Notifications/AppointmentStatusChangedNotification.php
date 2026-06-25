<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Appointment;

class AppointmentStatusChangedNotification extends Notification implements ShouldBroadcast
{
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
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Writes to database for the notification dropdown.
        // WebSocket broadcasting via Observer for real-time toast/sound.
        return ['database', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $doctorName = $this->appointment->doctor?->user?->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient_name;
        $isDoctor = $notifiable->isDoctor();
        $title = $this->getStatusChangeTitle($isDoctor);
        $message = $this->getStatusChangeMessage($doctorName, $patientName, $isDoctor);
        $icon = $this->getStatusChangeIcon();

        if ($isDoctor) {
            $link = route('doctor.appointments.show', $this->appointment->id);
        } else {
            $link = route('appointments.show', $this->appointment->id);
        }

        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'appointment_status_changed',
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'link' => $link,
            'data' => [
                'appointment_id' => $this->appointment->id,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
                'doctor_name' => $doctorName,
                'patient_name' => $patientName,
            ],
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $doctorName = $this->appointment->doctor?->user?->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient_name;
        $isDoctor = $notifiable->isDoctor();

        $title = $this->getStatusChangeTitle($isDoctor);
        $message = $this->getStatusChangeMessage($doctorName, $patientName, $isDoctor);
        $icon = $this->getStatusChangeIcon();

        // Use doctor route if notifiable is a doctor, otherwise use patient route
        if ($isDoctor) {
            $link = route('doctor.appointments.show', $this->appointment->id);
        } else {
            $link = route('appointments.show', $this->appointment->id);
        }

        return [
            'type' => 'appointment_status_changed',
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'link' => $link,
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
        $doctorName = $this->appointment->doctor?->user?->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient_name;
        $isDoctor = $notifiable->isDoctor();

        $subject = $this->getStatusChangeTitle($isDoctor);
        $message = $this->getStatusChangeMessage($doctorName, $patientName, $isDoctor);

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($message)
            ->line('Appointment Details:')
            ->line('Date: ' . $this->appointment->appointment_date->format('M j, Y g:i A'))
            ->line('Type: ' . $this->appointment->appointment_type)
            ->line('Status changed from: ' . ucfirst($this->oldStatus) . ' to ' . ucfirst($this->newStatus))
            ->action('View Appointment', $isDoctor ? route('doctor.appointments.show', $this->appointment->id) : route('appointments.show', $this->appointment->id))
            ->line('Thank you for using our platform!');

        return $mail;
    }

    /**
     * Get the title based on status change and notifiable type
     */
    protected function getStatusChangeTitle(bool $isDoctor = false): string
    {
        return match($this->newStatus) {
            'confirmed' => $isDoctor ? 'Patient Appointment Confirmed' : 'Appointment Confirmed',
            'cancelled' => $isDoctor ? 'Patient Appointment Cancelled' : 'Appointment Cancelled',
            'completed' => $isDoctor ? 'Patient Appointment Completed' : 'Appointment Completed',
            'no_show' => $isDoctor ? 'Patient No-Show' : 'Appointment No-Show',
            'pending' => $isDoctor ? 'Patient Appointment Updated' : 'Appointment Status Updated',
            default => $isDoctor ? 'Appointment Status Changed' : 'Appointment Status Changed'
        };
    }

    /**
     * Get the message based on status change and notifiable type
     */
    protected function getStatusChangeMessage(string $doctorName, string $patientName, bool $isDoctor = false): string
    {
        $date = $this->appointment->appointment_date->format('M j, Y g:i A');

        // If notifiable is a doctor, show patient's name; if patient, show doctor's name
        $appointmentWith = $isDoctor ? $patientName : "Dr. {$doctorName}";

        return match($this->newStatus) {
            'confirmed' => "Your appointment with {$appointmentWith} on {$date} has been confirmed.",
            'cancelled' => $isDoctor
                ? "The appointment with {$patientName} on {$date} has been cancelled."
                : "Your appointment with {$appointmentWith} on {$date} has been cancelled.",
            'completed' => $isDoctor
                ? "The appointment with {$patientName} on {$date} has been completed."
                : "Your appointment with {$appointmentWith} on {$date} has been completed.",
            'no_show' => $isDoctor
                ? "Patient {$patientName} did not show up for their appointment on {$date}."
                : "You did not show up for your appointment on {$date}. Please contact the clinic to reschedule.",
            'pending' => "Your appointment with {$appointmentWith} on {$date} status has been updated to pending.",
            default => "The appointment with {$appointmentWith} on {$date} status has changed from {$this->oldStatus} to {$this->newStatus}."
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

    /**
     * Get the channels the notification should broadcast on.
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
    public function broadcastAs(): string
    {
        return 'appointment-status-changed';
    }
}