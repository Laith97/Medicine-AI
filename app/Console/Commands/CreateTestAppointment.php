<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use Carbon\Carbon;

class CreateTestAppointment extends Command
{
    protected $signature = 'test:create-appointment';
    protected $description = 'Create a test appointment to test notifications';

    public function handle()
    {
        $this->info('Creating test appointment...');

        // Find a doctor
        $doctor = Doctor::with('user')->first();
        if (!$doctor) {
            $this->error('No doctor found in the system');
            return;
        }

        // Find a patient
        $patient = User::where('role', 'patient')->first();
        if (!$patient) {
            $this->error('No patient found in the system');
            return;
        }

        $this->info("Doctor: {$doctor->user->name} (ID: {$doctor->id})");
        $this->info("Patient: {$patient->name} (ID: {$patient->id})");

        // Create appointment
        $appointment = Appointment::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => Carbon::now()->addDays(1)->setHour(10)->setMinute(0),
            'appointment_end' => Carbon::now()->addDays(1)->setHour(11)->setMinute(0),
            'status' => 'pending',
            'reason' => 'Test appointment for notification system',
            'symptoms' => 'Testing notifications',
            'appointment_type' => 'in_person',
            'patient_notes' => 'This is a test appointment',
            'consultation_fee' => 100.00,
        ]);

        $this->info("Created appointment: ID {$appointment->id}");

        // Test the notification system
        $this->info('Testing notification system...');

        try {
            // Check doctor's notification preferences
            $doctorUser = $doctor->user;
            $preferences = $doctorUser->getOrCreateNotificationPreferences();
            $this->info("Doctor appointment notifications enabled: " . ($preferences->appointment_booked ? 'Yes' : 'No'));

            if ($doctorUser->wantsNotification('appointment_booked')) {
                $this->info('Doctor wants appointment notifications. Sending...');
                $doctorUser->notifyIfWants(new \App\Notifications\AppointmentBookedNotification($appointment), 'appointment_booked');
                $this->info('Notification sent to doctor!');
            } else {
                $this->warn('Doctor does not want appointment notifications');
            }

            // Check notifications count
            $unreadCount = $doctorUser->unreadNotifications()->count();
            $this->info("Doctor's unread notifications count: {$unreadCount}");

            if ($unreadCount > 0) {
                $latestNotification = $doctorUser->unreadNotifications()->latest()->first();
                $this->info("Latest notification type: {$latestNotification->type}");
                $this->info("Latest notification created: {$latestNotification->created_at}");
                $data = is_array($latestNotification->data) ? $latestNotification->data : json_decode($latestNotification->data, true);
                $this->info("Notification message: " . ($data['message'] ?? 'No message'));
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . " Line: " . $e->getLine());
        }
    }
}
