<?php

namespace App\Listeners;

use App\Events\AppointmentCompletedEvent;
use App\Services\WaitlistService;
use App\Services\AvailabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessWaitlistOnAppointmentCompletion implements ShouldQueue
{
    use InteractsWithQueue;

    protected $waitlistService;
    protected $availabilityService;

    /**
     * Create the event listener.
     */
    public function __construct(WaitlistService $waitlistService, AvailabilityService $availabilityService)
    {
        $this->waitlistService = $waitlistService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Handle the event.
     */
    public function handle(AppointmentCompletedEvent $event): void
    {
        $appointment = $event->appointment;

        Log::info('Processing waitlist for completed appointment', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'appointment_date' => $appointment->appointment_date,
        ]);

        // Process the slot opening for waitlist
        $this->waitlistService->processSlotOpening($appointment);

        // Log slot availability update
        $availableSlots = $this->availabilityService->getNextAvailableSlots($appointment->doctor_id, 3);
        Log::info('Updated slot availability after completion', [
            'doctor_id' => $appointment->doctor_id,
            'next_available_slots' => count($availableSlots),
        ]);

        Log::info('Completed waitlist processing for completed appointment', [
            'appointment_id' => $appointment->id,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(AppointmentCompletedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to process waitlist for completed appointment', [
            'appointment_id' => $event->appointment->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
