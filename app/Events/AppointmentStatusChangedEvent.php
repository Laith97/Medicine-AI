<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Appointment;

class AppointmentStatusChangedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;
    public $oldStatus;
    public $newStatus;
    public $changedBy;

    public function __construct(Appointment $appointment, string $oldStatus, string $newStatus, $changedBy = null)
    {
        $this->appointment = $appointment;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
    }

    public function broadcastOn()
    {
        $channels = [];

        // Broadcast to doctor's channel - use doctor.user_id for App.User channel
        if ($this->appointment->doctor) {
            $channels[] = new PrivateChannel('doctor.' . $this->appointment->doctor->id);
            // Use doctor->user_id (the user account ID) for App.User channel, not doctor->id
            $channels[] = new PrivateChannel('App.User.' . $this->appointment->doctor->user_id);
        }

        // Broadcast to patient's channel if registered patient
        if ($this->appointment->patient_id) {
            $channels[] = new PrivateChannel('App.User.' . $this->appointment->patient_id);
        }

        // Broadcast to clinic staff channels (admin, hospital_admin, manager, supervisor)
        $channels[] = new PrivateChannel('admin');
        $channels[] = new PrivateChannel('clinic-staff');

        // Broadcast to appointment-specific channel
        $channels[] = new PrivateChannel('appointment.' . $this->appointment->id);

        return $channels;
    }

    public function broadcastAs()
    {
        return 'appointment-status-changed';
    }

    public function broadcastWith()
    {
        $doctorName = $this->appointment->doctor ? $this->appointment->doctor->user->name ?? 'Unknown Doctor' : 'Unknown Doctor';
        $patientName = $this->appointment->patient_name;

        // Get status-specific title and icon
        $title = $this->getStatusChangeTitle();
        $icon = $this->getStatusChangeIcon();

        return [
            'id' => $this->appointment->id,
            'type' => 'appointment_status_changed',
            'title' => $title,
            'message' => $this->getStatusChangeMessage($doctorName, $patientName),
            'body' => "Appointment with {$patientName} on {$this->appointment->appointment_date->format('M j, Y g:i A')} has been updated from {$this->oldStatus} to {$this->newStatus}",
            'icon' => $icon,
            'link' => route('appointments.show', $this->appointment->id),
            'link_text' => 'View Appointment',
            'data' => [
                'appointment_id' => $this->appointment->id,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
                'doctor_name' => $doctorName,
                'patient_name' => $patientName,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
                'appointment_type' => $this->appointment->appointment_type,
                'changed_by' => $this->changedBy,
            ],
            'created_at' => now()->toISOString()
        ];
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
