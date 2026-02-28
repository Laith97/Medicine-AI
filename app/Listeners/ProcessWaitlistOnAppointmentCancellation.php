<?php

namespace App\Listeners;

use App\Events\AppointmentCancelledEvent;
use App\Services\WaitlistService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessWaitlistOnAppointmentCancellation implements ShouldQueue
{
    use InteractsWithQueue;

    protected $waitlistService;

    /**
     * Create the event listener.
     */
    public function __construct(WaitlistService $waitlistService)
    {
        $this->waitlistService = $waitlistService;
    }

    /**
     * Handle the event.
     */
    public function handle(AppointmentCancelledEvent $event): void
    {
        $appointment = $event->appointment;

        Log::info('Processing waitlist for cancelled appointment', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'appointment_date' => $appointment->appointment_date,
        ]);

        // Only process if the appointment was confirmed (not just pending)
        if ($appointment->isCancelled() && $appointment->confirmed_at) {
            $this->waitlistService->processSlotOpening($appointment);
        }

        Log::info('Completed waitlist processing for cancelled appointment', [
            'appointment_id' => $appointment->id,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(AppointmentCancelledEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to process waitlist for cancelled appointment', [
            'appointment_id' => $event->appointment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
