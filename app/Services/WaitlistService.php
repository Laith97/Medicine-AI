<?php

namespace App\Services;

use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use App\Models\WaitlistPatientPreference;
use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WaitlistService
{
    /**
     * Add a patient to the waitlist
     */
    public function addToWaitlist(int $patientId, int $doctorId, array $data): Waitlist
    {
        return DB::transaction(function () use ($patientId, $doctorId, $data) {
            // Check if patient is already on waitlist for this doctor
            $existingWaitlist = Waitlist::where('patient_id', $patientId)
                ->where('doctor_id', $doctorId)
                ->where('status', 'active')
                ->first();

            if ($existingWaitlist) {
                throw new \Exception('Patient is already on the waitlist for this doctor');
            }

            $waitlist = Waitlist::create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'service_type' => $data['service_type'] ?? 'consultation',
                'priority_level' => $data['priority_level'] ?? 'medium',
                'preferred_time_slots' => $data['preferred_time_slots'] ?? [],
                'preferred_days' => $data['preferred_days'] ?? [],
                'max_wait_days' => $data['max_wait_days'] ?? 30,
                'notification_channels' => $data['notification_channels'] ?? ['email'],
                'status' => 'active',
            ]);

            Log::info('Patient added to waitlist', [
                'waitlist_id' => $waitlist->id,
                'patient_id' => $patientId,
                'doctor_id' => $doctorId
            ]);

            return $waitlist;
        });
    }

    /**
     * Remove a patient from the waitlist
     */
    public function removeFromWaitlist(int $waitlistId): bool
    {
        $waitlist = Waitlist::findOrFail($waitlistId);

        return DB::transaction(function () use ($waitlist) {
            // Remove all entries
            $waitlist->entries()->delete();

            // Cancel the waitlist
            $waitlist->cancel();

            Log::info('Patient removed from waitlist', [
                'waitlist_id' => $waitlist->id,
                'patient_id' => $waitlist->patient_id,
                'doctor_id' => $waitlist->doctor_id
            ]);

            return true;
        });
    }

    /**
     * Find available slots for a doctor
     */
    public function findAvailableSlots(int $doctorId, int $daysAhead = 30): array
    {
        $endDate = now()->addDays($daysAhead);
        $availableSlots = [];

        // Get doctor's active weekly availability templates
        $templates = AvailabilitySlot::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now()->toDateString());
            })
            ->get();

        // Iterate through each day and check availability
        $date = now();
        while ($date <= $endDate) {
            $dayName = strtolower($date->format('l'));

            foreach ($templates as $template) {
                if ($template->day_of_week !== $dayName) {
                    continue;
                }

                // Count existing appointments for this doctor on this day/time range
                $bookingCount = Appointment::where('doctor_id', $doctorId)
                    ->whereDate('appointment_date', $date->toDateString())
                    ->whereTime('appointment_date', '>=', $template->start_time)
                    ->whereTime('appointment_date', '<', $template->end_time)
                    ->whereIn('status', ['confirmed', 'pending'])
                    ->count();

                if ($bookingCount < ($template->max_bookings_per_slot ?: 1)) {
                    $availableSlots[] = [
                        'date' => $date->toDateString(),
                        'time' => $template->start_time,
                        'duration' => $template->slot_duration,
                        'slot_id' => $template->id,
                    ];
                }
            }

            $date->addDay();
        }

        return $availableSlots;
    }

    /**
     * Process slot opening (when an appointment is cancelled)
     */
    public function processSlotOpening(Appointment $cancelledAppointment): void
    {
        $doctorId = $cancelledAppointment->doctor_id;
        $appointmentDate = $cancelledAppointment->appointment_date->toDateString();
        $appointmentTime = $cancelledAppointment->appointment_date->format('H:i:s');

        // Find active waitlists for this doctor
        $waitlists = Waitlist::where('doctor_id', $doctorId)
            ->active()
            ->orderByRaw("
                CASE priority_level
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END
            ")
            ->orderBy('created_at')
            ->get();

        foreach ($waitlists as $waitlist) {
            // Check if this slot matches patient preferences
            if ($this->slotMatchesPreferences($waitlist, $appointmentDate, $appointmentTime)) {
                // Create waitlist entry
                $entry = WaitlistEntry::create([
                    'waitlist_id' => $waitlist->id,
                    'slot_date' => $appointmentDate,
                    'slot_time' => $appointmentTime,
                    'status' => 'pending',
                ]);

                // Offer slot to patient
                $this->offerSlotToPatient($entry);

                // Only offer to first matching patient
                break;
            }
        }
    }

    /**
     * Offer slot to patient
     */
    public function offerSlotToPatient(WaitlistEntry $entry): void
    {
        $waitlist = $entry->waitlist;
        $patient = $waitlist->patient;

        // Set response deadline (24 hours)
        $deadline = now()->addHours(24);

        $entry->markAsOffered($deadline);

        // Check if patient has auto-accept preferences
        $preferences = WaitlistPatientPreference::where('patient_id', $patient->id)
            ->where('doctor_id', $waitlist->doctor_id)
            ->first();

        $daysUntilSlot = now()->diffInDays(Carbon::parse($entry->slot_date . ' ' . $entry->slot_time));

        if ($preferences && $preferences->shouldAutoAccept($daysUntilSlot)) {
            $this->acceptSlotOffer($entry->id);
            return;
        }

        // Send notification to patient
        $this->sendSlotOfferNotification($entry);

        Log::info('Slot offered to patient', [
            'entry_id' => $entry->id,
            'patient_id' => $patient->id,
            'slot_date' => $entry->slot_date,
            'slot_time' => $entry->slot_time,
        ]);
    }

    /**
     * Accept slot offer
     */
    public function acceptSlotOffer(int $entryId): bool
    {
        $entry = WaitlistEntry::findOrFail($entryId);

        if (!$entry->isOffered() || $entry->isResponseDeadlinePassed()) {
            throw new \Exception('Slot offer is no longer valid');
        }

        return DB::transaction(function () use ($entry) {
            $waitlist = $entry->waitlist;

            // Create appointment from the slot
            $appointment = Appointment::create([
                'patient_id' => $waitlist->patient_id,
                'doctor_id' => $waitlist->doctor_id,
                'appointment_date' => Carbon::parse($entry->slot_date . ' ' . $entry->slot_time),
                'status' => 'confirmed',
                'appointment_type' => $waitlist->service_type,
                'duration' => 30, // Default duration, can be made configurable
            ]);

            // Link appointment to entry
            $entry->update([
                'appointment_id' => $appointment->id,
                'status' => 'accepted'
            ]);

            // Fulfill the waitlist
            $waitlist->fulfill();

            // Send confirmation notification
            $this->sendSlotAcceptedNotification($entry, $appointment);

            Log::info('Slot offer accepted', [
                'entry_id' => $entry->id,
                'appointment_id' => $appointment->id,
                'patient_id' => $waitlist->patient_id,
            ]);

            return true;
        });
    }

    /**
     * Decline slot offer
     */
    public function declineSlotOffer(int $entryId): bool
    {
        $entry = WaitlistEntry::findOrFail($entryId);

        if (!$entry->isOffered()) {
            throw new \Exception('Slot offer not found');
        }

        $entry->decline();

        Log::info('Slot offer declined', [
            'entry_id' => $entry->id,
            'patient_id' => $entry->waitlist->patient_id,
        ]);

        return true;
    }

    /**
     * Get waitlist position for a patient
     */
    public function getWaitlistPosition(int $waitlistId): array
    {
        $waitlist = Waitlist::findOrFail($waitlistId);

        $position = Waitlist::where('doctor_id', $waitlist->doctor_id)
            ->where('status', 'active')
            ->where('created_at', '<=', $waitlist->created_at)
            ->orderBy('created_at')
            ->count();

        $totalWaitlisted = Waitlist::where('doctor_id', $waitlist->doctor_id)
            ->where('status', 'active')
            ->count();

        return [
            'position' => $position,
            'total_waitlisted' => $totalWaitlisted,
            'estimated_wait_days' => $this->estimateWaitTime($waitlist->doctor_id),
        ];
    }

    /**
     * Check if slot matches patient preferences using smart matching algorithm
     */
    private function slotMatchesPreferences(Waitlist $waitlist, string $date, string $time): bool
    {
        $preferences = WaitlistPatientPreference::where('patient_id', $waitlist->patient_id)
            ->where('doctor_id', $waitlist->doctor_id)
            ->first();

        if (!$preferences) {
            return true; // No preferences means any slot is acceptable
        }

        // Use the enhanced smart matching service
        $preferenceService = app(WaitlistPreferenceService::class);
        $slot = ['date' => $date, 'time' => $time];

        // Calculate matching score - consider it a match if score is above 60
        $score = $preferenceService->calculateMatchingScore($slot, $preferences, $waitlist->doctor_id);

        return $score >= 60;
    }

    /**
     * Estimate wait time for a doctor
     */
    private function estimateWaitTime(int $doctorId): int
    {
        // Simple estimation based on current waitlist size
        $waitlistCount = Waitlist::where('doctor_id', $doctorId)
            ->active()
            ->count();

        // Assume doctor sees 10 patients per day on average
        $estimatedDays = ceil($waitlistCount / 10);

        return max($estimatedDays, 1);
    }

    /**
     * Send slot offer notification
     */
    private function sendSlotOfferNotification(WaitlistEntry $entry): void
    {
        // Implementation depends on notification system
        // This would integrate with the existing notification service
        $waitlist = $entry->waitlist;
        $patient = $waitlist->patient;

        // For now, just log it
        Log::info('Slot offer notification sent', [
            'patient_id' => $patient->id,
            'slot_date' => $entry->slot_date,
            'slot_time' => $entry->slot_time,
        ]);
    }

    /**
     * Send slot accepted notification
     */
    private function sendSlotAcceptedNotification(WaitlistEntry $entry, Appointment $appointment): void
    {
        // Implementation depends on notification system
        $waitlist = $entry->waitlist;
        $patient = $waitlist->patient;

        Log::info('Slot accepted notification sent', [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
        ]);
    }

    /**
     * Process multiple slot openings in batch
     */
    public function processBatchSlotOpenings(array $cancelledAppointments): array
    {
        $results = [
            'processed' => 0,
            'slots_offered' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($cancelledAppointments, &$results) {
            foreach ($cancelledAppointments as $appointment) {
                try {
                    $this->processSlotOpening($appointment);
                    $results['processed']++;
                    $results['slots_offered']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to process slot opening in batch', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        Log::info('Batch slot opening processing completed', [
            'total_processed' => $results['processed'],
            'slots_offered' => $results['slots_offered'],
            'errors_count' => count($results['errors']),
        ]);

        return $results;
    }

    /**
     * Get waitlist statistics for monitoring
     */
    public function getWaitlistStatistics(int $doctorId = null): array
    {
        $query = Waitlist::query();

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $stats = [
            'total_active' => (clone $query)->active()->count(),
            'by_priority' => [
                'urgent' => (clone $query)->where('priority_level', 'urgent')->active()->count(),
                'high' => (clone $query)->where('priority_level', 'high')->active()->count(),
                'medium' => (clone $query)->where('priority_level', 'medium')->active()->count(),
                'low' => (clone $query)->where('priority_level', 'low')->active()->count(),
            ],
            'average_wait_days' => $this->calculateAverageWaitTime($doctorId),
            'recent_offers' => WaitlistEntry::whereHas('waitlist', function ($q) use ($doctorId) {
                if ($doctorId) {
                    $q->where('doctor_id', $doctorId);
                }
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->count(),
        ];

        return $stats;
    }

    /**
     * Calculate average wait time across waitlists
     */
    private function calculateAverageWaitTime(int $doctorId = null): float
    {
        $query = Waitlist::active();

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $waitlists = $query->get();
        $totalWaitDays = 0;
        $count = 0;

        foreach ($waitlists as $waitlist) {
            $waitDays = now()->diffInDays($waitlist->created_at);
            $totalWaitDays += $waitDays;
            $count++;
        }

        return $count > 0 ? round($totalWaitDays / $count, 1) : 0;
    }
}
