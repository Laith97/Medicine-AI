<?php

namespace App\Console\Commands;

use App\Services\SmsService;
use App\Models\User;
use Illuminate\Console\Command;

class TestSmsIntegration extends Command
{
    protected $signature = 'test:sms-integration {phone?} {--user-id=}';
    protected $description = 'Test SMS integration for reminders';

    public function handle()
    {
        $phone = $this->argument('phone');
        $userId = $this->option('user-id');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            $phone = $user->phone;
            $this->info("Using phone number from user {$user->name}: {$phone}");
        }

        if (!$phone) {
            $this->error('Please provide a phone number or user ID.');
            $this->line('Usage: php artisan test:sms-integration +1234567890');
            $this->line('   or: php artisan test:sms-integration --user-id=1');
            return 1;
        }

        $this->info('Testing SMS Integration...');
        $this->line('Phone: ' . $phone);

        try {
            $smsService = new SmsService();
            
            // Test basic SMS
            $this->info('1. Testing basic SMS service...');
            $result = $smsService->sendTestSms($phone);
            
            if ($result['success']) {
                $this->info('✅ Basic SMS sent successfully');
                $this->line('   Provider: ' . ($result['data']['provider'] ?? 'unknown'));
            } else {
                $this->error('❌ Basic SMS failed: ' . $result['message']);
            }

            // Test reminder-style messages
            $this->info('2. Testing reminder-style messages...');
            
            $messages = [
                'Grace Period' => "🔔 MedCura AI: Your subscription expired but you're in grace period. 5 days remaining. Renew now: https://app.com/subscription",
                'Warning' => "🚨 URGENT - MedCura AI: FINAL WARNING! Your account will be RESTRICTED in 2 days. Renew immediately: https://app.com/subscription",
                'Overdue' => "⚠️ MedCura AI: Your invoice of $29.99 is overdue. Update your payment method to avoid service interruption: https://app.com/subscription"
            ];

            foreach ($messages as $type => $message) {
                $this->line("   Testing {$type} message...");
                $result = $smsService->send($phone, $message);
                
                if ($result['success']) {
                    $this->info("   ✅ {$type} SMS sent successfully");
                } else {
                    $this->error("   ❌ {$type} SMS failed: " . $result['message']);
                }
                
                // Small delay between messages
                sleep(1);
            }

            // Test with actual user notification if user provided
            if ($userId && $user) {
                $this->info('3. Testing Laravel Notification integration...');
                
                // Check if user has monthly invoice setting
                if ($user->monthlyInvoiceSetting) {
                    try {
                        $user->notify(new \App\Notifications\GracePeriodReminder($user->monthlyInvoiceSetting));
                        $this->info('✅ Laravel Notification sent successfully');
                    } catch (\Exception $e) {
                        $this->error('❌ Laravel Notification failed: ' . $e->getMessage());
                    }
                } else {
                    $this->warn('⚠️  User has no monthly invoice setting - skipping notification test');
                }
            }

            $this->info('SMS Integration test completed!');
            $this->line('Check the logs for detailed delivery information.');

        } catch (\Exception $e) {
            $this->error('Test failed with exception: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}