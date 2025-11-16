<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppointmentBroadcastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentRealtimeController extends Controller
{
    protected AppointmentBroadcastService $broadcastService;

    public function __construct(AppointmentBroadcastService $broadcastService)
    {
        $this->broadcastService = $broadcastService;
    }

    /**
     * Get today's appointments with real-time subscription
     */
    public function getTodaysAppointments(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,confirmed,cancelled,completed,no_show',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
        ]);

        $filters = array_filter([
            'status' => $request->status,
            'doctor_id' => $request->doctor_id,
        ]);

        $result = $this->broadcastService->getTodaysAppointments(Auth::user(), $filters);

        return response()->json($result);
    }

    /**
     * Subscribe to real-time appointment updates
     */
    public function subscribeToUpdates(Request $request)
    {
        $request->validate([
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|string|in:pending,confirmed,cancelled,completed,no_show',
            'filters.doctor_id' => 'nullable|integer|exists:doctors,id',
        ]);

        $filters = $request->filters ?? [];

        $success = $this->broadcastService->subscribeToAppointments(Auth::user(), $filters);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Successfully subscribed to appointment updates',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to subscribe to appointment updates'
        ], 500);
    }

    /**
     * Unsubscribe from real-time appointment updates
     */
    public function unsubscribeFromUpdates(Request $request)
    {
        $success = $this->broadcastService->unsubscribeFromAppointments(Auth::user());

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Successfully unsubscribed from appointment updates'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to unsubscribe from appointment updates'
        ], 500);
    }
}
