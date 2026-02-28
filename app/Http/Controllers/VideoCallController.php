<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\DailyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoCallController extends Controller
{
    protected $dailyService;

    public function __construct(DailyService $dailyService)
    {
        $this->dailyService = $dailyService;
    }

    /**
     * Get patient phone number
     */
    public function getPatientPhone($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        
        // Only doctor can view patient phone
        if (Auth::id() !== $appointment->doctor->user_id) {
            abort(403);
        }

        $patientPhone = $appointment->patient_phone;
        
        if (!$patientPhone) {
            return response()->json(['error' => 'Patient phone number not available'], 400);
        }

        return response()->json([
            'success' => true,
            'phone' => $patientPhone,
            'patient_name' => $appointment->patient->name
        ]);
    }

    /**
     * Generate video token for appointment
     */
    public function generateVideoToken(Request $request, $appointmentId)
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);

            if (Auth::id() !== $appointment->doctor->user_id && Auth::id() !== $appointment->patient_id) {
                abort(403);
            }

            $roomName = 'appointment-' . $appointmentId;

            // Create room
            $room = $this->dailyService->createRoom($roomName, 120);

            $appointment->update([
                'meeting_link' => route('video.room', ['appointment' => $appointmentId]),
                'meeting_id' => $roomName
            ]);

            return response()->json([
                'roomUrl' => $room['url'],
                'roomName' => $roomName
            ]);
        } catch (\Exception $e) {
            \Log::error('Video token error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create video room',
                'message' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * End video call
     */
    public function endVideoCall($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        
        if (Auth::id() !== $appointment->doctor->user_id) {
            abort(403);
        }

        if ($appointment->meeting_id) {
            $this->dailyService->deleteRoom($appointment->meeting_id);
        }

        $appointment->update(['status' => 'completed']);

        return response()->json(['success' => true]);
    }
}
