<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\DoctorNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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

        // Get recently completed appointments
        $recentCompletedAppointments = $doctor->appointments()
            ->with(['patient.patientRiskScores'])
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
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
            'recentCompletedAppointments',
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

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        if ($appointment->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending appointments can be confirmed.']);
        }

        $appointment->confirm();

        // TODO: Send confirmation email to patient

        return back()->with('success', 'Appointment confirmed successfully.');
    }

    /**
     * Cancel an appointment
     */
    public function cancelAppointment(Request $request, Appointment $appointment)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        if (in_array($appointment->status, ['cancelled', 'completed', 'no_show'])) {
            return back()->withErrors(['error' => 'This appointment cannot be cancelled.']);
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        $appointment->cancel('doctor', $request->cancellation_reason);

        // TODO: Send cancellation email to patient

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

        // TODO: Send completion email to patient with review request

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
            // WhatsApp notification preferences
            'whatsapp_enabled' => 'boolean',
            'whatsapp_appointment_reminders' => 'boolean',
            'whatsapp_urgent_alerts' => 'boolean',
            'whatsapp_diagnosis_updates' => 'boolean',
            'whatsapp_review_requests' => 'boolean',
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

        // Update notification preferences if provided
        if ($request->hasAny([
            'whatsapp_enabled',
            'whatsapp_appointment_reminders',
            'whatsapp_urgent_alerts',
            'whatsapp_diagnosis_updates',
            'whatsapp_review_requests'
        ])) {
            $notificationPreferences = $doctor->user->getOrCreateNotificationPreferences();
            $notificationPreferences->update([
                'whatsapp_enabled' => $request->whatsapp_enabled ?? false,
                'whatsapp_appointment_reminders' => $request->whatsapp_appointment_reminders ?? false,
                'whatsapp_urgent_alerts' => $request->whatsapp_urgent_alerts ?? false,
                'whatsapp_diagnosis_updates' => $request->whatsapp_diagnosis_updates ?? false,
                'whatsapp_review_requests' => $request->whatsapp_review_requests ?? false,
            ]);
        }

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
     * Show completed appointment details
     */
    public function showCompletedAppointment(Appointment $appointment)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        // Verify appointment is completed
        if ($appointment->status !== 'completed') {
            return redirect()->route('doctor.appointments.show', $appointment)
                ->withErrors(['error' => 'This appointment is not completed yet.']);
        }

        // Log doctor access to patient appointment
        if ($appointment->patient_id) {
            \App\Services\AuditLoggingService::logDoctorAccessPatient(
                $this->getEffectiveDoctorUser()->id,
                $appointment->patient_id,
                ['appointment_id' => $appointment->id]
            );
        }

        // Load appointment with patient, prescriptions, and review
        $appointment->load(['patient', 'prescriptions', 'review']);

        // Generate risk predictions if they don't exist for this appointment
        $this->ensureRiskPredictions($appointment);

        // Reload appointment with risk scores
        $appointment->load(['patient.patientRiskScores' => function($query) use ($appointment) {
            $query->where('appointment_id', $appointment->id);
        }]);

        return view('doctor.appointments.completed', compact('appointment'));
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

        // TODO: Send email notification to patient about new follow-up appointment

        return redirect()->route('doctor.appointments.show', $appointment)
            ->with('success', 'Follow-up appointment created successfully!');
    }
}
