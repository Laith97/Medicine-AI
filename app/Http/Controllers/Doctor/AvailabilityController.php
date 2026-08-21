<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\HandlesEffectiveDoctor;

class AvailabilityController extends Controller
{
    use HandlesEffectiveDoctor;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            // Handle sub-users - they inherit access from their parent doctor
            if ($user->isSubUser()) {
                $parentUser = $user->parentUser;
                if (!$parentUser || !$parentUser->isDoctor() || !$parentUser->doctor || !$parentUser->doctor->is_active) {
                    abort(403, 'Access denied. Parent doctor profile required.');
                }
            } else {
                // Handle main users (doctors)
                if (!$user->isDoctor() || !$user->doctor) {
                    abort(403, 'Access denied. Doctor profile required.');
                }
                
                if (!$user->doctor->is_active) {
                    abort(403, 'Access denied. Your doctor account has been deactivated.');
                }
            }
            
            return $next($request);
        });
    }

    /**
     * Display doctor's availability slots
     */
    public function index()
    {
        $doctor = Auth::user()->getEffectiveDoctor();

        $availabilitySlots = $doctor->availabilitySlots()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $daysOfWeek = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday'
        ];

        return view('doctor.availability.index', compact('availabilitySlots', 'daysOfWeek'));
    }

    /**
     * Show the form for creating a new availability slot
     */
    public function create()
    {
        $daysOfWeek = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday'
        ];

        return view('doctor.availability.create', compact('daysOfWeek'));
    }

    /**
     * Store a newly created availability slot
     */
    public function store(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:15|max:120',
            'max_bookings_per_slot' => 'required|integer|min:1|max:10',
            'effective_from' => 'nullable|date|after_or_equal:today',
            'effective_until' => 'nullable|date|after:effective_from',
        ]);

        // Check for overlapping slots
        $overlapping = $doctor->availabilitySlots()
            ->where('day_of_week', $request->day_of_week)
            ->where('is_active', true)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                      });
            })
            ->exists();

        if ($overlapping) {
            return back()->withErrors(['error' => 'This time slot overlaps with an existing availability slot.']);
        }

        AvailabilitySlot::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'slot_duration' => $request->slot_duration,
            'max_bookings_per_slot' => $request->max_bookings_per_slot,
            'effective_from' => $request->effective_from,
            'effective_until' => $request->effective_until,
            'is_active' => true,
        ]);

        return redirect()->route('doctor.availability.index')
            ->with('success', 'Availability slot created successfully.');
    }

    /**
     * Show the form for editing the specified availability slot
     */
    public function edit(AvailabilitySlot $availability)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this slot belongs to the doctor
        if ($availability->doctor_id !== $doctor->id) {
            abort(403);
        }

        $daysOfWeek = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday'
        ];

        return view('doctor.availability.edit', compact('availability', 'daysOfWeek'));
    }

    /**
     * Update the specified availability slot
     */
    public function update(Request $request, AvailabilitySlot $availability)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this slot belongs to the doctor
        if ($availability->doctor_id !== $doctor->id) {
            abort(403);
        }

        // Convert start_time to H:i format if it has seconds
        $startTime = strlen($request->start_time) > 5 ? substr($request->start_time, 0, 5) : $request->start_time;

        $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required',
            'end_time' => 'required',
            'slot_duration' => 'required|integer|min:15|max:120',
            'max_bookings_per_slot' => 'required|integer|min:1|max:10',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
        ]);

        if (strtotime($request->end_time) <= strtotime($startTime)) {
            return back()->withInput()->withErrors(['end_time' => 'End time must be after start time.']);
        }

        // Check for overlapping slots (excluding current slot)
        $overlapping = $doctor->availabilitySlots()
            ->where('id', '!=', $availability->id)
            ->where('day_of_week', $request->day_of_week)
            ->where('is_active', true)
            ->where(function ($query) use ($startTime, $request) {
                $query->whereBetween('start_time', [$startTime, $request->end_time])
                      ->orWhereBetween('end_time', [$startTime, $request->end_time])
                      ->orWhere(function ($q) use ($startTime, $request) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $request->end_time);
                      });
            })
            ->exists();

        if ($overlapping) {
            return back()->withErrors(['error' => 'This time slot overlaps with an existing availability slot.']);
        }

        $availability->update([
            'day_of_week' => $request->day_of_week,
            'start_time' => $startTime,
            'end_time' => $request->end_time,
            'slot_duration' => $request->slot_duration,
            'max_bookings_per_slot' => $request->max_bookings_per_slot,
            'effective_from' => $request->effective_from,
            'effective_until' => $request->effective_until,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('doctor.availability.index')
            ->with('success', 'Availability slot updated successfully.');
    }

    /**
     * Remove the specified availability slot
     */
    public function destroy(AvailabilitySlot $availability)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this slot belongs to the doctor
        if ($availability->doctor_id !== $doctor->id) {
            abort(403);
        }

        // Check if any future appointments actually fall inside this slot's weekly window
        $hasBookedAppointments = Appointment::where('doctor_id', $availability->doctor_id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('appointment_date', '>', now())
            ->when($availability->effective_from, fn ($q) => $q->where('appointment_date', '>=', $availability->effective_from))
            ->when($availability->effective_until, fn ($q) => $q->where('appointment_date', '<=', $availability->effective_until->endOfDay()))
            ->get()
            ->contains(function (Appointment $appointment) use ($availability) {
                return strtolower($appointment->appointment_date->format('l')) === $availability->day_of_week
                    && $appointment->appointment_date->format('H:i:s') >= $availability->start_time
                    && $appointment->appointment_date->format('H:i:s') < $availability->end_time;
            });

        if ($hasBookedAppointments) {
            return back()->withErrors(['error' => 'Cannot delete availability slot with future appointments. Please cancel or reschedule appointments first.']);
        }

        $availability->delete();

        return redirect()->route('doctor.availability.index')
            ->with('success', 'Availability slot deleted successfully.');
    }

    /**
     * Toggle availability slot status
     */
    public function toggle(AvailabilitySlot $availability)
    {
        $doctor = $this->getEffectiveDoctor();

        // Check if this slot belongs to the doctor
        if ($availability->doctor_id !== $doctor->id) {
            abort(403);
        }

        $availability->update([
            'is_active' => !$availability->is_active
        ]);

        $status = $availability->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Availability slot {$status} successfully.");
    }

    /**
     * Bulk create availability slots for multiple days
     */
    public function bulkStore(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();

        $request->validate([
            'days' => 'required|array|min:1',
            'days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:15|max:120',
            'max_bookings_per_slot' => 'required|integer|min:1|max:10',
            'effective_from' => 'nullable|date|after_or_equal:today',
            'effective_until' => 'nullable|date|after:effective_from',
        ]);

        $created = 0;
        $errors = [];

        foreach ($request->days as $day) {
            // Check for overlapping slots
            $overlapping = $doctor->availabilitySlots()
                ->where('day_of_week', $day)
                ->where('is_active', true)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                          ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                          ->orWhere(function ($q) use ($request) {
                              $q->where('start_time', '<=', $request->start_time)
                                ->where('end_time', '>=', $request->end_time);
                          });
                })
                ->exists();

            if (!$overlapping) {
                AvailabilitySlot::create([
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $day,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'slot_duration' => $request->slot_duration,
                    'max_bookings_per_slot' => $request->max_bookings_per_slot,
                    'effective_from' => $request->effective_from,
                    'effective_until' => $request->effective_until,
                    'is_active' => true,
                ]);
                $created++;
            } else {
                $errors[] = ucfirst($day) . ' has overlapping time slots';
            }
        }

        $message = "Created {$created} availability slots successfully.";
        if (!empty($errors)) {
            $message .= ' Skipped: ' . implode(', ', $errors);
        }

        return redirect()->route('doctor.availability.index')
            ->with('success', $message);
    }
}
