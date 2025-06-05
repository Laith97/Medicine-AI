<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

            // Create admin user
    User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('admin@123'), // Make sure to hash the password
        'is_admin' => true, // Assuming you have an 'is_admin' column
    ]);

        $this->call([
            SymptomsTableSeeder::class, // Add this line
        ]);
    }
}
