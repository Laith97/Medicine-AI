<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Hospital;
use App\Models\Doctor;
use App\Helpers\MenuHelper;

class TestHospitalAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:hospital-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test hospital admin functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Hospital Admin Functionality...');
        $this->newLine();

        // Test 1: Check if hospital admin exists
        $hospitalAdmin = User::where('role', 'hospital_admin')->first();
        
        if (!$hospitalAdmin) {
            $this->error('❌ No hospital admin found. Run the seeder first: php artisan db:seed --class=HospitalAdminSeeder');
            return;
        }

        $this->info("✅ Hospital Admin found: {$hospitalAdmin->name} ({$hospitalAdmin->email})");

        // Test 2: Check hospital association
        if ($hospitalAdmin->hospital) {
            $this->info("✅ Hospital association: {$hospitalAdmin->hospital->name}");
        } else {
            $this->error('❌ Hospital admin has no hospital association');
            return;
        }

        // Test 3: Check role methods
        $this->info('Testing role methods:');
        $this->info("  - isHospitalAdmin(): " . ($hospitalAdmin->isHospitalAdmin() ? '✅ true' : '❌ false'));
        $this->info("  - isDoctor(): " . ($hospitalAdmin->isDoctor() ? '❌ true (should be false)' : '✅ false'));
        $this->info("  - isPatient(): " . ($hospitalAdmin->isPatient() ? '❌ true (should be false)' : '✅ false'));
        $this->info("  - isPaymentResponsible(): " . ($hospitalAdmin->isPaymentResponsible() ? '✅ true' : '❌ false'));

        // Test 4: Check managed doctors
        $managedDoctors = $hospitalAdmin->managedDoctors()->get();
        $this->info("✅ Managed doctors count: {$managedDoctors->count()}");

        if ($managedDoctors->count() > 0) {
            foreach ($managedDoctors as $doctor) {
                $canManage = $hospitalAdmin->canManageDoctor($doctor) ? '✅' : '❌';
                $this->info("  - {$doctor->name}: {$canManage}");
            }
        }

        // Test 5: Check hospital statistics
        $statistics = $hospitalAdmin->getHospitalAdminStatistics();
        $this->info('Hospital Statistics:');
        $this->info("  - Total doctors: {$statistics['total_doctors']}");
        $this->info("  - Active doctors: {$statistics['active_doctors']}");
        $this->info("  - Total appointments: {$statistics['total_appointments']}");
        $this->info("  - Average rating: " . number_format($statistics['average_rating'], 1));

        // Test 6: Check menu items
        $menuItems = MenuHelper::getMenuItems($hospitalAdmin);
        $this->info("✅ Menu items count: " . count($menuItems));
        
        $expectedMenus = ['Dashboard', 'Manage Doctors', 'Hospital Settings', 'Analytics', 'Billing'];
        foreach ($expectedMenus as $expectedMenu) {
            $found = collect($menuItems)->contains('name', $expectedMenu);
            $status = $found ? '✅' : '❌';
            $this->info("  - {$expectedMenu}: {$status}");
        }

        // Test 7: Check role display
        $roleDisplay = MenuHelper::getUserRoleDisplay($hospitalAdmin);
        $this->info("✅ Role display: {$roleDisplay}");

        // Test 8: Test doctor under hospital (if exists)
        $hospitalDoctor = $hospitalAdmin->hospital->doctors()->first();
        if ($hospitalDoctor) {
            $this->newLine();
            $this->info("Testing hospital doctor: {$hospitalDoctor->name}");
            $this->info("  - isPaymentResponsible(): " . ($hospitalDoctor->isPaymentResponsible() ? '❌ true (should be false)' : '✅ false'));
            $this->info("  - hospital_id: " . ($hospitalDoctor->hospital_id ? "✅ {$hospitalDoctor->hospital_id}" : '❌ null'));
            
            $paymentResponsible = $hospitalDoctor->getPaymentResponsibleUser();
            if ($paymentResponsible && $paymentResponsible->id === $hospitalAdmin->id) {
                $this->info("  - Payment responsible user: ✅ Hospital Admin");
            } else {
                $this->error("  - Payment responsible user: ❌ Not hospital admin");
            }

            // Check doctor menu (should not have billing)
            $doctorMenuItems = MenuHelper::getMenuItems($hospitalDoctor);
            $hasBilling = collect($doctorMenuItems)->contains(function ($item) {
                return isset($item['items']) && collect($item['items'])->contains('name', 'Billing & Invoices');
            });
            $this->info("  - Has billing menu: " . ($hasBilling ? '❌ true (should be false)' : '✅ false'));
        }

        $this->newLine();
        $this->info('🎉 Hospital Admin functionality test completed!');
        $this->newLine();
        $this->info('Next steps:');
        $this->info('1. Visit /hospital-admin/dashboard to see the hospital admin dashboard');
        $this->info('2. Login as hospital admin: ' . $hospitalAdmin->email);
        $this->info('3. Test doctor management features');
        $this->info('4. Test billing and subscription management');
    }
}