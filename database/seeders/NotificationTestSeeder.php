<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Review;
use App\Models\Notification;
use App\Notifications\AppointmentBookedNotification;
use App\Notifications\DiagnosisSubmittedNotification;
use App\Notifications\ReviewSubmittedNotification;
use App\Notifications\VoiceTranscriptionCompletedNotification;
use App\Notifications\SystemAlertNotification;
use Carbon\Carbon;

class NotificationTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get test users
        $doctor = User::where('role', 'doctor')->first();
        $patient = User::where('role', 'patient')->first();

        if (!$doctor || !$patient) {
            $this->command->info('No doctor or patient found for testing notifications');
            return;
        }

        // Create test appointment
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDays(2),
            'status' => 'confirmed',
            'appointment_type' => 'video',
            'duration' => 30,
            'fee' => 5000,
            'reason' => 'General checkup',
        ]);

        // Create test diagnosis
        $diagnosis = Diagnosis::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'diagnosis_text' => 'Patient presents with symptoms of common cold. Recommended rest and hydration.',
            'patient_notified' => false,
        ]);

        // Create test review
        $review = Review::create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'rating' => 5,
            'comment' => 'Excellent service! Very professional and caring.',
            'is_approved' => true,
        ]);

        // Test different notification types
        $this->command->info('Creating test notifications...');

        // 1. Appointment booked notification
        $appointmentNotification = $doctor->notify(new AppointmentBookedNotification($appointment));
        $this->command->info('✓ Appointment booked notification created');

        // 2. Diagnosis submitted notification
        $diagnosisNotification = $patient->notify(new DiagnosisSubmittedNotification($diagnosis));
        $this->command->info('✓ Diagnosis submitted notification created');

        // 3. Review submitted notification
        $reviewNotification = $doctor->notify(new ReviewSubmittedNotification($review));
        $this->command->info('✓ Review submitted notification created');

        // 4. Voice transcription completed notification
        $voiceNotification = $doctor->notify(new VoiceTranscriptionCompletedNotification([
            'patient_name' => $patient->name,
            'transcription_id' => uniqid(),
            'summary' => 'Patient discussed symptoms and treatment options.',
        ]));
        $this->command->info('✓ Voice transcription completed notification created');

        // 5. System alert notification
        $systemNotification = $doctor->notify(new SystemAlertNotification([
            'title' => 'System Maintenance',
            'message' => 'Scheduled maintenance will occur tonight from 2-4 AM.',
            'type' => 'warning',
        ]));
        $this->command->info('✓ System alert notification created');

        // Create some additional notifications for testing pagination
        for ($i = 1; $i <= 15; $i++) {
            $patient->notifications()->create([
                'type' => 'info',
                'title' => 'Test Notification ' . $i,
                'message' => 'This is a test notification message #' . $i,
                'data' => [
                    'link' => '/dashboard',
                    'type' => 'test',
                ],
                'read_at' => $i % 3 === 0 ? now() : null, // Mark every 3rd as read
            ]);
        }

        $this->command->info('✓ Created 15 additional test notifications for pagination testing');

        // Test notification preferences
        $doctor->notificationPreferences()->create([
            'notification_type' => 'appointment',
            'email_enabled' => true,
            'sms_enabled' => false,
            'in_app_enabled' => true,
        ]);

        $doctor->notificationPreferences()->create([
            'notification_type' => 'diagnosis',
            'email_enabled' => true,
            'sms_enabled' => true,
            'in_app_enabled' => true,
        ]);

        $doctor->notificationPreferences()->create([
            'notification_type' => 'review',
            'email_enabled' => false,
            'sms_enabled' => false,
            'in_app_enabled' => true,
        ]);

        $patient->notificationPreferences()->create([
            'notification_type' => 'appointment',
            'email_enabled' => true,
            'sms_enabled' => true,
            'in_app_enabled' => true,
        ]);

        $patient->notificationPreferences()->create([
            'notification_type' => 'diagnosis',
            'email_enabled' => true,
            'sms_enabled' => false,
            'in_app_enabled' => true,
        ]);

        $this->command->info('✓ Created notification preferences for testing');

        $this->command->info('🎉 Test notifications created successfully!');
        $this->command->info('📊 Summary:');
        $this->command->info("   - Doctor notifications: " . $doctor->notifications()->count());
        $this->command->info("   - Patient notifications: " . $patient->notifications()->count());
        $this->command->info("   - Unread notifications: " . Notification::whereNull('read_at')->count());

        // Display test URLs
        $this->command->info('🔗 Test URLs:');
        $this->command->info("   - Notifications page: " . route('notifications.index'));
        $this->command->info("   - Notification settings: " . route('notification.settings'));
        $this->command->info("   - Doctor dashboard: " . route('dashboard'));
        $this->command->info("   - Patient dashboard: " . route('dashboard'));
    }
}
