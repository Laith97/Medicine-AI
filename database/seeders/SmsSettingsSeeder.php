<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SmsSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Set default SMS provider if not already set
        if (!SystemSetting::where('key', 'sms_provider')->exists()) {
            SystemSetting::create([
                'key' => 'sms_provider',
                'value' => 'log',
                'type' => 'string',
                'description' => 'Active SMS provider for the system (twilio, plivo, messagebird, log)'
            ]);
        }
    }
}
