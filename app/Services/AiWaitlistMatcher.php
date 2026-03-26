<?php

namespace App\Services;

use App\Models\AvailabilitySlot;
use App\Models\Waitlist;
use App\Models\WaitlistAiSetting;
use App\Models\WaitlistMatchOffer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AiWaitlistMatcher
{
    // Score weights for matching algorithm
    private const SCORE_NEUTRAL = 0.1;
    private const SCORE_PERFECT_TIME = 0.3;
    private const SCORE_PARTIAL_TIME = 0.15;
    private const SCORE_PERFECT_DAY = 0.2;
    private const SCORE_SERVICE_MATCH = 0.15;
    private const SCORE_SERVICE_FALLBACK = 0.1;
    private const SCORE_PRIORITY_URGENT = 0.15;
    private const SCORE_PRIORITY_HIGH = 0.1;
    private const SCORE_PRIORITY_MEDIUM = 0.05;
    private const SCORE_MAX_WAIT = 0.2;
    private const WAIT_TIME_MULTIPLIER = 0.002;
    private const MAX_WAIT_DAYS = 90;

    /**
     * Find best matches for a cancelled slot.
     */
    public function findMatchesForSlot(AvailabilitySlot $slot): array
    {
        $settings = WaitlistAiSetting::firstOrCreate(
            ['doctor_id' => $slot->doctor_id],
            $this->getDefaultSettings()
        );

        if (!$settings->isEnabled()) {
            return [];
        }

        $waitlists = Waitlist::where('doctor_id', $slot->doctor_id)
            ->active()
            ->with('patient')
            ->get();

        $matches = [];

        foreach ($waitlists as $waitlist) {
            $score = $this->calculateMatchScore($waitlist, $slot);

            if ($score >= $settings->min_match_score) {
                $matches[] = [
                    'waitlist' => $waitlist,
                    'score' => $score,
                    'matched_preferences' => $this->getMatchedPreferences($waitlist, $slot),
                ];
            }
        }

        // Sort by score descending
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

        // Limit to max offers
        return array_slice($matches, 0, $settings->max_offers_per_slot);
    }

    /**
     * Calculate match score between waitlist and slot.
     */
    public function calculateMatchScore(Waitlist $waitlist, AvailabilitySlot $slot): float
    {
        $score = 0.0;

        // Time slot preference (0.3 max)
        $timeScore = $this->calculateTimePreferenceScore($waitlist, $slot);
        $score += $timeScore;

        // Day preference (0.2 max)
        $dayScore = $this->calculateDayPreferenceScore($waitlist, $slot);
        $score += $dayScore;

        // Service type match (0.15 max)
        $serviceScore = $this->calculateServiceTypeScore($waitlist, $slot);
        $score += $serviceScore;

        // Priority boost (0.15 max)
        $priorityScore = $this->calculatePriorityScore($waitlist);
        $score += $priorityScore;

        // Wait time factor (0.2 max)
        $waitScore = $this->calculateWaitTimeScore($waitlist);
        $score += $waitScore;

        return min(max($score, 0.0), 1.0);
    }

    /**
     * Calculate time preference score.
     */
    private function calculateTimePreferenceScore(Waitlist $waitlist, AvailabilitySlot $slot): float
    {
        $preferredTimes = $waitlist->preferred_time_slots ?? [];

        if (empty($preferredTimes)) {
            return self::SCORE_NEUTRAL;
        }

        $slotTime = substr($slot->start_time, 0, 5);

        if (in_array($slotTime, $preferredTimes)) {
            return self::SCORE_PERFECT_TIME;
        }

        foreach ($preferredTimes as $preferred) {
            $diff = abs($this->timeToMinutes($slotTime) - $this->timeToMinutes($preferred));
            if ($diff <= 60) {
                return self::SCORE_PARTIAL_TIME;
            }
        }

        return 0.0;
    }

    /**
     * Calculate day preference score.
     */
    private function calculateDayPreferenceScore(Waitlist $waitlist, AvailabilitySlot $slot): float
    {
        $preferredDays = $waitlist->preferred_days ?? [];

        if (empty($preferredDays)) {
            return self::SCORE_NEUTRAL;
        }

        $slotDay = strtolower(date('l', strtotime($slot->date)));

        if (in_array($slotDay, $preferredDays)) {
            return self::SCORE_PERFECT_DAY;
        }

        return 0.0;
    }

    /**
     * Calculate service type match score.
     */
    private function calculateServiceTypeScore(Waitlist $waitlist, AvailabilitySlot $slot): float
    {
        $serviceType = $waitlist->service_type ?? 'consultation';

        if ($serviceType === 'video' && $slot->is_video) {
            return self::SCORE_SERVICE_MATCH;
        }

        if ($serviceType === 'phone' && $slot->is_phone) {
            return self::SCORE_SERVICE_MATCH;
        }

        if ($serviceType === 'consultation') {
            return self::SCORE_SERVICE_FALLBACK;
        }

        return 0.0;
    }

    /**
     * Calculate priority score.
     */
    private function calculatePriorityScore(Waitlist $waitlist): float
    {
        return match ($waitlist->priority_level) {
            'urgent' => self::SCORE_PRIORITY_URGENT,
            'high' => self::SCORE_PRIORITY_HIGH,
            'medium' => self::SCORE_PRIORITY_MEDIUM,
            'low' => 0.0,
            default => self::SCORE_PRIORITY_MEDIUM,
        };
    }

    /**
     * Calculate wait time score (longer wait = higher priority).
     */
    private function calculateWaitTimeScore(Waitlist $waitlist): float
    {
        $waitDays = now()->diffInDays($waitlist->created_at);

        return min($waitDays * self::WAIT_TIME_MULTIPLIER, self::SCORE_MAX_WAIT);
    }

    /**
     * Get matched preferences for display.
     */
    public function getMatchedPreferences(Waitlist $waitlist, AvailabilitySlot $slot): array
    {
        $matched = [];

        // Check time
        $preferredTimes = $waitlist->preferred_time_slots ?? [];
        $slotTime = substr($slot->start_time, 0, 5);
        if (in_array($slotTime, $preferredTimes)) {
            $matched[] = 'Preferred time slot';
        }

        // Check day
        $preferredDays = $waitlist->preferred_days ?? [];
        $slotDay = strtolower(date('l', strtotime($slot->date)));
        if (in_array($slotDay, $preferredDays)) {
            $matched[] = 'Preferred day';
        }

        // Check service type
        $serviceType = $waitlist->service_type ?? 'consultation';
        if ($serviceType === 'video' && $slot->is_video) {
            $matched[] = 'Video consultation';
        }

        // Priority
        if ($waitlist->priority_level === 'urgent') {
            $matched[] = 'Urgent priority';
        }

        return $matched;
    }

    /**
     * Process a cancelled slot and create offers.
     */
    public function processCancelledSlot(AvailabilitySlot $slot): array
    {
        $matches = $this->findMatchesForSlot($slot);

        $offers = [];
        $settings = WaitlistAiSetting::firstOrCreate(
            ['doctor_id' => $slot->doctor_id],
            $this->getDefaultSettings()
        );

        foreach ($matches as $match) {
            $waitlist = $match['waitlist'];

            $offer = WaitlistMatchOffer::create([
                'waitlist_id' => $waitlist->id,
                'patient_id' => $waitlist->patient_id,
                'doctor_id' => $slot->doctor_id,
                'availability_slot_id' => $slot->id,
                'match_score' => $match['score'],
                'status' => 'pending',
                'expires_at' => now()->addMinutes($settings->offer_expiry_minutes ?? 60),
            ]);

            if ($settings->auto_send_offers) {
                $offer->markAsSent();
                // TODO: Trigger notification to patient
            }

            $offers[] = $offer;
        }

        Log::info('Waitlist AI processed cancelled slot', [
            'slot_id' => $slot->id,
            'matches_found' => count($matches),
            'offers_created' => count($offers),
        ]);

        return $offers;
    }

    /**
     * Get default settings.
     */
    private function getDefaultSettings(): array
    {
        return [
            'ai_matching_enabled' => false,
            'auto_send_offers' => false,
            'min_match_score' => 0.70,
            'offer_expiry_minutes' => 60,
            'max_offers_per_slot' => 3,
            'priority_override_auto' => false,
        ];
    }

    /**
     * Convert time string to minutes.
     */
    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return (int) $parts[0] * 60 + (int) ($parts[1] ?? 0);
    }
}
