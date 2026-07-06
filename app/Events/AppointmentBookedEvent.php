<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Appointment;

class AppointmentBookedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function broadcastOn()
    {
        $channels = [
            new \Illuminate\Broadcasting\PrivateChannel('App.User.' . $this->appointment->doctor->user_id),
            new \Illuminate\Broadcasting\Channel('doctor.' . $this->appointment->doctor->id)
        ];

        if ($this->appointment->patient) {
            $channels[] = new \Illuminate\Broadcasting\PrivateChannel('App.User.' . $this->appointment->patient_id);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'appointment-booked';
    }

    public function broadcastWith()
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';
        $patientName = $this->appointment->patient->name ?? 'Patient';

        return [
            'id' => $this->appointment->id,
            'type' => 'appointment_booked',
            'title' => 'New Appointment Booked',
            'message' => "{$patientName} has booked a new appointment with you on {$this->appointment->appointment_date->format('M j, Y g:i A')}",
            'body' => "{$patientName} has booked a new appointment with you on {$this->appointment->appointment_date->format('M j, Y g:i A')}",
            'icon' => 'calendar',
            'link' => route('doctor.appointments.show', $this->appointment->id),
            'link_text' => 'View Appointment',
            'data' => [
                'appointment_id' => $this->appointment->id,
                'doctor_name' => $doctorName,
                'patient_name' => $patientName,
                'doctor_id' => $this->appointment->doctor->id,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
                'appointment_type' => $this->appointment->appointment_type,
            ],
            'created_at' => now()->toISOString()
        ];
    }
}
