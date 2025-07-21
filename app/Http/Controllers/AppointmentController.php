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
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date|after:now',
            'reason' => 'required|string|max:500',
            'symptoms' => 'nullable|string|max:1000',
            'appointment_type' => 'required|in:in_person,video_call,phone_call',
            'patient_notes' => 'nullable|string|max:1000',
        ]);

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
            $appointment = Appointment::create([
                'doctor_id' => $doctor->id,
                'patient_id' => Auth::id(),
                'appointment_date' => $appointmentDate,
                'appointment_end' => $appointmentDate->copy()->addMinutes($doctor->appointment_duration),
                'status' => $doctor->auto_approve_appointments ? 'confirmed' : 'pending',
                'reason' => $request->reason,
                'symptoms' => $request->symptoms,
                'appointment_type' => $request->appointment_type,
                'patient_notes' => $request->patient_notes,
                'consultation_fee' => $doctor->consultation_fee,
            ]);

            if ($doctor->auto_approve_appointments) {
                $appointment->confirm();
            }

            DB::commit();

            // TODO: Send email notification to doctor and patient
            // TODO: Add to calendar

            return redirect()->route('appointments.show', $appointment)
                ->with('success', 'Appointment booked successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to book appointment. Please try again.']);
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

        // TODO: Send cancellation email to doctor

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

            // TODO: Send rescheduling email to doctor

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
}
