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
use Tests\TestCase;

class PerformanceLoadTest extends TestCase
{
    use RefreshDatabase;

    protected $patients = [];
    protected $doctors = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data for performance testing
        $this->createTestData();
    }

    protected function createTestData()
    {
        // Create 50 doctors
        for ($i = 0; $i < 50; $i++) {
            $doctorUser = User::factory()->create([
                'role' => 'doctor',
                'email' => "doctor{$i}@example.com"
            ]);

            $doctorProfile = new Doctor();
            $doctorProfile->user_id = $doctorUser->id;
            $doctorProfile->specialization = 'General Medicine';
            $doctorProfile->license_number = 'DOC' . str_pad($i, 6, '0', STR_PAD_LEFT);
            $doctorProfile->save();

            $this->doctors[] = $doctorUser;
        }

        // Create 500 patients
        for ($i = 0; $i < 500; $i++) {
            $patient = User::factory()->create([
                'role' => 'patient',
                'email' => "patient{$i}@example.com"
            ]);
            $this->patients[] = $patient;
        }
    }

    public function test_high_volume_appointment_booking_performance()
    {
        $startTime = microtime(true);

        // Simulate 100 concurrent appointment bookings
        for ($i = 0; $i < 100; $i++) {
            $patient = $this->patients[array_rand($this->patients)];
            $doctor = $this->doctors[array_rand($this->doctors)];

            $this->actingAs($patient);

            $appointmentData = [
                'doctor_id' => $doctor->doctor->id,
                'appointment_date' => now()->addDays(rand(1, 30))->setTime(rand(9, 16), rand(0, 59))->format('Y-m-d H:i:s'),
                'appointment_type' => 'consultation',
                'duration' => 30,
                'reason' => 'Routine checkup'
            ];

            $response = $this->post('/api/appointments', $appointmentData);
            $response->assertStatus(201);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert performance requirements (should complete within 30 seconds for 100 bookings)
        $this->assertLessThan(30, $executionTime, "High volume appointment booking took too long: {$executionTime}s");

        // Verify all appointments were created
        $appointmentCount = Appointment::whereIn('patient_id', collect($this->patients)->pluck('id'))->count();
        $this->assertEquals(100, $appointmentCount);
    }

    public function test_concurrent_patient_assessment_processing()
    {
        $startTime = microtime(true);

        // Simulate 50 concurrent patient assessments
        $assessmentPromises = [];

        for ($i = 0; $i < 50; $i++) {
            $patient = $this->patients[$i];

            $this->actingAs($patient);

            $healthData = [
                'symptoms' => ['fever', 'cough', 'headache'],
                'severity' => 'moderate',
                'duration' => rand(1, 7) . ' days',
                'pain_level' => rand(1, 10),
                'additional_notes' => 'Performance test assessment ' . $i
            ];

            $response = $this->post('/api/patient/health-assessment', $healthData);
            $response->assertStatus(201);

            $assessmentPromises[] = $response;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert performance requirements (should complete within 15 seconds for 50 assessments)
        $this->assertLessThan(15, $executionTime, "Concurrent assessment processing took too long: {$executionTime}s");

        // Verify all assessments were created
        $assessmentCount = PatientAnalysis::whereIn('user_id', collect($this->patients)->take(50)->pluck('id'))->count();
        $this->assertEquals(50, $assessmentCount);
    }

    public function test_database_query_performance_under_load()
    {
        // Pre-populate database with test data
        $this->createLargeDataset();

        $startTime = microtime(true);

        // Test complex queries under load
        for ($i = 0; $i < 20; $i++) {
            // Complex appointment search query
            $appointments = DB::select('
                SELECT a.*, u.name as patient_name, d.specialization
                FROM appointments a
                JOIN users u ON a.patient_id = u.id
                JOIN doctors doc ON a.doctor_id = doc.id
                JOIN users d ON doc.user_id = d.id
                WHERE a.status = ? AND a.appointment_date >= ?
                ORDER BY a.appointment_date ASC
                LIMIT 50
            ', ['confirmed', now()->format('Y-m-d')]);

            // Complex patient dashboard query
            $patientId = $this->patients[array_rand($this->patients)]->id;
            $patientData = DB::select('
                SELECT
                    u.*,
                    COUNT(DISTINCT a.id) as total_appointments,
                    COUNT(DISTINCT d.id) as total_diagnoses,
                    COUNT(DISTINCT p.id) as total_prescriptions
                FROM users u
                LEFT JOIN appointments a ON u.id = a.patient_id
                LEFT JOIN diagnoses d ON u.id = d.patient_id
                LEFT JOIN prescriptions p ON u.id = p.patient_id
                WHERE u.id = ?
                GROUP BY u.id
            ', [$patientId]);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert query performance (should complete within 5 seconds for 20 complex queries)
        $this->assertLessThan(5, $executionTime, "Database queries under load took too long: {$executionTime}s");
    }

    public function test_api_response_time_under_load()
    {
        $this->createLargeDataset();

        $responseTimes = [];

        // Test API endpoints under load
        for ($i = 0; $i < 30; $i++) {
            $patient = $this->patients[array_rand($this->patients)];
            $this->actingAs($patient);

            $startTime = microtime(true);

            // Test patient dashboard endpoint
            $response = $this->get('/api/patient/dashboard');
            $response->assertStatus(200);

            $endTime = microtime(true);
            $responseTimes[] = $endTime - $startTime;

            // Test appointments endpoint
            $startTime = microtime(true);
            $response = $this->get('/api/patient/appointments');
            $response->assertStatus(200);
            $endTime = microtime(true);
            $responseTimes[] = $endTime - $startTime;
        }

        $averageResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);

        // Assert API performance (average response time should be under 500ms, max under 2s)
        $this->assertLessThan(0.5, $averageResponseTime, "Average API response time too slow: {$averageResponseTime}s");
        $this->assertLessThan(2.0, $maxResponseTime, "Max API response time too slow: {$maxResponseTime}s");
    }

    public function test_memory_usage_during_bulk_operations()
    {
        $initialMemory = memory_get_usage(true);

        // Perform bulk diagnosis creation
        $this->actingAs($this->doctors[0]);

        for ($i = 0; $i < 100; $i++) {
            $patient = $this->patients[array_rand($this->patients)];

            $diagnosisData = [
                'patient_id' => $patient->id,
                'condition' => 'Test Condition ' . $i,
                'icd_code' => 'Z99.' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'severity' => 'mild',
                'confidence_level' => 80,
                'symptoms' => ['test symptom'],
                'recommendations' => 'Test recommendations'
            ];

            $response = $this->post('/api/diagnoses', $diagnosisData);
            $response->assertStatus(201);
        }

        $finalMemory = memory_get_usage(true);
        $memoryUsed = $finalMemory - $initialMemory;
        $memoryUsedMB = $memoryUsed / 1024 / 1024;

        // Assert memory usage (should use less than 50MB for 100 diagnoses)
        $this->assertLessThan(50, $memoryUsedMB, "Memory usage too high: {$memoryUsedMB}MB");

        // Verify all diagnoses were created
        $diagnosisCount = Diagnosis::where('condition', 'like', 'Test Condition%')->count();
        $this->assertEquals(100, $diagnosisCount);
    }

    public function test_cache_performance_under_load()
    {
        // Test caching performance with frequent reads
        $cacheHits = 0;
        $totalRequests = 100;

        for ($i = 0; $i < $totalRequests; $i++) {
            $cacheKey = 'test_performance_key_' . $i;

            $startTime = microtime(true);
            $cachedData = Cache::get($cacheKey);

            if ($cachedData === null) {
                // Simulate cache miss - store data
                $data = ['test' => 'data', 'iteration' => $i, 'timestamp' => now()];
                Cache::put($cacheKey, $data, 300); // 5 minutes
            } else {
                $cacheHits++;
            }

            $endTime = microtime(true);
            $cacheTime = $endTime - $startTime;

            // Assert cache operation performance (should be under 10ms)
            $this->assertLessThan(0.01, $cacheTime, "Cache operation too slow: {$cacheTime}s");
        }

        // Assert cache hit ratio (should be at least 50% after initial population)
        $cacheHitRatio = $cacheHits / $totalRequests;
        $this->assertGreaterThan(0.5, $cacheHitRatio, "Cache hit ratio too low: {$cacheHitRatio}");
    }

    public function test_concurrent_prescription_processing()
    {
        $startTime = microtime(true);

        // Simulate concurrent prescription creation and processing
        for ($i = 0; $i < 25; $i++) {
            $doctor = $this->doctors[array_rand($this->doctors)];
            $patient = $this->patients[array_rand($this->patients)];

            $this->actingAs($doctor);

            // Create diagnosis first
            $diagnosisData = [
                'patient_id' => $patient->id,
                'condition' => 'Performance Test Condition',
                'icd_code' => 'Z99.999',
                'severity' => 'mild'
            ];

            $response = $this->post('/api/diagnoses', $diagnosisData);
            $response->assertStatus(201);

            $diagnosis = Diagnosis::latest()->first();

            // Create prescription
            $prescriptionData = [
                'patient_id' => $patient->id,
                'diagnosis_id' => $diagnosis->id,
                'medications' => [
                    [
                        'drug_name' => 'Test Drug ' . $i,
                        'dosage' => '10mg',
                        'frequency' => 'once_daily',
                        'duration' => 7,
                        'instructions' => 'Take as directed'
                    ]
                ]
            ];

            $response = $this->post('/api/prescriptions', $prescriptionData);
            $response->assertStatus(201);

            $prescription = Prescription::latest()->first();

            // Check drug interactions
            $response = $this->get("/api/prescriptions/{$prescription->id}/interactions");
            $response->assertStatus(200);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert performance (should complete within 20 seconds for 25 complex operations)
        $this->assertLessThan(20, $executionTime, "Concurrent prescription processing took too long: {$executionTime}s");

        // Verify prescriptions were created
        $prescriptionCount = Prescription::where('medications', 'like', '%Test Drug%')->count();
        $this->assertGreaterThanOrEqual(25, $prescriptionCount);
    }

    public function test_system_stress_test_with_mixed_operations()
    {
        $startTime = microtime(true);
        $operations = 0;

        // Mix of different operations to simulate real-world load
        for ($i = 0; $i < 10; $i++) {
            // User authentication and profile operations
            $patient = $this->patients[array_rand($this->patients)];
            $this->actingAs($patient);

            $response = $this->get('/api/patient/profile');
            $response->assertStatus(200);
            $operations++;

            // Appointment operations
            $response = $this->get('/api/patient/appointments');
            $response->assertStatus(200);
            $operations++;

            // Health assessment
            $response = $this->get('/api/patient/health-records');
            $response->assertStatus(200);
            $operations++;

            // Doctor operations
            $doctor = $this->doctors[array_rand($this->doctors)];
            $this->actingAs($doctor);

            $response = $this->get('/api/doctor/appointments/today');
            $response->assertStatus(200);
            $operations++;

            $response = $this->get('/api/doctor/patients');
            $response->assertStatus(200);
            $operations++;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $avgOperationTime = $executionTime / $operations;

        // Assert mixed workload performance (average operation should be under 200ms)
        $this->assertLessThan(0.2, $avgOperationTime, "Average operation time too slow: {$avgOperationTime}s");
        $this->assertLessThan(30, $executionTime, "Total mixed operations took too long: {$executionTime}s");
    }

    protected function createLargeDataset()
    {
        // Create additional test data for performance testing
        for ($i = 0; $i < 200; $i++) {
            $patient = $this->patients[array_rand($this->patients)];
            $doctor = $this->doctors[array_rand($this->doctors)];

            Appointment::factory()->create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->doctor->id,
                'status' => 'confirmed',
                'appointment_date' => now()->addDays(rand(1, 30))
            ]);

            Diagnosis::factory()->create([
                'patient_id' => $patient->id,
                'condition' => 'Test Diagnosis ' . $i,
                'icd_code' => 'Z' . rand(10, 99) . '.' . rand(100, 999)
            ]);

            Prescription::factory()->create([
                'patient_id' => $patient->id,
                'medications' => [
                    [
                        'drug_name' => 'Test Med ' . $i,
                        'dosage' => '10mg',
                        'frequency' => 'once_daily',
                        'duration' => 7
                    ]
                ]
            ]);
        }
    }
}
