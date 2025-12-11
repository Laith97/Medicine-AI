<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Kiosk;
use App\Models\KioskSession;
use App\Services\AuditLoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class KioskController extends Controller
{
    /**
     * Display the kiosk welcome screen
     */
    public function welcome(Request $request): View
    {
        // Validate the token and doctor parameters if provided
        $token = $request->query('token');
        $doctorId = $request->query('doctor');

        if ($token && $doctorId) {
            // Verify the token is valid for this doctor
            $kioskConfig = \DB::table('doctor_kiosk_configs')
                ->where('kiosk_token', $token)
                ->where('doctor_id', $doctorId)
                ->first();

            if (!$kioskConfig) {
                // Check if the doctor exists using DB query to avoid model issues
                $doctorExists = \DB::table('doctors')->where('id', $doctorId)->exists();
                if (!$doctorExists) {
                    // Check if this might be a user ID instead of doctor ID
                    $userAsDoctor = \DB::table('users')->where('id', $doctorId)->where('role', 'doctor')->first();
                    if ($userAsDoctor) {
                        // Find the actual doctor profile for this user
                        $actualDoctor = \DB::table('doctors')->where('user_id', $doctorId)->first();
                        if ($actualDoctor) {
                            // Redirect to the correct URL with the actual doctor ID
                            $correctUrl = url('/kiosk') . '?token=' . $token . '&doctor=' . $actualDoctor->id;
                            return redirect($correctUrl);
                        }
                    }

                    abort(403, 'Invalid doctor ID.');
                }

                // For now, allow access with a warning that the kiosk isn't fully configured
                session(['kiosk_doctor_id' => $doctorId, 'kiosk_token' => $token]);
                session()->flash('warning', 'This kiosk is not fully configured. Please contact your administrator.');
            } else {
                // Store doctor context in session for this kiosk session
                session(['kiosk_doctor_id' => $doctorId]);
            }
        } elseif ($token) {
            // Token provided without doctor ID - try to find by token only
            $kioskConfig = \DB::table('doctor_kiosk_configs')
                ->where('kiosk_token', $token)
                ->first();

            if ($kioskConfig) {
                session(['kiosk_doctor_id' => $kioskConfig->doctor_id]);
            } else {
                abort(403, 'Invalid kiosk access token.');
            }
        } elseif ($doctorId) {
            // Doctor ID provided without token - check if doctor exists
            $doctorExists = \DB::table('doctors')->where('id', $doctorId)->exists();
            if (!$doctorExists) {
                // Check if this might be a user ID instead of doctor ID
                $userAsDoctor = \DB::table('users')->where('id', $doctorId)->where('role', 'doctor')->first();
                if ($userAsDoctor) {
                    // Find the actual doctor profile for this user
                    $actualDoctor = \DB::table('doctors')->where('user_id', $doctorId)->first();
                    if ($actualDoctor) {
                        // Redirect to the correct URL with the actual doctor ID
                        $correctUrl = url('/kiosk') . '?doctor=' . $actualDoctor->id;
                        if ($token) {
                            $correctUrl .= '&token=' . $token;
                        }
                        return redirect($correctUrl);
                    }
                }

                abort(403, 'Invalid doctor ID.');
            }
            session(['kiosk_doctor_id' => $doctorId]);
        }

        // Start a kiosk session if not already started
        $this->ensureKioskSession($request);

        return view('kiosk.welcome');
    }

    /**
     * Start the check-in process
     */
    public function checkinStart(Request $request): View
    {
        $this->ensureKioskSession($request);

        return view('kiosk.checkin.start');
    }

    /**
     * Display appointment search form
     */
    public function checkinSearch(Request $request): View
    {
        $this->ensureKioskSession($request);

        return view('kiosk.checkin.search');
    }

    /**
     * Process appointment search
     */
    public function checkinSearchSubmit(Request $request): RedirectResponse|View
    {
        $this->ensureKioskSession($request);

        $validator = Validator::make($request->all(), [
            'search_type' => 'required|in:appointment_number,name,phone,email',
            'search_value' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Search for appointments directly instead of using API call
            $query = \App\Models\Appointment::with(['doctor', 'patient'])
                ->where('status', 'confirmed')
                ->whereDate('appointment_date', '>=', today());

            // Apply the appropriate search filter based on search_type
            switch ($request->search_type) {
                case 'name':
                    $query->where(function ($q) use ($request) {
                        $q->where('guest_name', 'LIKE', '%' . $request->search_value . '%')
                          ->orWhereHas('patient', function ($patientQuery) use ($request) {
                              $patientQuery->where('name', 'LIKE', '%' . $request->search_value . '%');
                          });
                    });
                    break;
                case 'phone':
                    $query->where(function ($q) use ($request) {
                        $q->where('guest_phone', 'LIKE', '%' . $request->search_value . '%')
                          ->orWhereHas('patient', function ($patientQuery) use ($request) {
                              $patientQuery->where('phone', 'LIKE', '%' . $request->search_value . '%');
                          });
                    });
                    break;
                case 'appointment_number':
                    $query->where('id', $request->search_value);
                    break;
                default:
                    // For other search types, default to searching by name
                    $query->where(function ($q) use ($request) {
                        $q->where('guest_name', 'LIKE', '%' . $request->search_value . '%')
                          ->orWhereHas('patient', function ($patientQuery) use ($request) {
                              $patientQuery->where('name', 'LIKE', '%' . $request->search_value . '%');
                          });
                    });
                    break;
            }

            // Exclude already checked-in appointments
            $query->whereDoesntHave('kioskCheckins');

            $appointments = $query->orderBy('appointment_date')
                                 ->limit(20) // Limit results
                                 ->get();

            if ($appointments->count() > 0) {
                $appointmentData = $appointments->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                        'start_time' => $appointment->start_time->format('H:i'),
                        'end_time' => $appointment->end_time->format('H:i'),
                        'patient_name' => $appointment->patient ? $appointment->patient->name : $appointment->guest_name,
                        'patient_phone' => $appointment->patient ? $appointment->patient->phone : $appointment->guest_phone,
                        'doctor_name' => $appointment->doctor ? $appointment->doctor->user->name : 'N/A',
                    ];
                })->toArray();

                if (count($appointmentData) === 1) {
                    return redirect()->route('kiosk.checkin.verify', $appointmentData[0]['id']);
                }

                // Multiple appointments found, show selection
                return view('kiosk.checkin.search-results', ['appointments' => $appointmentData]);
            } else {
                return back()->with('error', 'No appointments found matching your search criteria.')->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Kiosk appointment search exception', [
                'error' => $e->getMessage(),
                'search_type' => $request->search_type,
                'search_value' => $request->search_value,
            ]);

            return back()->with('error', 'An error occurred while searching. Please try again.')->withInput();
        }
    }

    /**
     * Display appointment verification screen
     */
    public function checkinVerify(Request $request, Appointment $appointment): View
    {
        $this->ensureKioskSession($request);

        // Load appointment with related data
        $appointment->load(['doctor.user', 'patient']);

        return view('kiosk.checkin.verify', compact('appointment'));
    }

    /**
     * Confirm check-in
     */
    public function checkinConfirm(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->ensureKioskSession($request);

        $validator = Validator::make($request->all(), [
            'verification_method' => 'required|in:qr_code,id_card,biometric,manual',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            // Direct check-in logic instead of API call
            // Check if kiosk session exists
            $sessionId = session('kiosk_session_id');
            if (!$sessionId) {
                return back()->with('error', 'No active kiosk session found. Please start again.');
            }

            // Check if appointment is already checked in
            $existingCheckin = \App\Models\KioskCheckin::where('appointment_id', $appointment->id)->first();
            if ($existingCheckin) {
                return back()->with('error', 'This appointment has already been checked in.');
            }

            // Create kiosk checkin record
            $kioskCheckin = \App\Models\KioskCheckin::create([
                'appointment_id' => $appointment->id,
                'kiosk_session_id' => $sessionId,
                'verification_method' => $request->verification_method,
                'checkin_time' => now(),
            ]);

            if ($kioskCheckin) {
                // Check if payment is required
                if ($appointment->requires_payment || $appointment->fee > 0) {
                    return redirect()->route('kiosk.payment.amount', $appointment);
                } else {
                    return redirect()->route('kiosk.checkin.success', $appointment);
                }
            } else {
                return back()->with('error', 'Check-in failed. Please try again.');
            }
        } catch (\Exception $e) {
            Log::error('Kiosk check-in exception', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
                'verification_method' => $request->verification_method,
            ]);

            return back()->with('error', 'An error occurred during check-in. Please try again.');
        }
    }

    /**
     * Display check-in success screen
     */
    public function checkinSuccess(Request $request, Appointment $appointment): View
    {
        $this->ensureKioskSession($request);

        $appointment->load(['doctor.user', 'patient']);

        return view('kiosk.checkin.success', compact('appointment'));
    }

    /**
     * Display payment amount screen
     */
    public function paymentAmount(Request $request, Appointment $appointment): View
    {
        $this->ensureKioskSession($request);

        $appointment->load(['doctor.user']);

        return view('kiosk.payment.amount', compact('appointment'));
    }

    /**
     * Display card input screen
     */
    public function paymentCard(Request $request, Appointment $appointment): View
    {
        $this->ensureKioskSession($request);

        $appointment->load(['doctor.user']);

        return view('kiosk.payment.card', compact('appointment'));
    }

    /**
     * Process payment
     */
    public function paymentProcess(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->ensureKioskSession($request);

        try {
            // Determine the payment amount (could be appointment fee, consultation fee, or other charges)
            $amount = $appointment->fee ?? $appointment->doctor->consultation_fee ?? 0;

            // Ensure amount is in cents for Stripe
            $amountInCents = (int)($amount * 100);

            if ($amountInCents <= 0) {
                // If no payment is required, redirect to success
                return redirect()->route('kiosk.payment.receipt', $appointment);
            }

            // For now, simulate payment processing directly instead of API call
            // In a real implementation, this would create a payment intent with Stripe
            $sessionId = session('kiosk_session_id');
            if (!$sessionId) {
                return back()->with('error', 'No active kiosk session found. Please start again.');
            }

            // For now, just redirect to receipt since we're simulating the payment process
            return redirect()->route('kiosk.payment.receipt', $appointment);
        } catch (\Exception $e) {
            Log::error('Kiosk payment processing exception', [
                'error' => $e->getMessage(),
                'appointment_id' => $appointment->id,
            ]);

            return back()->with('error', 'An error occurred during payment processing. Please try again.');
        }
    }

    /**
     * Display payment receipt
     */
    public function paymentReceipt(Request $request, Appointment $appointment): View
    {
        $this->ensureKioskSession($request);

        $appointment->load(['doctor.user', 'kioskPayments']);

        return view('kiosk.payment.receipt', compact('appointment'));
    }

    /**
     * Start a kiosk session
     */
    public function startSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kiosk_id' => 'required|exists:kiosks,id',
        ]);

        if ($validator->fails()) {
            AuditLoggingService::logKioskSecurityEvent('invalid_kiosk_id_attempt', null, [
                'kiosk_id' => $request->kiosk_id,
                'validation_errors' => $validator->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid kiosk ID',
            ], 422);
        }

        try {
            // Call the API to start session
            $response = Http::timeout(30)->post(url('/api/kiosk-sessions/start/'.$request->kiosk_id));

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success']) {
                    $sessionId = $data['data']['session']['session_id'];
                    session(['kiosk_session_id' => $sessionId]);

                    // Log successful session start
                    AuditLoggingService::logKioskSessionStarted($request->kiosk_id, $sessionId, [
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    return response()->json([
                        'success' => true,
                        'session_id' => $sessionId,
                    ]);
                }
            }

            // Log failed session start
            AuditLoggingService::logKioskSecurityEvent('session_start_failed', null, [
                'kiosk_id' => $request->kiosk_id,
                'response_status' => $response->status(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start kiosk session',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Kiosk session start exception', [
                'error' => $e->getMessage(),
                'kiosk_id' => $request->kiosk_id,
            ]);

            AuditLoggingService::logKioskSecurityEvent('session_start_exception', null, [
                'kiosk_id' => $request->kiosk_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while starting the session',
            ], 500);
        }
    }

    /**
     * End a kiosk session
     */
    public function endSession(Request $request): JsonResponse
    {
        $sessionId = session('kiosk_session_id');

        if (!$sessionId) {
            AuditLoggingService::logKioskSecurityEvent('session_end_no_active_session', null, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No active session found',
            ], 404);
        }

        try {
            // Call the API to end session
            $response = Http::timeout(30)->post(url('/api/kiosk-sessions/'.$sessionId.'/end'));

            // Clear session regardless of API response
            session()->forget('kiosk_session_id');

            // Log session end
            AuditLoggingService::logKioskSessionEnded(null, $sessionId, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'api_response_success' => $response->successful(),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Session ended successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Session cleanup completed with warnings',
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error('Kiosk session end exception', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            // Still clear session even if API call fails
            session()->forget('kiosk_session_id');

            AuditLoggingService::logKioskSecurityEvent('session_end_exception', $sessionId, [
                'error' => $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Session ended with errors',
            ], 500);
        }
    }

    /**
     * Update kiosk preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'voice_enabled' => 'boolean',
            'high_contrast' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid preferences data',
            ], 422);
        }

        // Store preferences in session
        if ($request->has('voice_enabled')) {
            session(['voice_enabled' => $request->voice_enabled]);
        }

        if ($request->has('high_contrast')) {
            session(['high_contrast' => $request->high_contrast]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Ensure a kiosk session is active
     */
    private function ensureKioskSession(Request $request): void
    {
        // Check if we have a valid kiosk session
        $sessionId = session('kiosk_session_id');

        if (!$sessionId) {
            // No session found, try to create one automatically
            $this->autoStartKioskSession($request);
            return;
        }

        // Verify the session exists in the database and is still active
        $kioskSession = \App\Models\KioskSession::where('session_id', $sessionId)->first();

        if (!$kioskSession || $kioskSession->status !== 'active') {
            // Session is invalid or expired, clear it and try to start a new one
            session()->forget('kiosk_session_id');
            session()->flash('error', 'Your kiosk session has expired. Starting a new session.');

            $this->autoStartKioskSession($request);
        }
    }

    /**
     * Automatically start a kiosk session
     */
    private function autoStartKioskSession(Request $request): void
    {
        try {
            // Create a new kiosk session automatically
            // For now we'll create a generic kiosk session using the doctor context if available
            $doctorId = session('kiosk_doctor_id');

            // Create a new session ID
            $sessionId = 'kiosk_' . bin2hex(random_bytes(16)) . '_' . time();

            // Create a kiosk session record in the database
            $kioskSession = \App\Models\KioskSession::create([
                'session_id' => $sessionId,
                'kiosk_id' => null, // Allow null for web-based access (kiosk access URL opened in browser)
                'start_time' => now(),
                'status' => 'active',
                'session_data' => [
                    'doctor_id' => $doctorId,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            // Store session ID in session
            session(['kiosk_session_id' => $sessionId]);

            // Log successful session start
            \App\Services\AuditLoggingService::logKioskSessionStarted(null, $sessionId, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'doctor_id' => $doctorId,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Auto-start kiosk session failed', [
                'error' => $e->getMessage(),
                'doctor_id' => session('kiosk_doctor_id'),
            ]);

            // If auto-start fails, we can't proceed but we shouldn't redirect to a POST route
            // Instead, we could show an error or redirect to a different page
            abort(500, 'Unable to start kiosk session. Please try again later.');
        }
    }
}
