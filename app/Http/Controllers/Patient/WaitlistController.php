<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use App\Models\WaitlistPatientPreference;
use App\Services\WaitlistService;
use App\Services\WaitlistPreferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class WaitlistController extends Controller
{
    protected WaitlistService $waitlistService;
    protected WaitlistPreferenceService $preferenceService;

    public function __construct(WaitlistService $waitlistService, WaitlistPreferenceService $preferenceService)
    {
        $this->waitlistService = $waitlistService;
        $this->preferenceService = $preferenceService;
    }

    /**
     * Show the waitlist dashboard for the patient
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Get patient's active waitlists
        $activeWaitlists = Waitlist::where('patient_id', $user->id)
            ->where('status', 'active')
            ->with(['doctor:id,name,specialty,email', 'entries'])
            ->get();

        // Get patient preferences
        $preferences = WaitlistPatientPreference::where('patient_id', $user->id)->get();

        // Get recent waitlist entries (offered, pending, etc.)
        $recentEntries = WaitlistEntry::whereHas('waitlist', function ($query) use ($user) {
                $query->where('patient_id', $user->id);
            })
            ->with(['waitlist.doctor:id,name,specialty'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Calculate statistics
        $stats = [
            'total_active_waitlists' => $activeWaitlists->count(),
            'total_entries' => WaitlistEntry::whereHas('waitlist', function ($query) use ($user) {
                $query->where('patient_id', $user->id);
            })->count(),
            'pending_offers' => WaitlistEntry::whereHas('waitlist', function ($query) use ($user) {
                $query->where('patient_id', $user->id);
            })->where('status', 'offered')->count(),
        ];

        return view('patient.waitlist.dashboard', [
            'activeWaitlists' => $activeWaitlists,
            'preferences' => $preferences,
            'recentEntries' => $recentEntries,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the join waitlist form
     */
    public function joinForm()
    {
        $user = Auth::user();

        // Get available doctors (simplified - would typically have filters)
        $doctors = \App\Models\Doctor::with('user:id,name')->get();

        // Get user's existing preferences for suggested defaults
        $existingPreferences = WaitlistPatientPreference::where('patient_id', $user->id)->first();

        return view('patient.waitlist.join', [
            'doctors' => $doctors,
            'existingPreferences' => $existingPreferences,
        ]);
    }

    /**
     * Process joining a waitlist
     */
    public function join(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'service_type' => 'required|string|in:consultation,follow-up,urgent-care',
            'priority_level' => 'required|string|in:low,medium,high,urgent',
            'max_wait_days' => 'nullable|integer|min:1|max:365',
        ]);

        $user = Auth::user();

        try {
            $data = $request->only(['service_type', 'priority_level', 'max_wait_days']);
            $data['max_wait_days'] = $data['max_wait_days'] ?? 30;

            $waitlist = $this->waitlistService->addToWaitlist($user->id, $request->doctor_id, $data);

            return redirect()->route('patient.waitlist.status', $waitlist->id)
                ->with('success', 'Successfully joined the waitlist!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show waitlist status and position
     */
    public function status($waitlistId)
    {
        $user = Auth::user();

        $waitlist = Waitlist::where('id', $waitlistId)
            ->where('patient_id', $user->id)
            ->with(['doctor:id,name,specialty,email', 'entries'])
            ->firstOrFail();

        // Get position information
        $position = $this->waitlistService->getWaitlistPosition($waitlistId);

        // Get available slots for context
        $availableSlots = $this->waitlistService->findAvailableSlots($waitlist->doctor_id, 30);

        // Get patient preferences for this doctor
        $preferences = WaitlistPatientPreference::where('patient_id', $user->id)
            ->where('doctor_id', $waitlist->doctor_id)
            ->first();

        return view('patient.waitlist.status', [
            'waitlist' => $waitlist,
            'position' => $position,
            'availableSlots' => $availableSlots,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Show patient preferences management
     */
    public function preferences()
    {
        $user = Auth::user();

        $preferences = WaitlistPatientPreference::where('patient_id', $user->id)
            ->with('doctor.user:id,name')
            ->get();

        // Get suggested preferences based on learning
        $suggestedPreferences = $this->preferenceService->getSuggestedPreferences($user->id);

        // Get preference analytics
        $analytics = $this->preferenceService->getPreferenceAnalytics($user->id);

        return view('patient.waitlist.preferences', [
            'preferences' => $preferences,
            'suggestedPreferences' => $suggestedPreferences,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Show slot offer response interface
     */
    public function showOffer($entryId)
    {
        $user = Auth::user();

        $entry = WaitlistEntry::where('id', $entryId)
            ->whereHas('waitlist', function ($query) use ($user) {
                $query->where('patient_id', $user->id);
            })
            ->with(['waitlist.doctor:id,name,specialty', 'appointment'])
            ->firstOrFail();

        if (!$entry->isOffered()) {
            return redirect()->route('patient.waitlist.status', $entry->waitlist->id)
                ->with('error', 'This offer is no longer valid.');
        }

        return view('patient.waitlist.offer', [
            'entry' => $entry,
        ]);
    }

    /**
     * Accept a slot offer
     */
    public function acceptOffer(Request $request, $entryId)
    {
        $user = Auth::user();

        $entry = WaitlistEntry::where('id', $entryId)
            ->whereHas('waitlist', function ($query) use ($user) {
                $query->where('patient_id', $user->id);
            })
            ->firstOrFail();

        try {
            $this->waitlistService->acceptSlotOffer($entryId);

            return redirect()->route('patient.appointments')
                ->with('success', 'Slot accepted successfully! Your appointment has been confirmed.');
        } catch (\Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Decline a slot offer
     */
    public function declineOffer(Request $request, $entryId)
    {
        $user = Auth::user();

        $entry = WaitlistEntry::where('id', $entryId)
            ->whereHas('waitlist', function ($query) use ($user) {
                $query->where('patient_id', $user->id);
            })
            ->firstOrFail();

        try {
            $this->waitlistService->declineSlotOffer($entryId);

            return redirect()->route('patient.waitlist.status', $entry->waitlist->id)
                ->with('success', 'Offer declined. You will remain on the waitlist for other opportunities.');
        } catch (\Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove patient from waitlist
     */
    public function leave($waitlistId)
    {
        $user = Auth::user();

        $waitlist = Waitlist::where('id', $waitlistId)
            ->where('patient_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        try {
            $this->waitlistService->removeFromWaitlist($waitlistId);

            return redirect()->route('patient.waitlist.dashboard')
                ->with('success', 'You have been removed from the waitlist.');
        } catch (\Exception $e) {
            return back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Get real-time waitlist position (for AJAX)
     */
    public function getPosition($waitlistId)
    {
        $user = Auth::user();

        $waitlist = Waitlist::where('id', $waitlistId)
            ->where('patient_id', $user->id)
            ->firstOrFail();

        $position = $this->waitlistService->getWaitlistPosition($waitlistId);

        return response()->json([
            'position' => $position,
            'waitlist_status' => $waitlist->status,
        ]);
    }

    /**
     * Get matching recommendations for a doctor
     */
    public function getRecommendations(Request $request, $doctorId)
    {
        $user = Auth::user();

        $recommendations = $this->preferenceService->getMatchingRecommendations($user->id, $doctorId);

        return response()->json([
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Auto-accept configuration for quick bookings
     */
    public function configureAutoAccept(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'threshold_days' => 'required|integer|min:1|max:30',
            'enabled' => 'boolean',
        ]);

        $user = Auth::user();

        $preference = WaitlistPatientPreference::updateOrCreate(
            [
                'patient_id' => $user->id,
                'doctor_id' => $request->doctor_id,
            ],
            [
                'auto_accept_threshold' => $request->threshold_days,
                'notification_settings' => [
                    'auto_accept_enabled' => $request->enabled ?? false,
                    'email' => true,
                    'sms' => false,
                ],
            ]
        );

        return response()->json([
            'message' => 'Auto-accept configuration updated successfully.',
            'preference' => $preference,
        ]);
    }
}
