<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PatientAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PatientCasesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $doctor;
    protected $patient;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a doctor user
        $this->doctor = User::factory()->create([
            'role' => 'doctor',
            'subscription_active' => true,
        ]);

        // Create a patient
        $this->patient = User::factory()->create([
            'role' => 'patient',
        ]);
    }

    /** @test */
    public function cases_page_loads_successfully_with_authentication()
    {
        // Create some patient analyses for the doctor
        PatientAnalysis::factory()->count(3)->create([
            'user_id' => $this->doctor->id,
            'name' => $this->faker->name(),
            'age' => $this->faker->numberBetween(18, 80),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'ai_response' => $this->faker->paragraph(),
            'diagnosis' => $this->faker->sentence(),
        ]);

        // Act as the doctor
        $this->actingAs($this->doctor);

        // Visit the cases page
        $response = $this->get('/cases');

        // Assert that the response is successful
        $response->assertStatus(200);

        // Assert that the correct view is returned
        $response->assertViewIs('cases');

        // Assert that the view has the required data
        $response->assertViewHas(['records', 'patientGroups']);

        $viewData = $response->getData();

        // Verify records is a collection
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $viewData['records']);

        // Verify patientGroups is an array
        $this->assertIsArray($viewData['patientGroups']);

        // Verify we have the expected number of records
        $this->assertCount(3, $viewData['records']);
    }

    /** @test */
    public function cases_page_requires_authentication()
    {
        // Test without authentication
        $response = $this->get('/cases');

        // Should redirect to login or return unauthorized
        $response->assertStatus(302); // Redirect to login
        $response->assertRedirect('/login');
    }

    /** @test */
    public function cases_page_returns_correct_data_structure()
    {
        // Create a patient analysis
        $analysis = PatientAnalysis::factory()->create([
            'user_id' => $this->doctor->id,
            'name' => 'John Doe',
            'age' => 30,
            'gender' => 'male',
            'ai_response' => 'Test diagnosis response',
            'diagnosis' => 'Test diagnosis',
        ]);

        // Act as the doctor
        $this->actingAs($this->doctor);

        // Visit the cases page
        $response = $this->get('/cases');

        $response->assertStatus(200);

        $viewData = $response->getData();
        $records = $viewData['records'];

        // Verify the first record has the expected structure
        $record = $records->first();

        $this->assertObjectHasAttribute('id', $record);
        $this->assertObjectHasAttribute('name', $record);
        $this->assertObjectHasAttribute('age', $record);
        $this->assertObjectHasAttribute('gender', $record);
        $this->assertObjectHasAttribute('symptoms', $record);
        $this->assertObjectHasAttribute('ai_response', $record);
        $this->assertObjectHasAttribute('created_at', $record);
        $this->assertObjectHasAttribute('source_model', $record);
        $this->assertObjectHasAttribute('source_id', $record);

        // Verify data integrity
        $this->assertEquals($analysis->id, $record->id);
        $this->assertEquals('John Doe', $record->name);
        $this->assertEquals(30, $record->age);
        $this->assertEquals('male', $record->gender);
        $this->assertEquals('PatientAnalysis', $record->source_model);
    }

    /** @test */
    public function cases_page_handles_empty_patient_history()
    {
        // Act as the doctor (no analyses created)
        $this->actingAs($this->doctor);

        // Visit the cases page
        $response = $this->get('/cases');

        // Should still return successfully
        $response->assertStatus(200);
        $response->assertViewIs('cases');

        $viewData = $response->getData();

        // Verify records is empty collection
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $viewData['records']);
        $this->assertCount(0, $viewData['records']);

        // Verify patientGroups is empty array
        $this->assertIsArray($viewData['patientGroups']);
        $this->assertEmpty($viewData['patientGroups']);
    }

    /** @test */
    public function cases_page_groups_patients_correctly()
    {
        // Create multiple analyses for the same patient
        $patientName = 'Jane Smith';
        $patientAge = 25;
        $patientGender = 'female';

        PatientAnalysis::factory()->count(2)->create([
            'user_id' => $this->doctor->id,
            'name' => $patientName,
            'age' => $patientAge,
            'gender' => $patientGender,
            'ai_response' => $this->faker->paragraph(),
            'diagnosis' => $this->faker->sentence(),
        ]);

        // Create analysis for different patient
        PatientAnalysis::factory()->create([
            'user_id' => $this->doctor->id,
            'name' => 'Bob Johnson',
            'age' => 40,
            'gender' => 'male',
            'ai_response' => $this->faker->paragraph(),
            'diagnosis' => $this->faker->sentence(),
        ]);

        // Act as the doctor
        $this->actingAs($this->doctor);

        // Visit the cases page
        $response = $this->get('/cases');

        $response->assertStatus(200);

        $viewData = $response->getData();
        $patientGroups = $viewData['patientGroups'];

        // Should have 2 patient groups
        $this->assertCount(2, $patientGroups);

        // Verify structure of patient groups
        foreach ($patientGroups as $key => $group) {
            $this->assertArrayHasKey('patient', $group);
            $this->assertArrayHasKey('visits', $group);
            $this->assertArrayHasKey('visit_count', $group);
            $this->assertArrayHasKey('last_visit', $group);

            $this->assertIsArray($group['visits']);
            $this->assertGreaterThan(0, $group['visit_count']);
        }
    }
}