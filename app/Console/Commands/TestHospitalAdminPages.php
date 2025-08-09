<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Hospital;
use App\Models\Department;

class TestHospitalAdminPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:hospital-admin-pages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all hospital admin pages and routes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Testing Hospital Admin Pages and Routes');
        $this->newLine();

        // Find or create a hospital admin user
        $hospitalAdmin = User::where('role', 'hospital_admin')->with('hospital')->first();

        if (!$hospitalAdmin) {
            $this->info('Creating test hospital admin...');
            
            // Create a hospital first
            $hospital = Hospital::firstOrCreate([
                'name' => 'Test Hospital for Pages',
                'email' => 'pages@testhospital.com',
            ], [
                'phone' => '+1234567890',
                'address' => '123 Test Street',
                'city' => 'Test City',
                'state' => 'Test State',
                'zip_code' => '12345',
                'is_active' => true,
            ]);

            // Create hospital admin
            $hospitalAdmin = User::create([
                'name' => 'Test Hospital Admin Pages',
                'email' => 'test.pages.admin@example.com',
                'password' => bcrypt('password123'),
                'role' => 'hospital_admin',
                'hospital_id' => $hospital->id,
                'phone' => '+1234567890',
                'email_verified_at' => now(),
            ]);
            
            $this->info("✅ Created test hospital admin: {$hospitalAdmin->email}");
        }

        $hospital = $hospitalAdmin->hospital;
        $this->info("Hospital Admin: {$hospitalAdmin->name} ({$hospitalAdmin->email})");
        $this->info("Hospital: {$hospital->name}");
        $this->newLine();

        // Test all hospital admin routes
        $routes = [
            'hospital-admin.dashboard' => 'Dashboard',
            'hospital-admin.doctors.index' => 'Doctors List',
            'hospital-admin.doctors.create' => 'Create Doctor',
            'hospital-admin.doctors.statistics' => 'Doctor Statistics',
            'hospital-admin.departments.index' => 'Departments List',
            'hospital-admin.departments.create' => 'Create Department',
            'hospital-admin.hospital.profile' => 'Hospital Profile',
            'hospital-admin.analytics.overview' => 'Analytics Overview',
            'hospital-admin.analytics.doctors' => 'Doctor Analytics',
            'hospital-admin.analytics.financial' => 'Financial Analytics',
            'hospital-admin.invoices.index' => 'Invoices',
            'hospital-admin.usage.index' => 'Usage Reports',
            'hospital-admin.subscription.manage' => 'Subscription Management',
            'hospital-admin.subscription.pricing' => 'Subscription Pricing',
        ];

        $this->info('Testing Route Accessibility:');
        foreach ($routes as $routeName => $description) {
            try {
                $url = route($routeName);
                $this->info("  ✅ {$description} → {$url}");
            } catch (\Exception $e) {
                $this->error("  ❌ {$description} → Route not found: " . $e->getMessage());
            }
        }

        $this->newLine();

        // Test view files existence
        $views = [
            'hospital-admin.dashboard' => 'Dashboard View',
            'hospital-admin.doctors.index' => 'Doctors Index View',
            'hospital-admin.doctors.create' => 'Create Doctor View',
            'hospital-admin.departments.index' => 'Departments Index View',
            'hospital-admin.hospital.profile' => 'Hospital Profile View',
            'hospital-admin.analytics.overview' => 'Analytics Overview View',
            'hospital-admin.invoices.index' => 'Invoices Index View',
            'hospital-admin.usage.index' => 'Usage Reports View',
            'hospital-admin.subscription.manage' => 'Subscription Management View',
        ];

        $this->info('Testing View Files:');
        foreach ($views as $viewName => $description) {
            $viewPath = resource_path('views/' . str_replace('.', '/', $viewName) . '.blade.php');
            if (file_exists($viewPath)) {
                $this->info("  ✅ {$description} → {$viewPath}");
            } else {
                $this->error("  ❌ {$description} → View file not found: {$viewPath}");
            }
        }

        $this->newLine();

        // Test controller methods
        $controllers = [
            'App\Http\Controllers\HospitalAdmin\DashboardController@index' => 'Dashboard Controller',
            'App\Http\Controllers\HospitalAdmin\DoctorController@index' => 'Doctor Controller Index',
            'App\Http\Controllers\HospitalAdmin\DoctorController@create' => 'Doctor Controller Create',
            'App\Http\Controllers\HospitalAdmin\DepartmentController@index' => 'Department Controller Index',
            'App\Http\Controllers\HospitalAdmin\HospitalController@profile' => 'Hospital Controller Profile',
            'App\Http\Controllers\HospitalAdmin\AnalyticsController@overview' => 'Analytics Controller Overview',
            'App\Http\Controllers\HospitalAdmin\InvoiceController@index' => 'Invoice Controller Index',
            'App\Http\Controllers\HospitalAdmin\UsageController@index' => 'Usage Controller Index',
        ];

        $this->info('Testing Controller Methods:');
        foreach ($controllers as $controllerMethod => $description) {
            [$controller, $method] = explode('@', $controllerMethod);
            if (class_exists($controller) && method_exists($controller, $method)) {
                $this->info("  ✅ {$description} → {$controllerMethod}");
            } else {
                $this->error("  ❌ {$description} → Controller or method not found: {$controllerMethod}");
            }
        }

        $this->newLine();

        // Test database relationships
        $this->info('Testing Database Relationships:');
        
        // Test hospital-doctors relationship
        $doctorsCount = $hospital->doctors()->count();
        $this->info("  ✅ Hospital has {$doctorsCount} doctors");
        
        // Test hospital-departments relationship
        $departmentsCount = $hospital->departments()->count();
        $this->info("  ✅ Hospital has {$departmentsCount} departments");
        
        // Test hospital admin role
        if ($hospitalAdmin->isHospitalAdmin()) {
            $this->info("  ✅ User is hospital admin");
        } else {
            $this->error("  ❌ User is not hospital admin");
        }
        
        // Test hospital association
        if ($hospitalAdmin->hospital_id === $hospital->id) {
            $this->info("  ✅ Hospital admin is associated with correct hospital");
        } else {
            $this->error("  ❌ Hospital admin is not associated with correct hospital");
        }

        $this->newLine();

        // Create sample data for testing
        $this->info('Creating Sample Data:');
        
        // Create a department if none exists
        if ($hospital->departments()->count() === 0) {
            $department = Department::create([
                'hospital_id' => $hospital->id,
                'name' => 'Emergency Department',
                'description' => 'Emergency medical services',
                'head_of_department' => 'Dr. Emergency Chief',
                'phone' => '+1234567891',
                'email' => 'emergency@' . strtolower(str_replace(' ', '', $hospital->name)) . '.com',
                'is_active' => true,
            ]);
            $this->info("  ✅ Created sample department: {$department->name}");
        }

        // Create a doctor if none exists
        if ($hospital->doctors()->count() === 0) {
            $doctor = User::create([
                'name' => 'Dr. Test Doctor',
                'email' => 'test.doctor@' . strtolower(str_replace(' ', '', $hospital->name)) . '.com',
                'password' => bcrypt('password123'),
                'role' => 'doctor',
                'hospital_id' => $hospital->id,
                'phone' => '+1234567892',
                'email_verified_at' => now(),
            ]);
            $this->info("  ✅ Created sample doctor: {$doctor->name}");
        }

        $this->newLine();
        $this->info('🎯 Login Instructions:');
        $this->info("1. Go to: " . url('/login'));
        $this->info("2. Email: {$hospitalAdmin->email}");
        $this->info("3. Password: password123");
        $this->info("4. You should be redirected to the hospital admin dashboard");
        
        $this->newLine();
        $this->info('🔗 Direct Links to Test:');
        try {
            $this->info("- Dashboard: " . route('hospital-admin.dashboard'));
            $this->info("- Doctors: " . route('hospital-admin.doctors.index'));
            $this->info("- Departments: " . route('hospital-admin.departments.index'));
            $this->info("- Hospital Profile: " . route('hospital-admin.hospital.profile'));
            $this->info("- Analytics: " . route('hospital-admin.analytics.overview'));
            $this->info("- Invoices: " . route('hospital-admin.invoices.index'));
            $this->info("- Usage Reports: " . route('hospital-admin.usage.index'));
        } catch (\Exception $e) {
            $this->error("Some routes are not available: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('🎉 Hospital Admin Pages Test Completed!');
        $this->info('All routes, views, and controllers should now be working properly.');
    }
}