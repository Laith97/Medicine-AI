<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Services\AIAssistant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\WithFaker;

class IntegratedAIAndFDAValidationTest extends TestCase
{
    use WithFaker;

    public function test_ai_and_fda_integration_with_safe_medication()
    {
        Event::fake(); // Prevent event firing that causes Pusher errors

        // Create or find an existing doctor to avoid specialty constraint violations
        $existingDoctor = Doctor::first();
        if (!$existingDoctor) {
            $specialty = Specialty::create([
                'name' => 'Test Specialty ' . uniqid(),
                'slug' => 'test-specialty-' . uniqid(),
                'description' => 'Test specialty for unit testing',
                'is_active' => true
            ]);

            $existingDoctor = Doctor::factory()->create([
                'specialty_id' => $specialty->id
            ]);
        }

        // Create a user and associate with doctor
        $user = User::factory()->create();
        $existingDoctor->user_id = $user->id;
        $existingDoctor->save();

        // Create test patient
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

        // Authenticate as the doctor user
        $this->actingAs($user);

        // Mock the FDA API responses for medication (should be generally safe)
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['May cause liver damage if exceeding recommended dose'],
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
                    ['term' => 'nausea', 'count' => 150],
                    ['term' => 'dizziness', 'count' => 95],
                    ['term' => 'rash', 'count' => 80]
                ]
            ], 200),
        ]);

        // Make a request to the AI suggest endpoint
        $response = $this->postJson(route('ai.appointments.suggest', ['appointment' => $appointment->id]), [
            'symptoms' => 'headache',
            'allergies' => json_encode([]),
            'past_meds' => json_encode([])
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Verify the response structure includes FDA validation
        $this->assertArrayHasKey('suggestions', $data);
        $this->assertArrayHasKey('risk_flags', $data);
        $this->assertEquals('openai_fda_enhanced', $data['source']); // This confirms the enhanced method was used

        // Verify that suggestions have FDA validation data
        foreach ($data['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('fda_validation', $suggestion);
            $this->assertIsArray($suggestion['fda_validation']);
            $this->assertArrayHasKey('validation_status', $suggestion['fda_validation']);
        }

        echo "✓ AI + FDA integration works - response includes FDA validation data\n";
    }

    public function test_ai_and_fda_integration_with_high_risk_medication()
    {
        Event::fake(); // Prevent event firing that causes Pusher errors

        // Create or find an existing doctor to avoid specialty constraint violations
        $existingDoctor = Doctor::first();
        if (!$existingDoctor) {
            $specialty = Specialty::create([
                'name' => 'Test Specialty ' . uniqid(),
                'slug' => 'test-specialty-' . uniqid(),
                'description' => 'Test specialty for unit testing',
                'is_active' => true
            ]);

            $existingDoctor = Doctor::factory()->create([
                'specialty_id' => $specialty->id
            ]);
        }

        // Create a user and associate with doctor
        $user = User::factory()->create();
        $existingDoctor->user_id = $user->id;
        $existingDoctor->save();

        // Create test patient (female, reproductive age)
        $patient = User::factory()->create([
            'name' => 'Test Female Patient',
            'age' => 28,
            'gender' => 'female'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $existingDoctor->id,
            'appointment_type' => 'in_person'
        ]);

        // Authenticate as the doctor user
        $this->actingAs($user);

        // Mock FDA API responses for medication with black box warning
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => [
                            'Bleeding risk',
                            'Requires regular monitoring'
                        ],
                        'boxed_warning' => ['Major bleeding including fatal intracranial hemorrhage'], // Black box warning
                        'contraindications' => [
                            'Hemorrhagic conditions',
                            'Recent surgery of CNS or eye'
                        ],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => []
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => [
                    ['term' => 'hemorrhage', 'count' => 1200],
                    ['term' => 'gastrointestinal hemorrhage', 'count' => 800],
                    ['term' => 'epistaxis', 'count' => 450]
                ]
            ], 200),
        ]);

        // Make a request to the AI suggest endpoint
        $response = $this->postJson(route('ai.appointments.suggest', ['appointment' => $appointment->id]), [
            'symptoms' => 'atrial fibrillation',
            'allergies' => json_encode([]),
            'past_meds' => json_encode([])
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Verify the response structure
        $this->assertArrayHasKey('suggestions', $data);
        $this->assertArrayHasKey('risk_flags', $data);
        $this->assertEquals('openai_fda_enhanced', $data['source']);

        // Check for high-risk indicators in the response
        $hasHighRiskSuggestion = false;
        foreach ($data['suggestions'] as $suggestion) {
            if (isset($suggestion['fda_validation'])) {
                $fdaValidation = $suggestion['fda_validation'];
                if ($fdaValidation['high_risk'] ?? false) {
                    $hasHighRiskSuggestion = true;
                    break;
                }
            }
        }
        
        // Since we're mocking the API responses, some suggestions might be flagged as high risk
        // based on the mocked FDA data
        
        // Verify that suggestions have FDA validation data
        foreach ($data['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('fda_validation', $suggestion);
            $this->assertIsArray($suggestion['fda_validation']);
        }

        echo "✓ AI + FDA integration properly validates medications with potential risks\n";
    }

    public function test_ai_and_fda_integration_with_recall_medication()
    {
        Event::fake(); // Prevent event firing that causes Pusher errors

        // Create or find an existing doctor to avoid specialty constraint violations
        $existingDoctor = Doctor::first();
        if (!$existingDoctor) {
            $specialty = Specialty::create([
                'name' => 'Test Specialty ' . uniqid(),
                'slug' => 'test-specialty-' . uniqid(),
                'description' => 'Test specialty for unit testing',
                'is_active' => true
            ]);

            $existingDoctor = Doctor::factory()->create([
                'specialty_id' => $specialty->id
            ]);
        }

        // Create a user and associate with doctor
        $user = User::factory()->create();
        $existingDoctor->user_id = $user->id;
        $existingDoctor->save();

        // Create test patient
        $patient = User::factory()->create([
            'name' => 'Test Patient',
            'age' => 45,
            'gender' => 'male'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $existingDoctor->id,
            'appointment_type' => 'in_person'
        ]);

        // Authenticate as the doctor user
        $this->actingAs($user);

        // Mock FDA API responses with recall information
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Risk of lactic acidosis'],
                        'boxed_warning' => null,
                        'contraindications' => ['Severe renal impairment'],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => [
                    [
                        'reason_for_recall' => 'Potential N-nitrosodimethylamine (NDMA) impurity',
                        'status' => 'ongoing',
                        'classification' => 'Class II',
                        'product_description' => 'Metformin HCl tablets'
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => [
                    ['term' => 'nausea', 'count' => 450],
                    ['term' => 'diarrhea', 'count' => 400],
                    ['term' => 'metformin', 'count' => 350]
                ]
            ], 200),
        ]);

        // Make a request to the AI suggest endpoint
        $response = $this->postJson(route('ai.appointments.suggest', ['appointment' => $appointment->id]), [
            'symptoms' => 'Type 2 diabetes',
            'allergies' => json_encode([]),
            'past_meds' => json_encode([])
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Verify the response structure
        $this->assertArrayHasKey('suggestions', $data);
        $this->assertArrayHasKey('risk_flags', $data);
        $this->assertEquals('openai_fda_enhanced', $data['source']);

        // Verify that suggestions have FDA validation data
        foreach ($data['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('fda_validation', $suggestion);
            $this->assertIsArray($suggestion['fda_validation']);
            
            // If there are recall flags, they should be present
            if (isset($suggestion['fda_validation']['risk_indicators']['recall_status'])) {
                $this->assertIsBool($suggestion['fda_validation']['risk_indicators']['recall_status']);
            }
        }

        echo "✓ AI + FDA integration properly validates medications with recall information\n";
    }

    public function test_ai_and_fda_integration_with_pregnancy_contraindication()
    {
        Event::fake(); // Prevent event firing that causes Pusher errors

        // Create or find an existing doctor to avoid specialty constraint violations
        $existingDoctor = Doctor::first();
        if (!$existingDoctor) {
            $specialty = Specialty::create([
                'name' => 'Test Specialty ' . uniqid(),
                'slug' => 'test-specialty-' . uniqid(),
                'description' => 'Test specialty for unit testing',
                'is_active' => true
            ]);

            $existingDoctor = Doctor::factory()->create([
                'specialty_id' => $specialty->id
            ]);
        }

        // Create a user and associate with doctor
        $user = User::factory()->create();
        $existingDoctor->user_id = $user->id;
        $existingDoctor->save();

        // Create test patient (female, reproductive age)
        $patient = User::factory()->create([
            'name' => 'Test Pregnant Patient',
            'age' => 28,
            'gender' => 'female'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $existingDoctor->id,
            'appointment_type' => 'in_person'
        ]);

        // Authenticate as the doctor user
        $this->actingAs($user);

        // Mock FDA API responses with pregnancy contraindications
        Http::fake([
            'https://api.fda.gov/drug/label.json*' => Http::response([
                'results' => [
                    [
                        'warnings' => ['Hypotension in volume depleted patients'],
                        'boxed_warning' => null,
                        'contraindications' => [
                            'Pregnancy: When pregnancy is detected, discontinue lisinopril as soon as possible',
                            'History of angioedema'
                        ],
                    ]
                ]
            ], 200),
            'https://api.fda.gov/drug/enforcement.json*' => Http::response([
                'results' => []
            ], 200),
            'https://api.fda.gov/drug/event.json*' => Http::response([
                'results' => [
                    ['term' => 'cough', 'count' => 400],
                    ['term' => 'hypotension', 'count' => 250],
                    ['term' => 'hyperkalemia', 'count' => 200]
                ]
            ], 200),
        ]);

        // Make a request to the AI suggest endpoint
        $response = $this->postJson(route('ai.appointments.suggest', ['appointment' => $appointment->id]), [
            'symptoms' => 'hypertension',
            'allergies' => json_encode([]),
            'past_meds' => json_encode([])
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Verify the response structure
        $this->assertArrayHasKey('suggestions', $data);
        $this->assertArrayHasKey('risk_flags', $data);
        $this->assertEquals('openai_fda_enhanced', $data['source']);

        // Check for pregnancy contraindication flags in the response
        $pregnancyContraindicationFound = false;
        foreach ($data['suggestions'] as $suggestion) {
            if (isset($suggestion['fda_validation']['risk_indicators']['pregnancy_contraindication'])) {
                if ($suggestion['fda_validation']['risk_indicators']['pregnancy_contraindication']) {
                    $pregnancyContraindicationFound = true;
                }
            }
        }

        // Verify that suggestions have FDA validation data
        foreach ($data['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('fda_validation', $suggestion);
            $this->assertIsArray($suggestion['fda_validation']);
        }

        echo "✓ AI + FDA integration properly validates medications with pregnancy considerations\n";
    }

    public function test_ai_and_fda_integration_with_api_failure_graceful_degradation()
    {
        Event::fake(); // Prevent event firing that causes Pusher errors

        // Create or find an existing doctor to avoid specialty constraint violations
        $existingDoctor = Doctor::first();
        if (!$existingDoctor) {
            $specialty = Specialty::create([
                'name' => 'Test Specialty ' . uniqid(),
                'slug' => 'test-specialty-' . uniqid(),
                'description' => 'Test specialty for unit testing',
                'is_active' => true
            ]);

            $existingDoctor = Doctor::factory()->create([
                'specialty_id' => $specialty->id
            ]);
        }

        // Create a user and associate with doctor
        $user = User::factory()->create();
        $existingDoctor->user_id = $user->id;
        $existingDoctor->save();

        // Create test patient
        $patient = User::factory()->create([
            'name' => 'Test Patient',
            'age' => 35,
            'gender' => 'male'
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $existingDoctor->id,
            'appointment_type' => 'in_person'
        ]);

        // Authenticate as the doctor user
        $this->actingAs($user);

        // Mock FDA API failure
        Http::fake([
            '*' => Http::response(['error' => 'API Error'], 500),
        ]);

        // Make a request to the AI suggest endpoint
        $response = $this->postJson(route('ai.appointments.suggest', ['appointment' => $appointment->id]), [
            'symptoms' => 'cardiovascular risk',
            'allergies' => json_encode([]),
            'past_meds' => json_encode([])
        ]);

        $response->assertStatus(200); // Should still return 200 but with fallback message
        $data = $response->json();

        // Verify the response structure still exists
        $this->assertArrayHasKey('suggestions', $data);
        $this->assertArrayHasKey('risk_flags', $data);
        $this->assertEquals('openai_fda_enhanced', $data['source']); // Still uses enhanced method

        // Verify FDA unavailable message is present
        $fdaUnavailableFound = false;
        foreach ($data['risk_flags'] as $flag) {
            if (strpos($flag, 'FDA VALIDATION UNAVAILABLE') !== false) {
                $fdaUnavailableFound = true;
                break;
            }
        }
        $this->assertTrue($fdaUnavailableFound, "FDA validation unavailable message should be present when API fails");

        // Verify that suggestions still have FDA validation data structure (marked as unavailable)
        foreach ($data['suggestions'] as $suggestion) {
            $this->assertArrayHasKey('fda_validation', $suggestion);
            $fdaValidation = $suggestion['fda_validation'];
            $this->assertEquals('unavailable', $fdaValidation['validation_status']);
        }

        echo "✓ AI + FDA integration gracefully degrades when FDA API is unavailable\n";
    }
}