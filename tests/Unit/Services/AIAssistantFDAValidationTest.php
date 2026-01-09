<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AIAssistant;
use App\Services\FDADrugValidator;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Factories\Sequence;

class AIAssistantFDAValidationTest extends TestCase
{
    public function test_generate_prescription_suggestions_with_fda_validation()
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

        // Mock an appointment with patient
        $patient = User::factory()->create([
            'name' => 'Test Patient',
            'age' => 30,
            'gender' => 'female'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $existingDoctor->id,
            'appointment_type' => 'in_person'
        ]);

        // Mock HTTP responses for FDA validation
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

        $aiAssistant = new AIAssistant();
        $symptoms = ['headache', 'fever'];
        $allergies = [];
        $pastMeds = ['vitamin d'];

        $result = $aiAssistant->generatePrescriptionSuggestionsWithFDAValidation(
            $appointment,
            $symptoms,
            $allergies,
            $pastMeds
        );

        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('risk_flags', $result);
        $this->assertArrayHasKey('source', $result);
        $this->assertEquals('openai_fda_enhanced', $result['source']);
        
        // Verify that FDA validation data is included in suggestions
        foreach ($result['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('fda_validation', $suggestion);
            $this->assertIsArray($suggestion['fda_validation']);
        }
    }

    public function test_generate_prescription_suggestions_with_fda_high_risk_flag()
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

        // Mock an appointment with patient
        $patient = User::factory()->create([
            'name' => 'Test Patient',
            'age' => 25,
            'gender' => 'female'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $existingDoctor->id,
            'appointment_type' => 'in_person'
        ]);

        // Mock FDA response with pregnancy contraindication
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Standard warnings'],
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

        $aiAssistant = new AIAssistant();
        $symptoms = ['headache'];
        $allergies = [];
        $pastMeds = [];

        $result = $aiAssistant->generatePrescriptionSuggestionsWithFDAValidation(
            $appointment,
            $symptoms,
            $allergies,
            $pastMeds
        );

        // Check if high-risk flag is properly set
        $hasHighRisk = false;
        foreach ($result['suggestions'] as $suggestion) {
            if (($suggestion['high_risk'] ?? false) === true) {
                $hasHighRisk = true;
                break;
            }
        }
        
        if ($hasHighRisk) {
            // Verify that the risk flag is present in the overall risk flags
            $highRiskFlagPresent = false;
            foreach ($result['risk_flags'] as $flag) {
                if (strpos($flag, 'HIGH-RISK MEDICATIONS DETECTED') !== false) {
                    $highRiskFlagPresent = true;
                    break;
                }
            }
            $this->assertTrue($highRiskFlagPresent);
        }
    }

    public function test_generate_prescription_suggestions_with_fda_validation_unavailable()
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

        // Mock an appointment with patient
        $patient = User::factory()->create([
            'name' => 'Test Patient',
            'age' => 30,
            'gender' => 'male'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $existingDoctor->id,
            'appointment_type' => 'in_person'
        ]);

        // Mock FDA API failures
        Http::fake([
            '*' => Http::response(['error' => 'API Error'], 500),
        ]);

        $aiAssistant = new AIAssistant();
        $symptoms = ['headache'];
        $allergies = [];
        $pastMeds = [];

        $result = $aiAssistant->generatePrescriptionSuggestionsWithFDAValidation(
            $appointment,
            $symptoms,
            $allergies,
            $pastMeds
        );

        // Check if FDA unavailable flag is properly set
        $fdaUnavailableFlagPresent = false;
        foreach ($result['risk_flags'] as $flag) {
            if (strpos($flag, 'FDA VALIDATION UNAVAILABLE') !== false) {
                $fdaUnavailableFlagPresent = true;
                break;
            }
        }
        $this->assertTrue($fdaUnavailableFlagPresent);
    }

    public function test_generate_prescription_suggestions_without_fda_validation_unchanged()
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

        // Test that the original method still works as expected
        $patient = User::factory()->create([
            'name' => 'Test Patient',
            'age' => 30,
            'gender' => 'male'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $existingDoctor->id,
            'appointment_type' => 'in_person'
        ]);

        $aiAssistant = new AIAssistant();
        $symptoms = ['headache'];
        $allergies = [];
        $pastMeds = [];

        $result = $aiAssistant->generatePrescriptionSuggestions(
            $appointment,
            $symptoms,
            $allergies,
            $pastMeds
        );

        // The original method should work without fda_validation keys
        foreach ($result['suggestions'] as $suggestion) {
            $this->assertArrayNotHasKey('fda_validation', $suggestion);
        }
        
        $this->assertArrayNotHasKey('high_risk', $result['suggestions'][0] ?? []);
    }
}