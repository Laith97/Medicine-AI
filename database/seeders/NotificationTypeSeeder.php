<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define notification types with their default settings
        $notificationTypes = [
            // Appointment notifications
            [
                'type' => 'appointment_booked',
                'name' => 'New Appointment Booked',
                'description' => 'Receive notifications when a new appointment is booked',
                'default_enabled' => true,
                'default_channels' => ['database', 'email'],
                'icon' => 'calendar-plus',
                'color' => 'primary',
                'category' => 'appointments',
            ],
            [
                'type' => 'appointment_reminder',
                'name' => 'Appointment Reminder',
                'description' => 'Receive reminders about upcoming appointments',
                'default_enabled' => true,
                'default_channels' => ['database', 'email', 'sms'],
                'icon' => 'bell',
                'color' => 'warning',
                'category' => 'appointments',
            ],
            [
                'type' => 'appointment_cancelled',
                'name' => 'Appointment Cancelled',
                'description' => 'Receive notifications when appointments are cancelled',
                'default_enabled' => true,
                'default_channels' => ['database', 'email'],
                'icon' => 'calendar-x',
                'color' => 'danger',
                'category' => 'appointments',
            ],
            [
                'type' => 'appointment_rescheduled',
                'name' => 'Appointment Rescheduled',
                'description' => 'Receive notifications when appointments are rescheduled',
                'default_enabled' => true,
                'default_channels' => ['database', 'email'],
                'icon' => 'calendar-arrows',
                'color' => 'info',
                'category' => 'appointments',
            ],

            // Diagnosis notifications
            [
                'type' => 'diagnosis_submitted',
                'name' => 'Diagnosis Submitted',
                'description' => 'Receive notifications when a diagnosis is submitted',
                'default_enabled' => true,
                'default_channels' => ['database', 'email'],
                'icon' => 'file-medical',
                'color' => 'success',
                'category' => 'diagnosis',
            ],
            [
                'type' => 'diagnosis_updated',
                'name' => 'Diagnosis Updated',
                'description' => 'Receive notifications when a diagnosis is updated',
                'default_enabled' => true,
                'default_channels' => ['database'],
                'icon' => 'file-medical-alt',
                'color' => 'info',
                'category' => 'diagnosis',
            ],

            // Review notifications
            [
                'type' => 'review_submitted',
                'name' => 'New Review Submitted',
                'description' => 'Receive notifications when patients submit reviews',
                'default_enabled' => true,
                'default_channels' => ['database', 'email'],
                'icon' => 'star',
                'color' => 'warning',
                'category' => 'reviews',
            ],
            [
                'type' => 'review_approved',
                'name' => 'Review Approved',
                'description' => 'Receive notifications when reviews are approved',
                'default_enabled' => true,
                'default_channels' => ['database'],
                'icon' => 'check-circle',
                'color' => 'success',
                'category' => 'reviews',
            ],

            // Voice transcription notifications
            [
                'type' => 'voice_transcription_completed',
                'name' => 'Voice Transcription Completed',
                'description' => 'Receive notifications when voice transcriptions are completed',
                'default_enabled' => true,
                'default_channels' => ['database', 'email'],
                'icon' => 'microphone',
                'color' => 'info',
                'category' => 'voice_assistant',
            ],

            // System notifications
            [
                'type' => 'system_alert',
                'name' => 'System Alerts',
                'description' => 'Receive important system alerts and notifications',
                'default_enabled' => true,
                'default_channels' => ['database'],
                'icon' => 'exclamation-triangle',
                'color' => 'danger',
                'category' => 'system',
            ],
            [
                'type' => 'payment_due',
                'name' => 'Payment Due',
                'description' => 'Receive notifications about upcoming or overdue payments',
                'default_enabled' => true,
                'default_channels' => ['database', 'email', 'sms'],
                'icon' => 'credit-card',
                'color' => 'warning',
                'category' => 'billing',
            ],
            [
                'type' => 'payment_failed',
                'name' => 'Payment Failed',
                'description' => 'Receive notifications when payment attempts fail',
                'default_enabled' => true,
                'default_channels' => ['database', 'email', 'sms'],
                'icon' => 'times-circle',
                'color' => 'danger',
                'category' => 'billing',
            ],

            // Account notifications
            [
                'type' => 'account_created',
                'name' => 'Account Created',
                'description' => 'Receive notifications when new accounts are created',
                'default_enabled' => true,
                'default_channels' => ['database', 'email'],
                'icon' => 'user-plus',
                'color' => 'success',
                'category' => 'account',
            ],
            [
                'type' => 'password_changed',
                'name' => 'Password Changed',
                'description' => 'Receive notifications when passwords are changed',
                'default_enabled' => true,
                'default_channels' => ['database', 'email'],
                'icon' => 'key',
                'color' => 'info',
                'category' => 'account',
            ],

            // AI assistant notifications
            [
                'type' => 'ai_response_ready',
                'name' => 'AI Response Ready',
                'description' => 'Receive notifications when AI responses are ready',
                'default_enabled' => true,
                'default_channels' => ['database', 'email'],
                'icon' => 'robot',
                'color' => 'primary',
                'category' => 'ai_assistant',
            ],
            [
                'type' => 'follow_up_question',
                'name' => 'Follow-up Question',
                'description' => 'Receive notifications when patients ask follow-up questions',
                'default_enabled' => true,
                'default_channels' => ['database', 'email'],
                'icon' => 'question-circle',
                'color' => 'info',
                'category' => 'ai_assistant',
            ],
        ];

        // Insert notification types
        foreach ($notificationTypes as $type) {
            DB::table('notification_types')->updateOrInsert(
                ['type' => $type['type']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'default_enabled' => $type['default_enabled'],
                    'default_channels' => json_encode($type['default_channels']),
                    'icon' => $type['icon'],
                    'color' => $type['color'],
                    'category' => $type['category'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Set default notification preferences for existing users
        $this->setDefaultNotificationPreferences();

        $this->command->info('Notification types seeded successfully!');
    }

    /**
     * Set default notification preferences for existing users
     */
    private function setDefaultNotificationPreferences()
    {
        $users = User::all();
        $notificationTypes = DB::table('notification_types')->pluck('type', 'id');

        foreach ($users as $user) {
            foreach ($notificationTypes as $id => $type) {
                // Get the default settings for this notification type
                $typeSettings = DB::table('notification_types')->where('type', $type)->first();

                // Set default preferences
                DB::table('user_notification_preferences')->updateOrInsert(
                    ['user_id' => $user->id, 'notification_type_id' => $id],
                    [
                        'enabled' => $typeSettings->default_enabled,
                        'channels' => $typeSettings->default_channels,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('Default notification preferences set for ' . $users->count() . ' users!');
    }
}
