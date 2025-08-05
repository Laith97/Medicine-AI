<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateSubUserManual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:sub-user-manual {parent_email} {--name=Test Secretary} {--email=secretary@test.com} {--role=secretary}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually create a sub-user with detailed debugging';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $parentEmail = $this->argument('parent_email');
        $name = $this->option('name');
        $email = $this->option('email');
        $role = $this->option('role');

        $this->info("Creating sub-user with the following details:");
        $this->info("Parent Email: {$parentEmail}");
        $this->info("Name: {$name}");
        $this->info("Email: {$email}");
        $this->info("Role: {$role}");

        // Find the parent user (doctor)
        $parentUser = User::where('email', $parentEmail)->first();
        
        if (!$parentUser) {
            $this->error("Parent user with email '{$parentEmail}' not found.");
            return 1;
        }

        $this->info("✅ Parent user found: {$parentUser->name} (ID: {$parentUser->id})");

        if (!$parentUser->isDoctor()) {
            $this->error("Parent user must be a doctor to create sub-users.");
            return 1;
        }

        $this->info("✅ Parent user is a doctor");

        // Ensure parent has a doctor profile
        if (!$parentUser->doctor) {
            $this->error("Parent user must have a doctor profile to create sub-users.");
            return 1;
        }

        $this->info("✅ Parent user has doctor profile (ID: {$parentUser->doctor->id})");

        if (!$parentUser->doctor->is_active) {
            $this->error("Parent doctor profile must be active.");
            return 1;
        }

        $this->info("✅ Parent doctor profile is active");

        // Check if sub-user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email '{$email}' already exists.");
            return 1;
        }

        $this->info("✅ Email is available");

        // Create the sub-user using DB transaction
        DB::beginTransaction();
        
        try {
            $this->info("Creating sub-user...");
            
            $subUser = new User();
            $subUser->name = $name;
            $subUser->email = $email;
            $subUser->password = Hash::make('password123');
            $subUser->role = 'doctor'; // Sub-users inherit parent's role for system compatibility
            $subUser->parent_user_id = $parentUser->id;
            $subUser->sub_user_role = $role;
            $subUser->is_sub_user = true;
            $subUser->save();

            $this->info("✅ Sub-user created with ID: {$subUser->id}");

            // Verify the sub-user was created correctly
            $subUser->refresh();
            $this->info("Verifying sub-user data:");
            $this->info("- is_sub_user: " . ($subUser->is_sub_user ? 'true' : 'false'));
            $this->info("- parent_user_id: " . $subUser->parent_user_id);
            $this->info("- sub_user_role: " . $subUser->sub_user_role);
            $this->info("- isSubUser() method: " . ($subUser->isSubUser() ? 'true' : 'false'));

            // Assign some basic permissions
            $basicPermissions = Permission::whereIn('name', [
                'dashboard',
                'settings',
                'cases',
                'appointments',
                'reviews'
            ])->get();

            $this->info("Assigning permissions...");
            foreach ($basicPermissions as $permission) {
                $granted = $subUser->grantPermission($permission, $parentUser);
                $this->info("- {$permission->display_name}: " . ($granted ? 'granted' : 'failed'));
            }

            DB::commit();

            $this->info("✅ Sub-user created successfully!");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $subUser->name],
                    ['Email', $subUser->email],
                    ['Role', $subUser->sub_user_role],
                    ['Parent', $parentUser->name . ' (' . $parentUser->email . ')'],
                    ['Permissions', $subUser->permissions->pluck('display_name')->join(', ')],
                    ['Password', 'password123'],
                ]
            );

            // Test middleware logic
            $this->info("\n=== Testing Middleware Logic ===");
            if ($subUser->isSubUser()) {
                $parentUser = $subUser->parentUser;
                if ($parentUser && $parentUser->isDoctor() && $parentUser->doctor && $parentUser->doctor->is_active) {
                    $this->info("✅ Sub-user should pass doctor middleware");
                } else {
                    $this->error("❌ Sub-user would fail doctor middleware");
                }
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollback();
            $this->error("Failed to create sub-user: " . $e->getMessage());
            return 1;
        }
    }
}