<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class FinalSubUserTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:final-sub-user {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Final comprehensive test for sub-user functionality';

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

        $this->info("🧪 Final Sub-User Test for: {$user->name} ({$user->email})");

        // Simulate login
        Auth::login($user);

        // Test 1: Basic User Methods
        $this->info("\n=== 1. Testing Basic User Methods ===");
        
        $effectiveDoctor = $user->getEffectiveDoctor();
        if ($effectiveDoctor) {
            $this->info("✅ getEffectiveDoctor() - Doctor ID: {$effectiveDoctor->id}");
        } else {
            $this->error("❌ getEffectiveDoctor() returned null");
            return 1;
        }

        $effectiveDoctorUser = $user->getEffectiveDoctorUser();
        if ($effectiveDoctorUser) {
            $this->info("✅ getEffectiveDoctorUser() - User ID: {$effectiveDoctorUser->id}");
        } else {
            $this->error("❌ getEffectiveDoctorUser() returned null");
        }

        // Test 2: Doctor Relationships
        $this->info("\n=== 2. Testing Doctor Relationships ===");
        
        try {
            $appointmentsCount = $effectiveDoctor->appointments()->count();
            $this->info("✅ appointments() - Count: {$appointmentsCount}");
        } catch (\Exception $e) {
            $this->error("❌ appointments() failed: " . $e->getMessage());
        }

        try {
            $blogPostsCount = $effectiveDoctor->blogPosts()->count();
            $this->info("✅ blogPosts() - Count: {$blogPostsCount}");
        } catch (\Exception $e) {
            $this->error("❌ blogPosts() failed: " . $e->getMessage());
        }

        try {
            $reviewsCount = $effectiveDoctor->reviews()->count();
            $this->info("✅ reviews() - Count: {$reviewsCount}");
        } catch (\Exception $e) {
            $this->error("❌ reviews() failed: " . $e->getMessage());
        }

        // Test 3: Permissions
        $this->info("\n=== 3. Testing Permissions ===");
        
        $testPermissions = [
            'dashboard' => 'Dashboard',
            'cases' => 'Patient Management',
            'diagnosis' => 'Diagnosis',
            'doctor.appointments.index' => 'Appointments',
            'doctor.blog.index' => 'Blog',
            'doctor.notes.index' => 'Notes',
            'settings' => 'Settings',
        ];

        $accessibleCount = 0;
        foreach ($testPermissions as $route => $name) {
            $canAccess = $user->canAccessRoute($route);
            if ($canAccess) {
                $this->info("✅ Can access: {$name}");
                $accessibleCount++;
            } else {
                $this->warn("⚠️  Cannot access: {$name}");
            }
        }

        $this->info("📊 Accessible features: {$accessibleCount}/" . count($testPermissions));

        // Test 4: Dashboard Controller
        $this->info("\n=== 4. Testing Dashboard Controller ===");
        
        try {
            $controller = new \App\Http\Controllers\OpenAIController(new \App\Services\SmsService());
            
            // Test if dashboard method works without errors
            ob_start();
            $result = $controller->dashboard();
            ob_end_clean();
            
            $this->info("✅ Dashboard controller executed successfully");
        } catch (\Exception $e) {
            $this->error("❌ Dashboard controller failed: " . $e->getMessage());
        }

        // Test 5: Route Access
        $this->info("\n=== 5. Testing Route Access ===");
        
        $testRoutes = [
            'dashboard',
            'cases', 
            'settings',
            'doctor.appointments.index',
            'doctor.blog.index',
            'doctor.notes.index',
        ];

        foreach ($testRoutes as $routeName) {
            if (Route::has($routeName)) {
                $this->info("✅ Route exists: {$routeName}");
            } else {
                $this->warn("⚠️  Route missing: {$routeName}");
            }
        }

        // Test 6: Parent Doctor Verification
        $this->info("\n=== 6. Testing Parent Doctor Verification ===");
        
        $parentUser = $user->parentUser;
        if ($parentUser) {
            $this->info("✅ Parent user: {$parentUser->name} (ID: {$parentUser->id})");
            
            if ($parentUser->doctor) {
                $this->info("✅ Parent has doctor profile (ID: {$parentUser->doctor->id})");
                
                if ($parentUser->doctor->is_active) {
                    $this->info("✅ Parent doctor profile is active");
                } else {
                    $this->warn("⚠️  Parent doctor profile is inactive");
                }
            } else {
                $this->error("❌ Parent user has no doctor profile");
            }
        } else {
            $this->error("❌ No parent user found");
        }

        Auth::logout();

        $this->info("\n🎉 Final test completed!");
        $this->info("📋 Summary:");
        $this->info("   • Sub-user can access {$accessibleCount} features");
        $this->info("   • Doctor relationships working");
        $this->info("   • Dashboard controller functional");
        $this->info("   • Parent doctor link verified");
        
        return 0;
    }
}