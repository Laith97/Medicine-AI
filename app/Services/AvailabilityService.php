<?php

namespace App\Services;

use App\Models\AvailabilitySlot;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Get available slots for a doctor within a date range
     */
    public function getAvailableSlots(int $doctorId, Carbon $startDate, Carbon $endDate): array
    {
        // Get doctor's availability slots that don't have appointments
        $availableSlots = AvailabilitySlot::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('date', '>=', $startDate->toDateString())
            ->where('date', '<=', $endDate->toDateString())
            ->whereDoesntHave('appointments', function ($query) {
                $query->whereIn('status', ['confirmed', 'pending']);
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return $availableSlots->map(function ($slot) {
            return [
                'date' => $slot->date,
                'time' => $slot->start_time,
                'duration' => $slot->duration ?? 30,
                'slot_id' => $slot->id,
                'doctor_id' => $slot->doctor_id,
            ];
        })->toArray();
    }

    /**
     * Check if a specific slot is available
     */
    public function checkSlotAvailability(int $doctorId, string $date, string $time): bool
    {
        // Check if there's an availability slot for this doctor at this time
        $slotExists = AvailabilitySlot::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('date', $date)
            ->where('start_time', $time)
            ->exists();

        if (!$slotExists) {
            return false;
        }

        // Check if there's a confirmed or pending appointment for this slot
        $hasAppointment = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', $date . ' ' . $time)
            ->whereIn('status', ['confirmed', 'pending'])
            ->exists();

        return !$hasAppointment;
    }

    /**
     * Get next available slots for a doctor
     */
    public function getNextAvailableSlots(int $doctorId, int $limit = 5): array
    {
        $startDate = now()->toDateString();
        $endDate = now()->addDays(30)->toDateString();

        $availableSlots = $this->getAvailableSlots($doctorId, now(), now()->addDays(30));

        return array_slice($availableSlots, 0, $limit);
    }

    /**
     * Calculate slot utilization for a doctor over a period
     */
    public function calculateSlotUtilization(int $doctorId, Carbon $startDate, Carbon $endDate): array
    {
        // Get total available slots
        $totalSlots = AvailabilitySlot::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('date', '>=', $startDate->toDateString())
            ->where('date', '<=', $endDate->toDateString())
            ->count();

        // Get booked slots
        $bookedSlots = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', '>=', $startDate)
            ->where('appointment_date', '<=', $endDate)
            ->whereIn('status', ['confirmed', 'pending'])
            ->count();

        $utilizationRate = $totalSlots > 0 ? ($bookedSlots / $totalSlots) * 100 : 0;

        return [
            'total_slots' => $totalSlots,
            'booked_slots' => $bookedSlots,
            'available_slots' => $totalSlots - $bookedSlots,
            'utilization_rate' => round($utilizationRate, 2),
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
        ];
    }

    /**
     * Get slots that have opened up (cancelled/completed appointments)
     */
    public function getRecentlyOpenedSlots(int $doctorId, int $hoursBack = 24): array
    {
        $since = now()->subHours($hoursBack);

        // Find appointments that were cancelled or completed recently
        $changedAppointments = Appointment::where('doctor_id', $doctorId)
            ->where(function ($query) use ($since) {
                $query->where('cancelled_at', '>=', $since)
                      ->orWhere('completed_at', '>=', $since);
            })
            ->get();

        $openedSlots = [];

        foreach ($changedAppointments as $appointment) {
            $openedSlots[] = [
                'date' => $appointment->appointment_date->toDateString(),
                'time' => $appointment->appointment_date->format('H:i:s'),
                'doctor_id' => $appointment->doctor_id,
                'reason' => $appointment->cancelled_at ? 'cancelled' : 'completed',
                'opened_at' => $appointment->cancelled_at ?? $appointment->completed_at,
            ];
        }

        return $openedSlots;
    }

    /**
     * Batch check availability for multiple slots
     */
    public function batchCheckAvailability(int $doctorId, array $slots): array
    {
        $results = [];

        foreach ($slots as $slot) {
            $results[] = [
                'date' => $slot['date'],
                'time' => $slot['time'],
                'available' => $this->checkSlotAvailability($doctorId, $slot['date'], $slot['time']),
            ];
        }

        return $results;
    }

    /**
     * Find optimal slots based on waitlist preferences
     */
    public function findOptimalSlotsForWaitlist(int $waitlistId): array
    {
        // This would integrate with waitlist preferences
        // For now, return next available slots
        $waitlist = \App\Models\Waitlist::find($waitlistId);

        if (!$waitlist) {
            return [];
        }

        return $this->getNextAvailableSlots($waitlist->doctor_id, 10);
    }
}
