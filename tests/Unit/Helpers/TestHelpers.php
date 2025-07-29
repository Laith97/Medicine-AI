<?php

namespace Tests\Unit\Helpers;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\Appointment;
use App\Models\PatientAnalysis;
use App\Models\OpenAIUsage;
use App\Models\Subscription;
use App\Models\Review;
use App\Models\DoctorNote;
use Carbon\Carbon;

class TestHelpers
{
    /**
     * Create a complete doctor setup with user, specialty, and doctor record
     */
    public static function createDoctorSetup($userAttributes = [], $doctorAttributes = [])
    {
        $user = User::factory()->create(array_merge([
            'role' => 'doctor',
            'email_verified_at' => now(),
        ], $userAttributes));

        $specialty = Specialty::factory()->create();

        $doctor = Doctor::factory()->create(array_merge([
            'user_id' => $user->id,
            'specialty_id' => $specialty->id,
            'is_active' => true,
            'is_verified' => true,
        ], $doctorAttributes));

        return compact('user', 'doctor', 'specialty');
    }

    /**
     * Create a patient with medical history
     */
    public static function createPatientWithHistory($patientAttributes = [], $analysisCount = 3)
    {
        $patient = User::factory()->create(array_merge([
            'role' => 'patient',
            'email_verified_at' => now(),
        ], $patientAttributes));

        $analyses = PatientAnalysis::factory()->count($analysisCount)->create([
            'user_id' => $patient->id,
        ]);

        return compact('patient', 'analyses');
    }

    /**
     * Create an appointment with all related data
     */
    public static function createAppointmentSetup($appointmentAttributes = [])
    {
        $doctorSetup = self::createDoctorSetup();
        $patient = User::factory()->create(['role' => 'patient']);

        $appointment = Appointment::factory()->create(array_merge([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorSetup['doctor']->id,
            'appointment_date' => now()->addDay(),
            'status' => 'confirmed',
        ], $appointmentAttributes));

        return array_merge($doctorSetup, compact('patient', 'appointment'));
    }

    /**
     * Create usage data for testing limits and billing
     */
    public static function createUsageData($user, $tokenCount = 500, $cost = 25.00, $date = null)
    {
        return OpenAIUsage::factory()->create([
            'user_id' => $user->id,
            'total_tokens' => $tokenCount,
            'cost_estimate' => $cost,
            'created_at' => $date ?? now(),
        ]);
    }

    /**
     * Create a subscription for testing
     */
    public static function createSubscription($user, $status = 'active', $plan = 'premium')
    {
        return Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            'plan_name' => ucfirst($plan) . ' Plan',
            'current_period_start' => now()->startOfMonth()->timestamp,
            'current_period_end' => now()->endOfMonth()->timestamp,
        ]);
    }

    /**
     * Create reviews for a doctor
     */
    public static function createReviewsForDoctor($doctor, $count = 5, $averageRating = 4.5)
    {
        $reviews = collect();

        for ($i = 0; $i < $count; $i++) {
            $rating = max(1, min(5, round($averageRating + (rand(-10, 10) / 10))));

            $patient = User::factory()->create(['role' => 'patient']);

            $review = Review::factory()->create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'rating' => $rating,
                'is_approved' => true,
                'is_public' => true,
            ]);

            $reviews->push($review);
        }

        // Update doctor's rating
        $doctor->updateRating();

        return $reviews;
    }

    /**
     * Create doctor notes for testing
     */
    public static function createDoctorNotes($doctor, $patient = null, $count = 3)
    {
        if (!$patient) {
            $patient = User::factory()->create(['role' => 'patient']);
        }

        return DoctorNote::factory()->count($count)->create([
            'doctor_id' => $doctor->user_id,
            'patient_id' => $patient->id,
        ]);
    }

    /**
     * Create test data for dashboard statistics
     */
    public static function createDashboardTestData($user)
    {
        // Create recent analyses
        PatientAnalysis::factory()->count(5)->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(rand(1, 7)),
        ]);

        // Create usage data
        OpenAIUsage::factory()->count(3)->create([
            'user_id' => $user->id,
            'total_tokens' => rand(100, 500),
            'cost_estimate' => rand(5, 25),
            'created_at' => now()->subDays(rand(1, 30)),
        ]);

        // If user is a doctor, create appointments
        if ($user->role === 'doctor') {
            $doctor = Doctor::factory()->create(['user_id' => $user->id]);
            $patients = User::factory()->count(3)->create(['role' => 'patient']);

            foreach ($patients as $patient) {
                Appointment::factory()->create([
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'appointment_date' => now()->addDays(rand(1, 14)),
                    'status' => 'confirmed',
                ]);
            }
        }
    }

    /**
     * Create test data for billing scenarios
     */
    public static function createBillingTestData($user, $exceedLimit = false)
    {
        $user->monthly_cost_limit = 50.00;
        $user->save();

        $costAmount = $exceedLimit ? 75.00 : 30.00;

        return OpenAIUsage::factory()->create([
            'user_id' => $user->id,
            'cost_estimate' => $costAmount,
            'total_tokens' => $costAmount * 20, // Rough token estimate
            'created_at' => now(),
        ]);
    }

    /**
     * Create expired subscription scenario
     */
    public static function createExpiredSubscriptionScenario($user, $daysExpired = 5)
    {
        $user->subscription_active = false;
        $user->subscription_ends_at = now()->subDays($daysExpired);
        $user->save();

        return Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'canceled',
            'current_period_end' => now()->subDays($daysExpired)->timestamp,
            'canceled_at' => now()->subDays($daysExpired + 1)->timestamp,
        ]);
    }

    /**
     * Create trial subscription scenario
     */
    public static function createTrialSubscriptionScenario($user, $trialDaysLeft = 5)
    {
        $user->subscription_active = true;
        $user->current_plan = 'trial';
        $user->save();

        return Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'trialing',
            'trial_end' => now()->addDays($trialDaysLeft)->timestamp,
            'current_period_end' => now()->addDays($trialDaysLeft)->timestamp,
        ]);
    }

    /**
     * Create notification test data
     */
    public static function createNotificationTestData($user, $count = 5)
    {
        $notifications = collect();

        for ($i = 0; $i < $count; $i++) {
            $notification = \App\Models\Notification::factory()->create([
                'user_id' => $user->id,
                'is_read' => $i < 2, // First 2 are read
                'type' => ['info', 'warning', 'success', 'error'][rand(0, 3)],
                'created_at' => now()->subHours(rand(1, 48)),
            ]);

            $notifications->push($notification);
        }

        return $notifications;
    }

    /**
     * Mock external API responses
     */
    public static function mockExternalAPIs()
    {
        // Mock OpenAI API
        \Illuminate\Support\Facades\Http::fake([
            'api.openai.com/*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    ['message' => ['content' => 'Mocked AI response for testing']]
                ],
                'usage' => [
                    'total_tokens' => 100,
                    'prompt_tokens' => 50,
                    'completion_tokens' => 50
                ]
            ], 200)
        ]);
    }

    /**
     * Create time-based test data for charts and analytics
     */
    public static function createTimeBasedData($user, $days = 30)
    {
        $data = collect();

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);

            // Create usage data
            $usage = OpenAIUsage::factory()->create([
                'user_id' => $user->id,
                'total_tokens' => rand(50, 200),
                'cost_estimate' => rand(2, 10),
                'created_at' => $date,
            ]);

            // Create analysis data
            if (rand(0, 1)) {
                $analysis = PatientAnalysis::factory()->create([
                    'user_id' => $user->id,
                    'created_at' => $date,
                ]);

                $data->push(compact('usage', 'analysis', 'date'));
            } else {
                $data->push(compact('usage', 'date'));
            }
        }

        return $data;
    }

    /**
     * Assert common response structure
     */
    public static function assertApiResponse($response, $expectedStatus = 200, $hasData = true)
    {
        $response->assertStatus($expectedStatus);

        if ($expectedStatus === 200 && $hasData) {
            $response->assertJsonStructure([
                'success',
                'data'
            ]);
        } elseif ($expectedStatus >= 400) {
            $response->assertJsonStructure([
                'success',
                'error'
            ]);
        }
    }

    /**
     * Create test file for uploads
     */
    public static function createTestFile($filename = 'test.pdf', $size = 100)
    {
        return \Illuminate\Http\UploadedFile::fake()->create($filename, $size);
    }

    /**
     * Create test image file
     */
    public static function createTestImage($filename = 'test.jpg', $width = 100, $height = 100)
    {
        return \Illuminate\Http\UploadedFile::fake()->image($filename, $width, $height);
    }

    /**
     * Generate random medical data for testing
     */
    public static function generateMedicalTestData()
    {
        $symptoms = [
            'fever', 'cough', 'headache', 'fatigue', 'nausea',
            'chest pain', 'shortness of breath', 'dizziness'
        ];

        $medications = [
            'Aspirin', 'Ibuprofen', 'Acetaminophen', 'Lisinopril',
            'Metformin', 'Atorvastatin', 'Omeprazole'
        ];

        $conditions = [
            'Hypertension', 'Diabetes Type 2', 'Asthma', 'Arthritis',
            'Depression', 'Anxiety', 'Migraine', 'GERD'
        ];

        return [
            'symptoms' => implode(', ', array_rand(array_flip($symptoms), rand(2, 4))),
            'current_medications' => implode(', ', array_rand(array_flip($medications), rand(1, 3))),
            'medical_history' => implode(', ', array_rand(array_flip($conditions), rand(1, 2))),
        ];
    }

    /**
     * Clean up test data
     */
    public static function cleanupTestData()
    {
        // This method can be used to clean up any test data
        // that might persist between tests
        \Illuminate\Support\Facades\Cache::flush();
        \Illuminate\Support\Facades\Queue::flush();
    }
}
