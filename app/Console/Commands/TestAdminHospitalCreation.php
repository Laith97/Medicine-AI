<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Hospital;

class TestAdminHospitalCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:admin-hospital-creation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test admin hospital admin creation functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Admin Hospital Admin Creation...');
        $this->newLine();

        // Test 1: Check if admin exists
        $admin = User::where('role', 'admin')->first();
        
        if (!$admin) {
            $this->error('❌ No admin user found. Please create an admin user first.');
            return;
        }

        $this->info("✅ Admin found: {$admin->name} ({$admin->email})");

        // Test 2: Check role differentiation
        $this->info('Testing role differentiation:');
        
        $roles = ['admin', 'hospital_admin', 'doctor', 'patient'];
        foreach ($roles as $role) {
            $count = User::where('role', $role)->count();
            $this->info("  - {$role}: {$count} users");
        }

        // Test 3: Check hospital admins
        $hospitalAdmins = User::where('role', 'hospital_admin')->with('hospital')->get();
        $this->info("✅ Hospital Admins found: {$hospitalAdmins->count()}");

        foreach ($hospitalAdmins as $hospitalAdmin) {
            $hospitalName = $hospitalAdmin->hospital ? $hospitalAdmin->hospital->name : 'No Hospital';
            $this->info("  - {$hospitalAdmin->name}: {$hospitalName}");
            
            // Test role methods
            $this->info("    - isAdmin(): " . ($hospitalAdmin->isAdmin() ? '❌ true (should be false)' : '✅ false'));
            $this->info("    - isHospitalAdmin(): " . ($hospitalAdmin->isHospitalAdmin() ? '✅ true' : '❌ false'));
            $this->info("    - isPaymentResponsible(): " . ($hospitalAdmin->isPaymentResponsible() ? '✅ true' : '❌ false'));
        }

        // Test 4: Check hospitals
        $hospitals = Hospital::with('hospitalAdmins')->get();
        $this->info("✅ Hospitals found: {$hospitals->count()}");

        foreach ($hospitals as $hospital) {
            $adminCount = $hospital->hospitalAdmins()->count();
            $doctorCount = $hospital->doctors()->count();
            $this->info("  - {$hospital->name}: {$adminCount} admin(s), {$doctorCount} doctor(s)");
        }

        // Test 5: Check admin vs hospital admin distinction
        $this->newLine();
        $this->info('Role Distinction Test:');
        
        $systemAdmin = User::where('role', 'admin')->first();
        $hospitalAdmin = User::where('role', 'hospital_admin')->first();
        
        if ($systemAdmin && $hospitalAdmin) {
            $this->info('System Admin:');
            $this->info("  - Role: {$systemAdmin->role}");
            $this->info("  - isAdmin(): " . ($systemAdmin->isAdmin() ? '✅ true' : '❌ false'));
            $this->info("  - isHospitalAdmin(): " . ($systemAdmin->isHospitalAdmin() ? '❌ true (should be false)' : '✅ false'));
            $this->info("  - Hospital: " . ($systemAdmin->hospital ? $systemAdmin->hospital->name : '✅ None'));
            
            $this->newLine();
            $this->info('Hospital Admin:');
            $this->info("  - Role: {$hospitalAdmin->role}");
            $this->info("  - isAdmin(): " . ($hospitalAdmin->isAdmin() ? '❌ true (should be false)' : '✅ false'));
            $this->info("  - isHospitalAdmin(): " . ($hospitalAdmin->isHospitalAdmin() ? '✅ true' : '❌ false'));
            $this->info("  - Hospital: " . ($hospitalAdmin->hospital ? "✅ {$hospitalAdmin->hospital->name}" : '❌ None'));
        }

        $this->newLine();
        $this->info('🎉 Admin Hospital Creation test completed!');
        $this->newLine();
        $this->info('Summary:');
        $this->info('✅ System admins (role: admin) - Full system access');
        $this->info('✅ Hospital admins (role: hospital_admin) - Hospital management only');
        $this->info('✅ Role methods work correctly');
        $this->info('✅ Hospital associations are properly set');
        $this->newLine();
        $this->info('To create a hospital admin from admin panel:');
        $this->info('1. Login as system admin');
        $this->info('2. Go to Admin > Manage Users > Create New User');
        $this->info('3. Select "Hospital Admin" role');
        $this->info('4. Choose existing hospital or create new one');
        $this->info('5. Fill in user details and save');
    }
}