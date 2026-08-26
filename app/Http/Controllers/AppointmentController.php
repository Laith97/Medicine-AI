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
        // Redirect doctors to their appointment management page
        if (Auth::check() && Auth::user()->isDoctor()) {
            return redirect()->route('doctor.appointments.index');
        }

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

            // NOTE: Database notifications are sent via AppointmentObserver -> broadcastService
            // -> AppointmentStatusChangedEvent -> SendAppointmentStatusChangeNotification listener
            // No additional notification sending needed here to avoid duplicates.
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
            \Log::info('🔔 Starting appointment notifications', [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
            ]);

            // Eager load relationships to prevent N+1 queries
            if (!$appointment->relationLoaded('doctor.user')) {
                $appointment->load('doctor.user');
            }

            if ($appointment->patient_id && !$appointment->relationLoaded('patient')) {
                $appointment->load('patient');
            }

            // Send notification to doctor about new appointment
            // NOTE: The notification itself handles broadcasting via ShouldBroadcast
            // We do NOT need to call event() separately to avoid duplicates
            if ($appointment->doctor && $appointment->doctor->user) {
                $doctor = $appointment->doctor->user;
                \Log::info('📧 Checking doctor notification', [
                    'doctor_id' => $doctor->id,
                    'wants_notification' => $doctor->wantsNotification('appointment_booked'),
                ]);

                // Check if doctor wants appointment notifications
                if ($doctor->wantsNotification('appointment_booked')) {
                    $notification = new \App\Notifications\ReliableAppointmentBookedNotification($appointment);
                    $doctor->notify($notification);
                    \Log::info('✅ Doctor notification sent', ['doctor_id' => $doctor->id]);
                } else {
                    \Log::info('❌ Doctor notification skipped - preferences', ['doctor_id' => $doctor->id]);
                }
            } else {
                \Log::warning('❌ No doctor found for appointment', ['appointment_id' => $appointment->id]);
            }

            // Send notification to patient about appointment booking (always for registered patients)
            // NOTE: The notification itself handles broadcasting via ShouldBroadcast
            if ($appointment->patient) {
                $patient = $appointment->patient;
                \Log::info('📧 Checking patient notification', [
                    'patient_id' => $patient->id,
                    'wants_notification' => $patient->wantsNotification('appointment_booked'),
                ]);
                
                // Send notification to patient regardless of status
                if ($patient->wantsNotification('appointment_booked')) {
                    $notification = new \App\Notifications\ReliableAppointmentBookedNotification($appointment);
                    $patient->notify($notification);
                    \Log::info('✅ Patient notification sent', ['patient_id' => $patient->id]);
                } else {
                    \Log::info('❌ Patient notification skipped - preferences', ['patient_id' => $patient->id]);
                }
            } else {
                \Log::warning('❌ No patient found for appointment', ['appointment_id' => $appointment->id]);
            }

            // NOTE: The AppointmentBookedEvent is NOT broadcast separately here because
            // ReliableAppointmentBookedNotification already implements ShouldBroadcast
            // and handles the broadcasting. Broadcasting twice would cause duplicate notifications.

            // Send confirmation email to guest (uses EmailService fallback like contact)
            if ($appointment->isGuestAppointment()) {
                try {
                    $doctor = $appointment->doctor;
                    if (!$doctor) {
                        $appointment->load('doctor.user');
                        $doctor = $appointment->doctor;
                    }
                    $emailService = app(\App\Services\EmailService::class);
                    $emailService->sendEmail(
                        $appointment->guest_email,
                        'Appointment Confirmed - ' . config('app.name'),
                        'emails.appointment-confirmation',
                        [
                            'appointment' => $appointment,
                            'doctor' => $doctor,
                            'patient' => null,
                        ]
                    );
                    \Log::info('✅ Guest confirmation email sent', [
                        'guest_email' => $appointment->guest_email,
                        'appointment_id' => $appointment->id,
                        'appointment_number' => $appointment->appointment_number,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('❌ Failed to send guest confirmation email: ' . $e->getMessage(), [
                        'appointment_id' => $appointment->id,
                        'guest_email' => $appointment->guest_email ?? null,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            // Log notification errors but don't break the appointment process
            \Log::error('❌ Failed to send appointment notifications: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
                    $notification = new \App\Notifications\AppointmentStatusChangedNotification(
                        $appointment, 
                        'confirmed', // old status
                        'cancelled', // new status
                        'patient'    // changed by
                    );
                    $doctor->notify($notification);
                }
            }

            // Send notification to patient about cancellation
            if ($appointment->patient) {
                $patient = $appointment->patient;

                // Check if patient wants appointment notifications
                if ($patient->wantsNotification('appointment_booked')) {
                    $notification = new \App\Notifications\AppointmentStatusChangedNotification(
                        $appointment, 
                        'confirmed', // old status
                        'cancelled', // new status
                        'patient'    // changed by
                    );
                    $patient->notify($notification);
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
            'current_diagnosis' => 'nullable|string',
            'past_diagnoses' => 'nullable|string',
            'voice_diagnosis' => 'nullable|string',
            'reason_for_visit' => 'nullable|string',
            'continue_limited' => 'nullable|boolean',
            'clinical_notes' => 'nullable|string',
            'doctor_notes' => 'nullable|string',
        ]);

        // Server-authoritative: load verified data from DB, do not trust client for safety-critical fields
        $latestDiagnosis = \App\Models\Diagnosis::where('patient_id', $appointment->patient_id)->latest()->first();
        $pastDiagnosesModels = $latestDiagnosis ? \App\Models\Diagnosis::where('patient_id', $appointment->patient_id)->orderBy('created_at', 'desc')->skip(1)->take(10)->get() : collect();
        $voiceModel = $appointment->patient_id ? \App\Models\AiAssistantResult::where('patient_id', $appointment->patient_id)->where('source', 'voice_assistant')->latest()->first() : null;

        $continueLimited = $request->boolean('continue_limited');

        // Allergies — server-authoritative, allow dummy only if continue_limited explicitly set
        $serverAllergies = [];
        if ($latestDiagnosis && isset($latestDiagnosis->patient_data['allergies'])) {
            $val = $latestDiagnosis->patient_data['allergies'];
            if (is_array($val)) {
                $serverAllergies = array_values(array_filter(array_map('trim', $val), fn($v) => $v !== ''));
            } elseif (is_string($val) && trim($val) !== '') {
                $serverAllergies = [trim($val)];
            }
        }
        // Fallback to client only if server empty and continue_limited (for Quick Entry dummy)
        if (empty($serverAllergies) && $continueLimited) {
            $clientAllergies = json_decode($request->allergies, true) ?? [];
            if (!is_array($clientAllergies)) $clientAllergies = is_string($clientAllergies) && trim($clientAllergies) !== '' ? [trim($clientAllergies)] : [];
            if (!empty($clientAllergies)) $serverAllergies = $clientAllergies;
            if (empty($serverAllergies)) $serverAllergies = ['No known allergies'];
        }
        $allergies = $serverAllergies;

        // Medications — server-authoritative via Diagnosis + active prescriptions
        $serverMeds = [];
        if ($latestDiagnosis) {
            $pd = $latestDiagnosis->patient_data ?? [];
            $val = $pd['medications'] ?? $pd['past_medications'] ?? null;
            if (is_array($val)) {
                $serverMeds = array_values(array_filter(array_map('trim', $val), fn($v) => $v !== ''));
            } elseif (is_string($val) && trim($val) !== '') {
                $serverMeds = [trim($val)];
            }
        }
        if (empty($serverMeds) && $appointment->patient_id) {
            $activeMeds = \App\Models\Prescription::getActiveForPatient($appointment->patient_id)->pluck('medication_name')->toArray();
            if (!empty($activeMeds)) $serverMeds = $activeMeds;
        }
        if (empty($serverMeds) && $continueLimited) {
            $clientMeds = json_decode($request->past_meds, true) ?? [];
            if (!is_array($clientMeds)) $clientMeds = is_string($clientMeds) && trim($clientMeds) !== '' ? [trim($clientMeds)] : [];
            if (!empty($clientMeds)) $serverMeds = $clientMeds;
            if (empty($serverMeds)) $serverMeds = ['No current medications'];
        }
        $past_meds = $serverMeds;

        // Symptoms / clinical notes — server-authoritative
        $serverSymptoms = trim($appointment->doctor_notes ?? '');
        if (empty($serverSymptoms) && $latestDiagnosis) {
            $pd = $latestDiagnosis->patient_data ?? [];
            $serverSymptoms = trim($pd['clinical_notes'] ?? $pd['symptoms'] ?? '');
            if (empty($serverSymptoms)) $serverSymptoms = trim($latestDiagnosis->diagnosis_text ?? '');
        }
        if (empty($serverSymptoms) && $continueLimited) {
            $clientSymptomsRaw = $request->symptoms ?? $request->clinical_notes ?? '';
            $decoded = json_decode($clientSymptomsRaw, true);
            if (is_array($decoded)) $serverSymptoms = implode(', ', $decoded);
            elseif (is_string($decoded) && trim($decoded) !== '') $serverSymptoms = trim($decoded);
            elseif (is_string($clientSymptomsRaw) && trim($clientSymptomsRaw) !== '' && $clientSymptomsRaw !== '[]') $serverSymptoms = trim($clientSymptomsRaw);
            if (empty($serverSymptoms)) $serverSymptoms = 'General consultation - no specific symptoms documented';
        }
        $symptoms = $serverSymptoms ?: 'No symptoms provided';
        if (!is_array($symptoms)) $symptoms = [$symptoms];

        // Prepare additional data for AI processing — server-authoritative
        $additionalData = [];
        if ($latestDiagnosis) {
            $additionalData['current_diagnosis'] = $latestDiagnosis->toArray();
            if (!empty($pastDiagnosesModels) && $pastDiagnosesModels->count() > 0) {
                $additionalData['past_diagnoses'] = $pastDiagnosesModels->toArray();
            }
            if ($voiceModel) {
                $additionalData['voice_diagnosis'] = $voiceModel->patient_data['diagnosis'] ?? $voiceModel->ai_analysis ?? '';
            }
            // Always include doctor_notes from appointment or latest diagnosis
            $additionalData['doctor_notes'] = $appointment->doctor_notes ?: ($latestDiagnosis->patient_data['clinical_notes'] ?? '');
        } else {
            // No diagnosis — use appointment data
            if ($appointment->doctor_notes) $additionalData['doctor_notes'] = $appointment->doctor_notes;
            if ($appointment->reason) $additionalData['reason_for_visit'] = $appointment->reason;
            if ($continueLimited && $request->clinical_notes) $additionalData['doctor_notes'] = $request->clinical_notes;
        }
        if ($request->reason_for_visit && empty($additionalData['reason_for_visit'])) {
            $additionalData['reason_for_visit'] = $request->reason_for_visit;
        }

        // Debug logging — server-authoritative
        \Log::info('AI Suggestion Request Data (server-authoritative)', [
            'appointment_id' => $appointment->id,
            'continue_limited' => $continueLimited,
            'server_symptoms' => $symptoms,
            'server_allergies' => $allergies,
            'server_past_meds' => $past_meds,
            'has_current_diagnosis' => !empty($latestDiagnosis),
            'past_diagnoses_count' => $pastDiagnosesModels->count() ?? 0,
            'has_voice' => !empty($voiceModel),
            'additional_data_keys' => array_keys($additionalData),
        ]);

        $aiAssistant = app(AIAssistant::class);

        // Use the enhanced method that includes FDA validation
        $result = $aiAssistant->generatePrescriptionSuggestionsWithFDAValidation($appointment, $symptoms, $allergies, $past_meds, $additionalData);

        return response()->json($result);
    }

    /**
     * Generate AI medical copilot analysis for clinical decision support
     */
    public function aiMedicalCopilot(Request $request, Appointment $appointment)
    {
        // Validate doctor access to this appointment
        if (!Auth::user()->isDoctor() || $appointment->doctor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to AI Medical Copilot.');
        }

        // Validate required data structure
        $request->validate([
            'complaint' => 'required|array',
            'vitals' => 'required|array',
            'history' => 'required|array',
            'labs' => 'nullable|array',
            'previous_visits' => 'nullable|array',
        ]);

        // Create structured data array
        $structuredData = [
            'complaint' => $request->complaint,
            'vitals' => $request->vitals,
            'history' => $request->history,
            'labs' => $request->labs ?? [],
            'previous_visits' => $request->previous_visits ?? [],
            'patient_age' => $appointment->patient ? ($appointment->patient->age ?? ($appointment->patient->date_of_birth ? \Carbon\Carbon::parse($appointment->patient->date_of_birth)->age : null)) : null,
            'patient_gender' => $appointment->patient ? ($appointment->patient->gender ?? null) : null,
        ];

        // Initialize AI Medical Copilot service
        $copilotService = new \App\Services\AIMedicalCopilotService();

        // Generate medical analysis
        $result = $copilotService->generateMedicalAnalysis($appointment, $structuredData);

        // Log the response if successful
        if (!isset($result['error']) && !isset($result['disabled'])) {
            $copilotService->logAICopilotResponse($appointment->id, $result);
        }

        return response()->json($result);
    }

    /**
     * Get patient's AI copilot analysis history
     */
    public function getPatientAIAnalyses(Request $request, $patientId)
    {
        // Validate doctor access
        if (!Auth::user()->isDoctor()) {
            abort(403, 'Unauthorized access.');
        }

        $analyses = \App\Models\AICopilotAnalysis::with(['appointment', 'doctor', 'reviewer'])
            ->forPatient($patientId)
            ->active()
            ->orderBy('generated_at', 'desc')
            ->paginate(10);

        return response()->json($analyses);
    }

    /**
     * Show individual AI analysis details
     */
    public function showAIAnalysis(Request $request, $analysisId)
    {
        // Validate doctor access
        if (!Auth::user()->isDoctor()) {
            abort(403, 'Unauthorized access.');
        }

        $analysis = \App\Models\AICopilotAnalysis::with(['appointment.patient', 'doctor', 'reviewer'])
            ->findOrFail($analysisId);

        // Check if doctor has access to this patient's data
        // Doctors should be able to view analyses for patients they've treated
        $hasAccess = $analysis->doctor_id === Auth::id() ||
                    ($analysis->appointment && $analysis->appointment->doctor_id === Auth::user()->doctor->id);

        if (!$hasAccess) {
            abort(403, 'You do not have permission to view this analysis.');
        }

        return view('ai.analysis-detail', compact('analysis'));
    }

    /**
     * Save AI copilot analysis to patient medical history
     */
    public function saveAICopilotAnalysis(Request $request, Appointment $appointment)
    {
        // Validate doctor access to this appointment
        if (!Auth::user()->isDoctor() || $appointment->doctor->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to appointment.');
        }

        // Validate request data
        $request->validate([
            'analysis_data' => 'required|array',
            'include_in_note' => 'nullable|array',
        ]);

        // Initialize AI Medical Copilot service
        $copilotService = new \App\Services\AIMedicalCopilotService();

        // Save analysis to medical history
        $saved = $copilotService->saveAnalysisToMedicalHistory($appointment, $request->analysis_data);

        if ($saved) {
            return response()->json([
                'success' => true,
                'message' => 'AI Medical Copilot analysis saved successfully',
                'saved' => true
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save AI analysis',
                'saved' => false
            ], 422);
        }
    }

    /**
     * Save quick data for AI prescription suggestions
     */
    public function saveQuickData(Request $request, Appointment $appointment)
    {
        try {
            // Validate doctor access to this appointment
            if (!Auth::user()->isDoctor() || $appointment->doctor->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access to appointment.');
            }

            $request->validate([
                'allergies' => 'nullable|string|max:1000',
                'medications' => 'nullable|string|max:1000',
                'clinical_notes' => 'nullable|string|max:2000',
                'weight' => 'nullable|string|max:20',
                'diagnosis_text' => 'nullable|string|max:2000',
            ]);

            Log::info('Saving quick data', [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'allergies' => $request->allergies,
                'medications' => $request->medications,
                'clinical_notes' => $request->clinical_notes,
            ]);

            // Create or update diagnosis with the quick data
            $diagnosis = $appointment->patient ? 
                \App\Models\Diagnosis::where('patient_id', $appointment->patient->id)->latest()->first() : null;

            if (!$diagnosis) {
                // Create a new diagnosis record
                $diagnosis = \App\Models\Diagnosis::create([
                    'doctor_id' => Auth::id(),
                    'patient_id' => $appointment->patient_id,
                    'appointment_id' => $appointment->id,
                    'diagnosis_text' => $request->diagnosis_text ?: 'Quick data entry for AI prescription support',
                    'patient_data' => [
                        'allergies' => $request->allergies ?: '',
                        'medications' => $request->medications ?: '',
                        'clinical_notes' => $request->clinical_notes ?: '',
                        'weight' => $request->weight ?: '',
                        'quick_entry' => true,
                        'created_at' => now()->toISOString()
                    ]
                ]);
                
                Log::info('Created new diagnosis for quick data', [
                    'diagnosis_id' => $diagnosis->id,
                    'patient_data' => $diagnosis->patient_data
                ]);
            } else {
                // Update existing diagnosis with quick data
                $patientData = $diagnosis->patient_data ?? [];
                if ($request->allergies) {
                    $patientData['allergies'] = $request->allergies;
                }
                if ($request->medications) {
                    $patientData['medications'] = $request->medications;
                }
                if ($request->clinical_notes) {
                    $patientData['clinical_notes'] = $request->clinical_notes;
                }
                if ($request->weight) {
                    $patientData['weight'] = $request->weight;
                }
                if ($request->diagnosis_text) {
                    $diagnosis->diagnosis_text = $request->diagnosis_text;
                }
                $patientData['quick_entry_updated'] = now()->toISOString();
                
                $updateData = ['patient_data' => $patientData];
                if ($request->diagnosis_text) $updateData['diagnosis_text'] = $request->diagnosis_text;
                $diagnosis->update($updateData);
                
                Log::info('Updated existing diagnosis with quick data', [
                    'diagnosis_id' => $diagnosis->id,
                    'patient_data' => $patientData
                ]);
            }

            // Also update appointment doctor notes if clinical notes provided
            if ($request->clinical_notes) {
                $appointment->update(['doctor_notes' => $request->clinical_notes]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Quick data saved successfully',
                'diagnosis_id' => $diagnosis->id,
                'patient_data' => $diagnosis->patient_data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error saving quick data', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save quick data: ' . $e->getMessage()
            ], 500);
        }
    }}
