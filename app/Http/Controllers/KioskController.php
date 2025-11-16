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
            // Call the API to search for appointments
            $response = Http::timeout(30)->post(route('api.appointments.search'), [
                'search_type' => $request->search_type,
                'search_value' => $request->search_value,
                'kiosk_session_id' => session('kiosk_session_id'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] && !empty($data['data']['appointments'])) {
                    $appointments = $data['data']['appointments'];

                    // If only one appointment found, redirect to verification
                    if (count($appointments) === 1) {
                        return redirect()->route('kiosk.checkin.verify', $appointments[0]['id']);
                    }

                    // Multiple appointments found, show selection
                    return view('kiosk.checkin.search-results', compact('appointments'));
                } else {
                    return back()->with('error', 'No appointments found matching your search criteria.')->withInput();
                }
            } else {
                Log::error('Kiosk appointment search failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'search_type' => $request->search_type,
                    'search_value' => $request->search_value,
                ]);

                return back()->with('error', 'Unable to search for appointments. Please try again.')->withInput();
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
            // Call the API to check in the appointment
            $response = Http::timeout(30)->post(route('api.appointments.checkin', $appointment), [
                'kiosk_session_id' => session('kiosk_session_id'),
                'verification_method' => $request->verification_method,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success']) {
                    // Check if payment is required
                    if ($appointment->requires_payment) {
                        return redirect()->route('kiosk.payment.amount', $appointment);
                    } else {
                        return redirect()->route('kiosk.checkin.success', $appointment);
                    }
                } else {
                    return back()->with('error', $data['message'] ?? 'Check-in failed.');
                }
            } else {
                Log::error('Kiosk check-in failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'appointment_id' => $appointment->id,
                    'verification_method' => $request->verification_method,
                ]);

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

        // This would integrate with Stripe or other payment processor
        // For now, simulate successful payment

        try {
            // Call the API to create payment intent
            $response = Http::timeout(30)->post(route('api.appointments.payments.create-intent', $appointment), [
                'kiosk_session_id' => session('kiosk_session_id'),
                'amount' => $appointment->payment_amount ?? $appointment->doctor->consultation_fee,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success']) {
                    // In a real implementation, this would redirect to Stripe checkout
                    // For demo purposes, simulate successful payment
                    return redirect()->route('kiosk.payment.receipt', $appointment);
                } else {
                    return back()->with('error', $data['message'] ?? 'Payment setup failed.');
                }
            } else {
                Log::error('Kiosk payment intent creation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'appointment_id' => $appointment->id,
                ]);

                return back()->with('error', 'Unable to process payment. Please try again.');
            }
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
            $response = Http::timeout(30)->post(route('api.kiosk-sessions.start', $request->kiosk_id));

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
            $response = Http::timeout(30)->post(route('api.kiosk-sessions.end', $sessionId));

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
        if (!session()->has('kiosk_session_id')) {
            // For demo purposes, create a mock session
            // In production, this would be handled by kiosk registration
            session(['kiosk_session_id' => 'kiosk_' . time() . '_' . rand(1000, 9999)]);
        }
    }
}
