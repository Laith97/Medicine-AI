<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Hospital;
use Illuminate\Support\Facades\Hash;

class TestCompleteHospitalAdminFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:complete-hospital-admin-flow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test complete hospital admin creation flow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Complete Hospital Admin Flow Test');
        $this->newLine();

        // Step 1: Verify system admin exists
        $systemAdmin = User::where('role', 'admin')->first();
        if (!$systemAdmin) {
            $this->error('❌ No system admin found');
            return;
        }
        $this->info("✅ System Admin: {$systemAdmin->name} ({$systemAdmin->email})");

        // Step 2: Show available hospitals
        $hospitals = Hospital::all();
        $this->info("✅ Available Hospitals: {$hospitals->count()}");
        foreach ($hospitals as $hospital) {
            $this->info("   - {$hospital->name} (ID: {$hospital->id})");
        }

        // Step 3: Test creating hospital admin with existing hospital
        $this->newLine();
        $this->info('🔨 Test 1: Creating hospital admin with existing hospital');
        
        $existingHospital = $hospitals->first();
        if ($existingHospital) {
            try {
                $hospitalAdmin1 = User::create([
                    'name' => 'Test Hospital Admin 1',
                    'email' => 'test.admin1@' . strtolower(str_replace(' ', '', $existingHospital->name)) . '.com',
                    'phone' => '+1555000001',
                    'password' => Hash::make('password123'),
                    'role' => 'hospital_admin',
                    'hospital_id' => $existingHospital->id,
                    'email_verified_at' => now(),
                ]);

                $hospitalAdmin1->setting()->create([
                    'specialty' => 'Hospital Administration',
                    'criterion' => 'CDC',
                ]);

                $this->info("✅ Created: {$hospitalAdmin1->name}");
                $this->info("   - Email: {$hospitalAdmin1->email}");
                $this->info("   - Hospital: {$hospitalAdmin1->hospital->name}");
                $this->info("   - Role: {$hospitalAdmin1->role}");
                $this->info("   - isHospitalAdmin(): " . ($hospitalAdmin1->isHospitalAdmin() ? 'true' : 'false'));
                $this->info("   - isAdmin(): " . ($hospitalAdmin1->isAdmin() ? 'true' : 'false'));

            } catch (\Exception $e) {
                $this->error("❌ Failed to create hospital admin: " . $e->getMessage());
            }
        }

        // Step 4: Test creating hospital admin with new hospital
        $this->newLine();
        $this->info('🔨 Test 2: Creating hospital admin with new hospital');
        
        try {
            // Create new hospital
            $newHospital = Hospital::create([
                'name' => 'Test Medical Center',
                'slug' => 'test-medical-center',
                'email' => 'info@testmedical.com',
                'phone' => '+1555000002',
                'address' => '123 Test Street, Test City',
                'is_active' => true,
            ]);

            $hospitalAdmin2 = User::create([
                'name' => 'Test Hospital Admin 2',
                'email' => 'admin@testmedical.com',
                'phone' => '+1555000003',
                'password' => Hash::make('password123'),
                'role' => 'hospital_admin',
                'hospital_id' => $newHospital->id,
                'email_verified_at' => now(),
            ]);

            $hospitalAdmin2->setting()->create([
                'specialty' => 'Hospital Administration',
                'criterion' => 'CDC',
            ]);

            $this->info("✅ Created Hospital: {$newHospital->name}");
            $this->info("✅ Created Admin: {$hospitalAdmin2->name}");
            $this->info("   - Email: {$hospitalAdmin2->email}");
            $this->info("   - Hospital: {$hospitalAdmin2->hospital->name}");
            $this->info("   - Role: {$hospitalAdmin2->role}");

        } catch (\Exception $e) {
            $this->error("❌ Failed to create hospital and admin: " . $e->getMessage());
        }

        // Step 5: Verify role distinctions
        $this->newLine();
        $this->info('🔍 Role Verification');
        
        $allUsers = User::with('hospital')->get();
        $roleStats = [
            'admin' => 0,
            'hospital_admin' => 0,
            'doctor' => 0,
            'patient' => 0
        ];

        foreach ($allUsers as $user) {
            $roleStats[$user->role]++;
        }

        $this->info('User Role Distribution:');
        foreach ($roleStats as $role => $count) {
            $this->info("   - {$role}: {$count} users");
        }

        // Step 6: Show hospital admin access
        $this->newLine();
        $this->info('🎯 Hospital Admin Access Test');
        
        $hospitalAdmins = User::where('role', 'hospital_admin')->with('hospital')->get();
        foreach ($hospitalAdmins as $admin) {
            $this->info("Hospital Admin: {$admin->name}");
            $this->info("   - Hospital: {$admin->hospital->name}");
            $this->info("   - Can manage hospital: " . ($admin->isHospitalAdmin() ? '✅ Yes' : '❌ No'));
            $this->info("   - Is system admin: " . ($admin->isAdmin() ? '❌ Yes (should be No)' : '✅ No'));
            $this->info("   - Payment responsible: " . ($admin->isPaymentResponsible() ? '✅ Yes' : '❌ No'));
        }

        // Step 7: Show form instructions
        $this->newLine();
        $this->info('📋 How to Create Hospital Admin via Web Form:');
        $this->info('1. Login as system admin: admin@medical.com / admin123');
        $this->info('2. Go to: /admin/users/create');
        $this->info('3. Fill in user details (name, email, phone, password)');
        $this->info('4. Select Role: "Hospital Admin"');
        $this->info('5. Select Hospital: Choose existing or "Create New Hospital"');
        $this->info('6. If creating new hospital, fill hospital details');
        $this->info('7. Click "Create User"');

        $this->newLine();
        $this->info('🎉 Complete Hospital Admin Flow Test Completed!');
        
        $this->newLine();
        $this->info('Current Login Credentials:');
        $this->info('System Admin: admin@medical.com / admin123');
        foreach ($hospitalAdmins as $admin) {
            $this->info("Hospital Admin: {$admin->email} / password123");
        }
    }
}