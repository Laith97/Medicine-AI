<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kiosk;
use App\Models\KioskSession;
use App\Models\KioskCheckin;
use App\Models\KioskPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class KioskController extends Controller
{
    /**
     * Display a listing of kiosks
     */
    public function index(): View
    {
        $kiosks = Kiosk::with(['sessions' => function($query) {
            $query->latest()->limit(1);
        }])->get();

        return view('admin.kiosks.index', compact('kiosks'));
    }

    /**
     * Show the form for creating a new kiosk
     */
    public function create(): View
    {
        return view('admin.kiosks.create');
    }

    /**
     * Store a newly created kiosk
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'serial_number' => 'required|string|unique:kiosks,serial_number',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $kiosk = Kiosk::create([
                'name' => $request->name,
                'location' => $request->location,
                'serial_number' => $request->serial_number,
                'status' => 'inactive', // Start as inactive until registered
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kiosk created successfully',
                'data' => $kiosk,
            ]);
        } catch (\Exception $e) {
            Log::error('Kiosk creation failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create kiosk',
            ], 500);
        }
    }

    /**
     * Display the specified kiosk
     */
    public function show(Kiosk $kiosk): View
    {
        $kiosk->load([
            'sessions' => function($query) {
                $query->latest()->limit(10);
            },
            'sessions.checkins.appointment.patient',
            'sessions.payments.appointment.patient'
        ]);

        $stats = [
            'total_sessions' => $kiosk->sessions()->count(),
            'active_sessions' => $kiosk->sessions()->active()->count(),
            'total_checkins' => $kiosk->sessions()->join('kiosk_checkins', 'kiosk_sessions.session_id', '=', 'kiosk_checkins.kiosk_session_id')->count(),
            'total_payments' => $kiosk->sessions()->join('kiosk_payments', 'kiosk_sessions.session_id', '=', 'kiosk_payments.kiosk_session_id')->count(),
            'total_revenue' => $kiosk->sessions()->join('kiosk_payments', 'kiosk_sessions.session_id', '=', 'kiosk_payments.kiosk_session_id')
                ->where('kiosk_payments.status', 'succeeded')
                ->sum('kiosk_payments.amount') / 100, // Convert cents to dollars
        ];

        return view('admin.kiosks.show', compact('kiosk', 'stats'));
    }

    /**
     * Show the form for editing the specified kiosk
     */
    public function edit(Kiosk $kiosk): View
    {
        return view('admin.kiosks.edit', compact('kiosk'));
    }

    /**
     * Update the specified kiosk
     */
    public function update(Request $request, Kiosk $kiosk): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'configuration' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $kiosk->update([
                'name' => $request->name,
                'location' => $request->location,
                'status' => $request->status,
                'configuration' => $request->configuration,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kiosk updated successfully',
                'data' => $kiosk,
            ]);
        } catch (\Exception $e) {
            Log::error('Kiosk update failed', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update kiosk',
            ], 500);
        }
    }

    /**
     * Remove the specified kiosk
     */
    public function destroy(Kiosk $kiosk): JsonResponse
    {
        try {
            // Check if kiosk has active sessions
            if ($kiosk->sessions()->active()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete kiosk with active sessions',
                ], 422);
            }

            $kiosk->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kiosk deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Kiosk deletion failed', [
                'kiosk_id' => $kiosk->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete kiosk',
            ], 500);
        }
    }

    /**
     * Get kiosk statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_kiosks' => Kiosk::count(),
                'active_kiosks' => Kiosk::active()->count(),
                'online_kiosks' => Kiosk::active()->get()->filter->isOnline()->count(),
                'total_sessions_today' => KioskSession::whereDate('start_time', today())->count(),
                'active_sessions' => KioskSession::active()->count(),
                'total_checkins_today' => KioskCheckin::whereDate('checkin_time', today())->count(),
                'total_payments_today' => KioskPayment::whereDate('created_at', today())->where('status', 'succeeded')->count(),
                'total_revenue_today' => KioskPayment::whereDate('created_at', today())->where('status', 'succeeded')->sum('amount') / 100,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Kiosk statistics retrieval failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
            ], 500);
        }
    }
}
