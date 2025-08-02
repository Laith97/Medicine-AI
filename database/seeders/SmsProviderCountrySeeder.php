<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SmsProviderCountry;

class SmsProviderCountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing assignments
        SmsProviderCountry::truncate();

        // Example assignments for demonstration
        // In a real scenario, admin would configure these through the web interface

        // Assign Middle Eastern countries to Unifonic (good for Arabic regions)
        $unifonicCountries = [
            ['code' => 'JO', 'name' => 'Jordan'],
            ['code' => 'SA', 'name' => 'Saudi Arabia'],
            ['code' => 'AE', 'name' => 'United Arab Emirates'],
            ['code' => 'KW', 'name' => 'Kuwait'],
            ['code' => 'QA', 'name' => 'Qatar'],
            ['code' => 'BH', 'name' => 'Bahrain'],
            ['code' => 'OM', 'name' => 'Oman'],
            ['code' => 'LB', 'name' => 'Lebanon'],
            ['code' => 'EG', 'name' => 'Egypt'],
        ];

        foreach ($unifonicCountries as $country) {
            SmsProviderCountry::create([
                'provider_key' => 'unifonic',
                'country_code' => $country['code'],
                'country_name' => $country['name'],
                'is_active' => true,
            ]);
        }

        // Assign European countries to MessageBird
        $messageBirdCountries = [
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'DE', 'name' => 'Germany'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'IT', 'name' => 'Italy'],
            ['code' => 'ES', 'name' => 'Spain'],
            ['code' => 'NL', 'name' => 'Netherlands'],
            ['code' => 'BE', 'name' => 'Belgium'],
            ['code' => 'CH', 'name' => 'Switzerland'],
            ['code' => 'AT', 'name' => 'Austria'],
            ['code' => 'SE', 'name' => 'Sweden'],
            ['code' => 'NO', 'name' => 'Norway'],
            ['code' => 'DK', 'name' => 'Denmark'],
            ['code' => 'FI', 'name' => 'Finland'],
        ];

        foreach ($messageBirdCountries as $country) {
            SmsProviderCountry::create([
                'provider_key' => 'messagebird',
                'country_code' => $country['code'],
                'country_name' => $country['name'],
                'is_active' => true,
            ]);
        }

        // Assign North American countries to Twilio
        $twilioCountries = [
            ['code' => 'US', 'name' => 'United States of America'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'MX', 'name' => 'Mexico'],
        ];

        foreach ($twilioCountries as $country) {
            SmsProviderCountry::create([
                'provider_key' => 'twilio',
                'country_code' => $country['code'],
                'country_name' => $country['name'],
                'is_active' => true,
            ]);
        }

        // Assign some Asian countries to SMS Gateway Hub
        $smsGatewayHubCountries = [
            ['code' => 'IN', 'name' => 'India'],
            ['code' => 'PK', 'name' => 'Pakistan'],
            ['code' => 'BD', 'name' => 'Bangladesh'],
            ['code' => 'LK', 'name' => 'Sri Lanka'],
            ['code' => 'PH', 'name' => 'Philippines'],
            ['code' => 'TH', 'name' => 'Thailand'],
            ['code' => 'MY', 'name' => 'Malaysia'],
            ['code' => 'SG', 'name' => 'Singapore'],
        ];

        foreach ($smsGatewayHubCountries as $country) {
            SmsProviderCountry::create([
                'provider_key' => 'smsgatewayhub',
                'country_code' => $country['code'],
                'country_name' => $country['name'],
                'is_active' => true,
            ]);
        }

        $this->command->info('SMS Provider Country assignments seeded successfully!');
        $this->command->info('- Unifonic: ' . count($unifonicCountries) . ' Middle Eastern countries');
        $this->command->info('- MessageBird: ' . count($messageBirdCountries) . ' European countries');
        $this->command->info('- Twilio: ' . count($twilioCountries) . ' North American countries');
        $this->command->info('- SMS Gateway Hub: ' . count($smsGatewayHubCountries) . ' Asian countries');
        $this->command->info('- Other countries will use the fallback provider (Plivo or Log)');
    }
}
