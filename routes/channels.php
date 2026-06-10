<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
    try {
        \Log::info('Broadcasting auth attempt', [
            'channel' => 'App.User.' . $id,
            'user_id' => $user ? $user->id : null,
            'requested_id' => $id,
            'user_authenticated' => $user ? true : false
        ]);
        
        // User can only listen to their own channel
        if (!$user) {
            \Log::warning('Broadcasting auth failed: No user');
            return false;
        }
        
        $result = (int) $user->id === (int) $id;
        \Log::info('Broadcasting auth result: ' . ($result ? 'ALLOWED' : 'DENIED'));
        
        return $result;
    } catch (\Exception $e) {
        Log::error('Broadcasting auth error for App.User.' . $id, [
            'error' => $e->getMessage(),
            'user_id' => $user ? $user->id : null,
            'requested_id' => $id
        ]);
        return false;
    }
});

// Alternative user channel naming (used by notification catcher)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    try {
        if (!$user) {
            return false;
        }
        return (int) $user->id === (int) $id;
    } catch (\Exception $e) {
        Log::error('Broadcasting auth error for App.Models.User.' . $id, [
            'error' => $e->getMessage(),
            'user_id' => $user ? $user->id : null,
            'requested_id' => $id
        ]);
        return false;
    }
});

// General user channel (alternative naming)
Broadcast::channel('user.{id}', function ($user, $id) {
    try {
        if (!$user) {
            return false;
        }
        return (int) $user->id === (int) $id;
    } catch (\Exception $e) {
        Log::error('Broadcasting auth error for user.' . $id, [
            'error' => $e->getMessage(),
            'user_id' => $user ? $user->id : null,
            'requested_id' => $id
        ]);
        return false;
    }
});

// Private user channel (alternative naming)
Broadcast::channel('private-user.{id}', function ($user, $id) {
    try {
        if (!$user) {
            return false;
        }
        return (int) $user->id === (int) $id;
    } catch (\Exception $e) {
        Log::error('Broadcasting auth error for private-user.' . $id, [
            'error' => $e->getMessage(),
            'user_id' => $user ? $user->id : null,
            'requested_id' => $id
        ]);
        return false;
    }
});

// Doctor-specific channels
Broadcast::channel('doctor.{doctorId}', function ($user, $doctorId) {
    try {
        // Check if user is a doctor
        if (!$user || $user->role !== 'doctor') {
            return false;
        }

        // Load doctor relationship if not already loaded
        if (!$user->relationLoaded('doctor')) {
            $user->load('doctor');
        }

        // The doctorId from JS is the doctor's user_id or doctor->id
        // We need to check if it matches either the user's doctor.id or user.id
        $userDoctorId = $user->doctor ? (int) $user->doctor->id : null;
        $userId = (int) $user->id;

        // Accept if doctorId matches either the doctor's id (doctors table) or user's id
        return ($userDoctorId && (int) $doctorId === $userDoctorId) || ((int) $doctorId === $userId);
    } catch (\Exception $e) {
        Log::error('Broadcasting auth error for doctor.' . $doctorId, [
            'error' => $e->getMessage(),
            'user_id' => $user ? $user->id : null,
            'user_role' => $user ? $user->role : null
        ]);
        return false;
    }
});

// Admin channels
Broadcast::channel('admin', function ($user) {
    return $user->role === 'admin';
});

// Clinic staff channels (admin, hospital_admin, manager, supervisor)
Broadcast::channel('clinic-staff', function ($user) {
    return in_array($user->role, ['admin', 'hospital_admin', 'manager', 'supervisor']);
});

// Today's appointments channel for real-time updates
Broadcast::channel('appointments.today', function ($user) {
    // Allow doctors, clinic staff, and patients to subscribe to today's appointments
    return in_array($user->role, ['doctor', 'admin', 'hospital_admin', 'manager', 'supervisor']) ||
           $user->role === 'patient';
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

// Clinical Alerts channel
Broadcast::channel('clinical-alerts', function ($user) {
    return in_array($user->role, ['doctor', 'admin', 'hospital_admin', 'nurse', 'specialist']);
});
