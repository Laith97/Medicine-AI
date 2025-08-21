<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;

class AppointmentBookedEvent
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
        return 'appointment-booked';
    }

    public function broadcastWith()
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';

        return [
            'id' => $this->appointment->id,
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
                'doctor_id' => $this->appointment->doctor->id,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
                'appointment_type' => $this->appointment->appointment_type,
            ],
            'created_at' => now()->toISOString()
        ];
    }
}
