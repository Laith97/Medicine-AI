<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to the specified address';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Attempting to send test email to: {$email}");
        
        // Log mail configuration
        $this->info('Mail configuration:');
        $this->info('Driver: ' . config('mail.default'));
        $this->info('Host: ' . config('mail.mailers.smtp.host'));
        $this->info('Port: ' . config('mail.mailers.smtp.port'));
        $this->info('Username: ' . config('mail.mailers.smtp.username'));
        $this->info('From Address: ' . config('mail.from.address'));
        $this->info('From Name: ' . config('mail.from.name'));
        
        try {
            Mail::raw("This is a test email sent from the Laravel command line at " . now(), function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email from Command Line');
            });
            
            $this->info("Email sent successfully!");
        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}