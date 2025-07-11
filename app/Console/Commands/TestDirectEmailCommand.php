<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestDirectEmailCommand extends Command
{
    protected $signature = 'mail:test-direct {email}';
    protected $description = 'Send a test email directly without using the queue';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Attempting to send direct test email to {$email}...");

        try {
            Mail::raw('This is a direct test email from your Laravel application.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Direct Test Email from Laravel App');
            });

            $this->info('Direct test email sent successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to send direct test email: ' . $e->getMessage());
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}