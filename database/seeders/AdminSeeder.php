<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if it doesn't exist
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@medical.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Make sure the user is admin
        if (!$adminUser->is_admin) {
            $adminUser->update(['is_admin' => true]);
        }

        $this->command->info('Admin user created/updated: admin@medical.com (password: admin123)');

        // Also make the first user admin if they aren't already
        $firstUser = User::where('email', '!=', 'admin@medical.com')->first();
        if ($firstUser && !$firstUser->is_admin) {
            $firstUser->update(['is_admin' => true]);
            $this->command->info('Made first user admin: ' . $firstUser->email);
        }
    }
}