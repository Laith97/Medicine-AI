<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MonthlyInvoiceSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MonthlyInvoiceSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users (since admins are in separate table)
        $users = User::all();
        
        foreach ($users as $user) {
            MonthlyInvoiceSetting::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'monthly_amount' => rand(50, 200), // Random amount between $50-$200
                    'is_active' => true,
                    'grace_period_days' => 7, // 7 days grace period
                    'reminder_frequency_days' => 3, // Remind every 3 days
                    'restricted_pages' => [
                        'ask-ai',
                        'cases',
                        'dashboard'
                    ],
                    'is_restricted' => false,
                    'restriction_message' => null,
                ]
            );
        }
        
        $this->command->info('Monthly invoice settings created for ' . $users->count() . ' users.');
    }
}
