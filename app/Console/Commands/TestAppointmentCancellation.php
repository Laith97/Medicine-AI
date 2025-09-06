<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Http\Controllers\AppointmentController;
use Carbon\Carbon;

class TestAppointmentCancellation extends Command
{
    protected $signature = 'test:appointment-cancellation';
    protected $description = 'Test appointment cancellation notification system';

    public function handle()
    {
        $this->info('Testing appointment cancellation notification system...');

        // Find an existing appointment or create one
        $appointment = Appointment::where('status', 'pending')->first();

        if (!$appointment) {
            // Create a test appointment first
            $doctor = Doctor::with('user')->first();
            $patient = User::where('role', 'patient')->first();

            if (!$doctor || !$patient) {
                $this->error('No doctor or patient found in the system');
                return;
            }

            $appointment = Appointment::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_date' => Carbon::now()->addDays(1)->setHour(10)->setMinute(0),
                'appointment_end' => Carbon::now()->addDays(1)->setHour(11)->setMinute(0),
                'status' => 'pending',
                'reason' => 'Test appointment for cancellation',
                'symptoms' => 'Testing cancellation notifications',
                'appointment_type' => 'in_person',
                'patient_notes' => 'This is a test appointment for cancellation',
                'consultation_fee' => 100.00,
            ]);

            $this->info("Created test appointment: ID {$appointment->id}");
        }

        $this->info("Testing cancellation for appointment: ID {$appointment->id}");
        $this->info("Doctor: {$appointment->doctor->user->name}");
        $this->info("Patient: {$appointment->patient->name}");

        // Check initial notification counts
        $doctorInitialCount = $appointment->doctor->user->unreadNotifications()->count();
        $patientInitialCount = $appointment->patient->unreadNotifications()->count();

        $this->info("Doctor initial notifications: {$doctorInitialCount}");
        $this->info("Patient initial notifications: {$patientInitialCount}");

        // Test the cancellation notification system
        $controller = new AppointmentController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sendAppointmentCancellationNotifications');
        $method->setAccessible(true);

        try {
            $method->invoke($controller, $appointment, 'Testing notification system');
            $this->info('Cancellation notification method called successfully');
        } catch (\Exception $e) {
            $this->error("Error calling cancellation notification method: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . " Line: " . $e->getLine());
            return;
        }

        // Check final notification counts
        $doctorFinalCount = $appointment->doctor->user->unreadNotifications()->count();
        $patientFinalCount = $appointment->patient->unreadNotifications()->count();

        $this->info("Doctor final notifications: {$doctorFinalCount}");
        $this->info("Patient final notifications: {$patientFinalCount}");

        // Check if notifications were created
        $doctorNewCount = $doctorFinalCount - $doctorInitialCount;
        $patientNewCount = $patientFinalCount - $patientInitialCount;

        if ($doctorNewCount > 0) {
            $this->info("✅ SUCCESS: Doctor received {$doctorNewCount} new notification(s)");

            $latestNotification = $appointment->doctor->user->unreadNotifications()->latest()->first();
            if ($latestNotification) {
                $data = is_array($latestNotification->data) ? $latestNotification->data : json_decode($latestNotification->data, true);
                $this->info("Doctor notification message: " . ($data['message'] ?? 'No message'));
            }
        } else {
            $this->warn("Doctor did not receive any new notifications");
        }

        if ($patientNewCount > 0) {
            $this->info("✅ SUCCESS: Patient received {$patientNewCount} new notification(s)");

            $latestNotification = $appointment->patient->unreadNotifications()->latest()->first();
            if ($latestNotification) {
                $data = is_array($latestNotification->data) ? $latestNotification->data : json_decode($latestNotification->data, true);
                $this->info("Patient notification message: " . ($data['message'] ?? 'No message'));
            }
        } else {
            $this->warn("Patient did not receive any new notifications");
        }

        // Now actually cancel the appointment to test the full flow
        $appointment->update(['status' => 'cancelled']);
        $this->info("Appointment status updated to cancelled");
    }
}
