<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use App\Models\WaitlistMatchOffer;
use App\Models\WaitlistAiSetting;
use App\Models\Appointment;
use App\Services\AIAssistant;
use App\Services\AiWaitlistMatcher;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WaitlistFillerController extends Controller
{
    public function __construct(
        private AIAssistant $aiAssistant,
        private AiWaitlistMatcher $waitlistMatcher,
        private NotificationService $notificationService
    ) {}

    /**
     * Get AI settings for waitlist.
     */
    public function getSettings(): JsonResponse
    {
        $settings = WaitlistAiSetting::firstOrCreate(
            ['doctor_id' => Auth::id()],
            [
                'ai_matching_enabled' => false,
                'auto_send_offers' => false,
                'min_match_score' => 0.70,
                'offer_expiry_minutes' => 60,
                'max_offers_per_slot' => 3,
                'priority_override_auto' => false,
            ]
        );

        return response()->json(['settings' => $settings]);
    }

    /**
     * Update AI settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ai_matching_enabled' => 'sometimes|boolean',
            'auto_send_offers' => 'sometimes|boolean',
            'min_match_score' => 'sometimes|numeric|min:0.1|max:1',
            'offer_expiry_minutes' => 'sometimes|integer|min:5|max:1440',
            'max_offers_per_slot' => 'sometimes|integer|min:1|max:10',
            'priority_override_auto' => 'sometimes|boolean',
        ]);

        $settings = WaitlistAiSetting::updateOrCreate(
            ['doctor_id' => Auth::id()],
            $validated
        );

        return response()->json([
            'settings' => $settings,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Find matching patients for a cancelled slot.
     */
    public function findMatches(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slot_id' => 'required|exists:availability_slots,id',
        ]);

        $slot = AvailabilitySlot::findOrFail($validated['slot_id']);

        if ($slot->doctor_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $settings = WaitlistAiSetting::firstOrCreate(['doctor_id' => Auth::id()]);

        // Delegate to service for matching logic
        $matches = $this->waitlistMatcher->findMatchesForSlot($slot);

        // Filter by threshold and limit
        $filteredMatches = array_filter($matches, function ($match) use ($settings) {
            return $match['score'] >= ($settings->min_match_score ?? 0.70);
        });

        $limitedMatches = array_slice($filteredMatches, 0, $settings->max_offers_per_slot ?? 3);

        return response()->json([
            'slot' => $slot,
            'matches' => $limitedMatches,
            'count' => count($limitedMatches),
        ]);
    }

    /**
     * Send offer to a matched patient.
     */
    public function sendOffer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'waitlist_id' => 'required|exists:waitlists,id',
            'slot_id' => 'required|exists:availability_slots,id',
        ]);

        $slot = AvailabilitySlot::findOrFail($validated['slot_id']);

        if ($slot->doctor_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $waitlist = Waitlist::findOrFail($validated['waitlist_id']);

        // Verify waitlist belongs to this doctor
        if ($waitlist->doctor_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized - waitlist does not belong to you'], 403);
        }

        $settings = WaitlistAiSetting::firstOrCreate(['doctor_id' => Auth::id()]);

        // Calculate match score using service
        $matchScore = $this->waitlistMatcher->calculateMatchScore($waitlist, $slot);

        // Check if score meets threshold
        if ($matchScore < ($settings->min_match_score ?? 0.70)) {
            return response()->json([
                'error' => 'Match score below threshold',
                'score' => $matchScore,
                'threshold' => $settings->min_match_score,
            ], 400);
        }

        // Create offer
        $offer = WaitlistMatchOffer::create([
            'waitlist_id' => $waitlist->id,
            'patient_id' => $waitlist->patient_id,
            'doctor_id' => Auth::id(),
            'availability_slot_id' => $slot->id,
            'match_score' => $matchScore,
            'status' => 'pending',
            'expires_at' => now()->addMinutes($settings->offer_expiry_minutes ?? 60),
        ]);

        if ($settings->auto_send_offers) {
            $this->sendOfferNotification($offer);
        }

        return response()->json([
            'offer' => $offer,
            'message' => $settings->auto_send_offers ? 'Offer sent automatically' : 'Offer created - ready to send',
        ], 201);
    }

    /**
     * Send notification to patient about offer.
     */
    private function sendOfferNotification(WaitlistMatchOffer $offer): void
    {
        try {
            $offer->markAsSent();

            $this->notificationService->sendWaitlistOfferNotification($offer);

            Log::info('Waitlist offer sent to patient', [
                'offer_id' => $offer->id,
                'patient_id' => $offer->patient_id,
                'slot_id' => $offer->availability_slot_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send waitlist offer notification', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - offer is already marked as sent
        }
    }

    /**
     * List pending offers.
     */
    public function indexPendingOffers(): JsonResponse
    {
        $offers = WaitlistMatchOffer::where('doctor_id', Auth::id())
            ->with(['patient', 'waitlist', 'availabilitySlot'])
            ->valid()
            ->orderBy('match_score', 'desc')
            ->paginate(15);

        return response()->json($offers);
    }

    /**
     * Accept offer (patient side).
     */
    public function acceptOffer(int $id): JsonResponse
    {
        // Use query-scoped authorization to prevent enumeration
        $offer = WaitlistMatchOffer::where('id', $id)
            ->where('patient_id', Auth::id())
            ->first();

        if (!$offer) {
            return response()->json(['error' => 'Offer not found'], 404);
        }

        if (!$offer->isValid()) {
            return response()->json(['error' => 'Offer has expired'], 400);
        }

        $offer->accept();

        // Create appointment
        $appointment = Appointment::create([
            'patient_id' => $offer->patient_id,
            'doctor_id' => $offer->doctor_id,
            'slot_id' => $offer->availability_slot_id,
            'appointment_date' => $offer->availabilitySlot->date,
            'start_time' => $offer->availabilitySlot->start_time,
            'end_time' => $offer->availabilitySlot->end_time,
            'status' => 'confirmed',
            'is_from_waitlist' => true,
            'waitlist_offer_id' => $offer->id,
        ]);

        // Link appointment and mark as booked (refresh to get current status)
        $offer->refresh();
        $offer->markAsBooked();
        $offer->update(['appointment_id' => $appointment->id]);

        return response()->json([
            'offer' => $offer->fresh(),
            'appointment' => $appointment,
            'message' => 'Offer accepted and appointment booked',
        ]);
    }

    /**
     * Decline offer.
     */
    public function declineOffer(Request $request, int $id): JsonResponse
    {
        // Use query-scoped authorization to prevent enumeration
        $offer = WaitlistMatchOffer::where('id', $id)
            ->where('patient_id', Auth::id())
            ->first();

        if (!$offer) {
            return response()->json(['error' => 'Offer not found'], 404);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $offer->decline($validated['reason'] ?? null);

        return response()->json([
            'message' => 'Offer declined',
        ]);
    }

    /**
     * Get offer analytics.
     */
    public function getAnalytics(): JsonResponse
    {
        $doctorId = Auth::id();

        $totalOffers = WaitlistMatchOffer::where('doctor_id', $doctorId)->count();
        $acceptedOffers = WaitlistMatchOffer::where('doctor_id', $doctorId)
            ->where('status', 'accepted')
            ->count();
        $declinedOffers = WaitlistMatchOffer::where('doctor_id', $doctorId)
            ->where('status', 'declined')
            ->count();
        $expiredOffers = WaitlistMatchOffer::where('doctor_id', $doctorId)
            ->where('status', 'expired')
            ->count();

        $acceptRate = $totalOffers > 0 ? round(($acceptedOffers / $totalOffers) * 100, 1) : 0;

        $avgMatchScore = WaitlistMatchOffer::where('doctor_id', $doctorId)
            ->whereNotNull('match_score')
            ->avg('match_score');

        return response()->json([
            'total_offers' => $totalOffers,
            'accepted' => $acceptedOffers,
            'declined' => $declinedOffers,
            'expired' => $expiredOffers,
            'accept_rate' => $acceptRate,
            'avg_match_score' => round($avgMatchScore ?? 0, 3),
        ]);
    }
}
