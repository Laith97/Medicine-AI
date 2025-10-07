<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\PatientRiskScore;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class RiskBadgesDisplayTest extends TestCase
{
    use RefreshDatabase;

    private $doctor;
    private $doctorUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create specialty
        $specialty = Specialty::create([
            'name' => 'General Practice',
            'description' => 'General medical practice'
        ]);

        // Create doctor user
        $this->doctorUser = User::create([
            'name' => 'Dr. Test Doctor',
            'email' => 'doctor@test.com',
            'password' => bcrypt('password'),
            'role' => 'doctor',
            'email_verified_at' => now(),
        ]);

        // Create doctor profile
        $this->doctor = Doctor::create([
            'user_id' => $this->doctorUser->id,
            'specialty_id' => $specialty->id,
            'license_number' => 'TEST123456',
            'phone' => '+1234567890',
            'bio' => 'Test doctor for risk badges',
            'consultation_fee' => 10000,
            'appointment_duration' => 30,
            'is_active' => true,
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /** @test */
    public function low_risk_patients_display_green_badge()
    {
        // Create low risk patient
        $patient = User::create([
            'name' => 'Low Risk Patient',
            'email' => 'low@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        // Create appointment
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1),
            'appointment_end' => now()->addDays(1)->addMinutes(30),
            'status' => 'confirmed',
            'duration' => 30,
            'fee' => 10000,
            'confirmed_at' => now(),
        ]);

        // Create low risk score (both risks < 0.3)
        PatientRiskScore::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => 0.1,
            'hospitalization_risk' => 0.05,
        ]);

        // Act as doctor and visit appointments page
        Auth::login($this->doctorUser);
        $response = $this->get(route('doctor.appointments.index'));

        // Assert response is successful
        $response->assertStatus(200);

        // Assert the view contains the green badge for low risk
        $response->assertSee('<span class="badge bg-success">', false);
        $response->assertSee('<i class="fas fa-check-circle me-1"></i>Low', false);
    }

    /** @test */
    public function medium_risk_patients_display_yellow_badge()
    {
        // Create medium risk patient
        $patient = User::create([
            'name' => 'Medium Risk Patient',
            'email' => 'medium@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        // Create appointment
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1),
            'appointment_end' => now()->addDays(1)->addMinutes(30),
            'status' => 'confirmed',
            'duration' => 30,
            'fee' => 10000,
            'confirmed_at' => now(),
        ]);

        // Create medium risk score (max risk between 0.3 and 0.7)
        PatientRiskScore::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => 0.4,
            'hospitalization_risk' => 0.2,
        ]);

        // Act as doctor and visit appointments page
        Auth::login($this->doctorUser);
        $response = $this->get(route('doctor.appointments.index'));

        // Assert response is successful
        $response->assertStatus(200);

        // Assert the view contains the yellow badge for medium risk
        $response->assertSee('<span class="badge bg-warning">', false);
        $response->assertSee('<i class="fas fa-exclamation-triangle me-1"></i>Medium', false);
    }

    /** @test */
    public function high_risk_patients_display_red_badge()
    {
        // Create high risk patient
        $patient = User::create([
            'name' => 'High Risk Patient',
            'email' => 'high@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        // Create appointment
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1),
            'appointment_end' => now()->addDays(1)->addMinutes(30),
            'status' => 'confirmed',
            'duration' => 30,
            'fee' => 10000,
            'confirmed_at' => now(),
        ]);

        // Create high risk score (max risk >= 0.7)
        PatientRiskScore::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => 0.8,
            'hospitalization_risk' => 0.9,
        ]);

        // Act as doctor and visit appointments page
        Auth::login($this->doctorUser);
        $response = $this->get(route('doctor.appointments.index'));

        // Assert response is successful
        $response->assertStatus(200);

        // Assert the view contains the red badge for high risk
        $response->assertSee('<span class="badge bg-danger">', false);
        $response->assertSee('<i class="fas fa-exclamation-triangle me-1"></i>High', false);
    }

    /** @test */
    public function appointments_without_risk_scores_show_na()
    {
        // Create patient without risk score
        $patient = User::create([
            'name' => 'No Risk Score Patient',
            'email' => 'no-risk@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        // Create appointment
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1),
            'appointment_end' => now()->addDays(1)->addMinutes(30),
            'status' => 'confirmed',
            'duration' => 30,
            'fee' => 10000,
            'confirmed_at' => now(),
        ]);

        // No risk score created

        // Act as doctor and visit appointments page
        Auth::login($this->doctorUser);
        $response = $this->get(route('doctor.appointments.index'));

        // Assert response is successful
        $response->assertStatus(200);

        // Assert the view shows N/A for risk
        $response->assertSee('<span class="text-muted">N/A</span>', false);
    }

    /** @test */
    public function risk_filtering_works_correctly()
    {
        // Create patients with different risk levels
        $lowRiskPatient = User::create([
            'name' => 'Low Risk Patient',
            'email' => 'low@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        $mediumRiskPatient = User::create([
            'name' => 'Medium Risk Patient',
            'email' => 'medium@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        $highRiskPatient = User::create([
            'name' => 'High Risk Patient',
            'email' => 'high@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        // Create appointments
        $lowAppointment = Appointment::create([
            'patient_id' => $lowRiskPatient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(1),
            'appointment_end' => now()->addDays(1)->addMinutes(30),
            'status' => 'confirmed',
            'duration' => 30,
            'fee' => 10000,
            'confirmed_at' => now(),
        ]);

        $mediumAppointment = Appointment::create([
            'patient_id' => $mediumRiskPatient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(2),
            'appointment_end' => now()->addDays(2)->addMinutes(30),
            'status' => 'confirmed',
            'duration' => 30,
            'fee' => 10000,
            'confirmed_at' => now(),
        ]);

        $highAppointment = Appointment::create([
            'patient_id' => $highRiskPatient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(3),
            'appointment_end' => now()->addDays(3)->addMinutes(30),
            'status' => 'confirmed',
            'duration' => 30,
            'fee' => 10000,
            'confirmed_at' => now(),
        ]);

        // Create risk scores
        PatientRiskScore::create([
            'patient_id' => $lowRiskPatient->id,
            'appointment_id' => $lowAppointment->id,
            'no_show_risk' => 0.1,
            'hospitalization_risk' => 0.05,
        ]);

        PatientRiskScore::create([
            'patient_id' => $mediumRiskPatient->id,
            'appointment_id' => $mediumAppointment->id,
            'no_show_risk' => 0.4,
            'hospitalization_risk' => 0.2,
        ]);

        PatientRiskScore::create([
            'patient_id' => $highRiskPatient->id,
            'appointment_id' => $highAppointment->id,
            'no_show_risk' => 0.8,
            'hospitalization_risk' => 0.9,
        ]);

        // Act as doctor
        Auth::login($this->doctorUser);

        // Test no filter shows all appointments
        $response = $this->get(route('doctor.appointments.index'));

        $response->assertStatus(200);
        $response->assertSee('Low Risk Patient');
        $response->assertSee('Medium Risk Patient');
        $response->assertSee('High Risk Patient');

        // Test filtering by low risk
        $response = $this->get(route('doctor.appointments.index', ['risk_category' => 'low']));

        $response->assertStatus(200);
        $response->assertSee('Low Risk Patient');
        $response->assertDontSee('Medium Risk Patient');
        $response->assertDontSee('High Risk Patient');

        // Test filtering by medium risk
        $response = $this->get(route('doctor.appointments.index', ['risk_category' => 'medium']));

        $response->assertStatus(200);
        $response->assertSee('Medium Risk Patient');
        $response->assertDontSee('Low Risk Patient');
        $response->assertDontSee('High Risk Patient');

        // Test filtering by high risk
        $response = $this->get(route('doctor.appointments.index', ['risk_category' => 'high']));

        $response->assertStatus(200);
        $response->assertSee('High Risk Patient');
        $response->assertDontSee('Low Risk Patient');
        $response->assertDontSee('Medium Risk Patient');
    }

    /** @test */
    public function risk_filtering_handles_edge_cases_correctly()
    {
        // Create patients for edge cases
        $exactlyLowPatient = User::create([
            'name' => 'Exactly Low Risk Patient',
            'email' => 'exactly-low@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        $exactlyMediumPatient = User::create([
            'name' => 'Exactly Medium Risk Patient',
            'email' => 'exactly-medium@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        $exactlyHighPatient = User::create([
            'name' => 'Exactly High Risk Patient',
            'email' => 'exactly-high@test.com',
            'password' => bcrypt('password'),
            'role' => 'patient',
            'email_verified_at' => now(),
        ]);

        // Create appointments
        $exactlyLowAppointment = Appointment::create([
            'patient_id' => $exactlyLowPatient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(4),
            'appointment_end' => now()->addDays(4)->addMinutes(30),
            'status' => 'confirmed',
            'duration' => 30,
            'fee' => 10000,
            'confirmed_at' => now(),
        ]);

        $exactlyMediumAppointment = Appointment::create([
            'patient_id' => $exactlyMediumPatient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(5),
            'appointment_end' => now()->addDays(5)->addMinutes(30),
            'status' => 'confirmed',
            'duration' => 30,
            'fee' => 10000,
            'confirmed_at' => now(),
        ]);

        $exactlyHighAppointment = Appointment::create([
            'patient_id' => $exactlyHighPatient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_date' => now()->addDays(6),
            'appointment_end' => now()->addDays(6)->addMinutes(30),
            'status' => 'confirmed',
            'duration' => 30,
            'fee' => 10000,
            'confirmed_at' => now(),
        ]);

        // Create edge case risk scores
        // Exactly 0.3 - should be medium (since >= 0.3)
        PatientRiskScore::create([
            'patient_id' => $exactlyLowPatient->id,
            'appointment_id' => $exactlyLowAppointment->id,
            'no_show_risk' => 0.3,
            'hospitalization_risk' => 0.1,
        ]);

        // Exactly 0.7 - should be high (since >= 0.7)
        PatientRiskScore::create([
            'patient_id' => $exactlyMediumPatient->id,
            'appointment_id' => $exactlyMediumAppointment->id,
            'no_show_risk' => 0.7,
            'hospitalization_risk' => 0.2,
        ]);

        // Higher than 0.7 - should be high
        PatientRiskScore::create([
            'patient_id' => $exactlyHighPatient->id,
            'appointment_id' => $exactlyHighAppointment->id,
            'no_show_risk' => 0.8,
            'hospitalization_risk' => 0.9,
        ]);

        // Act as doctor
        Auth::login($this->doctorUser);

        // Test that exactly 0.3 is treated as medium risk
        $response = $this->get(route('doctor.appointments.index', ['risk_category' => 'medium']));
        $response->assertStatus(200);
        $response->assertSee('Exactly Low Risk Patient');

        // Test that exactly 0.7 is treated as high risk
        $response = $this->get(route('doctor.appointments.index', ['risk_category' => 'high']));
        $response->assertStatus(200);
        $response->assertSee('Exactly Medium Risk Patient');
        $response->assertSee('Exactly High Risk Patient');
    }
}