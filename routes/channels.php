<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Private user channel - allows users to listen to their own notifications
Broadcast::channel('App.User.{id}', function ($user, $id) {
    // User can only listen to their own channel
    return (int) $user->id === (int) $id;
});

// General user channel (alternative naming)
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Doctor-specific channels
Broadcast::channel('doctor.{doctorId}', function ($user, $doctorId) {
    // Check if user is a doctor and matches the doctor ID
    return $user->role === 'doctor' && (int) $user->doctor->id === (int) $doctorId;
});

// Admin channels
Broadcast::channel('admin', function ($user) {
    return $user->role === 'admin';
});

// Appointment channels
Broadcast::channel('appointment.{appointmentId}', function ($user, $appointmentId) {
    // Allow both patient and doctor to listen to appointment updates
    $appointment = \App\Models\Appointment::find($appointmentId);

    if (!$appointment) {
        return false;
    }

    // Patient can listen to their appointments
    if ($user->id === $appointment->patient_id) {
        return true;
    }

    // Doctor can listen to appointments with their patients
    if ($user->role === 'doctor' && $user->doctor && $user->doctor->id === $appointment->doctor_id) {
        return true;
    }

    return false;
});
