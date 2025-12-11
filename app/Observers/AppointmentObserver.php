<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Events\AppointmentCompletedEvent;
use App\Events\AppointmentCancelledEvent;
use App\Events\AppointmentStatusChangedEvent;
use App\Services\AppointmentBroadcastService;
use App\Services\AppointmentStatusSynchronizationService;
use App\Services\RealtimeCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AppointmentObserver
{
    protected AppointmentBroadcastService $broadcastService;
    protected AppointmentStatusSynchronizationService $syncService;
    protected RealtimeCacheService $cacheService;

    public function __construct(
        AppointmentBroadcastService $broadcastService,
        AppointmentStatusSynchronizationService $syncService,
        RealtimeCacheService $cacheService
    ) {
        $this->broadcastService = $broadcastService;
        $this->syncService = $syncService;
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Appointment "created" event.
     * Broadcast new appointment creation and update cache
     */
    public function created(Appointment $appointment): void
    {
        Log::info('New appointment created - broadcasting and caching', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => $appointment->patient_id,
            'status' => $appointment->status,
        ]);

        // Update cache with new appointment
        $this->cacheService->invalidateAppointmentCache($appointment);

        // Broadcast appointment creation
        $this->broadcastService->broadcastAppointmentCreated($appointment);
    }

    /**
     * Handle the Appointment "updated" event.
     * Monitor status changes for real-time broadcasting and cache invalidation
     */
    public function updated(Appointment $appointment): void
    {
        $changedAttributes = $appointment->getChanges();

        // Handle status changes with enhanced broadcasting
        if (isset($changedAttributes['status'])) {
            $oldStatus = $appointment->getOriginal('status');
            $newStatus = $appointment->status;

            Log::info('Appointment status changed - broadcasting update', [
                'appointment_id' => $appointment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'doctor_id' => $appointment->doctor_id,
                'appointment_date' => $appointment->appointment_date,
            ]);

            // Broadcast status change
            $this->broadcastService->broadcastStatusChange($appointment, $oldStatus, $newStatus);

            // Invalidate and update cache
            $this->cacheService->invalidateAppointmentCache($appointment);
            $this->cacheService->updateAppointmentInCache($appointment);

            // Handle status synchronization with related entities
            $this->syncService->handleAppointmentStatusChange($appointment, $oldStatus, $newStatus);

            // Fire specific events for certain status changes
            $this->fireStatusSpecificEvents($appointment, $oldStatus, $newStatus);
        }

        // Handle other attribute changes that might affect real-time data
        if ($this->hasRealtimeRelevantChanges($appointment, $changedAttributes)) {
            $this->broadcastService->broadcastAppointmentUpdated($appointment, $changedAttributes);
            $this->cacheService->invalidateAppointmentCache($appointment);
        }
    }

    /**
     * Handle the Appointment "deleted" event.
     * Clean up cache and broadcast deletion
     */
    public function deleted(Appointment $appointment): void
    {
        Log::info('Appointment deleted - cleaning cache and broadcasting', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
        ]);

        // Remove from cache
        $this->cacheService->removeAppointmentFromCache($appointment);

        // Broadcast appointment deletion
        $this->broadcastService->broadcastAppointmentDeleted($appointment);
    }

    /**
     * Fire status-specific events for important status transitions
     */
    protected function fireStatusSpecificEvents(Appointment $appointment, string $oldStatus, string $newStatus): void
    {
        // Fire completion event
        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            event(new AppointmentCompletedEvent($appointment));
        }

        // Fire cancellation event
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            event(new AppointmentCancelledEvent($appointment, null, $appointment->cancellation_reason));
        }

        // Fire general status change event
        event(new AppointmentStatusChangedEvent($appointment, $oldStatus, $newStatus));
    }

    /**
     * Check if the appointment has changes that are relevant for real-time updates
     */
    protected function hasRealtimeRelevantChanges(Appointment $appointment, array $changedAttributes): bool
    {
        $realtimeFields = [
            'appointment_date',
            'duration',
            'notes',
            'doctor_id',
            'patient_id',
            'appointment_type',
            'reason',
            'symptoms'
        ];

        foreach ($realtimeFields as $field) {
            if (isset($changedAttributes[$field])) {
                return true;
            }
        }

        return false;
    }
}
