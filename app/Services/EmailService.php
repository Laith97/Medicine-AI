<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class EmailService
{
    private $fallbackConfigs = [
        // Primary: Hostinger SMTP (working configuration)
        'hostinger' => [
            'driver' => 'smtp',
            'host' => 'smtp.hostinger.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'info@medcuraai.com',
            'password' => '!SWeG>1wI',
        ],
        // Fallback 1: System sendmail
        'sendmail' => [
            'driver' => 'sendmail',
            'path' => '/usr/sbin/sendmail -bs',
        ],
        // Fallback 2: Gmail SMTP (if needed)
        'gmail' => [
            'driver' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
        ],
    ];

    public function sendEmail($to, $subject, $view, $data = [])
    {
        $attempts = 0;
        $maxAttempts = 3;
        $lastError = null;

        foreach ($this->fallbackConfigs as $configName => $config) {
            if ($attempts >= $maxAttempts) {
                break;
            }

            try {
                $attempts++;
                Log::info("Attempting to send email using: $configName (attempt $attempts)", [
                    'to' => $to,
                    'subject' => $subject,
                    'view' => $view
                ]);

                // Temporarily set mail configuration
                $this->setMailConfig($config);

                // Clear any cached config
                app()->forgetInstance('mailer');

                // Send email with anti-spam headers
                Mail::send($view, $data, function ($message) use ($to, $subject) {
                    $message->to($to);
                    $message->subject($subject);
                    $message->from(config('mail.from.address'), config('mail.from.name'));
                    
                    // Add anti-spam headers
                    $message->getHeaders()
                        ->addTextHeader('X-Mailer', 'MedCura AI System')
                        ->addTextHeader('X-Priority', '3')
                        ->addTextHeader('List-Unsubscribe', '<mailto:unsubscribe@medcuraai.com>')
                        ->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN');
                    
                    // Set return path properly
                    $message->returnPath(config('mail.from.address'));
                });

                Log::info("Email sent successfully using: $configName to: $to");
                return true;

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("Failed to send email using $configName: " . $lastError, [
                    'to' => $to,
                    'view' => $view,
                    'error_trace' => $e->getTraceAsString()
                ]);
                
                // Reset mail config for next attempt
                $this->resetMailConfig();
                continue;
            }
        }

        // All methods failed, log final error
        Log::error("Failed to send email after $attempts attempts. Last error: $lastError", [
            'to' => $to,
            'subject' => $subject,
            'view' => $view
        ]);
        
        // Try basic PHP mail as last resort
        return $this->fallbackPHPMail($to, $subject, $data);
    }

    private function setMailConfig($config)
    {
        if ($config['driver'] === 'smtp') {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $config['host']);
            Config::set('mail.mailers.smtp.port', $config['port']);
            Config::set('mail.mailers.smtp.encryption', $config['encryption']);
            
            if (isset($config['username'])) {
                Config::set('mail.mailers.smtp.username', $config['username']);
                Config::set('mail.mailers.smtp.password', $config['password']);
            }
        } elseif ($config['driver'] === 'sendmail') {
            Config::set('mail.default', 'sendmail');
            Config::set('mail.mailers.sendmail.path', $config['path']);
        }
    }

    private function resetMailConfig()
    {
        // Reset to original config
        Config::set('mail.default', env('MAIL_MAILER', 'smtp'));
        Config::set('mail.mailers.smtp.host', env('MAIL_HOST'));
        Config::set('mail.mailers.smtp.port', env('MAIL_PORT'));
        Config::set('mail.mailers.smtp.username', env('MAIL_USERNAME'));
        Config::set('mail.mailers.smtp.password', env('MAIL_PASSWORD'));
        Config::set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION'));
    }

    private function fallbackPHPMail($to, $subject, $data)
    {
        try {
            $message = "New message from MedCura AI contact form:\n\n";
            if (isset($data['name'])) {
                $message .= "Name: " . $data['name'] . "\n";
                $message .= "Email: " . $data['email'] . "\n";
                $message .= "Phone: " . ($data['phone'] ?? 'Not provided') . "\n";
                $message .= "Service: " . ($data['service'] ?? 'Not specified') . "\n";
                $message .= "Subject: " . ($data['subject'] ?? 'No subject') . "\n";
                $message .= "Message: " . ($data['message'] ?? 'No message') . "\n";
            } else {
                $message .= $data['message'] ?? 'No content provided';
            }

            $headers = "From: info@medcuraai.com\r\n";
            $headers .= "Reply-To: " . ($data['email'] ?? 'info@medcuraai.com') . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            if (mail($to, $subject, $message, $headers)) {
                Log::info("Fallback PHP mail sent to: $to");
                return true;
            }
        } catch (\Exception $e) {
            Log::error("Even PHP mail failed: " . $e->getMessage());
        }

        return false;
    }

    public function testEmailConfiguration()
    {
        $testResults = [];
        
        foreach ($this->fallbackConfigs as $configName => $config) {
            try {
                $this->setMailConfig($config);
                
                // Test by sending a simple message
                $result = $this->sendEmail(
                    'laythfares99@gmail.com',
                    'Email Configuration Test - ' . ucfirst($configName),
                    'emails.test',
                    ['config' => $configName, 'timestamp' => now()]
                );
                
                $testResults[$configName] = $result ? 'SUCCESS' : 'FAILED';
                
            } catch (\Exception $e) {
                $testResults[$configName] = 'ERROR: ' . $e->getMessage();
            }
        }
        
        $this->resetMailConfig();
        return $testResults;
    }
}