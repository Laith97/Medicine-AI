<?php

namespace App\Providers;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['web']]);

        /*
         * Authenticate the user's channel socket connection.
         *
         * @param \Illuminate\Http\Request $request
         * @return \Illuminate\Http\Response|null
         */
        Broadcast::channel('App.User.{id}', function ($user, $id) {
            return (int) $user->id === (int) $id;
        });

        // Additional channel authorizations can be added here
        Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
            return (int) $user->id === (int) $id;
        });

        // Private user channel for notifications (matching the JavaScript subscription)
        Broadcast::channel('private-user.{id}', function ($user, $id) {
            return (int) $user->id === (int) $id;
        });

        // Debug channel for testing
        Broadcast::channel('debug.{id}', function ($user, $id) {
            return true; // Allow all connections for debugging
        });

        // Appointment-related channels
        Broadcast::channel('doctor.{doctorId}', function ($user, $doctorId) {
            return $user->role === 'doctor' && $user->doctor && $user->doctor->id == $doctorId;
        });

        Broadcast::channel('patient.{patientId}', function ($user, $patientId) {
            return $user->id == $patientId;
        });

        Broadcast::channel('appointments.{date}', function ($user, $date) {
            // Allow authenticated users to subscribe to appointment date channels
            return auth()->check();
        });

        Broadcast::channel('admin.appointments', function ($user) {
            return in_array($user->role, ['admin', 'hospital_admin']);
        });

        // Synchronization channels
        Broadcast::channel('sync.{userId}', function ($user, $userId) {
            return $user->id == $userId;
        });

        // Real-time dashboard channels
        Broadcast::channel('dashboard.{userId}.{dashboardId}', function ($user, $userId, $dashboardId) {
            return $user->id == $userId;
        });

        Broadcast::channel('dashboard.{dashboardId}', function ($user, $dashboardId) {
            return in_array($user->role, ['admin', 'hospital_admin', 'manager']);
        });

        // Notification channels
        Broadcast::channel('notifications.{userId}', function ($user, $userId) {
            return $user->id == $userId;
        });

        Broadcast::channel('alerts.{userId}', function ($user, $userId) {
            return $user->id == $userId;
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
