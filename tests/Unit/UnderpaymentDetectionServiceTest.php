<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Claim;
use App\Models\User;
use App\Services\UnderpaymentDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnderpaymentDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UnderpaymentDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UnderpaymentDetectionService::class);
    }

    public function test_calculate_variance()
    {
        $expected = 120.00;
        $paid = 95.00;
        $variance = $this->service->calculateVariance($expected, $paid);

        $this->assertEquals(25.00, $variance);
    }

    public function test_calculate_variance_percentage()
    {
        $expected = 120.00;
        $paid = 95.00;
        $percentage = $this->service->calculateVariancePercentage($expected, $paid);

        $this->assertEquals(20.83, round($percentage, 2));
    }

    public function test_is_underpayment_above_threshold()
    {
        $user = User::factory()->create();
        $claim = Claim::create([
            'claim_id' => 'TEST-001',
            'patient_id' => $user->id,
            'expected_amount' => 120.00,
            'paid_amount' => 95.00, // 20.83% variance
        ]);

        $isUnderpayment = $this->service->isUnderpayment($claim, 10.0);

        $this->assertTrue($isUnderpayment);
    }

    public function test_is_underpayment_below_threshold()
    {
        $user = User::factory()->create();
        $claim = Claim::create([
            'claim_id' => 'TEST-002',
            'patient_id' => $user->id,
            'expected_amount' => 120.00,
            'paid_amount' => 110.00, // 8.33% variance
        ]);

        $isUnderpayment = $this->service->isUnderpayment($claim, 10.0);

        $this->assertFalse($isUnderpayment);
    }

    public function test_get_underpayment_data()
    {
        $user = User::factory()->create();
        $claim = Claim::create([
            'claim_id' => 'TEST-003',
            'patient_id' => $user->id,
            'expected_amount' => 120.00,
            'paid_amount' => 95.00,
        ]);

        $data = $this->service->getUnderpaymentData($claim);

        $this->assertEquals([
            'claim_id' => 'TEST-003',
            'expected' => 120.00,
            'paid' => 95.00,
            'variance' => 25.00,
        ], $data);
    }

    public function test_get_threshold_percentage_from_config()
    {
        $threshold = $this->service->getThresholdPercentage();

        $this->assertEquals(10.0, $threshold);
    }
}
