<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;

class AppointmentCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function broadcastOn()
    {
        return [
            new \Illuminate\Broadcasting\Channel('doctor.' . $this->appointment->doctor->id),
            new \Illuminate\Broadcasting\Channel('App.User.' . $this->appointment->doctor->id)
        ];
    }

    public function broadcastAs()
    {
        return 'appointment-completed';
    }

    public function broadcastWith()
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient ? $this->appointment->patient->name : ($this->appointment->guest_name ?? 'Unknown Patient');

        return [
            'id' => $this->appointment->id,
            'type' => 'appointment_completed',
            'title' => 'Appointment Completed',
            'message' => "An appointment has been completed with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')}",
            'body' => "An appointment with patient {$patientName} has been completed with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')}",
            'icon' => 'calendar-check',
            'link' => route('appointments.index'),
            'link_text' => 'View Appointments',
            'data' => [
                'appointment_id' => $this->appointment->id,
                'doctor_name' => $doctorName,
                'doctor_id' => $this->appointment->doctor->id,
                'patient_name' => $patientName,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
            ],
            'created_at' => now()->toISOString()
        ];
    }
}
