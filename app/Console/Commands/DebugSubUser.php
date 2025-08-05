<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DebugSubUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:sub-user {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug sub-user data and relationships';

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

        $this->info("=== User Debug Information ===");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $user->role],
                ['is_sub_user (field)', $user->is_sub_user ? 'true' : 'false'],
                ['parent_user_id', $user->parent_user_id ?? 'null'],
                ['sub_user_role', $user->sub_user_role ?? 'null'],
                ['isSubUser() method', $user->isSubUser() ? 'true' : 'false'],
                ['isDoctor() method', $user->isDoctor() ? 'true' : 'false'],
                ['isMainUser() method', $user->isMainUser() ? 'true' : 'false'],
            ]
        );

        if ($user->parentUser) {
            $this->info("\n=== Parent User Information ===");
            $parent = $user->parentUser;
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $parent->id],
                    ['Name', $parent->name],
                    ['Email', $parent->email],
                    ['Role', $parent->role],
                    ['Has Doctor Profile', $parent->doctor ? 'Yes' : 'No'],
                    ['Doctor Active', $parent->doctor ? ($parent->doctor->is_active ? 'Yes' : 'No') : 'N/A'],
                ]
            );
        } else {
            $this->warn("No parent user found.");
        }

        if ($user->doctor) {
            $this->info("\n=== User's Doctor Profile ===");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Doctor ID', $user->doctor->id],
                    ['Is Active', $user->doctor->is_active ? 'Yes' : 'No'],
                ]
            );
        } else {
            $this->info("\nUser has no doctor profile (expected for sub-users).");
        }

        if ($user->isSubUser()) {
            $this->info("\n=== Sub-User Permissions ===");
            if ($user->permissions->count() > 0) {
                $permissionData = $user->permissions->map(function ($permission) {
                    return [
                        $permission->name,
                        $permission->display_name,
                        $permission->category,
                        $permission->is_restricted ? 'Yes' : 'No'
                    ];
                })->toArray();
                
                $this->table(
                    ['Name', 'Display Name', 'Category', 'Restricted'],
                    $permissionData
                );
            } else {
                $this->warn("No permissions assigned to this sub-user.");
            }
        }

        // Test middleware logic
        $this->info("\n=== Middleware Logic Test ===");
        if ($user->isSubUser()) {
            $parentUser = $user->parentUser;
            if (!$parentUser) {
                $this->error("❌ Parent user not found - middleware would fail");
            } elseif (!$parentUser->isDoctor()) {
                $this->error("❌ Parent user is not a doctor - middleware would fail");
            } elseif (!$parentUser->doctor) {
                $this->error("❌ Parent user has no doctor profile - middleware would fail");
            } elseif (!$parentUser->doctor->is_active) {
                $this->error("❌ Parent doctor profile is inactive - middleware would fail");
            } else {
                $this->info("✅ Sub-user should pass doctor middleware");
            }
        } else {
            if (!$user->isDoctor()) {
                $this->error("❌ User is not a doctor - middleware would fail");
            } elseif (!$user->doctor) {
                $this->error("❌ User has no doctor profile - middleware would fail");
            } elseif (!$user->doctor->is_active) {
                $this->error("❌ Doctor profile is inactive - middleware would fail");
            } else {
                $this->info("✅ Main user should pass doctor middleware");
            }
        }

        return 0;
    }
}