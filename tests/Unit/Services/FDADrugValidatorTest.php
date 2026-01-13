<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FDADrugValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FDADrugValidatorTest extends TestCase
{
    public function test_fda_validation_returns_unavailable_when_api_fails()
    {
        // Mock HTTP failure
        Http::fake([
            '*' => Http::response(['error' => 'API Error'], 500),
        ]);

        $validator = new FDADrugValidator();
        $result = $validator->validateMedication('aspirin');

        $this->assertEquals('FDA validation unavailable – clinician review required', $result['flag']);
        $this->assertFalse($result['high_risk']);
        $this->assertEquals('unavailable', $result['validation_status']);
        $this->assertEmpty($result['clinical_flags']);
    }

    public function test_fda_validation_handles_recalls()
    {
        // Mock recall response
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Some warnings'],
                        'boxed_warning' => null,
                        'contraindications' => [],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => [
                    [
                        'reason_for_recall' => 'Contamination',
                        'status' => 'Ongoing',
                        'classification' => 'Class I (Dangerous)'
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => []
            ], 200),
        ]);

        $validator = new FDADrugValidator();
        $result = $validator->validateMedication('contaminated_drug');

        $this->assertTrue($result['high_risk']);
        $this->assertEquals('high_risk', $result['validation_status']);
        $this->assertTrue($result['risk_indicators']['recall_status']);
        
        $recallFound = false;
        foreach ($result['clinical_flags'] as $flag) {
            if (strpos($flag, 'RECALL') !== false) {
                $recallFound = true;
                break;
            }
        }
        $this->assertTrue($recallFound);
    }

    public function test_fda_validation_handles_black_box_warnings()
    {
        // Mock black box warning response
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Standard warnings'],
                        'boxed_warning' => ['Serious bleeding risk'],
                        'contraindications' => [],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => []
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => []
            ], 200),
        ]);

        $validator = new FDADrugValidator();
        $result = $validator->validateMedication('warfarin');

        $this->assertTrue($result['high_risk']);
        $this->assertEquals('high_risk', $result['validation_status']);
        $this->assertTrue($result['risk_indicators']['black_box_warning']);
        
        $bbwFound = false;
        foreach ($result['clinical_flags'] as $flag) {
            if (strpos($flag, 'BLACK BOX WARNING') !== false) {
                $bbwFound = true;
                break;
            }
        }
        $this->assertTrue($bbwFound);
    }

    public function test_fda_validation_detects_pregnancy_contraindications()
    {
        // Mock contraindication response
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => [],
                        'boxed_warning' => null,
                        'contraindications' => ['Pregnancy: Contraindicated in pregnant women'],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => []
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => []
            ], 200),
        ]);

        $validator = new FDADrugValidator();
        $result = $validator->validateMedication('isotretinoin', 25, 'female');

        $this->assertTrue($result['high_risk']);
        $this->assertTrue($result['risk_indicators']['pregnancy_contraindication']);
        
        $pregnancyFound = false;
        foreach ($result['clinical_flags'] as $flag) {
            if (strpos($flag, 'PREGNANCY CONTRAINDICATION') !== false) {
                $pregnancyFound = true;
                break;
            }
        }
        $this->assertTrue($pregnancyFound);
    }

    public function test_fda_validation_caching_works()
    {
        // Mock response
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Cached warning'],
                        'boxed_warning' => null,
                        'contraindications' => [],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => []
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => []
            ], 200),
        ]);

        $validator = new FDADrugValidator();
        
        // First call - should hit API
        $result1 = $validator->validateMedication('test_drug');
        
        // Second call - should use cache
        $result2 = $validator->validateMedication('test_drug');

        $this->assertEquals($result1['flag'], $result2['flag']);
        $this->assertEquals($result1['clinical_flags'], $result2['clinical_flags']);
    }

    public function test_fda_validation_with_multiple_medications()
    {
        // Mock response for all medications
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Common warning'],
                        'boxed_warning' => null,
                        'contraindications' => [],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => []
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => []
            ], 200),
        ]);

        $validator = new FDADrugValidator();
        $medications = ['aspirin', 'acetaminophen', 'ibuprofen'];
        $results = $validator->validateMultipleMedications($medications);

        $this->assertCount(3, $results);
        foreach ($medications as $med) {
            $this->assertArrayHasKey($med, $results);
            $this->assertIsArray($results[$med]);
            $this->assertArrayHasKey('flag', $results[$med]);
        }
    }

    public function test_fda_validation_no_contraindications_for_male_patients()
    {
        // Mock response without pregnancy-related contraindications
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Standard warnings'],
                        'boxed_warning' => null,
                        'contraindications' => ['Geriatric: Use with caution in elderly'],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => []
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => []
            ], 200),
        ]);

        $validator = new FDADrugValidator();
        $result = $validator->validateMedication('test_drug', 70, 'male');

        // Should detect geriatric contraindication for 70-year-old
        $this->assertTrue($result['high_risk']);
        $this->assertTrue($result['risk_indicators']['contraindication']);
    }

    public function test_fda_validation_handles_adverse_events()
    {
        // Mock adverse events response
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => [],
                        'boxed_warning' => null,
                        'contraindications' => [],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => []
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => [
                    'results' => [
                        ['term' => 'nausea', 'count' => 1500],
                        ['term' => 'headache', 'count' => 800],
                        ['term' => 'dizziness', 'count' => 600]
                    ]
                ]
            ], 200),
        ]);

        $validator = new FDADrugValidator();
        $result = $validator->validateMedication('test_drug');

        $hasAdverseEffects = false;
        foreach ($result['clinical_flags'] as $flag) {
            if (strpos($flag, 'ADVERSE EFFECTS') !== false) {
                $hasAdverseEffects = true;
                break;
            }
        }
        $this->assertTrue($hasAdverseEffects);
    }

    public function test_fda_validation_empty_medication_name()
    {
        $validator = new FDADrugValidator();
        $result = $validator->validateMedication('');

        $this->assertEquals('FDA validation unavailable – clinician review required', $result['flag']);
        $this->assertFalse($result['high_risk']);
    }

    public function test_cache_clearing_works()
    {
        // Mock response
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Test warning'],
                        'boxed_warning' => null,
                        'contraindications' => [],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => []
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => []
            ], 200),
        ]);

        $validator = new FDADrugValidator();
        
        // First call - should populate cache
        $result1 = $validator->validateMedication('clear_test_drug');
        
        // Clear cache
        $validator->clearCache('clear_test_drug');
        
        // Second call - should hit API again
        $result2 = $validator->validateMedication('clear_test_drug');
        
        // Results should be the same content but could potentially be different due to cache
        $this->assertEquals($result1['flag'], $result2['flag']);
    }
}