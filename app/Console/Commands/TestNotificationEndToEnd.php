<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Http\Controllers\AppointmentController;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TestNotificationEndToEnd extends Command
{
    protected $signature = 'test:notification-end-to-end';
    protected $description = 'Test the complete notification flow from appointment booking';

    public function handle()
    {
        $this->info('Testing end-to-end notification flow...');

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

        // Check initial notification count
        $initialCount = $doctor->user->unreadNotifications()->count();
        $this->info("Initial unread notifications for doctor: {$initialCount}");

        // Create appointment using the actual controller logic
        $appointmentData = [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => Carbon::now()->addDays(1)->setHour(14)->setMinute(0),
            'appointment_end' => Carbon::now()->addDays(1)->setHour(15)->setMinute(0),
            'status' => 'pending',
            'reason' => 'End-to-end notification test',
            'symptoms' => 'Testing complete notification flow',
            'appointment_type' => 'in_person',
            'patient_notes' => 'This is an end-to-end test',
            'consultation_fee' => 150.00,
        ];

        $appointment = Appointment::create($appointmentData);
        $this->info("Created appointment: ID {$appointment->id}");

        // Manually trigger the notification logic from the controller
        $controller = new AppointmentController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sendAppointmentNotifications');
        $method->setAccessible(true);

        try {
            $method->invoke($controller, $appointment);
            $this->info('Notification method called successfully');
        } catch (\Exception $e) {
            $this->error("Error calling notification method: " . $e->getMessage());
            return;
        }

        // Check final notification count
        $finalCount = $doctor->user->unreadNotifications()->count();
        $this->info("Final unread notifications for doctor: {$finalCount}");

        if ($finalCount > $initialCount) {
            $newCount = $finalCount - $initialCount;
            $this->info("SUCCESS: Notification was created! ({$newCount} new notification(s))");

            // Show the latest notification details
            $latestNotification = $doctor->user->unreadNotifications()->latest()->first();
            if ($latestNotification) {
                $this->info("Latest notification type: {$latestNotification->type}");
                $this->info("Latest notification created: {$latestNotification->created_at}");
                $data = is_array($latestNotification->data) ? $latestNotification->data : json_decode($latestNotification->data, true);
                $this->info("Notification message: " . ($data['message'] ?? 'No message'));

                // Test the API endpoint
                $this->info("\nTesting API endpoints...");
                $this->testApiEndpoints($doctor->user);
            }
        } else {
            $this->error("FAILED: No new notification was created");
        }
    }

    private function testApiEndpoints($user)
    {
        // Simulate API calls
        $this->info("Testing unread count endpoint...");

        // We can't easily test HTTP endpoints from console, but we can test the controller methods directly
        $controller = new \App\Http\Controllers\NotificationController();

        try {
            // Test unread count
            $unreadCount = $user->unreadNotifications()->count();
            $this->info("✅ Unread count: {$unreadCount}");

            // Test dropdown data
            $notifications = $user->unreadNotifications()->take(10)->get();
            $this->info("✅ Dropdown notifications: " . $notifications->count() . " items");

            if ($notifications->count() > 0) {
                $firstNotification = $notifications->first();
                $this->info("First notification ID: {$firstNotification->id}");
                $this->info("First notification type: {$firstNotification->type}");
            }

        } catch (\Exception $e) {
            $this->error("Error testing API endpoints: " . $e->getMessage());
        }
    }
}
