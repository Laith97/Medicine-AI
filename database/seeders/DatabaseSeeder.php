<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the notification type seeder
        $this->call([
            PermissionSeeder::class,
            NotificationTypeSeeder::class,
            SpecialtySeeder::class,
            PatientCasesTestSeeder::class,
            AnalyticsSeeder::class,
        ]);
    }
}
