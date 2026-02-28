<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\KioskCheckin;
use App\Models\KioskSession;
use App\Services\AuditLoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class KioskCheckinController extends Controller
{
    /**
     * Search for appointments by date/name/phone
     */
    public function searchAppointments(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'nullable|date',
            'name' => 'nullable|string|min:2|max:255',
            'phone' => 'nullable|string|min:10|max:15',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = Appointment::with(['doctor', 'patient'])
                ->where('status', 'confirmed')
                ->whereDate('appointment_date', '>=', today());

            // Filter by date if provided
            if ($request->date) {
                $query->whereDate('appointment_date', $request->date);
            }

            // Filter by name (patient or guest name)
            if ($request->name) {
                $query->where(function ($q) use ($request) {
                    $q->where('guest_name', 'LIKE', '%' . $request->name . '%')
                      ->orWhereHas('patient', function ($patientQuery) use ($request) {
                          $patientQuery->where('name', 'LIKE', '%' . $request->name . '%');
                      });
                });
            }

            // Filter by phone (patient or guest phone)
            if ($request->phone) {
                $query->where(function ($q) use ($request) {
                    $q->where('guest_phone', 'LIKE', '%' . $request->phone . '%')
                      ->orWhereHas('patient', function ($patientQuery) use ($request) {
                          $patientQuery->where('phone', 'LIKE', '%' . $request->phone . '%');
                      });
                });
            }

            // Exclude already checked-in appointments
            $query->whereDoesntHave('kioskCheckins');

            $limit = $request->limit ?? 20;
            $appointments = $query->orderBy('appointment_date')
                                 ->limit($limit)
                                 ->get();

            return response()->json([
                'success' => true,
                'data' => $appointments->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'appointment_number' => $appointment->appointment_number,
                        'appointment_date' => $appointment->appointment_date->toISOString(),
                        'patient_name' => $appointment->patient_name,
                        'patient_email' => $appointment->patient_email,
                        'patient_phone' => $appointment->patient_phone,
                        'doctor_name' => $appointment->doctor->name ?? 'Unknown Doctor',
                        'appointment_type' => $appointment->appointment_type,
                        'fee' => $appointment->fee,
                        'status' => $appointment->status,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Appointment search failed', [
                'params' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to search appointments',
            ], 500);
        }
    }

    /**
     * Get appointment details for check-in
     */
    public function getAppointment(Appointment $appointment): JsonResponse
    {
        try {
            // Check if appointment is already checked in
            $existingCheckin = $appointment->kioskCheckins()->first();

            if ($existingCheckin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment already checked in',
                    'data' => [
                        'checkin' => $existingCheckin,
                        'appointment' => $appointment->load(['doctor', 'patient']),
                    ],
                ], 409);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'appointment' => $appointment->load(['doctor', 'patient']),
                    'can_checkin' => $appointment->isToday() || $appointment->isUpcoming(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get appointment details failed', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get appointment details',
            ], 500);
        }
    }

    /**
     * Check in an appointment
     */
    public function checkin(Request $request, Appointment $appointment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kiosk_session_id' => 'required|string|exists:kiosk_sessions,session_id',
            'verification_method' => 'required|in:qr_code,id_card,biometric,manual',
            'verification_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if appointment is already checked in
            $existingCheckin = $appointment->kioskCheckins()->first();

            if ($existingCheckin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment already checked in',
                    'data' => $existingCheckin,
                ], 409);
            }

            // Verify kiosk session is active
            $session = KioskSession::where('session_id', $request->kiosk_session_id)
                                  ->where('status', 'active')
                                  ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or inactive kiosk session',
                ], 422);
            }

            // Create checkin record
            $checkin = KioskCheckin::create([
                'appointment_id' => $appointment->id,
                'kiosk_session_id' => $request->kiosk_session_id,
                'verification_method' => $request->verification_method,
                'verification_data' => $request->verification_data,
            ]);

            // Update appointment status if needed
            if ($appointment->status === 'confirmed') {
                $appointment->update(['status' => 'checked_in']);
            }

            // Log successful check-in
            AuditLoggingService::logKioskCheckin(
                $appointment->id,
                $request->kiosk_session_id,
                $request->verification_method,
                [
                    'appointment_number' => $appointment->appointment_number,
                    'patient_name' => $appointment->patient_name,
                    'doctor_name' => $appointment->doctor->name ?? 'Unknown',
                    'checkin_time' => $checkin->checkin_time,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful',
                'data' => [
                    'checkin' => $checkin->load(['appointment.doctor', 'kioskSession.kiosk']),
                    'appointment' => $appointment->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Check-in failed', [
                'appointment_id' => $appointment->id,
                'kiosk_session_id' => $request->kiosk_session_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Check-in failed',
            ], 500);
        }
    }

    /**
     * Get check-in history for a session
     */
    public function getSessionCheckins(KioskSession $session): JsonResponse
    {
        try {
            $checkins = $session->checkins()
                               ->with(['appointment.doctor', 'appointment.patient'])
                               ->orderBy('checkin_time', 'desc')
                               ->get();

            return response()->json([
                'success' => true,
                'data' => $checkins,
            ]);
        } catch (\Exception $e) {
            Log::error('Get session checkins failed', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get session checkins',
            ], 500);
        }
    }
}
