<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;
use App\Models\WaitlistEntry;
use App\Models\WaitlistPatientPreference;
use App\Models\Doctor;
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
     * Show the doctor waitlist dashboard
     */
    public function dashboard()
    {
        $doctor = Auth::user()->doctor;

        // Get active waitlists for this doctor
        $activeWaitlists = Waitlist::where('doctor_id', $doctor->id)
            ->where('status', 'active')
            ->with(['patient:id,name,email', 'entries'])
            ->orderByRaw("
                CASE priority_level
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END
            ")
            ->orderBy('created_at')
            ->paginate(20);

        // Get waitlist statistics
        $stats = $this->waitlistService->getWaitlistStatistics($doctor->id);

        // Get recent activity
        $recentEntries = WaitlistEntry::whereHas('waitlist', function ($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })
            ->with(['waitlist.patient:id,name', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('doctor.waitlist.dashboard', [
            'activeWaitlists' => $activeWaitlists,
            'stats' => $stats,
            'recentEntries' => $recentEntries,
            'doctor' => $doctor,
        ]);
    }

    /**
     * Show waitlist management interface
     */
    public function manage(Request $request)
    {
        $doctor = Auth::user()->doctor;

        // Build query for waitlists
        $query = Waitlist::where('doctor_id', $doctor->id)
            ->with(['patient:id,name,email', 'entries', 'patientPreferences']);

        // Apply filters
        $status = $request->get('status', 'active');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $priority = $request->get('priority');
        if ($priority) {
            $query->where('priority_level', $priority);
        }

        $search = $request->get('search');
        if ($search) {
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'priority');
        $sortOrder = $request->get('sort_order', 'asc');

        switch ($sortBy) {
            case 'priority':
                $query->orderByRaw("
                    CASE priority_level
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                    END {$sortOrder}
                ");
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'patient_name':
                $query->join('users', 'waitlists.patient_id', '=', 'users.id')
                      ->orderBy('users.name', $sortOrder)
                      ->select('waitlists.*');
                break;
        }

        $waitlists = $query->paginate(20);

        // Get available slots for context
        $availableSlots = $this->waitlistService->findAvailableSlots($doctor->id, 14);

        return view('doctor.waitlist.manage', [
            'waitlists' => $waitlists,
            'availableSlots' => $availableSlots,
            'filters' => $request->only(['status', 'priority', 'search', 'sort_by', 'sort_order']),
            'doctor' => $doctor,
        ]);
    }

    /**
     * Show specific patient waitlist details
     */
    public function showPatient($waitlistId)
    {
        $doctor = Auth::user()->doctor;

        $waitlist = Waitlist::where('id', $waitlistId)
            ->where('doctor_id', $doctor->id)
            ->with([
                'patient:id,name,email,phone',
                'entries',
                'patientPreferences' => function ($query) use ($doctor) {
                    $query->where('doctor_id', $doctor->id);
                }
            ])
            ->firstOrFail();

        return view('doctor.waitlist.show-patient', [
            'waitlist' => $waitlist,
            'doctor' => $doctor,
        ]);
    }

    /**
     * Update patient priority
     */
    public function updatePriority(Request $request, $waitlistId)
    {
        $request->validate([
            'priority_level' => 'required|string|in:low,medium,high,urgent',
        ]);

        $doctor = Auth::user()->doctor;

        $waitlist = Waitlist::where('id', $waitlistId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $waitlist->update(['priority_level' => $request->priority_level]);

        return response()->json([
            'success' => true,
            'message' => 'Priority updated successfully.',
            'waitlist' => $waitlist->fresh(),
        ]);
    }

    /**
     * Update waitlist status
     */
    public function updateStatus(Request $request, $waitlistId)
    {
        $request->validate([
            'status' => 'required|string|in:active,paused,cancelled',
        ]);

        $doctor = Auth::user()->doctor;

        $waitlist = Waitlist::where('id', $waitlistId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $waitlist->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'waitlist' => $waitlist->fresh(),
        ]);
    }

    /**
     * Manually offer a slot to a patient
     */
    public function offerSlot(Request $request, $waitlistId)
    {
        $request->validate([
            'slot_date' => 'required|date|after:today',
            'slot_time' => 'required|date_format:H:i',
        ]);

        $doctor = Auth::user()->doctor;

        $waitlist = Waitlist::where('id', $waitlistId)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'active')
            ->firstOrFail();

        // Check if slot is available
        $availableSlots = $this->waitlistService->findAvailableSlots($doctor->id, 30);
        $requestedSlot = $request->slot_date . ' ' . $request->slot_time;

        $slotExists = collect($availableSlots)->contains(function ($slot) use ($requestedSlot) {
            return $slot['date'] . ' ' . $slot['time'] === $requestedSlot;
        });

        if (!$slotExists) {
            return response()->json([
                'success' => false,
                'message' => 'Selected slot is not available.',
            ], 422);
        }

        // Create manual entry
        $entry = WaitlistEntry::create([
            'waitlist_id' => $waitlist->id,
            'slot_date' => $request->slot_date,
            'slot_time' => $request->slot_time,
            'status' => 'pending',
        ]);

        // Offer to patient
        $this->waitlistService->offerSlotToPatient($entry);

        return response()->json([
            'success' => true,
            'message' => 'Slot offered to patient successfully.',
            'entry' => $entry,
        ]);
    }

    /**
     * Cancel pending offers
     */
    public function cancelOffers(Request $request, $waitlistId)
    {
        $request->validate([
            'entry_ids' => 'required|array',
            'entry_ids.*' => 'exists:waitlist_entries,id',
        ]);

        $doctor = Auth::user()->doctor;

        $waitlist = Waitlist::where('id', $waitlistId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $entries = WaitlistEntry::whereIn('id', $request->entry_ids)
            ->where('waitlist_id', $waitlist->id)
            ->where('status', 'offered')
            ->get();

        foreach ($entries as $entry) {
            $entry->update(['status' => 'cancelled']);
        }

        return response()->json([
            'success' => true,
            'message' => count($entries) . ' offer(s) cancelled successfully.',
            'cancelled_count' => $entries->count(),
        ]);
    }

    /**
     * Get waitlist statistics for AJAX
     */
    public function getStats()
    {
        $doctor = Auth::user()->doctor;
        $stats = $this->waitlistService->getWaitlistStatistics($doctor->id);

        return response()->json([
            'stats' => $stats,
        ]);
    }

    /**
     * Search patients for waitlist management
     */
    public function searchPatients(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $doctor = Auth::user()->doctor;

        $patients = \App\Models\User::where('role', 'patient')
            ->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->query}%")
                      ->orWhere('email', 'like', "%{$request->query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json([
            'patients' => $patients,
        ]);
    }

    /**
     * Add patient to waitlist manually
     */
    public function addPatient(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'service_type' => 'required|string|in:consultation,follow-up,urgent-care',
            'priority_level' => 'required|string|in:low,medium,high,urgent',
        ]);

        $doctor = Auth::user()->doctor;

        try {
            $data = [
                'service_type' => $request->service_type,
                'priority_level' => $request->priority_level,
                'max_wait_days' => 30,
            ];

            $waitlist = $this->waitlistService->addToWaitlist($request->patient_id, $doctor->id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Patient added to waitlist successfully.',
                'waitlist' => $waitlist->load('patient:id,name,email'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk operations for waitlist management
     */
    public function bulkOperations(Request $request)
    {
        $request->validate([
            'operation' => 'required|string|in:update_priority,update_status,remove_patients',
            'waitlist_ids' => 'required|array',
            'waitlist_ids.*' => 'exists:waitlists,id',
            'value' => 'required_if:operation,update_priority,update_status',
        ]);

        $doctor = Auth::user()->doctor;

        // Verify all waitlists belong to this doctor
        $waitlists = Waitlist::whereIn('id', $request->waitlist_ids)
            ->where('doctor_id', $doctor->id)
            ->get();

        if ($waitlists->count() !== count($request->waitlist_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some waitlists do not belong to you.',
            ], 403);
        }

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($waitlists as $waitlist) {
            try {
                switch ($request->operation) {
                    case 'update_priority':
                        $waitlist->update(['priority_level' => $request->value]);
                        break;
                    case 'update_status':
                        $waitlist->update(['status' => $request->value]);
                        break;
                    case 'remove_patients':
                        $this->waitlistService->removeFromWaitlist($waitlist->id);
                        break;
                }
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'waitlist_id' => $waitlist->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk operation completed: {$results['success']} successful, {$results['failed']} failed.",
            'results' => $results,
        ]);
    }

    /**
     * Export waitlist data
     */
    public function export(Request $request)
    {
        $doctor = Auth::user()->doctor;

        $waitlists = Waitlist::where('doctor_id', $doctor->id)
            ->with(['patient:id,name,email,phone', 'entries'])
            ->get();

        $filename = 'waitlist_' . $doctor->id . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($waitlists) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Patient Name',
                'Patient Email',
                'Patient Phone',
                'Service Type',
                'Priority Level',
                'Status',
                'Created At',
                'Position',
                'Offered Slots Count',
                'Accepted Slots Count'
            ]);

            foreach ($waitlists as $waitlist) {
                fputcsv($file, [
                    $waitlist->patient->name,
                    $waitlist->patient->email,
                    $waitlist->patient->phone,
                    $waitlist->service_type,
                    $waitlist->priority_level,
                    $waitlist->status,
                    $waitlist->created_at->format('Y-m-d H:i:s'),
                    '', // Position would need additional calculation
                    $waitlist->entries->where('status', 'offered')->count(),
                    $waitlist->entries->where('status', 'accepted')->count(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get waitlist analytics data
     */
    public function analytics(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $timeframe = $request->get('timeframe', '30days');

        $startDate = match($timeframe) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '1year' => now()->subYear(),
            default => now()->subDays(30),
        };

        // Get comprehensive analytics
        $analytics = [
            'overview' => $this->getWaitlistOverview($doctor->id, $startDate),
            'priority_distribution' => $this->getPriorityDistribution($doctor->id, $startDate),
            'wait_time_analysis' => $this->getWaitTimeAnalysis($doctor->id, $startDate),
            'conversion_rates' => $this->getConversionRates($doctor->id, $startDate),
            'trends' => $this->getTrends($doctor->id, $startDate),
        ];

        return response()->json([
            'analytics' => $analytics,
            'timeframe' => $timeframe,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Get waitlist overview statistics
     */
    private function getWaitlistOverview($doctorId, $startDate)
    {
        return [
            'total_waitlists' => Waitlist::where('doctor_id', $doctorId)
                ->where('created_at', '>=', $startDate)->count(),
            'active_waitlists' => Waitlist::where('doctor_id', $doctorId)
                ->where('status', 'active')->count(),
            'fulfilled_waitlists' => Waitlist::where('doctor_id', $doctorId)
                ->where('status', 'fulfilled')->count(),
            'cancelled_waitlists' => Waitlist::where('doctor_id', $doctorId)
                ->where('status', 'cancelled')->count(),
        ];
    }

    /**
     * Get priority distribution
     */
    private function getPriorityDistribution($doctorId, $startDate)
    {
        return Waitlist::where('doctor_id', $doctorId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('priority_level, COUNT(*) as count')
            ->groupBy('priority_level')
            ->pluck('count', 'priority_level')
            ->toArray();
    }

    /**
     * Get wait time analysis
     */
    private function getWaitTimeAnalysis($doctorId, $startDate)
    {
        $fulfilledWaitlists = Waitlist::where('doctor_id', $doctorId)
            ->where('status', 'fulfilled')
            ->where('created_at', '>=', $startDate)
            ->get();

        $waitTimes = [];
        foreach ($fulfilledWaitlists as $waitlist) {
            $waitTimes[] = $waitlist->updated_at->diffInDays($waitlist->created_at);
        }

        return [
            'average_wait_days' => !empty($waitTimes) ? round(array_sum($waitTimes) / count($waitTimes), 1) : 0,
            'median_wait_days' => !empty($waitTimes) ? $waitTimes[array_rand($waitTimes)] : 0, // Simplified
            'min_wait_days' => !empty($waitTimes) ? min($waitTimes) : 0,
            'max_wait_days' => !empty($waitTimes) ? max($waitTimes) : 0,
        ];
    }

    /**
     * Get conversion rates
     */
    private function getConversionRates($doctorId, $startDate)
    {
        $totalEntries = WaitlistEntry::whereHas('waitlist', function ($query) use ($doctorId, $startDate) {
            $query->where('doctor_id', $doctorId)
                  ->where('created_at', '>=', $startDate);
        })->count();

        $acceptedEntries = WaitlistEntry::whereHas('waitlist', function ($query) use ($doctorId, $startDate) {
            $query->where('doctor_id', $doctorId)
                  ->where('created_at', '>=', $startDate);
        })->where('status', 'accepted')->count();

        return [
            'offer_to_acceptance_rate' => $totalEntries > 0 ? round(($acceptedEntries / $totalEntries) * 100, 1) : 0,
            'total_offers' => $totalEntries,
            'accepted_offers' => $acceptedEntries,
        ];
    }

    /**
     * Get trends data
     */
    private function getTrends($doctorId, $startDate)
    {
        $trends = [];
        $current = clone $startDate;

        while ($current <= now()) {
            $dateStr = $current->format('Y-m-d');

            $trends[$dateStr] = [
                'new_waitlists' => Waitlist::where('doctor_id', $doctorId)
                    ->whereDate('created_at', $dateStr)->count(),
                'fulfilled_waitlists' => Waitlist::where('doctor_id', $doctorId)
                    ->whereDate('updated_at', $dateStr)
                    ->where('status', 'fulfilled')->count(),
                'offers_made' => WaitlistEntry::whereHas('waitlist', function ($query) use ($doctorId, $dateStr) {
                    $query->where('doctor_id', $doctorId)
                          ->whereDate('created_at', $dateStr);
                })->count(),
            ];

            $current->addDay();
        }

        return $trends;
    }
}
