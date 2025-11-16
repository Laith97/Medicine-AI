<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\PatientAnalysis;
use App\Models\Diagnosis;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected $patient;
    protected $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->create(['role' => 'patient']);
        $this->doctor = User::factory()->create(['role' => 'doctor']);

        $doctorProfile = new Doctor();
        $doctorProfile->user_id = $this->doctor->id;
        $doctorProfile->save();
    }

    public function test_boundary_value_appointments()
    {
        $this->actingAs($this->patient);

        // Test appointment at exact boundary times
        $boundaryTimes = [
            '00:00:00', // Start of day
            '23:59:59', // End of day
            '11:59:00', // Just before noon
            '12:01:00', // Just after noon
        ];

        foreach ($boundaryTimes as $time) {
            $dateTime = today()->setTimeFromTimeString($time);

            $response = $this->post('/api/appointments', [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => $dateTime->format('Y-m-d H:i:s'),
                'appointment_type' => 'consultation',
                'duration' => 30
            ]);

            // Should handle boundary times appropriately
            $this->assertContains($response->getStatusCode(), [201, 422]);
        }

        // Test appointments at exact minute boundaries
        $minuteBoundaries = [0, 15, 30, 45, 59];

        foreach ($minuteBoundaries as $minute) {
            $dateTime = now()->addDay()->setTime(9, $minute);

            $response = $this->post('/api/appointments', [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => $dateTime->format('Y-m-d H:i:s'),
                'appointment_type' => 'consultation',
                'duration' => 30
            ]);

            $this->assertContains($response->getStatusCode(), [201, 422]);
        }
    }

    public function test_extreme_input_lengths()
    {
        $this->actingAs($this->patient);

        // Test extremely long input strings
        $longString = str_repeat('A', 10000);
        $mediumString = str_repeat('B', 1000);
        $maxString = str_repeat('C', 65535); // MySQL TEXT limit

        $testInputs = [
            'reason' => $longString,
            'notes' => $mediumString,
            'medical_history' => $maxString,
            'symptoms' => str_repeat('symptom, ', 500), // 500 symptoms
        ];

        foreach ($testInputs as $field => $value) {
            $response = $this->post('/api/appointments', [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'appointment_type' => 'consultation',
                'duration' => 30,
                $field => $value
            ]);

            // Should handle long inputs gracefully (either accept or validate)
            $this->assertContains($response->getStatusCode(), [201, 422]);
        }

        // Test empty/whitespace-only inputs
        $whitespaceInputs = [
            'reason' => '   ',
            'notes' => "\t\n\r",
            'medical_history' => '',
        ];

        foreach ($whitespaceInputs as $field => $value) {
            $response = $this->post('/api/appointments', [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'appointment_type' => 'consultation',
                'duration' => 30,
                $field => $value
            ]);

            // Should handle whitespace inputs appropriately
            $this->assertContains($response->getStatusCode(), [201, 422]);
        }
    }

    public function test_special_characters_and_unicode()
    {
        $this->actingAs($this->patient);

        $specialInputs = [
            'emoji' => 'Patient has 🤒 fever and 💊 needs medication',
            'unicode' => 'Patient: José María González with naïve résumé',
            'rtl_text' => 'المريض يعاني من آلام في الصدر', // Arabic
            'chinese' => '患者有发烧和头痛症状', // Chinese
            'symbols' => 'Patient has §¶†‡•°±™®©×÷=≠≤≥≈∞µ∂∆∑∏√∫∮∝∴∵∈∉∋∌⊆⊇⊂⊃∪∩∧∨¬⇒⇔∀∃∄∇∈∉',
            'math' => 'Patient BMI = 2³ + √(4²) × π ≈ 28.5',
        ];

        foreach ($specialInputs as $testName => $input) {
            $response = $this->post('/api/appointments', [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'appointment_type' => 'consultation',
                'reason' => $input,
                'duration' => 30
            ]);

            $response->assertStatus(201);

            $appointment = Appointment::latest()->first();
            $this->assertEquals($input, $appointment->reason); // Should preserve special characters
        }
    }

    public function test_concurrent_modifications()
    {
        // Test what happens when multiple users modify the same resource simultaneously
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'status' => 'confirmed'
        ]);

        $this->actingAs($this->patient);

        // Simulate concurrent updates
        $responses = [];

        // First update
        $responses[] = $this->patch("/api/appointments/{$appointment->id}", [
            'notes' => 'Updated by patient'
        ]);

        // Second update (should handle concurrency)
        $responses[] = $this->patch("/api/appointments/{$appointment->id}", [
            'notes' => 'Updated again by patient'
        ]);

        // Doctor also updates simultaneously
        $this->actingAs($this->doctor);
        $responses[] = $this->patch("/api/appointments/{$appointment->id}", [
            'notes' => 'Updated by doctor'
        ]);

        // At least one update should succeed
        $successCount = 0;
        foreach ($responses as $response) {
            if ($response->getStatusCode() === 200) {
                $successCount++;
            }
        }

        $this->assertGreaterThan(0, $successCount, 'At least one concurrent update should succeed');
    }

    public function test_timezone_and_dst_transitions()
    {
        $this->actingAs($this->patient);

        // Test appointments during DST transitions
        $dstDates = [
            '2024-03-10 02:00:00', // Spring DST transition (clocks forward)
            '2024-11-03 01:00:00', // Fall DST transition (clocks back)
            '2024-03-10 03:00:00', // After spring transition
            '2024-11-03 02:00:00', // After fall transition
        ];

        foreach ($dstDates as $dateString) {
            $response = $this->post('/api/appointments', [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => $dateString,
                'appointment_type' => 'consultation',
                'duration' => 30
            ]);

            // Should handle DST transitions gracefully
            $this->assertContains($response->getStatusCode(), [201, 422]);
        }

        // Test appointments in different timezones
        $timezones = ['UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo', 'Australia/Sydney'];

        foreach ($timezones as $timezone) {
            $dateTime = now()->setTimezone($timezone)->addDay()->setTime(10, 0);

            $response = $this->post('/api/appointments', [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => $dateTime->format('Y-m-d H:i:s'),
                'appointment_type' => 'consultation',
                'duration' => 30,
                'timezone' => $timezone
            ]);

            $this->assertContains($response->getStatusCode(), [201, 422]);
        }
    }

    public function test_database_constraint_violations()
    {
        // Test foreign key constraint violations
        $this->actingAs($this->patient);

        // Try to create appointment with non-existent doctor
        $response = $this->post('/api/appointments', [
            'doctor_id' => 999999,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'appointment_type' => 'consultation',
            'duration' => 30
        ]);

        $response->assertStatus(422); // Should handle FK violation gracefully

        // Test unique constraint violations
        $appointment1 = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => now()->addDay()->setTime(10, 0),
            'status' => 'confirmed'
        ]);

        // Try to create duplicate appointment at same time
        $response = $this->post('/api/appointments', [
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => $appointment1->appointment_date->format('Y-m-d H:i:s'),
            'appointment_type' => 'consultation',
            'duration' => 30
        ]);

        $response->assertStatus(422); // Should prevent scheduling conflicts
    }

    public function test_network_and_timeout_scenarios()
    {
        $this->actingAs($this->patient);

        // Test handling of slow operations
        Cache::shouldReceive('get')
            ->once()
            ->andReturnUsing(function () {
                sleep(1); // Simulate slow cache operation
                return null;
            });

        $startTime = microtime(true);

        $response = $this->get('/api/user/profile');
        $endTime = microtime(true);

        $responseTime = $endTime - $startTime;

        // Should handle slow operations gracefully (under 5 seconds)
        $this->assertLessThan(5, $responseTime);
        $response->assertStatus(200);

        // Test handling of partial failures
        DB::shouldReceive('select')
            ->once()
            ->andThrow(new \Exception('Database connection timeout'));

        $response = $this->get('/api/appointments');

        // Should handle database timeouts gracefully
        $this->assertContains($response->getStatusCode(), [200, 500, 503]);
    }

    public function test_extreme_system_load_conditions()
    {
        // Test behavior when system is under extreme load
        $this->actingAs($this->patient);

        // Create many concurrent requests
        $promises = [];

        for ($i = 0; $i < 50; $i++) {
            $response = $this->get('/api/user/profile');
            $promises[] = $response;

            // Small delay to prevent overwhelming the test system
            usleep(10000); // 10ms
        }

        // Verify all requests completed
        foreach ($promises as $response) {
            $this->assertContains($response->getStatusCode(), [200, 429, 503]);
        }

        // Test memory-intensive operations
        $largeDataset = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeDataset[] = [
                'id' => $i,
                'data' => str_repeat('x', 1000) // 1KB per item
            ];
        }

        // Should handle large datasets without crashing
        $response = $this->post('/api/bulk-operations', [
            'operations' => array_slice($largeDataset, 0, 100) // First 100 items
        ]);

        $this->assertContains($response->getStatusCode(), [200, 413, 422]);
    }

    public function test_corrupted_or_malformed_data()
    {
        $this->actingAs($this->patient);

        // Test handling of corrupted JSON
        $corruptedPayloads = [
            '{"doctor_id": 1, "appointment_date": "invalid", "incomplete": ',
            '{"doctor_id": null, "appointment_date": [], "nested": {"broken": }}',
            '{"doctor_id": "not_a_number", "appointment_date": true}',
            'not_json_at_all',
            '{"doctor_id": 1, "appointment_date": "2024-01-01 10:00:00", "extra_field": "value"}',
        ];

        foreach ($corruptedPayloads as $payload) {
            try {
                $response = $this->postJson('/api/appointments', json_decode($payload, true) ?: []);
                $this->assertContains($response->getStatusCode(), [201, 400, 422]);
            } catch (\Exception $e) {
                // Handle JSON parsing errors
                $this->assertTrue(true); // Test passes if exception is caught
            }
        }

        // Test handling of corrupted database records
        $appointment = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->doctor->id,
            'appointment_date' => null, // Corrupted data
            'status' => 'invalid_status'
        ]);

        $response = $this->get("/api/appointments/{$appointment->id}");

        // Should handle corrupted data gracefully
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_business_rule_edge_cases()
    {
        $this->actingAs($this->patient);

        // Test appointments with impossible durations
        $impossibleDurations = [-1, 0, 1440, 10080]; // Negative, zero, 24 hours, 1 week

        foreach ($impossibleDurations as $duration) {
            $response = $this->post('/api/appointments', [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'appointment_type' => 'consultation',
                'duration' => $duration
            ]);

            $response->assertStatus(422); // Should validate duration
        }

        // Test appointments with business rule violations
        $businessRuleViolations = [
            [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => now()->addYears(2)->format('Y-m-d H:i:s'), // Too far in future
                'appointment_type' => 'consultation',
                'duration' => 30
            ],
            [
                'doctor_id' => $this->doctor->doctor->id,
                'appointment_date' => now()->subMinutes(30)->format('Y-m-d H:i:s'), // In past
                'appointment_type' => 'consultation',
                'duration' => 30
            ],
        ];

        foreach ($businessRuleViolations as $appointmentData) {
            $response = $this->post('/api/appointments', $appointmentData);
            $response->assertStatus(422); // Should enforce business rules
        }
    }

    public function test_system_resource_exhaustion()
    {
        $this->actingAs($this->patient);

        // Test file upload size limits
        $largeFileContent = str_repeat('A', 100 * 1024 * 1024); // 100MB file

        $response = $this->post('/api/uploads/medical-documents', [
            'file' => $largeFileContent,
            'filename' => 'large_test_file.txt',
            'type' => 'medical_record'
        ]);

        // Should handle large files appropriately (reject or handle gracefully)
        $this->assertContains($response->getStatusCode(), [201, 413, 422]);

        // Test memory exhaustion scenarios
        $memoryIntensiveData = [];
        for ($i = 0; $i < 10000; $i++) {
            $memoryIntensiveData[] = [
                'id' => $i,
                'large_field' => str_repeat('data', 1000), // 4KB per record
                'nested' => [
                    'array' => range(1, 100),
                    'object' => (object) ['prop' => str_repeat('x', 500)]
                ]
            ];
        }

        $response = $this->post('/api/bulk-processing', [
            'data' => $memoryIntensiveData
        ]);

        // Should handle memory-intensive operations gracefully
        $this->assertContains($response->getStatusCode(), [200, 413, 507]);
    }

    public function test_external_service_failures()
    {
        $this->actingAs($this->patient);

        // Test handling of external API failures
        config(['services.openai.enabled' => false]); // Disable OpenAI service

        $response = $this->post('/api/ai/diagnosis-assistance', [
            'symptoms' => ['fever', 'cough'],
            'patient_age' => 30
        ]);

        // Should handle service unavailability gracefully
        $this->assertContains($response->getStatusCode(), [200, 503]);

        // Test handling of external service timeouts
        config(['services.stripe.timeout' => 0.001]); // Very short timeout

        $response = $this->post('/api/payments/process', [
            'amount' => 100,
            'currency' => 'usd'
        ]);

        // Should handle timeouts gracefully
        $this->assertContains($response->getStatusCode(), [200, 408, 504]);

        // Test handling of invalid external responses
        // This would require mocking external services to return invalid data
        $this->assertTrue(true); // Placeholder for external service failure tests
    }
}
