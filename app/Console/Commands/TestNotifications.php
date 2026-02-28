<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Appointment;
use App\Notifications\AppointmentBookedNotification;

class TestNotifications extends Command
{
    protected $signature = 'test:notifications';
    protected $description = 'Test the notification system';

    public function handle()
    {
        $this->info('Testing notification system...');

        // Find a doctor user
        $doctor = User::where('role', 'doctor')->first();
        if (!$doctor) {
            $this->error('No doctor found in the system');
            return;
        }

        $this->info("Found doctor: {$doctor->name} (ID: {$doctor->id})");

        // Check notification preferences
        $preferences = $doctor->getOrCreateNotificationPreferences();
        $this->info("Appointment notifications enabled: " . ($preferences->appointment_booked ? 'Yes' : 'No'));
        $this->info("Database notifications enabled: " . ($preferences->in_app_enabled ? 'Yes' : 'No'));

        // Find a recent appointment
        $appointment = Appointment::with(['doctor.user', 'patient'])
            ->whereHas('doctor', function($query) use ($doctor) {
                $query->where('user_id', $doctor->id);
            })
            ->latest()
            ->first();

        if (!$appointment) {
            $this->error('No appointments found for this doctor');
            return;
        }

        $this->info("Found appointment: ID {$appointment->id}");

        // Test notification
        try {
            $this->info('Sending test notification...');
            $doctor->notifyIfWants(new AppointmentBookedNotification($appointment), 'appointment_booked');
            $this->info('Notification sent successfully!');

            // Check if notification was created
            $notificationCount = $doctor->unreadNotifications()->count();
            $this->info("Unread notifications count: {$notificationCount}");

            if ($notificationCount > 0) {
                $latestNotification = $doctor->unreadNotifications()->latest()->first();
                $this->info("Latest notification type: {$latestNotification->type}");
                $this->info("Latest notification data: " . json_encode($latestNotification->data));
            }

        } catch (\Exception $e) {
            $this->error("Error sending notification: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
