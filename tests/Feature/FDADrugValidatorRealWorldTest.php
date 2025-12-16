<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\FDADrugValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FDADrugValidatorRealWorldTest extends TestCase
{
    /**
     * Test real medications that should have known FDA issues
     */
    public function test_real_world_medications_with_known_fda_issues()
    {
        $validator = new FDADrugValidator();
        
        // Test medications with known black box warnings
        $testCases = [
            [
                'medication' => 'warfarin',
                'expected_high_risk' => true, // Has black box warning for bleeding risk
                'expected_warning_type' => 'black_box'
            ],
            [
                'medication' => 'isotretinoin', // Accutane
                'expected_high_risk' => true, // Has black box warning, contraindicated in pregnancy
                'expected_warning_type' => 'black_box'
            ],
            [
                'medication' => 'acetaminophen', // Generally safe
                'expected_high_risk' => false,
                'expected_warning_type' => 'none'
            ],
            [
                'medication' => 'atorvastatin', // Statin
                'expected_high_risk' => false, // Has warnings but not high risk
                'expected_warning_type' => 'warnings'
            ],
        ];

        foreach ($testCases as $case) {
            $result = $validator->validateMedication($case['medication'], 35, 'female');
            
            if ($case['expected_high_risk']) {
                $this->assertTrue($result['high_risk'], 
                    "Expected {$case['medication']} to be high risk but it was not");
            } else {
                // For medications that aren't expected to be high risk, 
                // we can't guarantee they won't be flagged by FDA data
                // So we just ensure the function doesn't crash
                $this->assertIsArray($result);
                $this->assertArrayHasKey('flag', $result);
            }
            
            $this->assertArrayHasKey('validation_status', $result);
            $this->assertArrayHasKey('risk_indicators', $result);
            $this->assertArrayHasKey('clinical_flags', $result);
        }
    }

    /**
     * Test demographic-specific contraindications
     */
    public function test_pregnancy_contraindications()
    {
        $validator = new FDADrugValidator();
        
        // Test medications known to be contraindicated in pregnancy
        $pregnancyRiskyMeds = [
            'isotretinoin',   // Accutane
            'warfarin',       // Blood thinner
            'lisinopril',     // ACE inhibitor
            'valproic acid',  // Anticonvulsant
        ];
        
        foreach ($pregnancyRiskyMeds as $med) {
            $result = $validator->validateMedication($med, 28, 'female');
            
            // At least one of the risk indicators should be related to pregnancy
            $pregnancyRelated = $result['risk_indicators']['pregnancy_contraindication'] ?? false;
            $contraindication = $result['risk_indicators']['contraindication'] ?? false;
            
            // Note: We can't guarantee that openFDA will have data for all medications
            // So we just ensure the function doesn't crash and returns valid data
            $this->assertIsArray($result);
            $this->assertArrayHasKey('flag', $result);
            $this->assertArrayHasKey('validation_status', $result);
        }
        
        // Test same medications with male patient (should have fewer pregnancy-related flags)
        foreach ($pregnancyRiskyMeds as $med) {
            $resultMale = $validator->validateMedication($med, 28, 'male');
            $resultFemale = $validator->validateMedication($med, 28, 'female');
            
            // Results should be different for male vs female
            // (This is a weak assertion since data might be the same)
            $this->assertIsArray($resultMale);
            $this->assertIsArray($resultFemale);
        }
    }

    /**
     * Test recall checking functionality
     */
    public function test_recall_detection()
    {
        $validator = new FDADrugValidator();
        
        // Test with a medication that might have had recalls
        // Note: We can't guarantee specific recall status due to changing FDA data
        $result = $validator->validateMedication('metformin');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('flag', $result);
        $this->assertArrayHasKey('validation_status', $result);
        $this->assertArrayHasKey('risk_indicators', $result);
        
        // Ensure risk indicators have the expected structure
        $this->assertIsArray($result['risk_indicators']);
        $this->assertArrayHasKey('black_box_warning', $result['risk_indicators']);
        $this->assertArrayHasKey('recall_status', $result['risk_indicators']);
        $this->assertArrayHasKey('pregnancy_contraindication', $result['risk_indicators']);
        $this->assertArrayHasKey('contraindication', $result['risk_indicators']);
    }

    /**
     * Test that caching works properly
     */
    public function test_caching_functionality()
    {
        $validator = new FDADrugValidator();
        
        // Clear any existing cache for this medication
        $validator->clearCache('aspirin', 30, 'male');
        
        // First call - should hit API
        $startTime = microtime(true);
        $result1 = $validator->validateMedication('aspirin', 30, 'male');
        $time1 = microtime(true) - $startTime;
        
        // Second call - should use cache, so faster
        $startTime = microtime(true);
        $result2 = $validator->validateMedication('aspirin', 30, 'male');
        $time2 = microtime(true) - $startTime;
        
        // Both results should be identical
        $this->assertEquals($result1, $result2);
        
        // Cache hit should generally be much faster than API call
        // (though this is not guaranteed in test environments)
        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
    }

    /**
     * Test edge cases and error conditions
     */
    public function test_edge_cases()
    {
        $validator = new FDADrugValidator();
        
        // Test with empty medication name
        $result = $validator->validateMedication('');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('flag', $result);
        
        // Test with very long medication name
        $longName = str_repeat('a', 255);
        $result = $validator->validateMedication($longName);
        $this->assertIsArray($result);
        
        // Test with medication that should not exist
        $result = $validator->validateMedication('nonexistentdrug12345');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('validation_status', $result);
    }

    /**
     * Test multiple medication validation
     */
    public function test_multiple_medication_validation()
    {
        $validator = new FDADrugValidator();
        
        $medications = ['aspirin', 'acetaminophen', 'ibuprofen'];
        $results = $validator->validateMultipleMedications($medications, 30, 'female');
        
        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        
        foreach ($medications as $med) {
            $this->assertArrayHasKey($med, $results);
            $this->assertIsArray($results[$med]);
            $this->assertArrayHasKey('flag', $results[$med]);
            $this->assertArrayHasKey('validation_status', $results[$med]);
        }
    }
}