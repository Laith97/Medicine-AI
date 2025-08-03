<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyUserEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:verify-email {email? : The email address of the user to verify} {--all : Verify all unverified users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify user email addresses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $count = User::whereNull('email_verified_at')->update(['email_verified_at' => now()]);
            $this->info("✅ Verified {$count} users successfully!");
            return;
        }

        $email = $this->argument('email');
        
        if (!$email) {
            $email = $this->ask('Enter the email address to verify');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ User with email '{$email}' not found.");
            return;
        }

        if ($user->email_verified_at) {
            $this->info("ℹ️  User '{$email}' is already verified (verified at: {$user->email_verified_at->format('Y-m-d H:i:s')})");
            return;
        }

        $user->update(['email_verified_at' => now()]);
        $this->info("✅ User '{$email}' has been verified successfully!");
    }
}