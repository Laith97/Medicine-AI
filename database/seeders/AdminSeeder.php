<?php

namespace Database\Seeders;

use App\Models\Admin;
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
        $adminUser = Admin::firstOrCreate(
            ['email' => 'admin@medical.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created/updated: admin@medical.com (password: admin123)');
    }
}