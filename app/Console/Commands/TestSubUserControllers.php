<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestSubUserControllers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sub-user-controllers {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sub-user controller access';

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

        $this->info("Testing controller access for sub-user: {$user->name} ({$user->email})");

        // Simulate login
        Auth::login($user);

        $controllers = [
            'BlogController' => \App\Http\Controllers\Doctor\BlogController::class,
            'LandingPageController' => \App\Http\Controllers\Doctor\LandingPageController::class,
            'TestimonialController' => \App\Http\Controllers\Doctor\TestimonialController::class,
            'AnalyticsController' => \App\Http\Controllers\Doctor\AnalyticsController::class,
            'ChatController' => \App\Http\Controllers\Doctor\ChatController::class,
            'DoctorNotesController' => \App\Http\Controllers\Doctor\DoctorNotesController::class,
        ];

        foreach ($controllers as $name => $class) {
            $this->info("\n=== Testing {$name} ===");
            
            try {
                $controller = new $class();
                $this->info("✅ {$name} instantiated successfully");
                
                // Test getEffectiveDoctor method if it exists
                if (method_exists($controller, 'getEffectiveDoctor')) {
                    $doctor = $controller->getEffectiveDoctor();
                    if ($doctor) {
                        $this->info("✅ getEffectiveDoctor() works - Doctor ID: {$doctor->id}");
                        
                        // Test specific relationships
                        if (method_exists($doctor, 'blogPosts')) {
                            $blogCount = $doctor->blogPosts()->count();
                            $this->info("✅ Can access blogPosts - Count: {$blogCount}");
                        }
                        
                        if (method_exists($doctor, 'appointments')) {
                            $appointmentCount = $doctor->appointments()->count();
                            $this->info("✅ Can access appointments - Count: {$appointmentCount}");
                        }
                        
                        if (method_exists($doctor, 'reviews')) {
                            $reviewCount = $doctor->reviews()->count();
                            $this->info("✅ Can access reviews - Count: {$reviewCount}");
                        }
                        
                        if (method_exists($doctor, 'chatSessions')) {
                            $chatCount = $doctor->chatSessions()->count();
                            $this->info("✅ Can access chatSessions - Count: {$chatCount}");
                        }
                        
                    } else {
                        $this->error("❌ getEffectiveDoctor() returned null");
                    }
                } else {
                    $this->warn("⚠️  {$name} doesn't have getEffectiveDoctor method");
                }
                
            } catch (\Exception $e) {
                $this->error("❌ {$name} failed: " . $e->getMessage());
            }
        }

        Auth::logout();
        $this->info("\n✅ All controller tests completed!");
        
        return 0;
    }
}