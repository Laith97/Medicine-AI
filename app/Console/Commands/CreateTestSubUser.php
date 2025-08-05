<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestSubUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-sub-user {parent_email} {--name=Test Secretary} {--email=secretary@test.com} {--role=secretary}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test sub-user for testing the role-based access control system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $parentEmail = $this->argument('parent_email');
        $name = $this->option('name');
        $email = $this->option('email');
        $role = $this->option('role');

        // Find the parent user (doctor)
        $parentUser = User::where('email', $parentEmail)->first();
        
        if (!$parentUser) {
            $this->error("Parent user with email '{$parentEmail}' not found.");
            return 1;
        }

        if (!$parentUser->isDoctor()) {
            $this->error("Parent user must be a doctor to create sub-users.");
            return 1;
        }

        // Ensure parent has a doctor profile
        if (!$parentUser->doctor) {
            $this->error("Parent user must have a doctor profile to create sub-users.");
            $this->info("You can create a doctor profile through the application interface.");
            return 1;
        }

        // Check if sub-user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email '{$email}' already exists.");
            return 1;
        }

        // Create the sub-user
        $subUser = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'doctor', // Sub-users inherit parent's role for system compatibility
            'parent_user_id' => $parentUser->id,
            'sub_user_role' => $role,
            'is_sub_user' => true,
        ]);

        // Assign some basic permissions
        $basicPermissions = Permission::whereIn('name', [
            'dashboard',
            'settings',
            'cases',
            'appointments',
            'reviews'
        ])->get();

        foreach ($basicPermissions as $permission) {
            $subUser->grantPermission($permission, $parentUser);
        }

        $this->info("Sub-user created successfully!");
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

        $this->info("You can now test the sub-user by logging in with:");
        $this->info("Email: {$email}");
        $this->info("Password: password123");

        return 0;
    }
}