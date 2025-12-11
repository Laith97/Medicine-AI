<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Events\AppointmentBookedEvent;
use App\Models\Doctor;
use App\Services\AIAssistant;
use App\Services\AuthorizationService;
use App\Services\BusinessRulesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AppointmentController extends Controller
{
    /**
     * Display patient's appointments
     */
    public function index(Request $request)
    {
        // Redirect guests to guest appointment lookup
        if (!Auth::check()) {
            return redirect()->route('appointments.guest.lookup');
        }

        $query = Auth::user()->appointments()
            ->with(['doctor.user', 'doctor.specialty']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('appointment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('appointment_date', '<=', $request->date_to);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->paginate(10);

        return view('appointments.index', compact('appointments'));
    }

    /**
     * Show the form for creating a new appointment
     */
    public function create(Request $request, Doctor $doctor)
    {
        $doctor->load(['user', 'specialty']);

        // Get available slots for the next 30 days
        $availableSlots = [];
        for ($i = 0; $i < 30; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $slots = $doctor->getAvailableSlots($date);
            if ($slots->isNotEmpty()) {
                $availableSlots[$date] = $slots;
            }
        }

        return view('appointments.create', compact('doctor', 'availableSlots'));
    }

    /**
     * Store a newly created appointment
     */
    public function store(Request $request)
    {
        $doctor = Doctor::findOrFail($request->doctor_id);
        $enabledTypes = $doctor->getEnabledAppointmentTypes();

        // Base validation rules
        $rules = [
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date|after:now',
            'reason' => 'required|string|max:500',
            'symptoms' => 'nullable|string|max:1000',
            'appointment_type' => 'required|in:' . implode(',', $enabledTypes),
            'patient_notes' => 'nullable|string|max:1000',
            // Insurance fields (optional)
            'insurance_provider_id' => 'nullable|exists:insurance_providers,id',
            'policy_number' => 'nullable|string|max:255',
            'group_number' => 'nullable|string|max:255',
            'subscriber_id' => 'nullable|string|max:255',
            'relationship_to_subscriber' => 'nullable|in:self,spouse,child,parent,other',
            'effective_date' => 'nullable|date',
        ];

        // Add validation rules based on booking type for guests
        if (!Auth::check()) {
            $bookingType = $request->input('booking_type', 'guest');

            if ($bookingType === 'guest') {
                $rules = array_merge($rules, [
                    'guest_name' => 'required|string|max:255',
                    'guest_email' => 'required|email|max:255',
                    'guest_phone' => 'required|string|max:20',
                    'guest_date_of_birth' => 'required|date|before:today',
                    'guest_gender' => 'required|in:male,female,other',
                    'guest_address' => 'nullable|string|max:500',
                ]);
            } elseif ($bookingType === 'register') {
                $rules = array_merge($rules, [
                    'reg_name' => 'required|string|max:255',
                    'reg_email' => 'required|email|max:255|unique:users,email',
                    'reg_password' => 'required|string|min:8|confirmed',
                ]);
            }
        }

        $request->validate($rules);

        $doctor = Doctor::findOrFail($request->doctor_id);

        // Validate that the slot is still available
        $appointmentDate = Carbon::parse($request->appointment_date);
        $slots = $doctor->getAvailableSlots($appointmentDate->format('Y-m-d'));

        $requestedSlot = $slots->first(function ($slot) use ($appointmentDate) {
            return $slot['datetime'] === $appointmentDate->toDateTimeString();
        });

        if (!$requestedSlot) {
            return back()->withErrors(['appointment_date' => 'The selected time slot is no longer available.']);
        }

        DB::beginTransaction();
        try {
            $patientId = null;

            // Handle user creation if registering during booking
            if (!Auth::check() && $request->input('booking_type') === 'register') {
                $user = \App\Models\User::create([
                    'name' => $request->reg_name,
                    'email' => $request->reg_email,
                    'password' => bcrypt($request->reg_password),
                    'role' => 'patient',
                ]);


                                Auth::login($user);
                                $patientId = $user->id;
                            } elseif (Auth::check()) {
                                $patientId = Auth::id();
                                $patient = Auth::user();

                                // If patient doesn't have a primary doctor or is booking with a different doctor,
                                // automatically assign them to this doctor
                                if (is_null($patient->primary_doctor_id) || $patient->primary_doctor_id != $doctor->user_id) {
                                    $patient->update(['primary_doctor_id' => $doctor->user_id]);
                                }
                            }
            // Create appointment data
            $appointmentData = [
                'doctor_id' => $doctor->id,
                'patient_id' => $patientId,
                'appointment_date' => $appointmentDate,
                'appointment_end' => $appointmentDate->copy()->addMinutes($doctor->appointment_duration),
                'status' => $doctor->auto_approve_appointments ? 'confirmed' : 'pending',
                'reason' => $request->reason,
                'symptoms' => $request->symptoms,
                'appointment_type' => $request->appointment_type,
                'patient_notes' => $request->patient_notes,
                'consultation_fee' => $doctor->consultation_fee,
            ];

            // Add guest data if booking as guest
            if (!Auth::check() && $request->input('booking_type') === 'guest') {
                $appointmentData = array_merge($appointmentData, [
                    'guest_name' => $request->guest_name,
                    'guest_email' => $request->guest_email,
                    'guest_phone' => $request->guest_phone,
                    'guest_date_of_birth' => $request->guest_date_of_birth,
                    'guest_gender' => $request->guest_gender,
                    'guest_address' => $request->guest_address,
                ]);
            }

            $appointment = Appointment::create($appointmentData);

            // Create patient insurance record if insurance information is provided
            $patientInsurance = null;
            if ($request->filled('insurance_provider_id') && $request->filled('policy_number')) {
                $insuranceData = [
                    'patient_id' => $patientId,
                    'insurance_provider_id' => $request->insurance_provider_id,
                    'policy_number' => $request->policy_number,
                    'group_number' => $request->group_number,
                    'subscriber_id' => $request->subscriber_id,
                    'relationship_to_subscriber' => $request->relationship_to_subscriber,
                    'effective_date' => $request->effective_date,
                ];

                $patientInsurance = \App\Models\PatientInsurance::create($insuranceData);
            }

            // Generate verification token for guest appointments
            if ($appointment->isGuestAppointment()) {
                $appointment->generateVerificationToken();
            }

            // Check eligibility if insurance is provided
            $eligibilityWarning = null;
            if ($patientInsurance) {
                try {
                    $eligibilityService = app(\App\Services\EligibilityServiceFactory::class)
                        ->getServiceForProvider($patientInsurance->insuranceProvider);

                    $eligibilityResult = $eligibilityService->checkEligibility(
                        $patientInsurance,
                        $request->appointment_type
                    );

                    if ($eligibilityResult['status'] === 'ineligible') {
                        $eligibilityWarning = 'Warning: Patient appears ineligible for this service type. Please verify insurance information.';
                    } elseif ($eligibilityResult['status'] === 'error') {
                        $eligibilityWarning = 'Unable to verify insurance eligibility. Please check manually.';
                    }
                } catch (\Exception $e) {
                    $eligibilityWarning = 'Unable to verify insurance eligibility. Please check manually.';
                    \Log::warning('Eligibility check failed during appointment booking', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($doctor->auto_approve_appointments) {
                $appointment->confirm();
            }

            DB::commit();

            // Send notifications
            $this->sendAppointmentNotifications($appointment);

            // Prepare success message with eligibility warning if applicable
            $successMessage = 'Appointment booked successfully! ';
            if ($appointment->isGuestAppointment()) {
                $successMessage .= 'Check your email for verification and appointment details.';
            }
            if ($eligibilityWarning) {
                $successMessage .= ' ' . $eligibilityWarning;
            }

            // Handle AJAX requests vs regular form submissions
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'eligibility_warning' => $eligibilityWarning,
                    'redirect_url' => $appointment->isGuestAppointment() ?
                        route('appointments.guest.show', [
                            'appointment' => $appointment->appointment_number,
                            'email' => $appointment->guest_email
                        ]) :
                        route('appointments.show', $appointment)
                ]);
            } else {
                $redirect = $appointment->isGuestAppointment() ?
                    redirect()->route('appointments.guest.show', [
                        'appointment' => $appointment->appointment_number,
                        'email' => $appointment->guest_email
                    ]) :
                    redirect()->route('appointments.show', $appointment);

                if ($eligibilityWarning) {
                    return $redirect->with('success', $successMessage)->with('warning', $eligibilityWarning);
                } else {
                    return $redirect->with('success', $successMessage);
                }
            }

        } catch (\Exception $e) {
            DB::rollback();

            // Handle AJAX requests vs regular form submissions
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to book appointment. Please try again.',
                    'error' => $e->getMessage()
                ], 422);
            } else {
                return back()->withErrors(['error' => 'Failed to book appointment. Please try again.']);
            }
        }
    }

    /**
     * Display the specified appointment
     */
    public function show(Appointment $appointment)
    {
        $authService = app(AuthorizationService::class);
        $user = $authService->getAuthenticatedUser();

        if (!$user || !$authService->canViewAppointment($user, $appointment)) {
            abort(403, 'Unauthorized access to appointment');
        }

        // Log doctor access to patient appointment
        if ($user->isDoctor() && $appointment->patient_id) {
            \App\Services\AuditLoggingService::logDoctorAccessPatient(
                $user->id,
                $appointment->patient_id,
                ['appointment_id' => $appointment->id]
            );
        }

        // Eager load all necessary relationships to prevent N+1 queries
        $relations = [
            'doctor.user',
            'doctor.specialty',
            'review',
            'prescriptions',
            'patient.patientData'
        ];

        $appointment->load($relations);

        return view('appointments.show', compact('appointment'));
    }

    /**
     * Cancel an appointment
     */
    public function cancel(Request $request, Appointment $appointment)
    {
        $authService = app(AuthorizationService::class);
        $businessRulesService = app(BusinessRulesService::class);

        $user = $authService->getAuthenticatedUser();

        if (!$user || !$authService->canCancelAppointment($user, $appointment)) {
            abort(403, 'Unauthorized to cancel this appointment');
        }

        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500'
        ]);

        // Validate business rules
        $validationResult = $businessRulesService->validateAppointmentCancellation(
            $appointment,
            $request->cancellation_reason
        );

        if (!$validationResult['valid']) {
            return back()->withErrors(['error' => implode(', ', $validationResult['errors'])]);
        }

        // Show warnings if any
        $redirect = redirect()->route('appointments.index');
        if (!empty($validationResult['warnings'])) {
            $redirect = $redirect->with('warning', implode(', ', $validationResult['warnings']));
        }

        DB::transaction(function () use ($appointment, $request, $user) {
            // Lock the appointment for update to prevent concurrent modifications
            $lockedAppointment = Appointment::where('id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedAppointment) {
                throw new \Exception('Appointment not found');
            }

            $cancelledBy = $user->isPatient() ? 'patient' : 'doctor';
            $lockedAppointment->cancel($cancelledBy, $request->cancellation_reason);

            // Send cancellation notifications
            $this->sendAppointmentCancellationNotifications($lockedAppointment, $request->cancellation_reason);
        });

        return $redirect->with('success', 'Appointment cancelled successfully.');
    }

    /**
     * Reschedule an appointment
     */
    public function reschedule(Request $request, Appointment $appointment)
    {
        // Check if user can reschedule this appointment
        if ($appointment->patient_id !== Auth::id()) {
            abort(403);
        }

        if (!$appointment->canBeRescheduled()) {
            return back()->withErrors(['error' => 'This appointment cannot be rescheduled.']);
        }

        $request->validate([
            'new_appointment_date' => 'required|date|after:now',
        ]);

        $doctor = $appointment->doctor;
        $newDate = Carbon::parse($request->new_appointment_date);

        // Validate that the new slot is available
        $slots = $doctor->getAvailableSlots($newDate->format('Y-m-d'));
        $requestedSlot = $slots->first(function ($slot) use ($newDate) {
            return $slot['datetime'] === $newDate->toDateTimeString();
        });

        if (!$requestedSlot) {
            return back()->withErrors(['new_appointment_date' => 'The selected time slot is not available.']);
        }

        DB::transaction(function () use ($appointment, $doctor, $newDate) {
            // Lock the appointment for update to prevent concurrent modifications
            $lockedAppointment = Appointment::where('id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedAppointment) {
                throw new \Exception('Appointment not found');
            }

            $lockedAppointment->update([
                'appointment_date' => $newDate,
                'appointment_end' => $newDate->copy()->addMinutes($doctor->appointment_duration),
                'status' => $doctor->auto_approve_appointments ? 'confirmed' : 'pending',
            ]);

            if ($doctor->auto_approve_appointments) {
                $lockedAppointment->confirm();
            }

            // Send rescheduling notifications
            $this->sendAppointmentReschedulingNotifications($lockedAppointment, $newDate);
        });

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Appointment rescheduled successfully!');
    }

    /**
     * Get appointment details for calendar view (AJAX)
     */
    public function getCalendarEvents(Request $request)
    {
        $start = $request->start;
        $end = $request->end;

        $appointments = Auth::user()->appointments()
            ->with(['doctor.user', 'doctor.specialty'])
            ->whereBetween('appointment_date', [$start, $end])
            ->get();

        $events = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => 'Dr. ' . $appointment->doctor->user->name,
                'start' => $appointment->appointment_date->toISOString(),
                'end' => $appointment->appointment_end->toISOString(),
                'color' => $this->getEventColor($appointment->status),
                'url' => route('appointments.show', $appointment),
                'extendedProps' => [
                    'status' => $appointment->status,
                    'doctor' => $appointment->doctor->user->name,
                    'specialty' => $appointment->doctor->specialty->name,
                    'type' => $appointment->appointment_type,
                ]
            ];
        });

        return response()->json($events);
    }

    /**
     * Get event color based on appointment status
     */
    private function getEventColor($status)
    {
        return match($status) {
            'pending' => '#f59e0b',
            'confirmed' => '#10b981',
            'cancelled' => '#ef4444',
            'completed' => '#3b82f6',
            'no_show' => '#6b7280',
            default => '#6b7280'
        };
    }

    /**
     * Show guest appointment lookup form
     */
    public function guestLookup()
    {
        return view('appointments.guest.lookup');
    }

    /**
     * Find guest appointments by email
     */
    public function guestSearch(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $appointments = Appointment::guest()
            ->byGuestEmail($request->email)
            ->with(['doctor.user', 'doctor.specialty'])
            ->orderBy('appointment_date', 'desc')
            ->get();

        if ($appointments->isEmpty()) {
            return back()->withErrors(['email' => 'No appointments found for this email address.']);
        }

        return view('appointments.guest.list', compact('appointments'));
    }

    /**
     * Show guest appointment details
     */
    public function guestShow(Request $request, $appointmentNumber)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $appointment = Appointment::where('appointment_number', $appointmentNumber)
            ->where('guest_email', $request->email)
            ->with(['doctor.user', 'doctor.specialty'])
            ->firstOrFail();

        return view('appointments.guest.show', compact('appointment'));
    }

    /**
     * Verify guest appointment
     */
    public function guestVerify(Request $request, $appointmentNumber)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $appointment = Appointment::where('appointment_number', $appointmentNumber)
            ->firstOrFail();

        if ($appointment->verifyWithToken($request->token)) {
            return redirect()->route('appointments.guest.show', [
                'appointment' => $appointmentNumber,
                'email' => $appointment->guest_email
            ])->with('success', 'Appointment verified successfully!');
        }

        return back()->withErrors(['token' => 'Invalid or expired verification token.']);
    }

    /**
     * Cancel guest appointment
     */
    public function guestCancel(Request $request, $appointmentNumber)
    {
        $request->validate([
            'email' => 'required|email',
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $appointment = Appointment::where('appointment_number', $appointmentNumber)
            ->where('guest_email', $request->email)
            ->firstOrFail();

        if (!$appointment->canBeCancelled()) {
            return back()->withErrors(['error' => 'This appointment cannot be cancelled.']);
        }

        $appointment->cancel('patient', $request->cancellation_reason);

        return redirect()->route('appointments.guest.show', [
            'appointment' => $appointmentNumber,
            'email' => $request->email
        ])->with('success', 'Appointment cancelled successfully.');
    }

    /**
     * Send notifications for appointment events
     */
    private function sendAppointmentNotifications(Appointment $appointment)
    {
        try {
            // Eager load relationships to prevent N+1 queries
            if (!$appointment->relationLoaded('doctor.user')) {
                $appointment->load('doctor.user');
            }

            if ($appointment->patient_id && !$appointment->relationLoaded('patient')) {
                $appointment->load('patient');
            }

            // Send notification to doctor about new appointment
            if ($appointment->doctor && $appointment->doctor->user) {
                $doctor = $appointment->doctor->user;

                // Check if doctor wants appointment notifications
                if ($doctor->wantsNotification('appointment_booked')) {
                    // 直接发送通知，不使用队列
                    $notification = new \App\Notifications\AppointmentBookedNotification($appointment);
                    $doctor->notify($notification);

                    // 立即广播事件，不使用队列
                    event(new \App\Events\AppointmentBookedEvent($appointment));
                }
            }

            // Send notification to patient about appointment confirmation
            if ($appointment->patient && $appointment->status === 'confirmed') {
                $patient = $appointment->patient;

                // Check if patient wants appointment notifications
                if ($patient->wantsNotification('appointment_booked')) {
                    $patient->notifyIfWants(new \App\Notifications\AppointmentBookedNotification($appointment), 'appointment_booked');
                }
            }

            // Send notification to guest about appointment confirmation
            if ($appointment->isGuestAppointment() && $appointment->status === 'confirmed') {
                // For guest appointments, we'll handle notifications differently
                // This could be handled through email notifications
            }

        } catch (\Exception $e) {
            // Log notification errors but don't break the appointment process
            \Log::error('Failed to send appointment notifications: ' . $e->getMessage());
        }
    }

    /**
     * Send notifications for appointment cancellation
     */
    private function sendAppointmentCancellationNotifications(Appointment $appointment, string $reason = null)
    {
        try {
            // Send notification to doctor about cancellation
            if ($appointment->doctor && $appointment->doctor->user) {
                $doctor = $appointment->doctor->user;

                // Check if doctor wants appointment notifications
                if ($doctor->wantsNotification('appointment_booked')) {
                    $doctor->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                        'Appointment Cancelled',
                        "Appointment #{$appointment->appointment_number} has been cancelled by patient. Reason: " . ($reason ?: 'Not specified'),
                        'warning',
                        [
                            'link' => route('appointments.index'),
                            'link_text' => 'View Appointments',
                            'related_type' => 'appointment',
                            'related_id' => $appointment->id
                        ]
                    ));
                }
            }

            // Send notification to patient about cancellation
            if ($appointment->patient) {
                $patient = $appointment->patient;

                // Check if patient wants appointment notifications
                if ($patient->wantsNotification('appointment_booked')) {
                    $patient->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                        'Appointment Cancelled',
                        "Your appointment #{$appointment->appointment_number} has been cancelled successfully.",
                        'info',
                        [
                            'link' => route('appointments.index'),
                            'link_text' => 'View Appointments',
                            'related_type' => 'appointment',
                            'related_id' => $appointment->id
                        ]
                    ));
                }
            }

        } catch (\Exception $e) {
            // Log notification errors but don't break the cancellation process
            \Log::error('Failed to send appointment cancellation notifications: ' . $e->getMessage());
        }
    }

    /**
     * Send notifications for appointment rescheduling
     */
    private function sendAppointmentReschedulingNotifications(Appointment $appointment, \Carbon\Carbon $newDate)
    {
        try {
            // Send notification to doctor about rescheduling
            if ($appointment->doctor && $appointment->doctor->user) {
                $doctor = $appointment->doctor->user;

                // Check if doctor wants appointment notifications
                if ($doctor->wantsNotification('appointment_reminder')) {
                    $doctor->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                        'Appointment Rescheduled',
                        "Appointment #{$appointment->appointment_number} has been rescheduled to {$newDate->format('M j, Y g:i A')}.",
                        'info',
                        [
                            'link' => route('appointments.show', $appointment),
                            'link_text' => 'View Appointment',
                            'related_type' => 'appointment',
                            'related_id' => $appointment->id
                        ]
                    ));
                }
            }

            // Send notification to patient about rescheduling
            if ($appointment->patient) {
                $patient = $appointment->patient;

                // Check if patient wants appointment notifications
                if ($patient->wantsNotification('appointment_reminder')) {
                    $patient->notifyIfWants(new \App\Notifications\SystemAlertNotification(
                        'Appointment Rescheduled',
                        "Your appointment #{$appointment->appointment_number} has been rescheduled to {$newDate->format('M j, Y g:i A')}.",
                        'info',
                        [
                            'link' => route('appointments.show', $appointment),
                            'link_text' => 'View Appointment',
                            'related_type' => 'appointment',
                            'related_id' => $appointment->id
                        ]
                    ));
                }
            }

        } catch (\Exception $e) {
            // Log notification errors but don't break the rescheduling process
            \Log::error('Failed to send appointment rescheduling notifications: ' . $e->getMessage());
        }
    }


    /**
     * Test OpenAI configuration and connectivity
     */
    public function testOpenAI(Request $request)
    {
        // Only allow doctors to test OpenAI
        if (!Auth::user()->isDoctor()) {
            abort(403, 'Unauthorized access.');
        }

        $results = [
            'config_check' => false,
            'api_key_configured' => false,
            'api_key_length' => 0,
            'organization_configured' => false,
            'connectivity_test' => false,
            'error_message' => null,
        ];

        try {
            // Check configuration
            $apiKey = config('openai.api_key');
            $organization = config('openai.organization');
            $timeout = config('openai.request_timeout', 60);

            $results['api_key_configured'] = !empty($apiKey);
            $results['api_key_length'] = strlen($apiKey ?? '');
            $results['organization_configured'] = !empty($organization);
            $results['config_check'] = $results['api_key_configured'];

            Log::info('OpenAI Configuration Check', [
                'api_key_configured' => $results['api_key_configured'],
                'api_key_length' => $results['api_key_length'],
                'organization_configured' => $results['organization_configured'],
                'timeout' => $timeout,
            ]);

            if (!$results['api_key_configured']) {
                $results['error_message'] = 'OpenAI API key not configured in environment variables';
                return response()->json($results);
            }

            // Test connectivity with a simple request
            $testResponse = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Hello, this is a test message. Please respond with "OpenAI connection successful".'
                    ]
                ],
                'max_tokens' => 50,
                'temperature' => 0.1,
            ]);

            $results['connectivity_test'] = true;
            $results['response_content'] = $testResponse->choices[0]->message->content ?? null;

            Log::info('OpenAI Connectivity Test Successful', [
                'response_id' => $testResponse->id ?? null,
                'model' => $testResponse->model ?? null,
                'usage' => $testResponse->usage ?? null,
            ]);

        } catch (\OpenAI\Exceptions\AuthenticationException $e) {
            $results['error_message'] = 'Authentication failed: ' . $e->getMessage();
            Log::error('OpenAI Test Authentication Error', ['error' => $e->getMessage()]);
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            $results['error_message'] = 'Rate limit exceeded: ' . $e->getMessage();
            Log::error('OpenAI Test Rate Limit Error', ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            $results['error_message'] = 'Connection test failed: ' . $e->getMessage();
            Log::error('OpenAI Test General Error', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e)
            ]);
        }

        return response()->json($results);
    }

    /**
     * Generate AI suggestions for prescription medications based on appointment data
     */
    public function aiSuggest(Request $request, Appointment $appointment)
    {
        // Validate doctor access to this appointment
        if (!Auth::user()->isDoctor() || $appointment->doctor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to appointment.');
        }

        // Debug: Log current AI configuration
        \Log::info('AI Configuration Check', [
            'ai_enabled' => config('ai.enabled', true),
            'prescription_suggestions_enabled' => config('ai.prescription_suggestions.enabled', true),
            'openai_api_key_configured' => !empty(config('openai.api_key')),
            'openai_api_key_length' => strlen(config('openai.api_key') ?? ''),
        ]);

        // Check if AI prescription suggestions are enabled
        if (!config('ai.prescription_suggestions.enabled', true)) {
            return response()->json([
                'suggestions' => [[
                    'med' => 'AI Feature Disabled',
                    'dosage' => 'N/A',
                    'freq' => 'N/A',
                    'dur' => 'N/A',
                    'confidence' => 0,
                    'reason' => 'AI prescription suggestions are currently disabled in system configuration.'
                ]],
                'risk_flags' => ['AI prescription suggestions are disabled by administrator'],
                'message' => 'AI prescription suggestions are disabled',
                'source' => 'disabled',
                'disabled' => true
            ], 200); // Changed to 200 so it shows the message instead of error
        }

        // Validate request data
        $request->validate([
            'symptoms' => 'nullable|string',
            'allergies' => 'nullable|string',
            'past_meds' => 'nullable|string',
        ]);

        // Decode JSON data
        $allergies = json_decode($request->allergies, true) ?? [];
        $past_meds = json_decode($request->past_meds, true) ?? [];

        // Handle symptoms fallback
        $symptoms = $request->symptoms ?: 'No symptoms provided';

        // Ensure symptoms is an array for AI service
        if (!is_array($symptoms)) {
            $symptoms = [$symptoms];
        }

        // Debug logging
        \Log::info('AI Suggestion Request Data', [
            'appointment_id' => $appointment->id,
            'raw_symptoms' => $request->symptoms,
            'processed_symptoms' => $symptoms,
            'allergies' => $allergies,
            'past_meds' => $past_meds,
        ]);

        $aiAssistant = new AIAssistant();
        $result = $aiAssistant->generatePrescriptionSuggestions($appointment, $symptoms, $allergies, $past_meds);

        return response()->json($result);
    }




}
