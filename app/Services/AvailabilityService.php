<?php

namespace App\Services;

use App\Models\AvailabilitySlot;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Get available slots for a doctor within a date range
     *
     * Weekly availability templates are expanded into concrete
     * date/time slots within the requested range.
     */
    public function getAvailableSlots(int $doctorId, Carbon $startDate, Carbon $endDate): array
    {
        $templates = AvailabilitySlot::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', $endDate->toDateString());
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $startDate->toDateString());
            })
            ->get();

        $availableSlots = [];
        $date = $startDate->copy()->startOfDay();

        while ($date->lte($endDate->copy()->startOfDay())) {
            $dayName = strtolower($date->format('l'));
            $dateString = $date->toDateString();

            foreach ($templates as $template) {
                if ($template->day_of_week !== $dayName) {
                    continue;
                }

                if ($template->effective_from && $template->effective_from > $dateString) {
                    continue;
                }

                if ($template->effective_until && $template->effective_until < $dateString) {
                    continue;
                }

                $hasAppointment = Appointment::where('doctor_id', $doctorId)
                    ->whereDate('appointment_date', $dateString)
                    ->whereTime('appointment_date', '>=', $template->start_time)
                    ->whereTime('appointment_date', '<', $template->end_time)
                    ->whereIn('status', ['confirmed', 'pending'])
                    ->exists();

                if (!$hasAppointment) {
                    $availableSlots[] = [
                        'date' => $dateString,
                        'time' => $template->start_time,
                        'duration' => $template->slot_duration ?? 30,
                        'slot_id' => $template->id,
                        'doctor_id' => $template->doctor_id,
                    ];
                }
            }

            $date->addDay();
        }

        return $availableSlots;
    }

    /**
     * Check if a specific slot is available
     */
    public function checkSlotAvailability(int $doctorId, string $date, string $time): bool
    {
        $dayName = strtolower(Carbon::parse($date)->format('l'));

        $slotExists = AvailabilitySlot::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where('day_of_week', $dayName)
            ->whereTime('start_time', '<=', $time)
            ->whereTime('end_time', '>', $time)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $date);
            })
            ->exists();

        if (!$slotExists) {
            return false;
        }

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
        $availableSlots = $this->getAvailableSlots($doctorId, now(), now()->addDays(30));

        return array_slice($availableSlots, 0, $limit);
    }

    /**
     * Calculate slot utilization for a doctor over a period
     */
    public function calculateSlotUtilization(int $doctorId, Carbon $startDate, Carbon $endDate): array
    {
        $templates = AvailabilitySlot::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', $endDate->toDateString());
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $startDate->toDateString());
            })
            ->get();

        $totalSlots = 0;
        $date = $startDate->copy()->startOfDay();

        while ($date->lte($endDate->copy()->startOfDay())) {
            $dayName = strtolower($date->format('l'));
            $totalSlots += $templates->where('day_of_week', $dayName)->count();
            $date->addDay();
        }

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
        $waitlist = \App\Models\Waitlist::find($waitlistId);

        if (!$waitlist) {
            return [];
        }

        return $this->getNextAvailableSlots($waitlist->doctor_id, 10);
    }
}