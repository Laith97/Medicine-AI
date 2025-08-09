<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Hospital;
use Illuminate\Support\Facades\Hash;

class CreateHospitalAdminDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:create-hospital-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate creating a hospital admin from system admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Hospital Admin Creation Demo');
        $this->newLine();

        // Step 1: Show system admin
        $systemAdmin = User::where('role', 'admin')->first();
        if (!$systemAdmin) {
            $this->error('❌ No system admin found. Please create one first.');
            return;
        }

        $this->info("✅ System Admin: {$systemAdmin->name} ({$systemAdmin->email})");
        $this->info("   - Role: {$systemAdmin->role}");
        $this->info("   - Can create hospital admins: " . ($systemAdmin->isAdmin() ? '✅ Yes' : '❌ No'));
        $this->newLine();

        // Step 2: Show available hospitals
        $hospitals = Hospital::all();
        $this->info("📋 Available Hospitals ({$hospitals->count()}):");
        foreach ($hospitals as $hospital) {
            $adminCount = $hospital->hospitalAdmins()->count();
            $this->info("   - {$hospital->name} ({$adminCount} admin(s))");
        }
        $this->newLine();

        // Step 3: Create a new hospital admin
        $this->info("🔨 Creating new hospital admin...");
        
        // Create a new hospital for demo
        $newHospital = Hospital::create([
            'name' => 'Metro Medical Center',
            'slug' => 'metro-medical-center',
            'email' => 'info@metromedical.com',
            'phone' => '+1-555-0199',
            'address' => '456 Healthcare Ave, Medical District',
            'is_active' => true,
        ]);

        $this->info("✅ Created new hospital: {$newHospital->name}");

        // Create hospital admin
        $hospitalAdmin = User::create([
            'name' => 'Dr. Emily Wilson',
            'email' => 'emily.wilson@metromedical.com',
            'phone' => '+1-555-0188',
            'password' => Hash::make('password123'),
            'role' => 'hospital_admin',
            'hospital_id' => $newHospital->id,
            'email_verified_at' => now(),
        ]);

        // Create settings for hospital admin
        $hospitalAdmin->setting()->create([
            'specialty' => 'Hospital Administration',
            'criterion' => 'CDC',
        ]);

        $this->info("✅ Created hospital admin: {$hospitalAdmin->name}");
        $this->info("   - Email: {$hospitalAdmin->email}");
        $this->info("   - Password: password123");
        $this->info("   - Role: {$hospitalAdmin->role}");
        $this->info("   - Hospital: {$hospitalAdmin->hospital->name}");
        $this->newLine();

        // Step 4: Show role distinctions
        $this->info("🔍 Role Distinctions:");
        $this->newLine();

        $this->info("System Admin ({$systemAdmin->name}):");
        $this->info("   ✅ Can access admin panel");
        $this->info("   ✅ Can create/manage all users");
        $this->info("   ✅ Can create/manage hospitals");
        $this->info("   ✅ Full system access");
        $this->info("   ❌ Not tied to any hospital");
        $this->newLine();

        $this->info("Hospital Admin ({$hospitalAdmin->name}):");
        $this->info("   ❌ Cannot access system admin panel");
        $this->info("   ✅ Can manage doctors in their hospital");
        $this->info("   ✅ Can view hospital analytics");
        $this->info("   ✅ Can manage hospital settings");
        $this->info("   ✅ Tied to: {$hospitalAdmin->hospital->name}");
        $this->newLine();

        // Step 5: Show menu differences
        $this->info("📱 Menu Access Differences:");
        $this->newLine();

        $this->info("System Admin Menu:");
        $this->info("   - Admin Dashboard");
        $this->info("   - Manage Users");
        $this->info("   - System Settings");
        $this->info("   - All Hospitals Overview");
        $this->newLine();

        $this->info("Hospital Admin Menu:");
        $this->info("   - Hospital Dashboard");
        $this->info("   - Manage Doctors");
        $this->info("   - Hospital Settings");
        $this->info("   - Analytics & Reports");
        $this->info("   - Billing & Subscription");
        $this->newLine();

        // Step 6: Show how to identify them
        $this->info("🎯 How to Identify User Types:");
        $this->newLine();

        $this->info("In Code:");
        $this->info("   \$user->isAdmin()         // System admin");
        $this->info("   \$user->isHospitalAdmin() // Hospital admin");
        $this->info("   \$user->isDoctor()        // Doctor");
        $this->info("   \$user->isPatient()       // Patient");
        $this->newLine();

        $this->info("In Database:");
        $this->info("   role = 'admin'         // System admin");
        $this->info("   role = 'hospital_admin' // Hospital admin");
        $this->info("   hospital_id IS NULL    // System admin");
        $this->info("   hospital_id IS NOT NULL // Hospital admin/doctor");
        $this->newLine();

        $this->info("🎉 Demo completed successfully!");
        $this->newLine();
        
        $this->info("Login Credentials:");
        $this->info("System Admin: admin@medical.com / admin123");
        $this->info("Hospital Admin: emily.wilson@metromedical.com / password123");
    }
}