<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailService;

class TestEmailConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration with multiple fallback methods';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?: 'laythfares99@gmail.com';
        
        $this->info("Testing email configurations...");
        $this->info("Test email will be sent to: $email");
        $this->newLine();

        $emailService = new EmailService();
        $results = $emailService->testEmailConfiguration();

        $this->table(
            ['Configuration', 'Status'],
            collect($results)->map(function ($status, $config) {
                return [$config, $status];
            })->toArray()
        );

        $this->newLine();
        $this->info("Check your email inbox and logs for detailed results.");
        $this->info("Logs location: storage/logs/laravel.log");
    }
}
