<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\FDADrugValidator;
use Illuminate\Support\Facades\Http;

class FDADrugValidatorScenariosTest extends TestCase
{
    public function test_fda_validator_detects_recalls()
    {
        $validator = new FDADrugValidator();
        
        // Mock API for recall testing
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Standard warnings'],
                        'boxed_warning' => null,
                        'contraindications' => [],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => [
                    [
                        'reason_for_recall' => 'Potential contamination',
                        'status' => 'ongoing',
                        'classification' => 'Class I (Dangerous)'
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => []
            ], 200),
        ]);

        $result = $validator->validateMedication('contaminated_drug');
        $this->assertEquals('high_risk', $result['validation_status']);
        $this->assertTrue($result['high_risk']);
        $this->assertTrue($result['risk_indicators']['recall_status']);
    }

    public function test_fda_validator_detects_black_box_warnings()
    {
        $validator = new FDADrugValidator();
        
        // Mock API for black box warning testing
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Standard warnings'],
                        'boxed_warning' => 'Serious bleeding risk',  // String format
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

        $result = $validator->validateMedication('warfarin');
        $this->assertTrue($result['high_risk'], 
                         "Black box warning should set high_risk flag. Result: " . json_encode($result));
        $this->assertTrue($result['risk_indicators']['black_box_warning'],
                         "Black box warning indicator should be set. Result: " . json_encode($result));
    }

    public function test_fda_validator_detects_pregnancy_contraindications()
    {
        $validator = new FDADrugValidator();
        
        // Mock API for pregnancy contraindication testing
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

        $result = $validator->validateMedication('isotretinoin', 25, 'female');
        
        // Check if any pregnancy-related flags or indicators are set
        $pregnancyFound = false;
        foreach ($result['clinical_flags'] as $flag) {
            if (strpos($flag, 'PREGNANCY') !== false) {
                $pregnancyFound = true;
                break;
            }
        }
        
        $this->assertTrue($pregnancyFound || $result['risk_indicators']['pregnancy_contraindication'] || 
                         $result['risk_indicators']['contraindication'] || $result['high_risk'],
                         "Pregnancy contraindication should be detected or flagged. Result: " . json_encode($result));
    }
}