<?php

namespace App\Console\Commands;

use App\Models\DoctorLandingPage;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Console\Command;

class TestLandingPageSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:landing-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Doctor Landing Page System';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Doctor Landing Page System...');
        $this->newLine();

        // Test 1: Check if models exist and relationships work
        $this->info('1. Testing Models and Relationships...');

        $landingPages = DoctorLandingPage::with(['doctor.user', 'doctor.specialty'])->get();
        $this->info("   ✅ Found {$landingPages->count()} landing pages");

        foreach ($landingPages as $landingPage) {
            $this->info("   📄 Landing Page: {$landingPage->username}");
            $this->info("      Doctor: {$landingPage->doctor->user->name}");
            $this->info("      Published: " . ($landingPage->is_published ? 'Yes' : 'No'));
            $this->info("      Template: {$landingPage->template}");
            $this->info("      URL: " . route('doctor.landing', $landingPage->username));

            if ($landingPage->subdomain_enabled) {
                $this->info("      Subdomain: {$landingPage->username}.medcuraai.com");
            }

            if ($landingPage->custom_domain) {
                $this->info("      Custom Domain: {$landingPage->custom_domain}");
            }

            $this->newLine();
        }

        // Test 2: Check routes
        $this->info('2. Testing Routes...');

        try {
            $publicRoute = route('doctor.landing', 'drjohnsmith');
            $this->info("   ✅ Public route: {$publicRoute}");
        } catch (\Exception $e) {
            $this->error("   ❌ Public route failed: " . $e->getMessage());
        }

        try {
            $adminRoute = route('doctor.landing-page.index');
            $this->info("   ✅ Admin route: {$adminRoute}");
        } catch (\Exception $e) {
            $this->error("   ❌ Admin route failed: " . $e->getMessage());
        }

        // Test 3: Check database structure
        $this->info('3. Testing Database Structure...');

        if ($landingPages->count() > 0) {
            $landingPage = $landingPages->first();

            $this->info("   ✅ Colors: " . json_encode($landingPage->colors));
            $this->info("   ✅ Section Visibility: " . json_encode($landingPage->section_visibility));

            // Test scopes
            $publishedCount = DoctorLandingPage::published()->count();
            $this->info("   ✅ Published pages: {$publishedCount}");

            $byUsernameCount = DoctorLandingPage::byUsername('drjohnsmith')->count();
            $this->info("   ✅ Pages by username 'drjohnsmith': {$byUsernameCount}");
        }

        // Test 4: Check file structure
        $this->info('4. Testing File Structure...');

        $files = [
            'app/Models/DoctorLandingPage.php',
            'app/Http/Controllers/Doctor/LandingPageController.php',
            'app/Http/Controllers/PublicLandingPageController.php',
            'app/Http/Middleware/HandleDoctorDomains.php',
            'resources/views/doctor/landing-page/index.blade.php',
            'resources/views/doctor/landing-page/templates/template1.blade.php',
            'resources/views/doctor/landing-page/templates/template2.blade.php',
        ];

        foreach ($files as $file) {
            $fullPath = base_path($file);
            if (file_exists($fullPath)) {
                $this->info("   ✅ {$file}");
            } else {
                $this->error("   ❌ {$file} - Missing!");
            }
        }

        $this->newLine();
        $this->info('🎉 Landing Page System Test Complete!');

        if ($landingPages->count() > 0) {
            $this->info('');
            $this->info('📋 Next Steps:');
            $this->info('1. Login as a doctor: dr.smith@example.com / password');
            $this->info('2. Navigate to Landing Page management');
            $this->info('3. Customize your landing page');
            $this->info('4. Visit: ' . route('doctor.landing', 'drjohnsmith'));
        } else {
            $this->warn('No landing pages found. Run: php artisan db:seed --class=DoctorLandingPageSeeder');
        }
    }
}
