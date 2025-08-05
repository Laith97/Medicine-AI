<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Console\Command;

class GrantPermissionsToSubUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grant:permissions {email} {--all : Grant all non-restricted permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant permissions to a sub-user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $grantAll = $this->option('all');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        if (!$user->isSubUser()) {
            $this->error("User is not a sub-user.");
            return 1;
        }

        $parentUser = $user->parentUser;
        if (!$parentUser) {
            $this->error("Parent user not found.");
            return 1;
        }

        if ($grantAll) {
            // Grant all non-restricted permissions
            $permissions = Permission::where('is_restricted', false)->get();
            
            $this->info("Granting all non-restricted permissions to {$user->name}...");
            
            foreach ($permissions as $permission) {
                if (!$user->hasPermission($permission->name)) {
                    $granted = $user->grantPermission($permission, $parentUser);
                    if ($granted) {
                        $this->info("✅ Granted: {$permission->display_name}");
                    } else {
                        $this->warn("⚠️  Failed to grant: {$permission->display_name}");
                    }
                } else {
                    $this->info("⏭️  Already has: {$permission->display_name}");
                }
            }
        } else {
            // Interactive permission granting
            $availablePermissions = Permission::where('is_restricted', false)
                ->whereNotIn('id', $user->permissions->pluck('id'))
                ->get();
            
            if ($availablePermissions->isEmpty()) {
                $this->info("User already has all available permissions.");
                return 0;
            }
            
            $this->info("Available permissions to grant:");
            foreach ($availablePermissions as $index => $permission) {
                $this->info(($index + 1) . ". {$permission->display_name} ({$permission->category})");
            }
            
            $choice = $this->ask('Enter permission numbers to grant (comma-separated) or "all" for all');
            
            if (strtolower($choice) === 'all') {
                foreach ($availablePermissions as $permission) {
                    $user->grantPermission($permission, $parentUser);
                    $this->info("✅ Granted: {$permission->display_name}");
                }
            } else {
                $choices = array_map('trim', explode(',', $choice));
                foreach ($choices as $choiceIndex) {
                    if (is_numeric($choiceIndex) && isset($availablePermissions[$choiceIndex - 1])) {
                        $permission = $availablePermissions[$choiceIndex - 1];
                        $user->grantPermission($permission, $parentUser);
                        $this->info("✅ Granted: {$permission->display_name}");
                    }
                }
            }
        }

        $this->info("\nCurrent permissions for {$user->name}:");
        $user->refresh();
        foreach ($user->permissions as $permission) {
            $this->info("• {$permission->display_name} ({$permission->category})");
        }

        return 0;
    }
}