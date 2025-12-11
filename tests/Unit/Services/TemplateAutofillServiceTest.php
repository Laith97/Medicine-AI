<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TemplateAutofillService;
use App\Services\AIWritingAssistantService;
use App\Services\ComplianceMonitoringService;
use App\Models\DocumentTemplate;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateAutofillServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TemplateAutofillService $autofillService;
    protected AIWritingAssistantService $aiAssistant;
    protected ComplianceMonitoringService $complianceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aiAssistant = $this->mock(AIWritingAssistantService::class);
        $this->complianceService = $this->mock(ComplianceMonitoringService::class);

        $this->autofillService = new TemplateAutofillService(
            $this->aiAssistant,
            $this->complianceService
        );
    }

    /** @test */
    public function it_can_autofill_template_with_patient_data()
    {
        // Create test data
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'medical_record_number' => 'MRN12345',
        ]);

        $template = DocumentTemplate::factory()->create([
            'template_content' => 'Patient: {{patient_name}}, DOB: {{patient_dob}}, Gender: {{patient_gender}}',
            'placeholders' => [
                'patient_name' => ['type' => 'text', 'required' => true],
                'patient_dob' => ['type' => 'date', 'required' => true],
                'patient_gender' => ['type' => 'text', 'required' => true],
            ],
        ]);

        // Execute autofill
        $result = $this->autofillService->autofillTemplate($template, $patient, $user);

        // Assert results
        $this->assertArrayHasKey('filled_data', $result);
        $this->assertArrayHasKey('validation_result', $result);
        $this->assertArrayHasKey('autofill_metadata', $result);

        // Check that patient data was filled
        $this->assertEquals('John Doe', $result['filled_data']['patient_name']);
        $this->assertEquals('1990-01-01', $result['filled_data']['patient_dob']);
        $this->assertEquals('male', $result['filled_data']['patient_gender']);

        // Check validation
        $this->assertTrue($result['validation_result']['is_valid']);
        $this->assertEquals(3, $result['validation_result']['filled_placeholders']);
        $this->assertEquals(3, $result['validation_result']['total_placeholders']);
    }

    /** @test */
    public function it_handles_missing_patient_data_gracefully()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            // Missing DOB and gender
        ]);

        $template = DocumentTemplate::factory()->create([
            'template_content' => 'Patient: {{patient_name}}, DOB: {{patient_dob}}, Gender: {{patient_gender}}',
            'placeholders' => [
                'patient_name' => ['type' => 'text', 'required' => true],
                'patient_dob' => ['type' => 'date', 'required' => false],
                'patient_gender' => ['type' => 'text', 'required' => false],
            ],
        ]);

        $result = $this->autofillService->autofillTemplate($template, $patient, $user);

        // Should still fill available data
        $this->assertEquals('Jane Smith', $result['filled_data']['patient_name']);
        $this->assertArrayNotHasKey('patient_dob', $result['filled_data']);
        $this->assertArrayNotHasKey('patient_gender', $result['filled_data']);

        // Validation should pass since only required field is filled
        $this->assertTrue($result['validation_result']['is_valid']);
    }

    /** @test */
    public function it_provides_autofill_suggestions()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
            'date_of_birth' => '1985-03-15',
            'gender' => 'female',
        ]);

        $template = DocumentTemplate::factory()->create([
            'placeholders' => [
                'patient_name' => ['type' => 'text', 'required' => true],
                'patient_dob' => ['type' => 'date', 'required' => true],
                'patient_age' => ['type' => 'number', 'required' => false],
            ],
        ]);

        $suggestions = $this->autofillService->getAutofillSuggestions($template, $patient);

        $this->assertArrayHasKey('suggestions', $suggestions);
        $this->assertArrayHasKey('total_suggestions', $suggestions);
        $this->assertArrayHasKey('coverage_percentage', $suggestions);

        // Should suggest patient name and DOB
        $this->assertArrayHasKey('patient_name', $suggestions['suggestions']);
        $this->assertArrayHasKey('patient_dob', $suggestions['suggestions']);

        // Check suggestion values
        $this->assertEquals('Alice Johnson', $suggestions['suggestions']['patient_name']['suggested_value']);
        $this->assertEquals('1985-03-15', $suggestions['suggestions']['patient_dob']['suggested_value']);
    }

    /** @test */
    public function it_calculates_confidence_scores_for_suggestions()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Wilson',
            'date_of_birth' => '1975-07-20',
        ]);

        $template = DocumentTemplate::factory()->create([
            'placeholders' => [
                'patient_name' => ['type' => 'text', 'required' => true],
                'patient_dob' => ['type' => 'date', 'required' => true],
                'current_date' => ['type' => 'date', 'required' => false],
            ],
        ]);

        $suggestions = $this->autofillService->getAutofillSuggestions($template, $patient);

        // Check confidence scores
        $this->assertGreaterThan(0, $suggestions['suggestions']['patient_name']['confidence']);
        $this->assertGreaterThan(0, $suggestions['suggestions']['patient_dob']['confidence']);

        // Direct patient data should have higher confidence
        $this->assertGreaterThanOrEqual(
            $suggestions['suggestions']['patient_name']['confidence'],
            $suggestions['suggestions']['patient_dob']['confidence']
        );
    }

    /** @test */
    public function it_handles_different_placeholder_types()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'date_of_birth' => '1990-01-01',
        ]);

        $template = DocumentTemplate::factory()->create([
            'placeholders' => [
                'patient_age' => ['type' => 'number', 'required' => true],
                'is_adult' => ['type' => 'boolean', 'required' => false],
                'patient_info' => ['type' => 'array', 'required' => false],
            ],
        ]);

        $result = $this->autofillService->autofillTemplate($template, $patient, $user);

        // Age should be calculated and formatted as number
        $this->assertIsInt($result['filled_data']['patient_age']);
        $this->assertGreaterThan(30, $result['filled_data']['patient_age']); // Should be around 34

        // Boolean should be formatted correctly
        $this->assertIsBool($result['filled_data']['is_adult']);
        $this->assertTrue($result['filled_data']['is_adult']);
    }

    /** @test */
    public function it_validates_filled_data_against_requirements()
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'first_name' => 'Required',
            'last_name' => 'Field',
            // Missing required DOB
        ]);

        $template = DocumentTemplate::factory()->create([
            'placeholders' => [
                'patient_name' => ['type' => 'text', 'required' => true],
                'patient_dob' => ['type' => 'date', 'required' => true], // Required but missing
                'patient_gender' => ['type' => 'text', 'required' => false], // Optional
            ],
        ]);

        $result = $this->autofillService->autofillTemplate($template, $patient, $user);

        // Should fill available data
        $this->assertEquals('Required Field', $result['filled_data']['patient_name']);
        $this->assertArrayNotHasKey('patient_dob', $result['filled_data']);

        // Validation should fail due to missing required field
        $this->assertFalse($result['validation_result']['is_valid']);
        $this->assertContains(
            'Required placeholder \'patient_dob\' could not be auto-filled',
            $result['validation_result']['violations']
        );
    }
}
