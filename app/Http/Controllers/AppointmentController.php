<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

            // Generate verification token for guest appointments
            if ($appointment->isGuestAppointment()) {
                $appointment->generateVerificationToken();
            }

            if ($doctor->auto_approve_appointments) {
                $appointment->confirm();
            }

            DB::commit();

            // Send notifications
            $this->sendAppointmentNotifications($appointment);

            // Handle AJAX requests vs regular form submissions
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Appointment booked successfully! ' .
                        ($appointment->isGuestAppointment() ? 'Check your email for verification and appointment details.' : ''),
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'redirect_url' => $appointment->isGuestAppointment() ?
                        route('appointments.guest.show', [
                            'appointment' => $appointment->appointment_number,
                            'email' => $appointment->guest_email
                        ]) :
                        route('appointments.show', $appointment)
                ]);
            } else {
                if ($appointment->isGuestAppointment()) {
                    return redirect()->route('appointments.guest.show', [
                        'appointment' => $appointment->appointment_number,
                        'email' => $appointment->guest_email
                    ])->with('success', 'Appointment booked successfully! Check your email for verification and appointment details.');
                } else {
                    return redirect()->route('appointments.show', $appointment)
                        ->with('success', 'Appointment booked successfully!');
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
        // Check if user can view this appointment
        if ($appointment->patient_id !== Auth::id() &&
            (!Auth::user()->isDoctor() || $appointment->doctor->user_id !== Auth::id())) {
            abort(403);
        }

        // Log doctor access to patient appointment
        if (Auth::user()->isDoctor() && $appointment->patient_id) {
            \App\Services\AuditLoggingService::logDoctorAccessPatient(
                Auth::id(),
                $appointment->patient_id,
                ['appointment_id' => $appointment->id]
            );
        }

        $appointment->load(['doctor.user', 'doctor.specialty', 'patient', 'review']);

        return view('appointments.show', compact('appointment'));
    }

    /**
     * Cancel an appointment
     */
    public function cancel(Request $request, Appointment $appointment)
    {
        // Check if user can cancel this appointment
        if ($appointment->patient_id !== Auth::id()) {
            abort(403);
        }

        if (!$appointment->canBeCancelled()) {
            return back()->withErrors(['error' => 'This appointment cannot be cancelled.']);
        }

        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500'
        ]);

        $appointment->cancel('patient', $request->cancellation_reason);

        // Send cancellation notifications
        $this->sendAppointmentCancellationNotifications($appointment, $request->cancellation_reason);

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment cancelled successfully.');
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

        DB::beginTransaction();
        try {
            $appointment->update([
                'appointment_date' => $newDate,
                'appointment_end' => $newDate->copy()->addMinutes($doctor->appointment_duration),
                'status' => $doctor->auto_approve_appointments ? 'confirmed' : 'pending',
            ]);

            if ($doctor->auto_approve_appointments) {
                $appointment->confirm();
            }

            DB::commit();

            // Send rescheduling notifications
            $this->sendAppointmentReschedulingNotifications($appointment, $newDate);

            return redirect()->route('appointments.show', $appointment)
                ->with('success', 'Appointment rescheduled successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to reschedule appointment. Please try again.']);
        }
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
            // Send notification to doctor about new appointment
            if ($appointment->doctor && $appointment->doctor->user) {
                $doctor = $appointment->doctor->user;

                // Check if doctor wants appointment notifications
                if ($doctor->wantsNotification('appointment_booked')) {
                    $doctor->notifyIfWants(new \App\Notifications\AppointmentBookedNotification($appointment), 'appointment_booked');
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


}
