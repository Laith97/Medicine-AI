<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WaitlistPatientPreference;
use App\Services\WaitlistPreferenceService;
use App\Http\Requests\StoreWaitlistPreferenceRequest;
use App\Http\Requests\UpdateWaitlistPreferenceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WaitlistPreferenceController extends Controller
{
    protected WaitlistPreferenceService $preferenceService;

    public function __construct(WaitlistPreferenceService $preferenceService)
    {
        $this->preferenceService = $preferenceService;
    }

    /**
     * Get waitlist preferences for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $doctorId = $request->query('doctor_id');

        $preferences = WaitlistPatientPreference::where('patient_id', $user->id)
            ->when($doctorId, function ($query) use ($doctorId) {
                return $query->where('doctor_id', $doctorId);
            })
            ->with(['doctor:id,name', 'patient:id,name'])
            ->get();

        // Include suggested preferences based on learning
        $suggestedPreferences = $this->preferenceService->getSuggestedPreferences($user->id);

        return response()->json([
            'preferences' => $preferences,
            'suggested' => $suggestedPreferences,
        ]);
    }

    /**
     * Store a new waitlist preference
     */
    public function store(StoreWaitlistPreferenceRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = $request->validated();
        $data['patient_id'] = $user->id;

        $preference = WaitlistPatientPreference::create($data);

        // Update learning data
        $this->preferenceService->updateLearningData($user->id, $data);

        return response()->json([
            'message' => 'Waitlist preference created successfully',
            'preference' => $preference->load(['doctor:id,name', 'patient:id,name']),
        ], 201);
    }

    /**
     * Update a specific waitlist preference
     */
    public function update(UpdateWaitlistPreferenceRequest $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $preference = WaitlistPatientPreference::where('id', $id)
            ->where('patient_id', $user->id)
            ->firstOrFail();

        $data = $request->validated();
        $preference->update($data);

        // Update learning data
        $this->preferenceService->updateLearningData($user->id, $data);

        return response()->json([
            'message' => 'Waitlist preference updated successfully',
            'preference' => $preference->fresh()->load(['doctor:id,name', 'patient:id,name']),
        ]);
    }

    /**
     * Get smart matching recommendations for a doctor
     */
    public function getMatchingRecommendations(Request $request, int $doctorId): JsonResponse
    {
        $user = Auth::user();

        $recommendations = $this->preferenceService->getMatchingRecommendations($user->id, $doctorId);

        return response()->json([
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Get preference analytics for the user
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();

        $analytics = $this->preferenceService->getPreferenceAnalytics($user->id);

        return response()->json([
            'analytics' => $analytics,
        ]);
    }
}
