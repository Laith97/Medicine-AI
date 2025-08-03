<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailService;
use Illuminate\Support\Facades\Mail;

class EmailHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:health-check {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a comprehensive email system health check';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?: 'laythfares99@gmail.com';
        
        $this->info("🔍 Email System Health Check");
        $this->info("Test email: $email");
        $this->newLine();

        // 1. Configuration Check
        $this->info("1. 📋 Configuration Check");
        $this->checkConfiguration();
        $this->newLine();

        // 2. Connectivity Test
        $this->info("2. 🌐 SMTP Connectivity Test");
        $this->testConnectivity();
        $this->newLine();

        // 3. Template Test
        $this->info("3. 📧 Email Template Test");
        $this->testTemplates($email);
        $this->newLine();

        // 4. DNS Records Check
        $this->info("4. 🔍 DNS Records Check");
        $this->checkDNSRecords();
        $this->newLine();

        // 5. Deliverability Score
        $this->info("5. 📊 Deliverability Assessment");
        $this->assessDeliverability();
        $this->newLine();

        $this->info("✅ Health check complete!");
        $this->info("💡 Check your email inbox: $email");
    }

    private function checkConfiguration()
    {
        $config = [
            'Driver' => config('mail.default'),
            'Host' => config('mail.mailers.smtp.host'),
            'Port' => config('mail.mailers.smtp.port'),
            'Encryption' => config('mail.mailers.smtp.encryption'),
            'Username' => config('mail.mailers.smtp.username'),
            'From Address' => config('mail.from.address'),
            'From Name' => config('mail.from.name'),
        ];

        $this->table(['Setting', 'Value'], collect($config)->map(function ($value, $key) {
            return [$key, $value ?: 'Not Set'];
        })->toArray());

        // Check for common issues
        if (!config('mail.from.address')) {
            $this->warn("⚠️  MAIL_FROM_ADDRESS not set");
        }
        if (!config('mail.mailers.smtp.username')) {
            $this->warn("⚠️  MAIL_USERNAME not set");
        }
        if (!config('mail.mailers.smtp.password')) {
            $this->warn("⚠️  MAIL_PASSWORD not set");
        }
    }

    private function testConnectivity()
    {
        try {
            $emailService = new EmailService();
            $results = $emailService->testEmailConfiguration();
            
            $this->table(['Configuration', 'Status'], collect($results)->map(function ($status, $config) {
                return [$config, $status];
            })->toArray());

            $successCount = collect($results)->filter(function ($status) {
                return strpos($status, 'SUCCESS') !== false;
            })->count();

            if ($successCount > 0) {
                $this->info("✅ $successCount configuration(s) working");
            } else {
                $this->error("❌ No working email configurations found");
            }
        } catch (\Exception $e) {
            $this->error("❌ Connectivity test failed: " . $e->getMessage());
        }
    }

    private function testTemplates($email)
    {
        $templates = [
            'Contact Form' => 'emails.contact',
            'Grace Period' => 'emails.reminders.grace-period-simple',
            'Warning Period' => 'emails.reminders.warning-period',
            'Overdue' => 'emails.reminders.overdue',
        ];

        $results = [];
        $emailService = new EmailService();

        foreach ($templates as $name => $template) {
            try {
                $testData = [
                    'contactName' => 'Test User',
                    'contactEmail' => $email,
                    'contactPhone' => '+1234567890',
                    'contactService' => 'Health Check',
                    'contactSubject' => 'Template Test',
                    'messageContent' => 'This is a template test.',
                    'userName' => 'Test User',
                    'userEmail' => $email,
                    'billingAmount' => 99.99,
                    'gracePeriodDays' => 7,
                    'subscriptionEndsAt' => now()->subDays(5),
                    'reminderType' => 'test',
                ];

                $success = $emailService->sendEmail($email, "Health Check - $name", $template, $testData);
                $results[] = [$name, $success ? '✅ SUCCESS' : '❌ FAILED'];
            } catch (\Exception $e) {
                $results[] = [$name, '❌ ERROR: ' . substr($e->getMessage(), 0, 50) . '...'];
            }
        }

        $this->table(['Template', 'Status'], $results);
    }

    private function checkDNSRecords()
    {
        $domain = 'medcuraai.com';
        $checks = [];

        // SPF Record Check
        try {
            $spfRecords = dns_get_record($domain, DNS_TXT);
            $spfFound = false;
            foreach ($spfRecords as $record) {
                if (strpos($record['txt'], 'v=spf1') === 0) {
                    $spfFound = true;
                    $checks[] = ['SPF Record', '✅ Found: ' . substr($record['txt'], 0, 50) . '...'];
                    break;
                }
            }
            if (!$spfFound) {
                $checks[] = ['SPF Record', '❌ Not Found - Add: v=spf1 include:_spf.hostinger.com ~all'];
            }
        } catch (\Exception $e) {
            $checks[] = ['SPF Record', '⚠️  Could not check: ' . $e->getMessage()];
        }

        // DMARC Record Check
        try {
            $dmarcRecords = dns_get_record('_dmarc.' . $domain, DNS_TXT);
            $dmarcFound = false;
            foreach ($dmarcRecords as $record) {
                if (strpos($record['txt'], 'v=DMARC1') === 0) {
                    $dmarcFound = true;
                    $checks[] = ['DMARC Record', '✅ Found: ' . substr($record['txt'], 0, 50) . '...'];
                    break;
                }
            }
            if (!$dmarcFound) {
                $checks[] = ['DMARC Record', '❌ Not Found - Add: v=DMARC1; p=quarantine; rua=mailto:dmarc@medcuraai.com'];
            }
        } catch (\Exception $e) {
            $checks[] = ['DMARC Record', '⚠️  Could not check: ' . $e->getMessage()];
        }

        // MX Record Check
        try {
            $mxRecords = dns_get_record($domain, DNS_MX);
            if (!empty($mxRecords)) {
                $checks[] = ['MX Records', '✅ Found: ' . count($mxRecords) . ' record(s)'];
            } else {
                $checks[] = ['MX Records', '❌ Not Found'];
            }
        } catch (\Exception $e) {
            $checks[] = ['MX Records', '⚠️  Could not check: ' . $e->getMessage()];
        }

        $this->table(['DNS Check', 'Status'], $checks);
    }

    private function assessDeliverability()
    {
        $score = 0;
        $maxScore = 10;
        $recommendations = [];

        // Configuration completeness (2 points)
        if (config('mail.from.address') && config('mail.mailers.smtp.username')) {
            $score += 2;
        } else {
            $recommendations[] = "Complete email configuration in .env file";
        }

        // Working SMTP (3 points)
        try {
            $emailService = new EmailService();
            $results = $emailService->testEmailConfiguration();
            $workingConfigs = collect($results)->filter(function ($status) {
                return strpos($status, 'SUCCESS') !== false;
            })->count();
            
            if ($workingConfigs > 0) {
                $score += 3;
            } else {
                $recommendations[] = "Fix SMTP authentication issues";
            }
        } catch (\Exception $e) {
            $recommendations[] = "Resolve email service connectivity issues";
        }

        // Domain reputation (2 points)
        if (config('mail.from.address') && strpos(config('mail.from.address'), 'medcuraai.com') !== false) {
            $score += 2;
        } else {
            $recommendations[] = "Use domain-based email address (info@medcuraai.com)";
        }

        // DNS records (3 points)
        $domain = 'medcuraai.com';
        try {
            $spfRecords = dns_get_record($domain, DNS_TXT);
            $spfFound = false;
            foreach ($spfRecords as $record) {
                if (strpos($record['txt'], 'v=spf1') === 0) {
                    $spfFound = true;
                    $score += 1;
                    break;
                }
            }
            if (!$spfFound) {
                $recommendations[] = "Add SPF record to DNS";
            }

            $dmarcRecords = dns_get_record('_dmarc.' . $domain, DNS_TXT);
            $dmarcFound = false;
            foreach ($dmarcRecords as $record) {
                if (strpos($record['txt'], 'v=DMARC1') === 0) {
                    $dmarcFound = true;
                    $score += 2;
                    break;
                }
            }
            if (!$dmarcFound) {
                $recommendations[] = "Add DMARC record to DNS";
            }
        } catch (\Exception $e) {
            $recommendations[] = "Configure DNS records for better deliverability";
        }

        // Display score
        $percentage = round(($score / $maxScore) * 100);
        $this->info("📊 Deliverability Score: $score/$maxScore ($percentage%)");

        if ($percentage >= 80) {
            $this->info("🎉 Excellent! Your email system is well configured.");
        } elseif ($percentage >= 60) {
            $this->warn("⚠️  Good, but there's room for improvement.");
        } else {
            $this->error("❌ Poor deliverability. Immediate action needed.");
        }

        if (!empty($recommendations)) {
            $this->newLine();
            $this->info("🔧 Recommendations:");
            foreach ($recommendations as $recommendation) {
                $this->line("  • $recommendation");
            }
        }
    }
}