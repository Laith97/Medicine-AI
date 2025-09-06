<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\DiagnosisController;
use App\Models\User;
use App\Models\Diagnosis;
use App\Models\DiagnosisFollowUp;
use App\Services\OpenAIClient;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;

class DiagnosisControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $openAIClientMock;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openAIClientMock = Mockery::mock(OpenAIClient::class);
        $this->app->instance(OpenAIClient::class, $this->openAIClientMock);

        $this->controller = new DiagnosisController();

        $this->user = User::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Test',
            'email' => 'doctor@test.com'
        ]);

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_diagnosis_list()
    {
        Diagnosis::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('diagnosis', $response->getContent());
    }

    public function test_create_returns_diagnosis_form()
    {
        $response = $this->controller->create();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('create', $response->getContent());
    }

    public function test_store_creates_new_diagnosis()
    {
        $diagnosisData = [
            'patient_name' => 'John Doe',
            'patient_age' => 35,
            'patient_gender' => 'male',
            'symptoms' => 'Fever, headache, body aches',
            'diagnosis_text' => 'Viral syndrome',
            'confidence_level' => 85,
            'urgency_level' => 'medium',
            'treatment_plan' => 'Rest, fluids, over-the-counter pain relievers'
        ];

        $request = Request::create('/diagnosis', 'POST', $diagnosisData);

        $response = $this->controller->store($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertDatabaseHas('diagnoses', [
            'patient_name' => 'John Doe',
            'diagnosis_text' => 'Viral syndrome',
            'user_id' => $this->user->id
        ]);
    }

    public function test_store_with_ai_analysis()
    {
        $diagnosisData = [
            'patient_name' => 'Jane Smith',
            'patient_age' => 42,
            'patient_gender' => 'female',
            'symptoms' => 'Chest pain, shortness of breath',
            'use_ai_analysis' => true
        ];

        $aiResponse = [
            'diagnosis' => 'Possible angina pectoris',
            'confidence' => 75,
            'differential_diagnosis' => ['Myocardial infarction', 'Pulmonary embolism'],
            'recommended_tests' => ['ECG', 'Chest X-ray'],
            'urgency' => 'high'
        ];

        $this->openAIClientMock
            ->shouldReceive('ask')
            ->once()
            ->andReturn(json_encode($aiResponse));

        $request = Request::create('/diagnosis', 'POST', $diagnosisData);

        $response = $this->controller->store($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertDatabaseHas('diagnoses', [
            'patient_name' => 'Jane Smith',
            'diagnosis_text' => 'Possible angina pectoris',
            'confidence_level' => 75,
            'urgency_level' => 'high'
        ]);
    }

    public function test_show_returns_diagnosis_details()
    {
        $diagnosis = Diagnosis::factory()->create(['user_id' => $this->user->id]);

        $response = $this->controller->show($diagnosis);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($diagnosis->patient_name, $response->getContent());
    }

    public function test_edit_returns_diagnosis_edit_form()
    {
        $diagnosis = Diagnosis::factory()->create(['user_id' => $this->user->id]);

        $response = $this->controller->edit($diagnosis);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('edit', $response->getContent());
    }

    public function test_update_modifies_existing_diagnosis()
    {
        $diagnosis = Diagnosis::factory()->create([
            'user_id' => $this->user->id,
            'diagnosis_text' => 'Initial diagnosis'
        ]);

        $updateData = [
            'diagnosis_text' => 'Updated diagnosis',
            'confidence_level' => 90,
            'treatment_plan' => 'Updated treatment plan'
        ];

        $request = Request::create("/diagnosis/{$diagnosis->id}", 'PUT', $updateData);

        $response = $this->controller->update($request, $diagnosis);

        $this->assertEquals(302, $response->getStatusCode());
        $diagnosis->refresh();
        $this->assertEquals('Updated diagnosis', $diagnosis->diagnosis_text);
        $this->assertEquals(90, $diagnosis->confidence_level);
    }

    public function test_destroy_deletes_diagnosis()
    {
        $diagnosis = Diagnosis::factory()->create(['user_id' => $this->user->id]);

        $response = $this->controller->destroy($diagnosis);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertDatabaseMissing('diagnoses', ['id' => $diagnosis->id]);
    }

    public function test_search_diagnoses_by_patient()
    {
        Diagnosis::factory()->create([
            'user_id' => $this->user->id,
            'patient_name' => 'John Doe',
            'diagnosis_text' => 'Hypertension'
        ]);

        Diagnosis::factory()->create([
            'user_id' => $this->user->id,
            'patient_name' => 'John Doe',
            'diagnosis_text' => 'Diabetes'
        ]);

        Diagnosis::factory()->create([
            'user_id' => $this->user->id,
            'patient_name' => 'Jane Smith',
            'diagnosis_text' => 'Migraine'
        ]);

        $request = Request::create('/diagnosis/search', 'GET', ['patient_name' => 'John Doe']);

        $response = $this->controller->search($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData['diagnoses']);
    }

    public function test_get_patient_history()
    {
        $patientName = 'John Doe';
        $patientAge = 35;

        Diagnosis::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'patient_name' => $patientName,
            'patient_age' => $patientAge
        ]);

        $request = Request::create('/diagnosis/patient-history', 'GET', [
            'patient_name' => $patientName,
            'patient_age' => $patientAge
        ]);

        $response = $this->controller->getPatientHistory($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(3, $responseData['history']);
    }

    public function test_add_follow_up()
    {
        $diagnosis = Diagnosis::factory()->create(['user_id' => $this->user->id]);

        $followUpData = [
            'follow_up_date' => now()->addWeek()->format('Y-m-d'),
            'notes' => 'Schedule follow-up appointment',
            'priority' => 'medium'
        ];

        $request = Request::create("/diagnosis/{$diagnosis->id}/follow-up", 'POST', $followUpData);

        $response = $this->controller->addFollowUp($request, $diagnosis);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertDatabaseHas('diagnosis_follow_ups', [
            'diagnosis_id' => $diagnosis->id,
            'notes' => 'Schedule follow-up appointment'
        ]);
    }

    public function test_update_diagnosis_status()
    {
        $diagnosis = Diagnosis::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $request = Request::create("/diagnosis/{$diagnosis->id}/status", 'PATCH', [
            'status' => 'resolved',
            'resolution_notes' => 'Patient recovered fully'
        ]);

        $response = $this->controller->updateStatus($request, $diagnosis);

        $this->assertEquals(200, $response->getStatusCode());
        $diagnosis->refresh();
        $this->assertEquals('resolved', $diagnosis->status);
        $this->assertEquals('Patient recovered fully', $diagnosis->resolution_notes);
    }

    public function test_get_diagnosis_statistics()
    {
        // Create diagnoses with different statuses
        Diagnosis::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        Diagnosis::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'resolved'
        ]);

        Diagnosis::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'urgency_level' => 'high'
        ]);

        $response = $this->controller->getStatistics();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('total_diagnoses', $responseData);
        $this->assertArrayHasKey('active_diagnoses', $responseData);
        $this->assertArrayHasKey('resolved_diagnoses', $responseData);
        $this->assertArrayHasKey('high_urgency_diagnoses', $responseData);
    }

    public function test_export_diagnosis_report()
    {
        Diagnosis::factory()->count(5)->create(['user_id' => $this->user->id]);

        $request = Request::create('/diagnosis/export', 'GET', [
            'format' => 'csv',
            'date_from' => now()->subMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);

        $response = $this->controller->exportReport($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_unauthorized_user_cannot_access_other_diagnoses()
    {
        $otherUser = User::factory()->create(['role' => 'doctor']);
        $otherDiagnosis = Diagnosis::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->controller->show($otherDiagnosis);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_validate_diagnosis_data()
    {
        $invalidData = [
            'patient_name' => '', // Required field
            'patient_age' => 'invalid', // Should be numeric
            'confidence_level' => 150 // Should be between 0-100
        ];

        $request = Request::create('/diagnosis', 'POST', $invalidData);

        $response = $this->controller->store($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_bulk_update_diagnoses()
    {
        $diagnoses = Diagnosis::factory()->count(3)->create(['user_id' => $this->user->id]);
        $diagnosisIds = $diagnoses->pluck('id')->toArray();

        $request = Request::create('/diagnosis/bulk-update', 'PATCH', [
            'diagnosis_ids' => $diagnosisIds,
            'status' => 'resolved',
            'bulk_notes' => 'Bulk resolution'
        ]);

        $response = $this->controller->bulkUpdate($request);

        $this->assertEquals(200, $response->getStatusCode());

        foreach ($diagnoses as $diagnosis) {
            $diagnosis->refresh();
            $this->assertEquals('resolved', $diagnosis->status);
        }
    }
}
