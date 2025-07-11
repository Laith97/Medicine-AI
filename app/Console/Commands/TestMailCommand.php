<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email}';
    protected $description = 'Send a test email to verify mail configuration';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Attempting to send test email to {$email}...");

        try {
            Mail::raw('This is a test email from your Laravel application to verify mail configuration.', function (Message $message) use ($email) {
                $message->to($email)
                    ->subject('Test Email from Laravel App');
            });

            $this->info('Test email sent successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to send test email: ' . $e->getMessage());
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}