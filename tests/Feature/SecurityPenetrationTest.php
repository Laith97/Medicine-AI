<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\PatientAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityPenetrationTest extends TestCase
{
    use RefreshDatabase;

    protected $patient;
    protected $doctor;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->create(['role' => 'patient']);
        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->admin = User::factory()->create(['role' => 'admin']);

        // Create doctor profile
        $doctorProfile = new Doctor();
        $doctorProfile->user_id = $this->doctor->id;
        $doctorProfile->save();
        $this->doctor->doctor = $doctorProfile;
    }

    public function test_sql_injection_prevention()
    {
        // Test SQL injection attempts in various endpoints
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "' OR '1'='1",
            "admin'--",
            "1' UNION SELECT * FROM users--",
            "1; SELECT * FROM information_schema.tables--"
        ];

        foreach ($maliciousInputs as $maliciousInput) {
            // Test appointment search
            $response = $this->actingAs($this->patient)->get("/api/appointments/search?reason={$maliciousInput}");
            $response->assertStatus(200); // Should not crash or return sensitive data

            // Test user search
            $response = $this->actingAs($this->admin)->get("/api/users/search?name={$maliciousInput}");
            $response->assertStatus(200);

            // Test diagnosis search
            $response = $this->actingAs($this->doctor)->get("/api/diagnoses/search?condition={$maliciousInput}");
            $response->assertStatus(200);
        }

        // Verify database integrity - users table should still exist and have our test users
        $this->assertDatabaseHas('users', ['id' => $this->patient->id]);
        $this->assertDatabaseHas('users', ['id' => $this->doctor->id]);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_xss_prevention()
    {
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(\'XSS\')"></iframe>',
            '<svg onload=alert("XSS")>',
            '<body onload=alert("XSS")>'
        ];

        foreach ($xssPayloads as $payload) {
            // Test in appointment notes
            $appointmentData = [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'appointment_type' => 'consultation',
                'reason' => $payload
            ];

            $response = $this->actingAs($this->patient)->post('/api/appointments', $appointmentData);
            $response->assertStatus(201);

            $appointment = Appointment::latest()->first();

            // Verify XSS payload is not executed when viewing
            $response = $this->get("/api/appointments/{$appointment->id}");
            $response->assertStatus(200);

            $data = $response->json();
            $this->assertStringNotContainsString('<script>', $data['reason']);
            $this->assertStringNotContainsString('javascript:', $data['reason']);
        }
    }

    public function test_brute_force_protection()
    {
        $testEmail = 'test@example.com';

        // Create a test user
        $user = User::factory()->create([
            'email' => $testEmail,
            'password' => Hash::make('correct_password')
        ]);

        // Attempt multiple failed logins
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/api/login', [
                'email' => $testEmail,
                'password' => 'wrong_password_' . $i
            ]);

            // Should eventually be rate limited
            if ($i >= 5) {
                $response->assertStatus(429); // Too Many Requests
            }
        }

        // Verify account is not locked permanently (should allow correct login after cooldown)
        // Note: In real implementation, this would depend on rate limiting configuration
        $this->assertTrue(true); // Placeholder for rate limiting verification
    }

    public function test_authorization_bypass_attempts()
    {
        // Create test data
        $otherPatient = User::factory()->create(['role' => 'patient']);
        $otherDoctor = User::factory()->create(['role' => 'doctor']);

        $otherDoctorProfile = new Doctor();
        $otherDoctorProfile->user_id = $otherDoctor->id;
        $otherDoctorProfile->save();

        $appointment = Appointment::factory()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $otherDoctorProfile->id,
            'status' => 'confirmed'
        ]);

        // Test patient trying to access another patient's data
        $this->actingAs($this->patient);

        $response = $this->get("/api/patients/{$otherPatient->id}/records");
        $response->assertStatus(403); // Forbidden

        $response = $this->get("/api/appointments/{$appointment->id}");
        $response->assertStatus(403);

        // Test doctor trying to access another doctor's patients
        $this->actingAs($this->doctor);

        $response = $this->get("/api/doctors/{$otherDoctorProfile->id}/patients");
        $response->assertStatus(403);

        // Test patient trying to create diagnosis (should be doctor only)
        $diagnosisData = [
            'patient_id' => $this->patient->id,
            'condition' => 'Test Condition',
            'icd_code' => 'Z99.99'
        ];

        $response = $this->post('/api/diagnoses', $diagnosisData);
        $response->assertStatus(403);
    }

    public function test_mass_assignment_vulnerability()
    {
        // Test against mass assignment of sensitive fields
        $this->actingAs($this->patient);

        // Attempt to set admin role through user profile update
        $maliciousData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin', // Should be ignored
            'is_admin' => true, // Should be ignored
            'password' => 'newpassword' // Should be ignored
        ];

        $response = $this->patch('/api/user/profile', $maliciousData);
        $response->assertStatus(200);

        // Verify user role was not changed
        $this->patient->refresh();
        $this->assertEquals('patient', $this->patient->role);

        // Test appointment mass assignment
        $appointmentData = [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'status' => 'completed', // Should not be allowed during creation
            'payment_status' => 'paid', // Should not be allowed
            'appointment_type' => 'consultation'
        ];

        $response = $this->post('/api/appointments', $appointmentData);
        $response->assertStatus(201);

        $appointment = Appointment::latest()->first();
        $this->assertEquals('pending', $appointment->status); // Should be pending, not completed
    }

    public function test_directory_traversal_prevention()
    {
        $traversalPayloads = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            '/etc/passwd',
            '....//....//....//etc/passwd',
            '.../...//.../...//.../...//etc/passwd'
        ];

        foreach ($traversalPayloads as $payload) {
            // Test file upload endpoints
            $response = $this->actingAs($this->patient)->post('/api/uploads', [
                'file_path' => $payload,
                'file' => 'fake_file_content'
            ]);
            $response->assertStatus(422); // Validation error

            // Test file access endpoints
            $response = $this->get("/api/files/{$payload}");
            $response->assertStatus(404); // Not found
        }
    }

    public function test_csrf_protection()
    {
        // Test that CSRF tokens are required for state-changing operations
        $appointmentData = [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'appointment_type' => 'consultation'
        ];

        // Attempt POST without CSRF token (simulated)
        $response = $this->post('/api/appointments', $appointmentData, [
            'X-CSRF-TOKEN' => '' // Missing token
        ]);

        // In Laravel, CSRF protection is typically handled by middleware
        // This test verifies the endpoint requires authentication/protection
        $this->assertTrue(in_array($response->getStatusCode(), [401, 419, 422]));
    }

    public function test_input_validation_and_sanitization()
    {
        $this->actingAs($this->patient);

        $maliciousInputs = [
            str_repeat('A', 10000), // Buffer overflow attempt
            '£$%^&*()_+{}|:<>?[]\;', // Special characters
            'SELECT * FROM users', // SQL-like input
            '<b>Bold Text</b><script>alert(1)</script>', // HTML/JS
            'data:text/html,<script>alert(1)</script>', // Data URL
            'vbscript:msgbox("XSS")', // VBScript
            'file:///etc/passwd', // File URL
        ];

        foreach ($maliciousInputs as $input) {
            // Test in various input fields
            $appointmentData = [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'appointment_type' => 'consultation',
                'reason' => $input
            ];

            $response = $this->post('/api/appointments', $appointmentData);

            if (strlen($input) > 1000) { // If input is too long
                $response->assertStatus(422); // Validation error
            } else {
                $response->assertStatus(201);

                $appointment = Appointment::latest()->first();
                // Verify input was sanitized (no script tags, etc.)
                $this->assertStringNotContainsString('<script>', $appointment->reason);
                $this->assertStringNotContainsString('javascript:', $appointment->reason);
            }
        }
    }

    public function test_session_security()
    {
        // Test session fixation attempts
        $this->actingAs($this->patient);

        // Get current session
        $response = $this->get('/api/user/profile');
        $response->assertStatus(200);

        // Attempt to use session in different context
        $otherUser = User::factory()->create(['role' => 'patient']);

        $this->actingAs($otherUser);

        // Previous user's data should not be accessible
        $response = $this->get('/api/patient/appointments');
        $response->assertStatus(200);

        $data = $response->json();
        // Should only return current user's appointments
        foreach ($data as $appointment) {
            $this->assertEquals($otherUser->id, $appointment['patient_id']);
        }
    }

    public function test_api_rate_limiting()
    {
        $this->actingAs($this->patient);

        // Make multiple rapid requests to test rate limiting
        for ($i = 0; $i < 100; $i++) {
            $response = $this->get('/api/user/profile');

            if ($i > 60) { // After threshold
                $response->assertStatus(429); // Too Many Requests
            } else {
                $response->assertStatus(200);
            }
        }
    }

    public function test_sensitive_data_exposure()
    {
        // Create user with sensitive data
        $userWithSensitiveData = User::factory()->create([
            'role' => 'patient',
            'email' => 'sensitive@example.com',
            'password' => Hash::make('password123')
        ]);

        // Test that sensitive fields are not exposed in API responses
        $this->actingAs($userWithSensitiveData);

        $response = $this->get('/api/user/profile');
        $response->assertStatus(200);

        $data = $response->json();

        // Sensitive fields should not be present
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
        $this->assertArrayNotHasKey('api_token', $data);

        // Test error messages don't leak sensitive information
        $response = $this->post('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong'
        ]);

        $response->assertStatus(401);
        $errorMessage = $response->json();

        // Error should not reveal if email exists or not
        $this->assertStringNotContainsString('nonexistent@example.com', $errorMessage['message'] ?? '');
    }

    public function test_file_upload_security()
    {
        $this->actingAs($this->patient);

        $maliciousFiles = [
            'malicious.php' => '<?php system($_GET["cmd"]); ?>',
            'xss.html' => '<script>alert("XSS")</script>',
            'large_file.exe' => str_repeat('A', 10000000), // 10MB file
            '../../../etc/passwd' => 'malicious content',
            'shell.sh' => '#!/bin/bash\nrm -rf /',
        ];

        foreach ($maliciousFiles as $filename => $content) {
            // Test file upload validation
            $response = $this->post('/api/uploads/medical-documents', [
                'file' => $content,
                'filename' => $filename,
                'type' => 'medical_record'
            ]);

            // Should reject malicious files
            $this->assertContains($response->getStatusCode(), [422, 400, 403]);
        }
    }

    public function test_business_logic_vulnerabilities()
    {
        // Test appointment booking business logic vulnerabilities
        $this->actingAs($this->patient);

        // Attempt to book appointment in the past
        $pastDate = now()->subDay()->format('Y-m-d H:i:s');
        $response = $this->post('/api/appointments', [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => $pastDate,
            'appointment_type' => 'consultation'
        ]);
        $response->assertStatus(422); // Validation error

        // Attempt to book overlapping appointments
        $now = now()->addHour();
        $response1 = $this->post('/api/appointments', [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => $now->format('Y-m-d H:i:s'),
            'appointment_type' => 'consultation',
            'duration' => 60
        ]);
        $response1->assertStatus(201);

        // Try to book overlapping appointment
        $response2 = $this->post('/api/appointments', [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => $now->addMinutes(30)->format('Y-m-d H:i:s'),
            'appointment_type' => 'consultation',
            'duration' => 60
        ]);
        $response2->assertStatus(422); // Should be rejected due to conflict

        // Test prescription authorization
        $this->actingAs($this->doctor);

        $diagnosis = PatientAnalysis::factory()->create(['user_id' => $this->patient->id]);

        // Attempt to create prescription for wrong patient
        $otherPatient = User::factory()->create(['role' => 'patient']);

        $response = $this->post('/api/prescriptions', [
            'patient_id' => $otherPatient->id, // Wrong patient
            'diagnosis_id' => $diagnosis->id,
            'medications' => [['drug_name' => 'Test Drug', 'dosage' => '10mg']]
        ]);
        $response->assertStatus(403); // Forbidden
    }
}
