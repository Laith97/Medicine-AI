<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\FDADrugValidator;
use App\Services\AIAssistant;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;

class FDADrugValidationIntegrationTest extends TestCase
{
    public function test_fda_validation_integration_with_ai_assistant()
    {
        Event::fake(); // Prevent event firing that causes Pusher errors

        // Create or find an existing doctor to avoid specialty constraint violations
        $existingDoctor = \App\Models\Doctor::first();
        if (!$existingDoctor) {
            // Create a doctor with a specific specialty to avoid constraint violations
            $specialty = \App\Models\Specialty::create([
                'name' => 'Test Specialty ' . uniqid(),
                'slug' => 'test-specialty-' . uniqid(),
                'description' => 'Test specialty for unit testing',
                'is_active' => true
            ]);

            $existingDoctor = \App\Models\Doctor::factory()->create([
                'specialty_id' => $specialty->id
            ]);
        }

        // Create test patient and appointment
        $patient = User::factory()->create([
            'name' => 'Test Patient',
            'age' => 35,
            'gender' => 'female'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $existingDoctor->id,
            'appointment_type' => 'in_person'
        ]);

        // Mock the API responses for FDA validation
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['May cause GI upset'],
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
                    ['term' => 'nausea', 'count' => 120],
                    ['term' => 'headache', 'count' => 85],
                    ['term' => 'dizziness', 'count' => 60]
                ]
            ], 200),
        ]);

        // Test the integrated functionality
        $aiAssistant = new AIAssistant();
        
        // Mock the AI response by temporarily overriding the OpenAI call
        // For this test, we'll focus on calling the enhanced method and ensuring it works
        $symptoms = ['headache'];
        $allergies = [];
        $pastMeds = [];

        $result = $aiAssistant->generatePrescriptionSuggestionsWithFDAValidation(
            $appointment,
            $symptoms,
            $allergies,
            $pastMeds
        );

        // Verify the result structure includes FDA validation
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('risk_flags', $result);
        $this->assertArrayHasKey('source', $result);
        
        // The source should be the enhanced one
        $this->assertEquals('openai_fda_enhanced', $result['source']);
        
        // If there are suggestions, verify they have FDA validation data
        foreach ($result['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('fda_validation', $suggestion);
            $this->assertIsArray($suggestion['fda_validation']);
            $this->assertArrayHasKey('flag', $suggestion['fda_validation']);
            $this->assertArrayHasKey('validation_status', $suggestion['fda_validation']);
        }
        
        echo "✓ FDA validation successfully integrated with AI Assistant\n";
    }

    public function test_fda_validation_direct_api_calls()
    {
        $validator = new FDADrugValidator();

        // This test uses HTTP mocking to simulate API calls
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['May cause drowsiness'],
                        'boxed_warning' => ['Serious allergic reactions possible'],
                        'contraindications' => ['Pregnancy: Avoid in third trimester'],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => [
                    [
                        'reason_for_recall' => 'Potential contamination',
                        'status' => 'ongoing',
                        'classification' => 'Class II'
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => [
                    ['term' => 'nausea', 'count' => 250],
                    ['term' => 'fatigue', 'count' => 180],
                    ['term' => 'dry mouth', 'count' => 150]
                ]
            ], 200),
        ]);

        // Test validation with demographics
        $result = $validator->validateMedication('diphenhydramine', 28, 'female');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('flag', $result);
        $this->assertArrayHasKey('high_risk', $result);
        $this->assertArrayHasKey('validation_status', $result);
        $this->assertArrayHasKey('risk_indicators', $result);
        $this->assertArrayHasKey('clinical_flags', $result);

        // Verify specific risk indicators
        $riskIndicators = $result['risk_indicators'];
        $this->assertIsArray($riskIndicators);
        $this->assertArrayHasKey('black_box_warning', $riskIndicators);
        $this->assertArrayHasKey('recall_status', $riskIndicators);
        $this->assertArrayHasKey('pregnancy_contraindication', $riskIndicators);
        $this->assertArrayHasKey('contraindication', $riskIndicators);

        // Check that clinical flags contain expected information
        $this->assertNotEmpty($result['clinical_flags']);
        
        echo "✓ Direct FDA API calls work correctly with mocked responses\n";
    }

    public function test_fda_validation_multiple_medications()
    {
        $validator = new FDADrugValidator();

        // Mock response for all medications
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
                'results' => []
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => []
            ], 200),
        ]);

        $medications = ['aspirin', 'acetaminophen', 'ibuprofen'];
        $results = $validator->validateMultipleMedications($medications, 40, 'male');

        $this->assertCount(3, $results);

        foreach ($medications as $med) {
            $this->assertArrayHasKey($med, $results);
            $this->assertIsArray($results[$med]);
            $this->assertArrayHasKey('flag', $results[$med]);
            $this->assertArrayHasKey('validation_status', $results[$med]);
        }

        echo "✓ Multiple medication validation works correctly\n";
    }

    public function test_graceful_degradation_when_fda_api_unavailable()
    {
        $validator = new FDADrugValidator();

        // Mock API failure
        Http::fake([
            '*' => Http::response(['error' => 'API Error'], 500),
        ]);

        $result = $validator->validateMedication('test_drug');

        // Should return graceful fallback
        $this->assertEquals('FDA validation unavailable – clinician review required', $result['flag']);
        $this->assertFalse($result['high_risk']);
        $this->assertEquals('unavailable', $result['validation_status']);

        echo "✓ FDA validation gracefully degrades when API is unavailable\n";
    }
}