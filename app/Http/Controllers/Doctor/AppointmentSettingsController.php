<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentSettingsController extends Controller
{
    public function __construct()
    {
        // Middleware is handled at route level
    }

    /**
     * Display appointment settings page
     */
    public function index()
    {
        $doctor = Auth::user()->doctor;
        $appointmentTypes = [
            'in_person' => 'In-Person Consultation',
            'video_call' => 'Video Call',
            'phone_call' => 'Phone Call'
        ];

        return view('doctor.settings.appointments', compact('doctor', 'appointmentTypes'));
    }

    /**
     * Update appointment type preferences
     */
    public function updateAppointmentTypes(Request $request)
    {
        $doctor = Auth::user()->doctor;

        $request->validate([
            'appointment_types' => 'nullable|array',
            'appointment_types.*' => 'in:in_person,video_call,phone_call'
        ]);

        // Get enabled types from request
        $enabledTypes = $request->input('appointment_types', []);

        // Create preferences array
        $preferences = [
            'in_person' => in_array('in_person', $enabledTypes),
            'video_call' => in_array('video_call', $enabledTypes),
            'phone_call' => in_array('phone_call', $enabledTypes)
        ];

        // Ensure at least one appointment type is enabled
        if (!array_filter($preferences)) {
            return back()->withErrors(['appointment_types' => 'At least one appointment type must be enabled.']);
        }

        $doctor->updateAppointmentTypePreferences($preferences);

        return back()->with('success', 'Appointment type preferences updated successfully!');
    }
}
