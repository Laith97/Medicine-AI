<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\ClinicalDecisionRule;
use App\Models\ClinicalIndicator;
use App\Services\ClinicalDecisionSupportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\ClinicalAlertTriggered;

class ClinicalDecisionSupportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClinicalDecisionSupportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClinicalDecisionSupportService();
    }

    public function test_it_triggers_alert_when_condition_is_met()
    {
        Event::fake();

        $patient = User::factory()->create();
        
        ClinicalDecisionRule::create([
            'name' => 'High Blood Pressure Alert',
            'trigger_conditions' => [
                [
                    'type' => 'vital_sign',
                    'indicator' => 'Systolic BP',
                    'operator' => '>',
                    'value' => 140
                ]
            ],
            'action_type' => 'alert',
            'action_payload' => ['message' => 'High BP detected'],
            'is_active' => true
        ]);

        ClinicalIndicator::create([
            'patient_id' => $patient->id,
            'type' => 'vital_sign',
            'name' => 'Systolic BP',
            'value' => 150,
            'unit' => 'mmHg',
            'measured_at' => now()
        ]);

        $alerts = $this->service->evaluateRules($patient->id);

        $this->assertCount(1, $alerts);
        $this->assertEquals('High BP detected', $alerts[0]['message']);
        Event::assertDispatched(ClinicalAlertTriggered::class);
    }

    public function test_it_does_not_trigger_alert_when_condition_is_not_met()
    {
        Event::fake();

        $patient = User::factory()->create();
        
        ClinicalDecisionRule::create([
            'name' => 'High Blood Pressure Alert',
            'trigger_conditions' => [
                [
                    'type' => 'vital_sign',
                    'indicator' => 'Systolic BP',
                    'operator' => '>',
                    'value' => 140
                ]
            ],
            'action_type' => 'alert',
            'action_payload' => ['message' => 'High BP detected'],
            'is_active' => true
        ]);

        ClinicalIndicator::create([
            'patient_id' => $patient->id,
            'type' => 'vital_sign',
            'name' => 'Systolic BP',
            'value' => 120,
            'unit' => 'mmHg',
            'measured_at' => now()
        ]);

        $alerts = $this->service->evaluateRules($patient->id);

        $this->assertCount(0, $alerts);
        Event::assertNotDispatched(ClinicalAlertTriggered::class);
    }
}
