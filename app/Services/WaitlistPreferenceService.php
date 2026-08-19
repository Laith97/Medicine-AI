<?php

namespace App\Services;

use App\Models\WaitlistPatientPreference;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WaitlistPreferenceService
{
    /**
     * Calculate smart matching score for a slot based on patient preferences
     */
    public function calculateMatchingScore(array $slot, WaitlistPatientPreference $preferences, int $doctorId): float
    {
        $score = 0;
        $maxScore = 100;

        // Time preference matching (30 points)
        $timeScore = $this->calculateTimePreferenceScore($slot['time'], $preferences);
        $score += $timeScore * 0.3;

        // Day preference matching (25 points)
        $dayScore = $this->calculateDayPreferenceScore($slot['date'], $preferences);
        $score += $dayScore * 0.25;

        // Geographic proximity (20 points)
        $proximityScore = $this->calculateGeographicProximityScore($slot, $preferences, $doctorId);
        $score += $proximityScore * 0.2;

        // Service type compatibility (15 points)
        $serviceScore = $this->calculateServiceCompatibilityScore($slot, $preferences);
        $score += $serviceScore * 0.15;

        // Wait time optimization (10 points)
        $waitTimeScore = $this->calculateWaitTimeOptimizationScore($slot, $preferences);
        $score += $waitTimeScore * 0.1;

        return min($score, $maxScore);
    }

    /**
     * Calculate time preference score
     */
    private function calculateTimePreferenceScore(string $slotTime, WaitlistPatientPreference $preferences): float
    {
        if (empty($preferences->preferred_times)) {
            return 100; // No preference means full score
        }

        $hour = (int) date('H', strtotime($slotTime));
        $preferredTimes = $preferences->preferred_times;

        // Define time ranges
        $timeRanges = [
            'morning' => [6, 12],
            'afternoon' => [12, 17],
            'evening' => [17, 22],
        ];

        foreach ($preferredTimes as $preferredTime) {
            if (isset($timeRanges[$preferredTime])) {
                [$start, $end] = $timeRanges[$preferredTime];
                if ($hour >= $start && $hour < $end) {
                    return 100; // Perfect match
                }
            }
        }

        // Partial match for adjacent time slots
        foreach ($preferredTimes as $preferredTime) {
            if (isset($timeRanges[$preferredTime])) {
                [$start, $end] = $timeRanges[$preferredTime];
                if ($hour >= $start - 1 && $hour < $end + 1) {
                    return 70; // Close match
                }
            }
        }

        return 30; // Poor match
    }

    /**
     * Calculate day preference score
     */
    private function calculateDayPreferenceScore(string $slotDate, WaitlistPatientPreference $preferences): float
    {
        if (empty($preferences->preferred_days)) {
            return 100; // No preference means full score
        }

        $dayOfWeek = strtolower(Carbon::parse($slotDate)->format('l'));
        $preferredDays = array_map('strtolower', $preferences->preferred_days);

        if (in_array($dayOfWeek, $preferredDays)) {
            return 100; // Perfect match
        }

        // Check for weekend/weekday preferences
        $weekendDays = ['saturday', 'sunday'];
        $weekdayDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        $prefersWeekend = !empty(array_intersect($preferredDays, $weekendDays));
        $prefersWeekday = !empty(array_intersect($preferredDays, $weekdayDays));

        if (($prefersWeekend && in_array($dayOfWeek, $weekendDays)) ||
            ($prefersWeekday && in_array($dayOfWeek, $weekdayDays))) {
            return 80; // Good match
        }

        return 40; // Poor match
    }

    /**
     * Calculate geographic proximity score
     */
    private function calculateGeographicProximityScore(array $slot, WaitlistPatientPreference $preferences, int $doctorId): float
    {
        // Get doctor's location
        $doctor = Doctor::find($doctorId);
        if (!$doctor || !$doctor->latitude || !$doctor->longitude) {
            return 50; // Neutral score if no location data
        }

        // Get patient's preferred location
        if (!$preferences->preferred_location_lat || !$preferences->preferred_location_lng) {
            return 50; // Neutral score if no preferred location
        }

        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance(
            $preferences->preferred_location_lat,
            $preferences->preferred_location_lng,
            $doctor->latitude,
            $doctor->longitude
        );

        // Check max travel distance preference
        $maxDistance = $preferences->max_travel_distance ?? 50; // Default 50km

        if ($distance <= $maxDistance) {
            // Score based on distance (closer is better)
            $score = max(0, 100 - ($distance / $maxDistance) * 50);
            return $score;
        }

        return 20; // Poor score for distances exceeding preference
    }

    /**
     * Calculate service compatibility score
     */
    private function calculateServiceCompatibilityScore(array $slot, WaitlistPatientPreference $preferences): float
    {
        // This would check service type compatibility
        // For now, return neutral score
        return 100;
    }

    /**
     * Calculate wait time optimization score
     */
    private function calculateWaitTimeOptimizationScore(array $slot, WaitlistPatientPreference $preferences): float
    {
        $daysUntilSlot = abs(Carbon::parse($slot['date'])->diffInDays(now()));

        // Prefer slots that are not too far in the future
        if ($daysUntilSlot <= 7) {
            return 100;
        } elseif ($daysUntilSlot <= 14) {
            return 80;
        } elseif ($daysUntilSlot <= 30) {
            return 60;
        }

        return 30;
    }

    /**
     * Get matching recommendations for a patient and doctor
     */
    public function getMatchingRecommendations(int $patientId, int $doctorId): array
    {
        $preferences = WaitlistPatientPreference::where('patient_id', $patientId)
            ->where('doctor_id', $doctorId)
            ->first();

        if (!$preferences) {
            return [];
        }

        // Get available slots for the doctor
        $waitlistService = app(WaitlistService::class);
        $availableSlots = $waitlistService->findAvailableSlots($doctorId, 30);

        $recommendations = [];

        foreach ($availableSlots as $slot) {
            $score = $this->calculateMatchingScore($slot, $preferences, $doctorId);

            if ($score >= 60) { // Only include reasonably good matches
                $recommendations[] = [
                    'slot' => $slot,
                    'matching_score' => $score,
                    'match_reasons' => $this->getMatchReasons($slot, $preferences, $doctorId),
                ];
            }
        }

        // Sort by matching score (highest first)
        usort($recommendations, function ($a, $b) {
            return $b['matching_score'] <=> $a['matching_score'];
        });

        return array_slice($recommendations, 0, 10); // Return top 10
    }

    /**
     * Get reasons why a slot matches preferences
     */
    private function getMatchReasons(array $slot, WaitlistPatientPreference $preferences, int $doctorId): array
    {
        $reasons = [];

        // Time matching
        if ($this->calculateTimePreferenceScore($slot['time'], $preferences) >= 80) {
            $reasons[] = 'Preferred time slot';
        }

        // Day matching
        if ($this->calculateDayPreferenceScore($slot['date'], $preferences) >= 80) {
            $reasons[] = 'Preferred day';
        }

        // Proximity
        if ($this->calculateGeographicProximityScore($slot, $preferences, $doctorId) >= 80) {
            $reasons[] = 'Within preferred distance';
        }

        // Wait time
        $daysUntilSlot = abs(Carbon::parse($slot['date'])->diffInDays(now()));
        if ($daysUntilSlot <= 7) {
            $reasons[] = 'Available soon';
        }

        return $reasons;
    }

    /**
     * Get suggested preferences based on patient's booking history
     */
    public function getSuggestedPreferences(int $patientId): array
    {
        $appointments = Appointment::where('patient_id', $patientId)
            ->where('status', 'completed')
            ->with('doctor')
            ->orderBy('appointment_date', 'desc')
            ->take(20)
            ->get();

        if ($appointments->isEmpty()) {
            return [];
        }

        $timePreferences = [];
        $dayPreferences = [];
        $doctorPreferences = [];

        foreach ($appointments as $appointment) {
            $hour = (int) $appointment->appointment_date->format('H');
            $dayOfWeek = strtolower($appointment->appointment_date->format('l'));

            // Analyze time preferences
            if ($hour >= 6 && $hour < 12) {
                $timePreferences['morning'] = ($timePreferences['morning'] ?? 0) + 1;
            } elseif ($hour >= 12 && $hour < 17) {
                $timePreferences['afternoon'] = ($timePreferences['afternoon'] ?? 0) + 1;
            } elseif ($hour >= 17 && $hour < 22) {
                $timePreferences['evening'] = ($timePreferences['evening'] ?? 0) + 1;
            }

            // Analyze day preferences
            $dayPreferences[$dayOfWeek] = ($dayPreferences[$dayOfWeek] ?? 0) + 1;

            // Analyze doctor preferences
            $doctorId = $appointment->doctor_id;
            $doctorPreferences[$doctorId] = ($doctorPreferences[$doctorId] ?? 0) + 1;
        }

        // Get top preferences
        arsort($timePreferences);
        arsort($dayPreferences);
        arsort($doctorPreferences);

        return [
            'preferred_times' => array_keys(array_slice($timePreferences, 0, 2)),
            'preferred_days' => array_keys(array_slice($dayPreferences, 0, 3)),
            'preferred_doctors' => array_keys(array_slice($doctorPreferences, 0, 3)),
        ];
    }

    /**
     * Update learning data when preferences are set/updated
     */
    public function updateLearningData(int $patientId, array $preferenceData): void
    {
        // This could store learning data in a separate table for future analysis
        // For now, just log the preference update
        Log::info('Patient preference updated for learning', [
            'patient_id' => $patientId,
            'preferences' => $preferenceData,
        ]);
    }

    /**
     * Get preference analytics for a patient
     */
    public function getPreferenceAnalytics(int $patientId): array
    {
        $preferences = WaitlistPatientPreference::where('patient_id', $patientId)->get();

        $analytics = [
            'total_preferences' => $preferences->count(),
            'doctors_with_preferences' => $preferences->pluck('doctor_id')->unique()->count(),
            'most_common_time_preferences' => $this->getMostCommonPreferences($preferences, 'preferred_times'),
            'most_common_day_preferences' => $this->getMostCommonPreferences($preferences, 'preferred_days'),
            'average_auto_accept_threshold' => $preferences->avg('auto_accept_threshold'),
        ];

        return $analytics;
    }

    /**
     * Get most common preferences across all patient preferences
     */
    private function getMostCommonPreferences($preferences, string $field): array
    {
        $allPreferences = [];
        foreach ($preferences as $preference) {
            $values = $preference->$field ?? [];
            foreach ($values as $value) {
                $allPreferences[$value] = ($allPreferences[$value] ?? 0) + 1;
            }
        }

        arsort($allPreferences);
        return array_slice($allPreferences, 0, 5, true);
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
