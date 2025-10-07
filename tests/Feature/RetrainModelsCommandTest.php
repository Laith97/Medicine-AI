<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Diagnosis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Mockery;
use App\Services\PredictiveAnalyticsService;

class RetrainModelsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_calls_train_models_method_on_service()
    {
        // Mock the PredictiveAnalyticsService
        /** @noinspection ArgumentType */
        $mockService = Mockery::mock(PredictiveAnalyticsService::class);
        /** @var mixed $mockService */
        $mockService->shouldReceive('trainModels')->once();

        // Bind the mock to the container
        $this->app->instance(PredictiveAnalyticsService::class, $mockService);

        // Run the command
        $exitCode = Artisan::call('predictions:retrain');

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Verify the service method was called
        // @phan-ignore-next-line
        $mockService->shouldHaveReceived('trainModels')->once();
    }

    /** @test */
    public function it_outputs_success_message_when_training_completes()
    {
        // Mock the service to avoid actual ML training
        /** @noinspection PhpParamsInspection */
        $mockService = Mockery::mock(PredictiveAnalyticsService::class);
        /** @var mixed $mockService */
        $mockService->shouldReceive('trainModels')->once();

        $this->app->instance(PredictiveAnalyticsService::class, $mockService);

        // Run the command
        Artisan::call('predictions:retrain');

        // Check output contains success message
        $output = Artisan::output();
        $this->assertTrue(str_contains($output, 'Starting model retraining'));
        $this->assertTrue(str_contains($output, 'Model retraining completed successfully'));
    }

    /** @test */
    public function it_handles_exceptions_and_returns_error_code()
    {
        // Mock the service to throw an exception
        /** @noinspection PhpParamsInspection */
        $mockService = Mockery::mock(PredictiveAnalyticsService::class);
        /** @var mixed $mockService */
        $mockService->shouldReceive('trainModels')->once()->andThrow(new \Exception('Training failed'));

        $this->app->instance(PredictiveAnalyticsService::class, $mockService);

        // Mock the logger to avoid actual logging in tests
        Log::shouldReceive('error')->once();

        // Run the command
        $exitCode = Artisan::call('predictions:retrain');

        // Assert command failed
        $this->assertEquals(1, $exitCode);
    }

    /** @test */
    public function it_outputs_error_message_when_training_fails()
    {
        // Mock the service to throw an exception
        /** @noinspection PhpParamsInspection */
        $mockService = Mockery::mock(PredictiveAnalyticsService::class);
        /** @var mixed $mockService */
        $mockService->shouldReceive('trainModels')->once()->andThrow(new \Exception('Training failed'));

        $this->app->instance(PredictiveAnalyticsService::class, $mockService);

        // Mock the logger
        Log::shouldReceive('error')->once();

        // Run the command
        Artisan::call('predictions:retrain');

        // Check output contains error message
        $output = Artisan::output();
        $this->assertTrue(str_contains($output, 'Starting model retraining'));
        $this->assertTrue(str_contains($output, 'Model retraining failed'));
    }

    /** @test */
    public function it_logs_errors_when_training_fails()
    {
        $exceptionMessage = 'Database connection failed';
        $exception = new \Exception($exceptionMessage);

        // Mock the service to throw an exception
        /** @noinspection PhpParamsInspection */
        $mockService = Mockery::mock(PredictiveAnalyticsService::class);
        /** @var mixed $mockService */
        $mockService->shouldReceive('trainModels')->once()->andThrow($exception);

        $this->app->instance(PredictiveAnalyticsService::class, $mockService);

        // Expect the logger to be called with correct parameters
        Log::shouldReceive('error')
            ->once()
            ->with('Model retraining failed', Mockery::on(function ($data) use ($exceptionMessage) {
                return isset($data['error']) &&
                       $data['error'] === $exceptionMessage &&
                       isset($data['trace']);
            }));

        // Run the command
        Artisan::call('predictions:retrain');
    }

    /** @test */
    public function it_uses_correct_command_signature()
    {
        // Test that the command is properly registered
        $exitCode = Artisan::call('predictions:retrain');

        // The command should exist and be callable
        $this->assertContains($exitCode, [0, 1]); // Either success or failure is acceptable
    }

    /** @test */
    public function it_can_be_called_with_artisan_command()
    {
        /** @noinspection PhpParamsInspection */
        $mockService = Mockery::mock(PredictiveAnalyticsService::class);
        /** @var mixed $mockService */
        $mockService->shouldReceive('trainModels')->once();

        $this->app->instance(PredictiveAnalyticsService::class, $mockService);

        // Test calling via Artisan facade
        $result = Artisan::call('predictions:retrain');

        $this->assertIsInt($result);
    }

    /** @test */
    public function it_handles_service_dependency_injection()
    {
        // Test that the command properly resolves the service from the container
        /** @noinspection PhpParamsInspection */
        $mockService = Mockery::mock(PredictiveAnalyticsService::class);
        /** @var mixed $mockService */
        $mockService->shouldReceive('trainModels')->once();

        // Replace the service in the container
        $this->app->instance(PredictiveAnalyticsService::class, $mockService);

        // Run command
        Artisan::call('predictions:retrain');

        // Verify the mock was used
        // @phan-ignore-next-line
        $mockService->shouldHaveReceived('trainModels')->once();
    }

    /** @test */
    public function it_does_not_affect_database_state()
    {
        // Create some test data
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'appointment_date' => now()->subDays(1),
            'status' => 'completed'
        ]);

        $initialUserCount = User::count();
        $initialAppointmentCount = Appointment::count();

        /** @noinspection PhpParamsInspection */
        $mockService = Mockery::mock(PredictiveAnalyticsService::class);
        /** @var mixed $mockService */
        $mockService->shouldReceive('trainModels')->once();

        $this->app->instance(PredictiveAnalyticsService::class, $mockService);

        // Run the command
        Artisan::call('predictions:retrain');

        // Verify database state is unchanged
        $this->assertEquals($initialUserCount, User::count());
        $this->assertEquals($initialAppointmentCount, Appointment::count());
    }

    /** @test */
    public function it_can_handle_multiple_consecutive_runs()
    {
        /** @noinspection PhpParamsInspection */
        $mockService = Mockery::mock(PredictiveAnalyticsService::class);
        /** @var mixed $mockService */
        $mockService->shouldReceive('trainModels')->twice();

        $this->app->instance(PredictiveAnalyticsService::class, $mockService);

        // Run command twice
        Artisan::call('predictions:retrain');
        Artisan::call('predictions:retrain');

        // Verify the service was called twice
        // @phan-ignore-next-line
        $mockService->shouldHaveReceived('trainModels')->twice();
    }
}
