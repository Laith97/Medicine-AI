<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\RefreshTestDatabase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshTestDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test environment
        $this->setupTestEnvironment();
    }

    protected function setupTestEnvironment(): void
    {
        // Use SQLite for testing as configured in phpunit.xml
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        config(['database.connections.sqlite.foreign_key_constraints' => false]);

        // Configure mail for testing
        config(['mail.default' => 'array']);

        // Configure queue for testing
        config(['queue.default' => 'sync']);

        // Configure cache for testing
        config(['cache.default' => 'array']);

        // Configure session for testing
        config(['session.driver' => 'array']);

        // Disable broadcasting for tests
        config(['broadcasting.default' => 'null']);

        // Set test API keys
        config([
            'services.openai.key' => 'test-openai-key',
            'stripe.key' => 'sk_test_123',
            'stripe.public_key' => 'pk_test_123',
        ]);

        // Disable foreign key constraints for SQLite testing
        try {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys=OFF');
        } catch (\Exception $e) {
            // Ignore if DB is not ready yet
        }
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $this->cleanupTestEnvironment();

        parent::tearDown();
    }

    protected function cleanupTestEnvironment(): void
    {
        // Clear any cached data
        if (app()->bound('cache')) {
            try {
                app('cache')->flush();
            } catch (\Exception $e) {
                // Ignore cache flush errors in tests
            }
        }

        // Clear any queued jobs
        if (app()->bound('queue')) {
            try {
                $queue = app('queue');
                if (method_exists($queue, 'flush')) {
                    $queue->flush();
                }
            } catch (\Exception $e) {
                // Ignore queue flush errors in tests
            }
        }
    }

    /**
     * Create a user and authenticate them for testing
     */
    protected function authenticateUser($attributes = [], $role = 'doctor')
    {
        $user = \App\Models\User::factory()->create(array_merge([
            'role' => $role,
            'email_verified_at' => now(),
        ], $attributes));

        $this->actingAs($user);

        return $user;
    }

    /**
     * Create a doctor with associated user for testing
     */
    protected function createDoctor($userAttributes = [], $doctorAttributes = [])
    {
        $user = \App\Models\User::factory()->create(array_merge([
            'role' => 'doctor',
            'email_verified_at' => now(),
        ], $userAttributes));

        $specialty = \App\Models\Specialty::factory()->create();

        $doctor = \App\Models\Doctor::factory()->create(array_merge([
            'user_id' => $user->id,
            'specialty_id' => $specialty->id,
        ], $doctorAttributes));

        return compact('user', 'doctor', 'specialty');
    }

    /**
     * Create a patient user for testing
     */
    protected function createPatient($attributes = [])
    {
        return \App\Models\User::factory()->create(array_merge([
            'role' => 'patient',
            'email_verified_at' => now(),
        ], $attributes));
    }

    /**
     * Create an admin user for testing
     */
    protected function createAdmin($attributes = [])
    {
        return \App\Models\User::factory()->create(array_merge([
            'role' => 'admin',
            'email_verified_at' => now(),
        ], $attributes));
    }

    /**
     * Assert that a response contains validation errors for specific fields
     */
    protected function assertValidationErrors($response, $fields)
    {
        $response->assertStatus(422);

        $errors = $response->json('errors');

        foreach ((array) $fields as $field) {
            $this->assertArrayHasKey($field, $errors, "Validation error for field '{$field}' not found");
        }
    }

    /**
     * Assert that a response is a successful JSON response
     */
    protected function assertSuccessfulJsonResponse($response, $data = [])
    {
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        if (!empty($data)) {
            $response->assertJson($data);
        }
    }

    /**
     * Assert that a response is an error JSON response
     */
    protected function assertErrorJsonResponse($response, $status = 400, $message = null)
    {
        $response->assertStatus($status);
        $response->assertJson(['success' => false]);

        if ($message) {
            $response->assertJson(['message' => $message]);
        }
    }

    /**
     * Mock external API calls for testing
     */
    protected function mockExternalAPIs()
    {
        // Mock OpenAI API
        $this->mockOpenAI();

        // Mock Stripe API
        $this->mockStripe();
    }

    /**
     * Mock OpenAI API responses
     */
    protected function mockOpenAI()
    {
        \Illuminate\Support\Facades\Http::fake([
            'api.openai.com/*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    ['message' => ['content' => 'Mocked OpenAI response']]
                ]
            ], 200)
        ]);
    }

    /**
     * Mock Stripe API responses
     */
    protected function mockStripe()
    {
        // This would typically involve mocking Stripe SDK calls
        // For now, we'll use Mockery in individual tests
    }

    /**
     * Create test data for common scenarios
     */
    protected function createTestData()
    {
        return [
            'user' => $this->createPatient(),
            'doctor_data' => $this->createDoctor(),
            'admin' => $this->createAdmin(),
        ];
    }

    /**
     * Assert that an email was sent to a specific user
     */
    protected function assertEmailSentTo($user, $mailableClass)
    {
        \Illuminate\Support\Facades\Mail::assertSent($mailableClass, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /**
     * Assert that a notification was sent to a specific user
     */
    protected function assertNotificationSentTo($user, $notificationClass)
    {
        \Illuminate\Support\Facades\Notification::assertSentTo($user, $notificationClass);
    }

    /**
     * Assert that a job was dispatched
     */
    protected function assertJobDispatched($jobClass)
    {
        \Illuminate\Support\Facades\Queue::assertPushed($jobClass);
    }

    /**
     * Create a temporary file for testing file uploads
     */
    protected function createTestFile($name = 'test.txt', $content = 'test content')
    {
        return \Illuminate\Http\UploadedFile::fake()->createWithContent($name, $content);
    }

    /**
     * Create a test image file
     */
    protected function createTestImage($name = 'test.jpg', $width = 100, $height = 100)
    {
        return \Illuminate\Http\UploadedFile::fake()->image($name, $width, $height);
    }

    /**
     * Assert that a model exists in the database with specific attributes
     */
    protected function assertModelExists($model, $attributes = [])
    {
        $this->assertDatabaseHas($model->getTable(), array_merge([
            'id' => $model->id
        ], $attributes));
    }

    /**
     * Assert that a model does not exist in the database
     */
    protected function assertModelNotExists($model)
    {
        $this->assertDatabaseMissing($model->getTable(), [
            'id' => $model->id
        ]);
    }

    /**
     * Travel to a specific time for testing
     */
    public function travelTo($date, $callback = null)
    {
        return parent::travelTo($date, $callback);
    }

    /**
     * Travel back to the present time
     */
    public function travelBack()
    {
        return parent::travelBack();
    }
}
