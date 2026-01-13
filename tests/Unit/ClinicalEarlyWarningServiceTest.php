<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ClinicalEarlyWarningService;

class ClinicalEarlyWarningServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClinicalEarlyWarningService();
    }

    public function test_calculate_news2_low_risk()
    {
        $vitals = [
            'respiration_rate' => 15,
            'oxygen_saturation' => 98,
            'systolic_bp' => 120,
            'heart_rate' => 70,
            'temperature' => 37.0,
            'avpu' => 'Alert'
        ];

        $result = $this->service->calculateNEWS2($vitals);

        $this->assertEquals(0, $result['score']);
        $this->assertEquals('low', $result['risk_level']);
    }

    public function test_calculate_news2_high_risk()
    {
        $vitals = [
            'respiration_rate' => 26, // 3 points
            'oxygen_saturation' => 91, // 3 points
            'systolic_bp' => 90,  // 3 points
            'heart_rate' => 135, // 3 points
            'temperature' => 38.5, // 1 point
            'avpu' => 'Voice' // 3 points
        ];

        $result = $this->service->calculateNEWS2($vitals);

        $this->assertGreaterThan(7, $result['score']);
        $this->assertEquals('high', $result['risk_level']);
    }

    public function test_calculate_qsofa_sepsis()
    {
        $vitals = [
            'respiration_rate' => 24, // 1 point
            'systolic_bp' => 95, // 1 point
            'avpu' => 'Pain' // 1 point
        ];

        $result = $this->service->calculateQSOFA($vitals);

        $this->assertEquals(3, $result['score']);
        $this->assertEquals('high', $result['risk_level']);
    }
}
