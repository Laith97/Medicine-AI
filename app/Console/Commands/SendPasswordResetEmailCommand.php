<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use App\Models\User;

class SendPasswordResetEmailCommand extends Command
{
    protected $signature = 'password:reset-email {email}';
    protected $description = 'Send a password reset email to a specific user';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Attempting to send password reset email to {$email}...");

        // Find the user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Send the reset link
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->info('Password reset email sent successfully!');
            return 0;
        } else {
            $this->error("Failed to send password reset email: {$status}");
            return 1;
        }
    }
}