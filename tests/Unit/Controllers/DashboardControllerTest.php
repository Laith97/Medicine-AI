<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Doctor\DashboardController;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Specialty;
use App\Models\Review;
use App\Models\DoctorNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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

        $this->controller = new DashboardController();
        $this->actingAs($this->user);
    }

    public function test_dashboard_index_returns_view()
    {
        $response = $this->controller->index();

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('doctor.dashboard', $response->getName());
    }

    public function test_dashboard_shows_today_appointments()
    {
        $patient = User::factory()->create(['role' => 'patient']);

        // Create today's appointments
        Appointment::factory()->count(2)->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => today()->addHours(10),
            'status' => 'confirmed'
        ]);

        $response = $this->controller->index();
        $viewData = $response->getData();

        $this->assertArrayHasKey('todayAppointments', $viewData);
        $this->assertCount(2, $viewData['todayAppointments']);
    }

    public function test_dashboard_shows_upcoming_appointments()
    {
        $patient = User::factory()->create(['role' => 'patient']);

        // Create upcoming appointments
        Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(2),
            'status' => 'confirmed'
        ]);

        $response = $this->controller->index();
        $viewData = $response->getData();

        $this->assertArrayHasKey('upcomingAppointments', $viewData);
        $this->assertCount(3, $viewData['upcomingAppointments']);
    }

    public function test_dashboard_shows_pending_appointments()
    {
        $patient = User::factory()->create(['role' => 'patient']);

        // Create pending appointments
        Appointment::factory()->count(2)->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(1),
            'status' => 'pending'
        ]);

        $response = $this->controller->index();
        $viewData = $response->getData();

        $this->assertArrayHasKey('pendingAppointments', $viewData);
        $this->assertCount(2, $viewData['pendingAppointments']);
    }

    public function test_dashboard_shows_recent_reviews()
    {
        $patient = User::factory()->create(['role' => 'patient']);

        // Create recent reviews
        Review::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'rating' => 5,
            'comment' => 'Great doctor!'
        ]);

        $response = $this->controller->index();
        $viewData = $response->getData();

        $this->assertArrayHasKey('recentReviews', $viewData);
        $this->assertCount(3, $viewData['recentReviews']);
    }

    public function test_dashboard_shows_recent_notes()
    {
        $patient = User::factory()->create(['role' => 'patient']);

        // Create recent notes
        DoctorNote::factory()->count(2)->create([
            'doctor_id' => $this->user->id,
            'patient_id' => $patient->id,
            'note_type' => 'text',
            'note_text' => 'Test note content'
        ]);

        $response = $this->controller->index();
        $viewData = $response->getData();

        $this->assertArrayHasKey('recentNotes', $viewData);
        $this->assertCount(2, $viewData['recentNotes']);
    }

    public function test_dashboard_shows_statistics()
    {
        $patient = User::factory()->create(['role' => 'patient']);

        // Create some appointments for statistics
        Appointment::factory()->count(5)->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'status' => 'completed'
        ]);

        $response = $this->controller->index();
        $viewData = $response->getData();

        $this->assertArrayHasKey('stats', $viewData);
        $this->assertIsArray($viewData['stats']);
        $this->assertArrayHasKey('total_appointments', $viewData['stats']);
        $this->assertEquals(5, $viewData['stats']['total_appointments']);
    }

    public function test_get_calendar_events()
    {
        $patient = User::factory()->create(['role' => 'patient']);

        // Create appointments for calendar
        Appointment::factory()->count(3)->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'appointment_date' => now()->addDays(1),
            'status' => 'confirmed'
        ]);

        $request = Request::create('/calendar/events', 'GET', [
            'start' => now()->startOfMonth()->toISOString(),
            'end' => now()->endOfMonth()->toISOString()
        ]);

        $response = $this->controller->getCalendarEvents($request);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(3, $responseData);
    }

    public function test_appointments_index()
    {
        $patient = User::factory()->create(['role' => 'patient']);

        // Create appointments
        Appointment::factory()->count(10)->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
        ]);

        $request = Request::create('/appointments', 'GET');
        $response = $this->controller->appointments($request);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertEquals('doctor.appointments.index', $response->getName());
    }

    public function test_appointments_can_be_filtered_by_status()
    {
        $patient = User::factory()->create(['role' => 'patient']);

        // Create appointments with different statuses
        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'status' => 'pending'
        ]);

        Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $patient->id,
            'status' => 'confirmed'
        ]);

        $request = Request::create('/appointments', 'GET', ['status' => 'pending']);
        $response = $this->controller->appointments($request);

        $viewData = $response->getData();
        $appointments = $viewData['appointments'];

        $this->assertCount(1, $appointments);
        $this->assertEquals('pending', $appointments->first()->status);
    }
}
