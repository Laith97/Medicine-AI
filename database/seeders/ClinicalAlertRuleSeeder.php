<?php

namespace Database\Seeders;

use App\Models\ClinicalAlertRule;
use Illuminate\Database\Seeder;

class ClinicalAlertRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'name' => 'NEWS2 High Risk',
                'algorithm_type' => 'news2',
                'severity' => 'critical',
                'threshold_min' => 7.00,
                'threshold_max' => null,
                'notification_channels' => ['database', 'broadcast', 'sms'],
                'is_active' => true,
            ],
            [
                'name' => 'NEWS2 Medium Risk',
                'algorithm_type' => 'news2',
                'severity' => 'high',
                'threshold_min' => 5.00,
                'threshold_max' => 6.99,
                'notification_channels' => ['database', 'broadcast'],
                'is_active' => true,
            ],
            [
                'name' => 'Sepsis Risk (qSOFA)',
                'algorithm_type' => 'sepsis',
                'severity' => 'critical',
                'threshold_min' => 2.00,
                'threshold_max' => null,
                'notification_channels' => ['database', 'broadcast', 'sms'],
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            ClinicalAlertRule::updateOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}
