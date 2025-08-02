<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmailMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:monitor {--alert-email=laythfares99@gmail.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor email system health and send alerts if issues detected';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $alertEmail = $this->option('alert-email');
        
        Log::info('Email monitoring started', ['alert_email' => $alertEmail]);
        
        $issues = [];
        $emailService = new EmailService();

        // Test 1: SMTP Connectivity
        try {
            $results = $emailService->testEmailConfiguration();
            $workingConfigs = collect($results)->filter(function ($status) {
                return strpos($status, 'SUCCESS') !== false;
            })->count();
            
            if ($workingConfigs === 0) {
                $issues[] = 'No working SMTP configurations found';
            }
        } catch (\Exception $e) {
            $issues[] = 'SMTP connectivity test failed: ' . $e->getMessage();
        }

        // Test 2: Template rendering
        try {
            $testData = [
                'userName' => 'Monitor Test',
                'userEmail' => $alertEmail,
                'billingAmount' => 99.99,
                'gracePeriodDays' => 7,
                'subscriptionEndsAt' => now()->subDays(5),
                'reminderType' => 'monitor_test',
            ];

            // Test if we can render a template without sending
            view('emails.reminders.grace-period-simple', $testData)->render();
        } catch (\Exception $e) {
            $issues[] = 'Email template rendering failed: ' . $e->getMessage();
        }

        // Test 3: Configuration completeness
        $requiredConfigs = [
            'mail.from.address',
            'mail.mailers.smtp.host',
            'mail.mailers.smtp.username',
            'mail.mailers.smtp.password',
        ];

        foreach ($requiredConfigs as $config) {
            if (!config($config)) {
                $issues[] = "Missing configuration: $config";
            }
        }

        // Store monitoring results
        $monitoringData = [
            'timestamp' => now()->toISOString(),
            'issues_count' => count($issues),
            'issues' => $issues,
            'status' => count($issues) === 0 ? 'healthy' : 'issues_detected',
        ];

        Cache::put('email_monitoring_last_check', $monitoringData, now()->addHours(24));

        // Log results
        if (count($issues) === 0) {
            Log::info('Email monitoring: All systems healthy');
            $this->info('✅ Email system is healthy');
        } else {
            Log::warning('Email monitoring: Issues detected', ['issues' => $issues]);
            $this->warn('⚠️  Issues detected:');
            foreach ($issues as $issue) {
                $this->line("  • $issue");
            }

            // Send alert email if issues found
            $this->sendAlert($alertEmail, $issues, $emailService);
        }

        return count($issues) === 0 ? 0 : 1;
    }

    private function sendAlert($alertEmail, $issues, $emailService)
    {
        try {
            // Only send alert if we haven't sent one recently
            $lastAlert = Cache::get('email_monitoring_last_alert');
            if ($lastAlert && now()->diffInHours($lastAlert) < 6) {
                $this->info('Skipping alert - already sent within last 6 hours');
                return;
            }

            $subject = 'MedCura AI - Email System Alert';
            $alertData = [
                'issues' => $issues,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'server' => gethostname(),
            ];

            // Create a simple alert template
            $alertContent = view('emails.system-alert', $alertData)->render();
            
            $success = $emailService->sendEmail($alertEmail, $subject, 'emails.system-alert', $alertData);
            
            if ($success) {
                Cache::put('email_monitoring_last_alert', now(), now()->addHours(24));
                $this->info('Alert email sent successfully');
                Log::info('Email monitoring alert sent', ['to' => $alertEmail]);
            } else {
                $this->error('Failed to send alert email');
                Log::error('Failed to send email monitoring alert', ['to' => $alertEmail]);
            }
        } catch (\Exception $e) {
            $this->error('Error sending alert: ' . $e->getMessage());
            Log::error('Email monitoring alert error', ['error' => $e->getMessage()]);
        }
    }
}