<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Http\Controllers\OpenAIController;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestSubUserDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sub-user-dashboard {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sub-user dashboard access and functionality';

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

        $this->info("Testing dashboard access for sub-user: {$user->name} ({$user->email})");

        // Simulate login
        Auth::login($user);
        $this->info("✅ User logged in successfully");

        // Test effective doctor methods
        $this->info("\n=== Testing Effective Doctor Methods ===");
        
        $effectiveDoctor = $user->getEffectiveDoctor();
        if ($effectiveDoctor) {
            $this->info("✅ getEffectiveDoctor() works - Doctor ID: {$effectiveDoctor->id}");
            
            // Test appointments access
            try {
                $appointmentsCount = $effectiveDoctor->appointments()->count();
                $this->info("✅ Can access appointments - Count: {$appointmentsCount}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to access appointments: " . $e->getMessage());
            }
            
            // Test reviews access
            try {
                $reviewsCount = $effectiveDoctor->reviews()->count();
                $this->info("✅ Can access reviews - Count: {$reviewsCount}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to access reviews: " . $e->getMessage());
            }
        } else {
            $this->error("❌ getEffectiveDoctor() returned null");
        }

        // Test effective assigned patients
        try {
            $patientsCount = $user->getEffectiveAssignedPatients()->count();
            $this->info("✅ Can access assigned patients - Count: {$patientsCount}");
        } catch (\Exception $e) {
            $this->error("❌ Failed to access assigned patients: " . $e->getMessage());
        }

        // Test dashboard controller
        $this->info("\n=== Testing Dashboard Controller ===");
        try {
            $controller = new OpenAIController(new SmsService());
            $this->info("✅ OpenAIController instantiated successfully");
            
            // Test dashboard method (this would normally return a view)
            // We'll just check if it doesn't throw an exception
            ob_start();
            try {
                $result = $controller->dashboard();
                $this->info("✅ Dashboard method executed successfully");
            } catch (\Exception $e) {
                $this->error("❌ Dashboard method failed: " . $e->getMessage());
            }
            ob_end_clean();
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to instantiate OpenAIController: " . $e->getMessage());
        }

        // Test permissions
        $this->info("\n=== Testing Permissions ===");
        $testRoutes = [
            'dashboard' => 'Dashboard',
            'cases' => 'Patient Cases',
            'doctor.appointments.index' => 'Appointments',
            'doctor.availability.index' => 'Availability',
            'doctor.reviews.index' => 'Reviews',
            'settings' => 'Settings',
            'doctor.profile.edit' => 'Profile Edit',
            'doctor.notes.index' => 'Notes',
            'doctor.blog.index' => 'Blog',
        ];

        foreach ($testRoutes as $route => $name) {
            $canAccess = $user->canAccessRoute($route);
            if ($canAccess) {
                $this->info("✅ Can access: {$name}");
            } else {
                $this->warn("⚠️  Cannot access: {$name}");
            }
        }

        // Test restricted routes (should be blocked)
        $restrictedRoutes = [
            'ai_assistant' => 'AI Assistant',
            'diagnosis' => 'Diagnoses',
            'voice_assistant' => 'Voice Assistant',
            'sub_users' => 'Sub-User Management',
        ];

        $this->info("\n=== Testing Restricted Routes (Should be blocked) ===");
        foreach ($restrictedRoutes as $route => $name) {
            $canAccess = $user->canAccessRoute($route);
            if (!$canAccess) {
                $this->info("✅ Correctly blocked: {$name}");
            } else {
                $this->error("❌ Should be blocked but isn't: {$name}");
            }
        }

        Auth::logout();
        $this->info("\n✅ All tests completed!");
        
        return 0;
    }
}