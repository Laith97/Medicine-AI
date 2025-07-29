<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\OpenAIController;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\PatientAnalysis;
use App\Models\Symptom;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAIControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;
    protected $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'doctor',
        ]);

        $specialty = Specialty::factory()->create();
        $this->doctor = Doctor::factory()->create([
            'user_id' => $this->user->id,
            'specialty_id' => $specialty->id
        ]);

        // Create a setting for the user
        Setting::factory()->create([
            'user_id' => $this->user->id,
            'criterion' => 'CDC',
            'specialty' => 'Internal Medicine'
        ]);

        $this->controller = new OpenAIController();
        $this->actingAs($this->user);
    }

    public function test_show_form_returns_view()
    {
        // Create some symptoms for the form
        Symptom::factory()->count(5)->create();

        $request = Request::create('/openai', 'GET');
        $response = $this->controller->showForm($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('openai', $response->getName());

        $viewData = $response->getData();
        $this->assertArrayHasKey('symptoms', $viewData);
        $this->assertArrayHasKey('existingPatients', $viewData);
    }

    public function test_show_form_with_existing_patients()
    {
        // Create some existing patient records with the same patient key
        $patientKey = PatientAnalysis::generatePatientKey('John Doe', 35, 'male', $this->user->id);

        PatientAnalysis::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'age' => 35,
            'gender' => 'male',
            'patient_key' => $patientKey
        ]);

        $request = Request::create('/openai', 'GET');
        $response = $this->controller->showForm($request);

        $viewData = $response->getData();
        $this->assertArrayHasKey('existingPatients', $viewData);
        $this->assertCount(1, $viewData['existingPatients']); // Should be grouped by patient
    }

    public function test_show_form_with_edit_patient()
    {
        $patient = PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Jane Smith',
            'age' => 28,
            'symptoms' => 'Headache, fever'
        ]);

        $request = Request::create('/openai', 'GET', ['edit_patient' => $patient->id]);
        $response = $this->controller->showForm($request);

        $viewData = $response->getData();
        $this->assertArrayHasKey('patientToEdit', $viewData);
        $this->assertEquals($patient->id, $viewData['patientToEdit']->id);
    }

    public function test_show_form_groups_patients_correctly()
    {
        // Create multiple visits for the same patient
        $patientKey = PatientAnalysis::generatePatientKey('John Doe', 30, 'male', $this->user->id);

        PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'age' => 30,
            'gender' => 'male',
            'patient_key' => $patientKey,
            'visit_number' => 1
        ]);

        PatientAnalysis::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'age' => 30,
            'gender' => 'male',
            'patient_key' => $patientKey,
            'visit_number' => 2
        ]);

        $request = Request::create('/openai', 'GET');
        $response = $this->controller->showForm($request);

        $viewData = $response->getData();
        $this->assertArrayHasKey('simplifiedVisits', $viewData);
        $this->assertArrayHasKey($patientKey, $viewData['simplifiedVisits']);
        $this->assertEquals(2, $viewData['simplifiedVisits'][$patientKey]['count']);
    }
}
