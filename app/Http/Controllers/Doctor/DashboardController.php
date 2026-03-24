<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\DoctorNote;
use App\Mail\AppointmentConfirmationMail;
use App\Mail\AppointmentCancellationMail;
use App\Mail\AppointmentCompletionMail;
use App\Mail\FollowUpAppointmentMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Traits\HandlesEffectiveDoctor;

class DashboardController extends Controller
{
    use HandlesEffectiveDoctor;
    public function __construct()
    {
        // Middleware is handled at route level
    }

    /**
     * Display the doctor dashboard
     */
    public function index()
    {
        $doctor = $this->getEffectiveDoctor();

        // Get today's appointments
        $todayAppointments = $doctor->appointments()
            ->with(['patient.patientRiskScores'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_date')
            ->get();

        // Get upcoming appointments (next 7 days)
        $upcomingAppointments = $doctor->appointments()
            ->with(['patient.patientRiskScores'])
            ->whereBetween('appointment_date', [now(), now()->addDays(7)])
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('appointment_date')
            ->limit(5)
            ->get();

        // Get pending appointments
        $pendingAppointments = $doctor->appointments()
            ->with(['patient.patientRiskScores'])
            ->where('status', 'pending')
            ->orderBy('appointment_date')
            ->limit(5)
            ->get();

        // Get recent reviews
        $recentReviews = $doctor->reviews()
            ->with(['patient'])
            ->latest()
            ->limit(5)
            ->get();

        // Get recent notes
        $recentNotes = $this->getEffectiveDoctorUser()->doctorNotes()
            ->with(['patient'])
            ->latest()
            ->limit(5)
            ->get();

        // Calculate statistics
        $stats = $this->getDashboardStats($doctor);

        return view('doctor.dashboard', compact(
            'doctor',
            'todayAppointments',
            'upcomingAppointments',
            'pendingAppointments',
            'recentReviews',
            'recentNotes',
            'stats'
        ));
    }

    /**
     * Display appointments calendar
     */
    public function appointments(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        $query = $doctor->appointments()->with(['patient.patientRiskScores']);

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

        // Filter by risk category
        if ($request->filled('risk_category')) {
            $query->whereHas('patient.patientRiskScores', function ($q) use ($request) {
                $q->whereColumn('appointment_id', 'appointments.id');

                switch ($request->risk_category) {
                    case 'low':
                        $q->whereRaw('GREATEST(no_show_risk, hospitalization_risk) < 0.3');
                        break;
                    case 'medium':
                        $q->whereRaw('GREATEST(no_show_risk, hospitalization_risk) >= 0.3')
                          ->whereRaw('GREATEST(no_show_risk, hospitalization_risk) < 0.7');
                        break;
                    case 'high':
                        $q->whereRaw('GREATEST(no_show_risk, hospitalization_risk) >= 0.7');
                        break;
                }
            });
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->paginate(15);

        return view('doctor.appointments.index', compact('appointments'));
    }

    /**
     * Show appointment details
     */
    public function showAppointment(Appointment $appointment)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        // Log doctor access to patient appointment
        if ($appointment->patient_id) {
            \App\Services\AuditLoggingService::logDoctorAccessPatient(
                $this->getEffectiveDoctorUser()->id,
                $appointment->patient_id,
                ['appointment_id' => $appointment->id]
            );
        }

        $appointment->load(['patient', 'review']);

        // Generate risk predictions if they don't exist for this appointment
        $this->ensureRiskPredictions($appointment);

        // Reload appointment with risk scores
        $appointment->load(['patient.patientRiskScores' => function($query) use ($appointment) {
            $query->where('appointment_id', $appointment->id);
        }]);

        return view('doctor.appointments.show', compact('appointment'));
    }

    /**
     * Confirm an appointment
     */
    public function confirmAppointment(Appointment $appointment)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if effective doctor exists
        if (!$doctor) {
            Log::error('No effective doctor found for user during appointment confirmation', [
                'user_id' => Auth::id(),
                'appointment_id' => $appointment->id,
                'user_role' => Auth::user()->role,
                'is_sub_user' => Auth::user()->isSubUser(),
                'parent_user_id' => Auth::user()->parent_user_id,
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'No doctor profile found. Please contact support if you believe this is an error.');
        }

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        if ($appointment->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending appointments can be confirmed.']);
        }

        $appointment->confirm();

        // Load patient relationship for email sending
        $appointment->load('patient');

        // Send confirmation email to patient
        if ($appointment->patient && $appointment->patient->email) {
            Log::info('Sending appointment confirmation email', [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient->id,
                'patient_email' => $appointment->patient->email,
                'doctor_id' => $appointment->doctor_id,
                'appointment_date' => $appointment->appointment_date,
                'status' => $appointment->status
            ]);

            try {
                Mail::to($appointment->patient->email)->send(new AppointmentConfirmationMail($appointment));
                Log::info('Appointment confirmation email sent successfully', [
                    'appointment_id' => $appointment->id,
                    'patient_email' => $appointment->patient->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send appointment confirmation email', [
                    'appointment_id' => $appointment->id,
                    'patient_email' => $appointment->patient->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with the process even if email fails
            }
        } else {
            Log::warning('Cannot send appointment confirmation email - missing patient or email', [
                'appointment_id' => $appointment->id,
                'has_patient' => $appointment->patient ? true : false,
                'patient_id' => $appointment->patient ? $appointment->patient->id : null,
                'has_email' => $appointment->patient && $appointment->patient->email ? true : false,
                'guest_appointment' => $appointment->isGuestAppointment(),
                'guest_email' => $appointment->guest_email
            ]);
        }

        return back()->with('success', 'Appointment confirmed successfully.');
    }

    /**
     * Cancel an appointment
     */
    public function cancelAppointment(Request $request, Appointment $appointment)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if effective doctor exists
        if (!$doctor) {
            Log::error('No effective doctor found for user during appointment cancellation', [
                'user_id' => Auth::id(),
                'appointment_id' => $appointment->id,
                'user_role' => Auth::user()->role,
                'is_sub_user' => Auth::user()->isSubUser(),
                'parent_user_id' => Auth::user()->parent_user_id,
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'No doctor profile found. Please contact support if you believe this is an error.');
        }

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        if (in_array($appointment->status, ['cancelled', 'completed'])) {
            return back()->withErrors(['error' => 'This appointment cannot be cancelled.']);
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        $appointment->cancel('doctor', $request->cancellation_reason);

        // Load patient relationship for email sending
        $appointment->load('patient');

        // Send cancellation email to patient
        if ($appointment->patient && $appointment->patient->email) {
            Log::info('Sending appointment cancellation email', [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient->id,
                'patient_email' => $appointment->patient->email,
                'doctor_id' => $appointment->doctor_id,
                'appointment_date' => $appointment->appointment_date,
                'status' => $appointment->status,
                'cancellation_reason' => $request->cancellation_reason
            ]);

            try {
                Mail::to($appointment->patient->email)->send(new AppointmentCancellationMail($appointment, $request->cancellation_reason));
                Log::info('Appointment cancellation email sent successfully', [
                    'appointment_id' => $appointment->id,
                    'patient_email' => $appointment->patient->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send appointment cancellation email', [
                    'appointment_id' => $appointment->id,
                    'patient_email' => $appointment->patient->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with the process even if email fails
            }
        } else {
            Log::warning('Cannot send appointment cancellation email - missing patient or email', [
                'appointment_id' => $appointment->id,
                'has_patient' => $appointment->patient ? true : false,
                'patient_id' => $appointment->patient ? $appointment->patient->id : null,
                'has_email' => $appointment->patient && $appointment->patient->email ? true : false,
                'guest_appointment' => $appointment->isGuestAppointment(),
                'guest_email' => $appointment->guest_email
            ]);
        }

        return back()->with('success', 'Appointment cancelled successfully.');
    }

    /**
     * Complete an appointment
     */
    public function completeAppointment(Request $request, Appointment $appointment)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        if ($appointment->status !== 'confirmed') {
            return back()->withErrors(['error' => 'Only confirmed appointments can be completed.']);
        }

        $request->validate([
            'doctor_notes' => 'nullable|string|max:2000',
            'follow_up_required' => 'nullable'
        ]);

        $appointment->update([
            'doctor_notes' => $request->doctor_notes,
            'follow_up_required' => $request->boolean('follow_up_required'),
        ]);

        $appointment->complete();

        // Load patient relationship for email sending
        $appointment->load('patient');

        // Send completion email to patient with review request
        if ($appointment->patient && $appointment->patient->email) {
            Log::info('Sending appointment completion email', [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient->id,
                'patient_email' => $appointment->patient->email,
                'doctor_id' => $appointment->doctor_id,
                'appointment_date' => $appointment->appointment_date,
                'status' => $appointment->status,
                'diagnosis_id' => $appointment->diagnosis_id
            ]);

            try {
                $diagnosis = $appointment->diagnosis_id ? \App\Models\Diagnosis::find($appointment->diagnosis_id) : null;
                Mail::to($appointment->patient->email)->send(new AppointmentCompletionMail($appointment, $diagnosis));
                Log::info('Appointment completion email sent successfully', [
                    'appointment_id' => $appointment->id,
                    'patient_email' => $appointment->patient->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send appointment completion email', [
                    'appointment_id' => $appointment->id,
                    'patient_email' => $appointment->patient->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with the process even if email fails
            }
        } else {
            Log::warning('Cannot send appointment completion email - missing patient or email', [
                'appointment_id' => $appointment->id,
                'has_patient' => $appointment->patient ? true : false,
                'patient_id' => $appointment->patient ? $appointment->patient->id : null,
                'has_email' => $appointment->patient && $appointment->patient->email ? true : false,
                'guest_appointment' => $appointment->isGuestAppointment(),
                'guest_email' => $appointment->guest_email
            ]);
        }

        return back()->with('success', 'Appointment completed successfully.');
    }

    /**
     * Mark appointment as no show
     */
    public function markNoShow(Appointment $appointment)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        if ($appointment->status !== 'confirmed') {
            return back()->withErrors(['error' => 'Only confirmed appointments can be marked as no show.']);
        }

        $appointment->markAsNoShow();

        return back()->with('success', 'Appointment marked as no show.');
    }

    /**
     * Toggle auto-approve appointments setting
     */
    public function toggleAutoApprove(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        $request->validate([
            'auto_approve' => 'required|boolean'
        ]);

        $doctor->update([
            'auto_approve_appointments' => $request->auto_approve
        ]);

        $status = $request->auto_approve ? 'enabled' : 'disabled';

        return response()->json([
            'success' => true,
            'message' => "Auto-approve appointments {$status} successfully!",
            'auto_approve' => $request->auto_approve
        ]);
    }

    /**
     * Show form to create a new appointment
     */
    public function createAppointment()
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if effective doctor exists
        if (!$doctor) {
            Log::error('No effective doctor found for user during appointment creation', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role,
                'is_sub_user' => Auth::user()->isSubUser(),
                'parent_user_id' => Auth::user()->parent_user_id,
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'No doctor profile found. Please contact support if you believe this is an error.');
        }

        // Log doctor info for debugging
        Log::info('Create appointment - doctor info', [
            'current_user_id' => Auth::id(),
            'current_user_role' => Auth::user()->role,
            'is_sub_user' => Auth::user()->isSubUser(),
            'parent_user_id' => Auth::user()->parent_user_id,
            'effective_doctor_id' => $doctor ? $doctor->id : null,
            'user_doctor_id' => Auth::user()->doctor ? Auth::user()->doctor->id : null,
        ]);

        // Get available slots for next 30 days
        $availableSlots = [];
        for ($i = 0; $i < 30; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $slots = $doctor->getAvailableSlots($date);
            if ($slots->isNotEmpty()) {
                $availableSlots[$date] = $slots;
            }
        }

        // Get doctor's existing patients for selection (unified method - primary_doctor_id OR appointments)
        $patients = $this->getEffectiveDoctorUser()->getDoctorPatients();

        return view('doctor.appointments.create', compact('doctor', 'availableSlots', 'patients'));
    }

    /**
     * Store a newly created appointment
     */
    public function storeAppointment(Request $request)
    {
        // Log that the method was called
        Log::info('storeAppointment method called', [
            'current_user_id' => Auth::id(),
            'request_method' => $request->method(),
            'request_data' => $request->all(),
            'has_csrf_token' => $request->has('_token'),
        ]);

        $doctor = $this->getEffectiveDoctor();

        // Prepare validation rules based on patient type
        $rules = [
            'appointment_date' => 'required|date|after:now',
            'appointment_type' => 'required|in:' . implode(',', $doctor->getEnabledAppointmentTypes()),
            'reason' => 'required|string|max:500',
            'patient_type' => 'required|in:existing,new',
        ];

        if ($request->patient_type === 'existing') {
            $rules['existing_patient_id'] = 'required|exists:users,id';
        } else {
            $rules['patient_name'] = 'required|string|max:255';
            $rules['patient_email'] = 'required|email|unique:users,email';
            $rules['patient_phone'] = 'required|string|max:20';
            $rules['patient_date_of_birth'] = 'required|date|before:today';
            $rules['patient_gender'] = 'required|in:male,female,other';
            $rules['patient_terms'] = 'required|accepted';
        }

        try {
            $request->validate($rules);
            Log::info('Validation passed successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            throw $e;
        }

        // Validate slot availability
        $appointmentDate = Carbon::parse($request->appointment_date);
        $slots = $doctor->getAvailableSlots($appointmentDate->format('Y-m-d'));
        $requestedSlot = $slots->first(fn($slot) => $slot['datetime'] === $appointmentDate->toDateTimeString());

        if (!$requestedSlot) {
            return back()->withErrors(['appointment_date' => 'Selected time slot is not available.']);
        }

        // Handle patient creation/selection
        if ($request->patient_type === 'existing') {
            $patient = \App\Models\User::findOrFail($request->existing_patient_id);
        } else {
            // Auto-generate a secure password
            $generatedPassword = \Illuminate\Support\Str::random(12);

            // Calculate age from date of birth
            $birthDate = \Carbon\Carbon::parse($request->patient_date_of_birth);
            $age = $birthDate->age;

            $patient = \App\Models\User::create([
                'name' => $request->patient_name,
                'email' => $request->patient_email,
                'phone' => $request->patient_phone,
                'date_of_birth' => $request->patient_date_of_birth,
                'age' => $age, // Calculate age from date of birth
                'gender' => $request->patient_gender,
                'password' => bcrypt($generatedPassword),
                'role' => 'patient',
                'email_verified_at' => now(), // Auto-verify since created by doctor
                'primary_doctor_id' => $doctor->user_id, // Assign the doctor as primary doctor
            ]);

            // Send welcome notification with login credentials
            try {
                $patient->notify(new \App\Notifications\SystemAlertNotification(
                    'Welcome to Our Medical Portal',
                    "Your patient account has been created by Dr. {$doctor->user->name}.\n\n" .
                    "Login Email: {$request->patient_email}\n" .
                    "Temporary Password: {$generatedPassword}\n\n" .
                    "Please log in and change your password. You can manage your appointments and health records.",
                    'success',
                    [
                        'link' => route('login'),
                        'link_text' => 'Sign In to Your Account'
                    ]
                ));
            } catch (\Exception $e) {
                Log::error('Failed to send welcome notification to new patient: ' . $e->getMessage());
            }
        }

        // Create appointment
        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => $appointmentDate,
            'appointment_end' => $appointmentDate->copy()->addMinutes($doctor->appointment_duration),
            'status' => $doctor->auto_approve_appointments ? 'confirmed' : 'pending',
            'appointment_type' => $request->appointment_type,
            'reason' => $request->reason,
            'consultation_fee' => $doctor->consultation_fee,
        ]);

        // Confirm appointment if auto-approve is enabled (same as patient booking)
        if ($doctor->auto_approve_appointments) {
            $appointment->confirm();
        }

        // Log appointment creation for debugging
        Log::info('Appointment created by doctor', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => $appointment->patient_id,
            'appointment_date' => $appointment->appointment_date,
            'status' => $appointment->status,
            'auto_approve' => $doctor->auto_approve_appointments,
            'current_user_id' => Auth::id(),
            'effective_doctor_id' => $doctor->id,
        ]);

        // Send notifications (same as patient booking flow)
        $this->sendAppointmentNotifications($appointment);

        return redirect()->route('doctor.appointments.show', $appointment)
            ->with('success', 'Appointment booked successfully!');
    }

    /**
     * Send appointment notifications (same as patient booking flow)
     */
    private function sendAppointmentNotifications(Appointment $appointment)
    {
        try {
            // Send notification to doctor about new appointment
            if ($appointment->doctor && $appointment->doctor->user) {
                $doctor = $appointment->doctor->user;

                // Check if doctor wants appointment notifications
                if ($doctor->wantsNotification('appointment_booked')) {
                    // Send notification directly, not using queue
                    $notification = new \App\Notifications\AppointmentBookedNotification($appointment);
                    $doctor->notify($notification);

                    // Broadcast event immediately, not using queue
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
            Log::error('Failed to send appointment notifications: ' . $e->getMessage());
        }
    }

    /**
     * Search patients for appointment booking
     */
    public function searchPatients(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2|max:100'
            ]);

            $query = $request->input('query');

            // Get only patients related to the current doctor
            $doctorUser = $this->getEffectiveDoctorUser();
            $patients = $doctorUser->getDoctorPatients()
                ->filter(function($patient) use ($query) {
                    return stripos($patient->name, $query) !== false ||
                           stripos($patient->email, $query) !== false ||
                           ($patient->phone && stripos($patient->phone, $query) !== false);
                })
                ->take(10)
                ->map(function($patient) {
                    return [
                        'id' => $patient->id,
                        'name' => $patient->name,
                        'email' => $patient->email,
                        'phone' => $patient->phone,
                        'text' => "{$patient->name} ({$patient->email})"
                    ];
                })
                ->values()
                ->toArray();

            return response()->json($patients);
        } catch (\Exception $e) {
            Log::error('Patient search error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'query' => $request->all()
            ]);
            return response()->json([
                'error' => 'Search failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display reviews
     */
    public function reviews(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        $query = $doctor->reviews()->with(['patient', 'appointment']);

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter by approval status
        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_approved', false);
            }
        }

        $reviews = $query->latest()->paginate(15);

        // Calculate positive reviews (ratings 4-5)
        $positiveReviews = $doctor->reviews()->whereIn('rating', [4, 5])->count();

        // Calculate recent reviews (this month)
        $recentReviews = $doctor->reviews()->whereMonth('created_at', now()->month)->count();

        return view('doctor.reviews.index', compact('doctor', 'reviews', 'positiveReviews', 'recentReviews'));
    }

    /**
     * Show doctor profile edit form
     */
    public function profile()
    {
        $doctor = $this->getEffectiveDoctor();
        $doctor->load(['user', 'specialty', 'googleAccount']);

        // Get available specialties for the dropdown
        $specialties = \App\Models\Specialty::orderBy('name')->get();

        return view('doctor.profile.edit', compact('doctor', 'specialties'));
    }

    /**
     * Update doctor profile
     */
    public function updateProfile(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        $request->validate([
            'bio' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:20',
            'specialty_id' => 'required|exists:specialties,id',
            'consultation_fee' => 'required|numeric|min:0|max:999999',
            'appointment_duration' => 'required|integer|min:15|max:240',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'auto_approve_appointments' => 'boolean',
            'allow_cancellation' => 'boolean',
            'allow_rescheduling' => 'boolean',
            'cancellation_hours' => 'required|integer|min:1|max:168',
        ]);

        $data = $request->except(['profile_image']);

        // Convert consultation fee to cents
        $data['consultation_fee'] = $request->consultation_fee * 100;

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($doctor->profile_image) {
                \Storage::disk('public')->delete($doctor->profile_image);
            }

            $data['profile_image'] = $request->file('profile_image')->store('doctor-profiles', 'public');
        }

        $doctor->update($data);

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Get calendar events for appointments (AJAX)
     */
    public function getCalendarEvents(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();
        $start = $request->start;
        $end = $request->end;

        $appointments = $doctor->appointments()
            ->with(['patient.patientRiskScores'])
            ->whereBetween('appointment_date', [$start, $end])
            ->get();

        $events = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => $appointment->patient_name,
                'start' => $appointment->appointment_date->toISOString(),
                'end' => $appointment->appointment_end->toISOString(),
                'color' => $this->getEventColor($appointment->status),
                'url' => route('doctor.appointments.show', $appointment),
                'extendedProps' => [
                    'status' => $appointment->status,
                    'patient' => $appointment->patient_name,
                    'reason' => $appointment->reason,
                    'type' => $appointment->appointment_type,
                    'phone' => $appointment->patient_phone,
                ]
            ];
        });

        return response()->json($events);
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats($doctor)
    {
        $today = today();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            'total_appointments' => $doctor->appointments()->count(),
            'today_appointments' => $doctor->appointments()->whereDate('appointment_date', $today)->count(),
            'pending_appointments' => $doctor->appointments()->where('status', 'pending')->count(),
            'this_month_appointments' => $doctor->appointments()->whereDate('appointment_date', '>=', $thisMonth)->count(),
            'completed_appointments' => $doctor->appointments()->where('status', 'completed')->count(),
            'cancelled_appointments' => $doctor->appointments()->where('status', 'cancelled')->count(),
            'average_rating' => $doctor->average_rating,
            'total_reviews' => $doctor->total_reviews,
            'this_month_reviews' => $doctor->reviews()->whereDate('created_at', '>=', $thisMonth)->count(),
            'revenue_this_month' => $doctor->appointments()
                ->where('status', 'completed')
                ->whereDate('appointment_date', '>=', $thisMonth)
                ->sum('consultation_fee') / 100, // Convert from cents to dollars
            'total_notes' => $this->getEffectiveDoctorUser()->doctorNotes()->count(),
            'voice_notes' => $this->getEffectiveDoctorUser()->doctorNotes()->where('note_type', 'voice')->count(),
            'this_month_notes' => $this->getEffectiveDoctorUser()->doctorNotes()->whereDate('created_at', '>=', $thisMonth)->count(),
        ];
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
     * Display the on-deck dashboard for real-time appointment tracking
     */
    public function onDeck(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        // Get appointments for on-deck display (today and upcoming)
        $query = $doctor->appointments()
            ->with(['patient.patientRiskScores'])
            ->whereIn('status', ['check_in', 'in_progress', 'confirmed'])
            ->whereDate('appointment_date', '>=', today())
            ->whereDate('appointment_date', '<=', today()->addDays(1)); // Today and tomorrow

        // Filter by status if specified
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Order by appointment time and priority
        $appointments = $query->orderBy('appointment_date')
            ->orderByRaw("CASE
                WHEN status = 'in_progress' THEN 1
                WHEN status = 'check_in' THEN 2
                WHEN status = 'confirmed' THEN 3
                ELSE 4
            END")
            ->get();

        // Add priority based on risk scores and appointment time
        $appointments->transform(function ($appointment) {
            $riskScore = $appointment->patient->patientRiskScores
                ->where('appointment_id', $appointment->id)
                ->first();

            $priority = 'low';
            if ($riskScore) {
                $maxRisk = max($riskScore->no_show_risk, $riskScore->hospitalization_risk);
                if ($maxRisk >= 0.7) {
                    $priority = 'high';
                } elseif ($maxRisk >= 0.3) {
                    $priority = 'medium';
                }
            }

            $appointment->priority = $priority;
            return $appointment;
        });

        // Sort by priority and time
        $appointments = $appointments->sort(function ($a, $b) {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            $priorityDiff = $priorityOrder[$b->priority] - $priorityOrder[$a->priority];

            if ($priorityDiff !== 0) {
                return $priorityDiff;
            }

            return $a->appointment_date <=> $b->appointment_date;
        })->values();

        return view('doctor.on-deck', compact('appointments'));
    }

    /**
     * Update appointment status via AJAX
     */
    public function updateAppointmentStatus(Request $request, Appointment $appointment)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:check_in,in_progress,completed,no_show'
        ]);

        $newStatus = $request->status;

        // Validate status transitions
        $validTransitions = [
            'check_in' => ['in_progress', 'no_show'],
            'in_progress' => ['completed', 'no_show'],
            'confirmed' => ['check_in', 'no_show'],
        ];

        if (!isset($validTransitions[$appointment->status]) ||
            !in_array($newStatus, $validTransitions[$appointment->status])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition'
            ], 400);
        }

        try {
            // Update appointment status
            switch ($newStatus) {
                case 'in_progress':
                    if ($appointment->status === 'check_in') {
                        $appointment->update(['status' => 'in_progress']);
                    }
                    break;
                case 'completed':
                    if ($appointment->status === 'in_progress') {
                        $appointment->complete();
                    }
                    break;
                case 'no_show':
                    if (in_array($appointment->status, ['check_in', 'in_progress', 'confirmed'])) {
                        $appointment->markAsNoShow();
                    }
                    break;
            }

            // Broadcast the status change
            broadcast(new \App\Events\AppointmentStatusUpdated($appointment))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Appointment status updated successfully',
                'appointment' => [
                    'id' => $appointment->id,
                    'status' => $appointment->status,
                    'updated_at' => $appointment->updated_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update appointment status', [
                'appointment_id' => $appointment->id,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update appointment status'
            ], 500);
        }
    }

    /**
     * Update appointment order (drag and drop)
     */
    public function reorderAppointments(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:appointments,id'
        ]);

        try {
            // Update sort order for the doctor's appointments
            foreach ($request->order as $index => $appointmentId) {
                $appointment = Appointment::where('id', $appointmentId)
                    ->where('doctor_id', $doctor->id)
                    ->first();

                if ($appointment) {
                    $appointment->update(['sort_order' => $index + 1]);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to reorder appointments', [
                'error' => $e->getMessage(),
                'order' => $request->order
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update appointment order'
            ], 500);
        }
    }

    /**
     * Ensure risk predictions exist for an appointment
     */
    private function ensureRiskPredictions(Appointment $appointment)
    {
        // Skip if no patient associated
        if (!$appointment->patient_id) {
            return;
        }

        // Cache key for this appointment's risk predictions
        $cacheKey = "risk_predictions_{$appointment->patient_id}_{$appointment->id}";

        // Check cache first
        if (Cache::has($cacheKey)) {
            return; // Already processed recently
        }

        // Check if risk score already exists for this appointment
        $existingRiskScore = \App\Models\PatientRiskScore::where('patient_id', $appointment->patient_id)
            ->where('appointment_id', $appointment->id)
            ->first();

        if ($existingRiskScore) {
            // Cache for 1 hour to prevent repeated checks
            Cache::put($cacheKey, true, 3600);
            return; // Already exists
        }

        try {
            // Generate predictions using the service
            $predictiveService = app(\App\Services\PredictiveAnalyticsService::class);
            $predictions = $predictiveService->predictRisks($appointment->patient, $appointment);

            // Create and save the risk score
            $riskScore = new \App\Models\PatientRiskScore();
            $riskScore->patient_id = $appointment->patient_id;
            $riskScore->appointment_id = $appointment->id;
            $riskScore->no_show_risk = $predictions['no_show_risk'];
            $riskScore->hospitalization_risk = $predictions['hospitalization_risk'];
            $riskScore->save();

            // Cache success for 1 hour
            Cache::put($cacheKey, true, 3600);

        } catch (\Exception $e) {
            // Log error but don't fail the page load
            Log::error('Failed to generate risk predictions for appointment ' . $appointment->id, [
                'error' => $e->getMessage(),
                'patient_id' => $appointment->patient_id
            ]);

            // Cache failure for 5 minutes to avoid repeated attempts
            Cache::put($cacheKey, false, 300);
        }
    }

    /**
     * Show form to create a follow-up appointment
     */
    public function createFollowUp(Appointment $appointment)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        // Only allow follow-up creation for completed appointments
        if ($appointment->status !== 'completed') {
            return back()->withErrors(['error' => 'Follow-up appointments can only be created for completed appointments.']);
        }

        return view('doctor.appointments.create-follow-up', compact('appointment'));
    }

    /**
     * Store a new follow-up appointment
     */
    public function storeFollowUp(Request $request, Appointment $appointment)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        // Validate the request
        $request->validate([
            'appointment_date' => 'required|date|after:now',
            'appointment_type' => 'required|in:video_call,phone_call,in_person,follow_up',
            'consultation_fee' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000',
            'duration' => 'nullable|integer|min:15|max:240',
        ]);

        // Create new appointment as follow-up
        $followUpAppointment = new Appointment();
        $followUpAppointment->doctor_id = $doctor->id;
        $followUpAppointment->patient_id = $appointment->patient_id;
        $followUpAppointment->patient_name = $appointment->patient_name;
        $followUpAppointment->patient_email = $appointment->patient_email;
        $followUpAppointment->patient_phone = $appointment->patient_phone;
        $followUpAppointment->appointment_date = $request->appointment_date;
        $followUpAppointment->appointment_type = $request->appointment_type;
        $followUpAppointment->consultation_fee = $request->consultation_fee * 100; // Convert to cents
        $followUpAppointment->appointment_duration = $request->duration ?? 30;
        $followUpAppointment->reason = $request->reason;
        $followUpAppointment->status = 'pending';
        $followUpAppointment->is_follow_up = true;
        $followUpAppointment->original_appointment_id = $appointment->id;
        $followUpAppointment->save();

        // Load the patient relationship for email sending
        $followUpAppointment->load('patient');

        // Send email notification to patient about new follow-up appointment
        if ($followUpAppointment->patient && $followUpAppointment->patient->email) {
            Log::info('Sending follow-up appointment email', [
                'follow_up_appointment_id' => $followUpAppointment->id,
                'original_appointment_id' => $appointment->id,
                'patient_id' => $followUpAppointment->patient->id,
                'patient_email' => $followUpAppointment->patient->email,
                'doctor_id' => $followUpAppointment->doctor_id,
                'appointment_date' => $followUpAppointment->appointment_date,
                'status' => $followUpAppointment->status
            ]);

            try {
                Mail::to($followUpAppointment->patient->email)->send(new FollowUpAppointmentMail($followUpAppointment, $appointment));
                Log::info('Follow-up appointment email sent successfully', [
                    'follow_up_appointment_id' => $followUpAppointment->id,
                    'patient_email' => $followUpAppointment->patient->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send follow-up appointment email', [
                    'follow_up_appointment_id' => $followUpAppointment->id,
                    'patient_email' => $followUpAppointment->patient->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with the process even if email fails
            }
        } else {
            Log::warning('Cannot send follow-up appointment email - missing patient or email', [
                'follow_up_appointment_id' => $followUpAppointment->id,
                'original_appointment_id' => $appointment->id,
                'has_patient' => $followUpAppointment->patient ? true : false,
                'patient_id' => $followUpAppointment->patient ? $followUpAppointment->patient->id : null,
                'has_email' => $followUpAppointment->patient && $followUpAppointment->patient->email ? true : false,
                'guest_appointment' => $followUpAppointment->isGuestAppointment(),
                'guest_email' => $followUpAppointment->guest_email
            ]);
        }

        return redirect()->route('doctor.appointments.show', $appointment)
            ->with('success', 'Follow-up appointment created successfully!');
    }

    /**
     * Display SMS provider settings page for doctors
     */
    public function smsSettings()
    {
        $doctor = $this->getEffectiveDoctor();

        // Get current doctor provider setting
        $doctorProvider = $doctor->sms_provider;

        // Get system provider
        $systemProvider = app(\App\Services\SmsService::class)->getSystemProviderPublic();

        // Get hospital information if doctor belongs to a hospital
        $user = $this->getEffectiveDoctorUser();
        $hospital = $user->hospital;

        $hospitalProvider = $hospital ? $hospital->sms_provider : null;
        $hospitalName = $hospital ? $hospital->name : null;

        // Determine effective provider using the same logic as the API
        if ($doctorProvider) {
            $effectiveProvider = [
                'provider' => $doctorProvider,
                'source' => 'doctor',
                'inherited_from' => null
            ];
        } elseif ($hospitalProvider) {
            $effectiveProvider = [
                'provider' => $hospitalProvider,
                'source' => 'hospital',
                'inherited_from' => $hospitalName
            ];
        } else {
            $effectiveProvider = [
                'provider' => $systemProvider,
                'source' => 'system',
                'inherited_from' => null
            ];
        }

        return view('doctor.sms-settings', compact(
            'doctorProvider',
            'systemProvider',
            'hospitalProvider',
            'hospitalName',
            'effectiveProvider'
        ));
    }

}
