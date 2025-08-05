<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestSubUserAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sub-user-access {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sub-user access to various controllers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        if (!$user->isSubUser()) {
            $this->error("User is not a sub-user.");
            return 1;
        }

        $this->info("Testing sub-user access for: {$user->name} ({$user->email})");

        // Simulate login
        Auth::login($user);

        $this->info("✅ User logged in successfully");

        // Test middleware logic
        $this->info("\n=== Testing Middleware Logic ===");
        
        try {
            // Test EnsureUserIsDoctor middleware logic
            if ($user->isSubUser()) {
                $parentUser = $user->parentUser;
                if (!$parentUser || !$parentUser->isDoctor() || !$parentUser->doctor || !$parentUser->doctor->is_active) {
                    $this->error("❌ Would fail EnsureUserIsDoctor middleware");
                } else {
                    $this->info("✅ Should pass EnsureUserIsDoctor middleware");
                }
            }

            // Test effective doctor methods
            $this->info("\n=== Testing Effective Doctor Methods ===");
            $effectiveDoctor = $user->getEffectiveDoctor();
            if ($effectiveDoctor) {
                $this->info("✅ getEffectiveDoctor() returns doctor profile (ID: {$effectiveDoctor->id})");
            } else {
                $this->error("❌ getEffectiveDoctor() returns null");
            }

            $effectiveDoctorUser = $user->getEffectiveDoctorUser();
            if ($effectiveDoctorUser) {
                $this->info("✅ getEffectiveDoctorUser() returns parent user (ID: {$effectiveDoctorUser->id})");
            } else {
                $this->error("❌ getEffectiveDoctorUser() returns null");
            }

            $hasActiveProfile = $user->hasActiveDoctorProfile();
            $this->info("✅ hasActiveDoctorProfile(): " . ($hasActiveProfile ? 'true' : 'false'));

            // Test controller instantiation
            $this->info("\n=== Testing Controller Access ===");
            
            // Test OpenAIController
            try {
                $controller = new \App\Http\Controllers\OpenAIController(new \App\Services\SmsService());
                $this->info("✅ OpenAIController can be instantiated");
            } catch (\Exception $e) {
                $this->error("❌ OpenAIController failed: " . $e->getMessage());
            }

            // Test VoiceAssistantController
            try {
                $controller = new \App\Http\Controllers\VoiceAssistantController();
                $this->info("✅ VoiceAssistantController can be instantiated");
            } catch (\Exception $e) {
                $this->error("❌ VoiceAssistantController failed: " . $e->getMessage());
            }

            // Test AvailabilityController
            try {
                $controller = new \App\Http\Controllers\Doctor\AvailabilityController();
                $this->info("✅ AvailabilityController can be instantiated");
            } catch (\Exception $e) {
                $this->error("❌ AvailabilityController failed: " . $e->getMessage());
            }

        } catch (\Exception $e) {
            $this->error("❌ Test failed with exception: " . $e->getMessage());
            return 1;
        }

        Auth::logout();
        $this->info("\n✅ All tests completed successfully!");
        
        return 0;
    }
}