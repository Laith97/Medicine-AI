<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;
use App\Mail\ManualReminderMail;
use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use App\Services\EmailService;

class TestAllEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-all {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all email templates and configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?: 'laythfares99@gmail.com';
        
        $this->info("Testing all email templates...");
        $this->info("Test emails will be sent to: $email");
        $this->newLine();

        $results = [];

        // Test 1: Basic email configuration
        $this->info("1. Testing basic email configuration...");
        try {
            $emailService = new EmailService();
            $configResults = $emailService->testEmailConfiguration();
            $results['basic_config'] = $configResults;
            $this->table(['Configuration', 'Status'], collect($configResults)->map(function ($status, $config) {
                return [$config, $status];
            })->toArray());
        } catch (\Exception $e) {
            $results['basic_config'] = 'ERROR: ' . $e->getMessage();
            $this->error("Basic config test failed: " . $e->getMessage());
        }

        $this->newLine();

        // Test 2: Contact form email
        $this->info("2. Testing contact form email...");
        try {
            $contactData = [
                'name' => 'Test User',
                'email' => $email,
                'phone' => '+1234567890',
                'service' => 'Email Testing',
                'subject' => 'Email System Test',
                'message' => 'This is a test message to verify the contact form email template is working correctly.'
            ];

            Mail::to($email)->send(new ContactFormMail($contactData));
            $results['contact_form'] = 'SUCCESS';
            $this->info("✓ Contact form email sent successfully");
        } catch (\Exception $e) {
            $results['contact_form'] = 'ERROR: ' . $e->getMessage();
            $this->error("✗ Contact form email failed: " . $e->getMessage());
        }

        $this->newLine();

        // Test 3: Reminder emails
        $this->info("3. Testing reminder emails...");
        
        // Get a test user or create a mock one
        $testUser = User::first();
        if (!$testUser) {
            $this->warn("No users found in database. Creating mock user data for testing...");
            $testUser = new User([
                'id' => 999,
                'name' => 'Test User',
                'email' => $email,
            ]);
        }

        // Create mock setting
        $mockSetting = new MonthlyInvoiceSetting([
            'user_id' => $testUser->id,
            'billing_amount' => 99.99,
            'monthly_price' => 99.99,
            'yearly_price' => 999.99,
            'subscription_period_months' => 1,
            'subscription_starts_at' => now()->subDays(35),
            'subscription_ends_at' => now()->subDays(5),
            'grace_period_days' => 7,
            'warning_period_days' => 3,
            'reminder_frequency_days' => 3,
            'is_restricted' => false,
            'is_active' => true,
        ]);

        $reminderTypes = ['grace_period', 'warning_period', 'overdue'];
        
        foreach ($reminderTypes as $type) {
            try {
                Mail::to($email)->send(new ManualReminderMail($testUser, $mockSetting, $type));
                $results["reminder_$type"] = 'SUCCESS';
                $this->info("✓ $type reminder email sent successfully");
            } catch (\Exception $e) {
                $results["reminder_$type"] = 'ERROR: ' . $e->getMessage();
                $this->error("✗ $type reminder email failed: " . $e->getMessage());
            }
        }

        $this->newLine();

        // Test 4: Email deliverability check
        $this->info("4. Email deliverability recommendations...");
        $this->checkEmailDeliverability();

        $this->newLine();

        // Summary
        $this->info("=== TEST SUMMARY ===");
        $successCount = 0;
        $totalTests = 0;

        foreach ($results as $test => $result) {
            $totalTests++;
            if (is_array($result)) {
                foreach ($result as $subTest => $subResult) {
                    $totalTests++;
                    if (strpos($subResult, 'SUCCESS') !== false) {
                        $successCount++;
                    }
                }
            } else {
                if (strpos($result, 'SUCCESS') !== false) {
                    $successCount++;
                }
            }
        }

        $this->info("Tests passed: $successCount/$totalTests");
        
        if ($successCount === $totalTests) {
            $this->info("🎉 All email tests passed! Your email system is working correctly.");
        } else {
            $this->warn("⚠️  Some email tests failed. Check the logs and configuration.");
        }

        $this->newLine();
        $this->info("Check your email inbox: $email");
        $this->info("Check logs: storage/logs/laravel.log");
    }

    private function checkEmailDeliverability()
    {
        $this->info("Email deliverability recommendations:");
        
        // Check SPF record
        $domain = 'medcuraai.com';
        $this->line("• SPF Record: Add 'v=spf1 include:_spf.hostinger.com ~all' to DNS");
        
        // Check DKIM
        $this->line("• DKIM: Enable DKIM signing in Hostinger control panel");
        
        // Check DMARC
        $this->line("• DMARC: Add 'v=DMARC1; p=quarantine; rua=mailto:dmarc@medcuraai.com' to DNS");
        
        // Check reverse DNS
        $this->line("• Reverse DNS: Ensure PTR record points to medcuraai.com");
        
        // Check content
        $this->line("• Content: Avoid spam trigger words, maintain good text-to-image ratio");
        
        // Check reputation
        $this->line("• Reputation: Monitor sender reputation and bounce rates");
        
        $this->newLine();
        $this->info("Current mail configuration:");
        $this->line("Host: " . config('mail.mailers.smtp.host'));
        $this->line("Port: " . config('mail.mailers.smtp.port'));
        $this->line("Encryption: " . config('mail.mailers.smtp.encryption'));
        $this->line("From: " . config('mail.from.address'));
    }
}