<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isDoctor() || !Auth::user()->doctor) {
                abort(403, 'Access denied. Doctor profile required.');
            }
            return $next($request);
        });
    }

    /**
     * Display the doctor dashboard
     */
    public function index()
    {
        $doctor = Auth::user()->doctor;

        // Get today's appointments
        $todayAppointments = $doctor->appointments()
            ->with(['patient'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_date')
            ->get();

        // Get upcoming appointments (next 7 days)
        $upcomingAppointments = $doctor->appointments()
            ->with(['patient'])
            ->whereBetween('appointment_date', [now(), now()->addDays(7)])
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('appointment_date')
            ->limit(5)
            ->get();

        // Get pending appointments
        $pendingAppointments = $doctor->appointments()
            ->with(['patient'])
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

        // Calculate statistics
        $stats = $this->getDashboardStats($doctor);

        return view('doctor.dashboard', compact(
            'doctor',
            'todayAppointments',
            'upcomingAppointments',
            'pendingAppointments',
            'recentReviews',
            'stats'
        ));
    }

    /**
     * Display appointments calendar
     */
    public function appointments(Request $request)
    {
        $doctor = Auth::user()->doctor;

        $query = $doctor->appointments()->with(['patient']);

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

        $appointments = $query->orderBy('appointment_date', 'desc')->paginate(15);

        return view('doctor.appointments.index', compact('appointments'));
    }

    /**
     * Show appointment details
     */
    public function showAppointment(Appointment $appointment)
    {
        $doctor = Auth::user()->doctor;

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        $appointment->load(['patient', 'review']);

        return view('doctor.appointments.show', compact('appointment'));
    }

    /**
     * Confirm an appointment
     */
    public function confirmAppointment(Appointment $appointment)
    {
        $doctor = Auth::user()->doctor;

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
        $doctor = Auth::user()->doctor;

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

        // TODO: Send cancellation email to patient

        return back()->with('success', 'Appointment cancelled successfully.');
    }

    /**
     * Complete an appointment
     */
    public function completeAppointment(Request $request, Appointment $appointment)
    {
        $doctor = Auth::user()->doctor;

        // Check if this appointment belongs to the doctor
        if ($appointment->doctor_id !== $doctor->id) {
            abort(403);
        }

        if ($appointment->status !== 'confirmed') {
            return back()->withErrors(['error' => 'Only confirmed appointments can be completed.']);
        }

        $request->validate([
            'doctor_notes' => 'nullable|string|max:2000',
            'follow_up_required' => 'boolean'
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
        $doctor = Auth::user()->doctor;

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
     * Display reviews
     */
    public function reviews(Request $request)
    {
        $doctor = Auth::user()->doctor;

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

        return view('doctor.reviews.index', compact('reviews'));
    }

    /**
     * Get calendar events for appointments (AJAX)
     */
    public function getCalendarEvents(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $start = $request->start;
        $end = $request->end;

        $appointments = $doctor->appointments()
            ->with(['patient'])
            ->whereBetween('appointment_date', [$start, $end])
            ->get();

        $events = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => $appointment->patient->name,
                'start' => $appointment->appointment_date->toISOString(),
                'end' => $appointment->appointment_end->toISOString(),
                'color' => $this->getEventColor($appointment->status),
                'url' => route('doctor.appointments.show', $appointment),
                'extendedProps' => [
                    'status' => $appointment->status,
                    'patient' => $appointment->patient->name,
                    'reason' => $appointment->reason,
                    'type' => $appointment->appointment_type,
                    'phone' => $appointment->patient->phone,
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
}
