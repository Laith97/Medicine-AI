<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Hospital;

class TestHospitalAdminLogin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:hospital-admin-login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test hospital admin login and route access';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Testing Hospital Admin Login and Routes');
        $this->newLine();

        // Find a hospital admin user
        $hospitalAdmin = User::where('role', 'hospital_admin')->with('hospital')->first();

        if (!$hospitalAdmin) {
            $this->error('❌ No hospital admin found in the database.');
            $this->info('Creating a test hospital admin...');
            
            // Create a hospital first
            $hospital = Hospital::first();
            if (!$hospital) {
                $hospital = Hospital::create([
                    'name' => 'Test Hospital for Admin',
                    'email' => 'admin@testhospital.com',
                    'phone' => '+1234567890',
                    'address' => '123 Test Street',
                    'city' => 'Test City',
                    'state' => 'Test State',
                    'zip_code' => '12345',
                ]);
                $this->info("✅ Created test hospital: {$hospital->name}");
            }

            // Create hospital admin
            $hospitalAdmin = User::create([
                'name' => 'Test Hospital Admin',
                'email' => 'test.hospital.admin@example.com',
                'password' => bcrypt('password123'),
                'role' => 'hospital_admin',
                'hospital_id' => $hospital->id,
                'phone' => '+1234567890',
            ]);
            $this->info("✅ Created test hospital admin: {$hospitalAdmin->email}");
        }

        $this->info("Hospital Admin Details:");
        $this->info("  - Name: {$hospitalAdmin->name}");
        $this->info("  - Email: {$hospitalAdmin->email}");
        $this->info("  - Role: {$hospitalAdmin->role}");
        $this->info("  - Hospital: " . ($hospitalAdmin->hospital ? $hospitalAdmin->hospital->name : 'None'));
        $this->newLine();

        // Test route access
        $this->info("Testing Route Access:");
        
        $routes = [
            'hospital-admin.dashboard',
            'hospital-admin.departments.index',
            'hospital-admin.doctors.index',
            'hospital-admin.hospital.profile',
            'hospital-admin.invoices.index',
            'hospital-admin.usage.index',
            'hospital-admin.analytics.overview',
        ];

        foreach ($routes as $routeName) {
            try {
                $url = route($routeName);
                $this->info("  ✅ {$routeName} → {$url}");
            } catch (\Exception $e) {
                $this->error("  ❌ {$routeName} → Route not found: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("🎯 Login Instructions:");
        $this->info("1. Go to: " . url('/login'));
        $this->info("2. Email: {$hospitalAdmin->email}");
        $this->info("3. Password: password123");
        $this->info("4. You should be redirected to the hospital admin dashboard");
        
        $this->newLine();
        $this->info("🔗 Direct Links:");
        try {
            $this->info("- Dashboard: " . route('hospital-admin.dashboard'));
            $this->info("- Departments: " . route('hospital-admin.departments.index'));
            $this->info("- Doctors: " . route('hospital-admin.doctors.index'));
        } catch (\Exception $e) {
            $this->error("Some routes are not available: " . $e->getMessage());
        }
    }
}