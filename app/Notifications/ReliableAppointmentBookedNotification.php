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

class ReliableAppointmentBookedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
        $this->onQueue('realtime');
        $this->delay(0);
    }

    /**
     * CRITICAL FIX: Only use reliable channels
     * Database and broadcast are most reliable
     * NOTE: Broadcasting is handled by AppointmentObserver for creation events.
     * Using broadcast here would cause duplicate WebSocket events.
     */
    public function via(object $notifiable): array
    {
        // Only use database channel - observer handles the broadcast event
        return ['database'];
    }

    /**
     * Database notification data
     */
    public function toArray(object $notifiable): array
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient->name ?? 'Patient';
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
     * Broadcast notification data
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient->name ?? 'Patient';
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

        return new BroadcastMessage($payload);
    }

    /**
     * Broadcast channels
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.User.' . ($this->notifiable?->id ?? 'default'))];
    }

    /**
     * Broadcast event name
     */
    public function broadcastAs()
    {
        return 'appointment-booked';
    }
}