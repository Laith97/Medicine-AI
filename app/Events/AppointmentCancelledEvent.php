<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Appointment;

class AppointmentCancelledEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;
    public $cancelledBy;
    public $reason;

    public function __construct(Appointment $appointment, $cancelledBy = null, $reason = null)
    {
        $this->appointment = $appointment;
        $this->cancelledBy = $cancelledBy;
        $this->reason = $reason;
    }

    public function broadcastOn()
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('App.User.' . $this->appointment->doctor->user_id),
            new \Illuminate\Broadcasting\PrivateChannel('doctor.' . $this->appointment->doctor->id)
        ];
    }

    public function broadcastAs()
    {
        return 'appointment-cancelled';
    }

    public function broadcastWith()
    {
        $doctorName = $this->appointment->doctor->user->name ?? 'Unknown Doctor';
        $cancelledByName = $this->cancelledBy ? $this->cancelledBy->name : 'System';

        return [
            'id' => $this->appointment->id,
            'type' => 'appointment_cancelled',
            'title' => 'Appointment Cancelled',
            'message' => "An appointment has been cancelled with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')}",
            'body' => "An appointment has been cancelled with Dr. {$doctorName} on {$this->appointment->appointment_date->format('M j, Y g:i A')}. Cancelled by: {$cancelledByName}",
            'icon' => 'calendar-times',
            'link' => route('appointments.index'),
            'link_text' => 'View Appointments',
            'data' => [
                'appointment_id' => $this->appointment->id,
                'doctor_name' => $doctorName,
                'doctor_id' => $this->appointment->doctor->id,
                'appointment_date' => $this->appointment->appointment_date->format('Y-m-d H:i:s'),
                'cancelled_by' => $this->cancelledBy ? $this->cancelledBy->id : null,
                'cancellation_reason' => $this->reason,
            ],
            'created_at' => now()->toISOString()
        ];
    }
}
