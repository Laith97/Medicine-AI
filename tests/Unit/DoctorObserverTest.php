<?php

namespace Tests\Unit;

use App\Models\Doctor;
use App\Models\User;
use App\Observers\DoctorObserver;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class DoctorObserverTest extends TestCase
{
    use RefreshDatabase;

    protected $doctorObserver;
    protected $smsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->smsServiceMock = Mockery::mock(SmsService::class);
        $this->app->instance(SmsService::class, $this->smsServiceMock);
        
        $this->doctorObserver = new DoctorObserver();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function test_configuration_log_created_when_sms_provider_changes()
    {
        // Create doctor and user
        $user = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create([
            'user_id' => $user->id,
            'sms_provider' => 'twilio'
        ]);

        // Set up mock expectations
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->once()
            ->with('doctor_sms_provider_changed', Mockery::on(function ($details) use ($user, $doctor) {
                return $details['user_id'] === $user->id &&
                       $details['model_type'] === 'doctor' &&
                       $details['model_id'] === $doctor->id &&
                       $details['old_provider'] === 'twilio' &&
                       $details['new_provider'] === 'plivo';
            }));

        // Update doctor's SMS provider
        $doctor->sms_provider = 'plivo';
        $doctor->save();

        // Assert that the observer was triggered
        $this->assertTrue(true);
    }

    /** @test */
    public function test_no_log_created_when_sms_provider_does_not_change()
    {
        // Create doctor and user
        $user = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create([
            'user_id' => $user->id,
            'sms_provider' => 'twilio'
        ]);

        // Set up mock expectations - should not be called
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->never();

        // Update doctor's name (not SMS provider)
        $doctor->bio = 'Updated bio';
        $doctor->save();

        // Assert that the observer was not triggered for SMS provider change
        $this->assertTrue(true);
    }

    /** @test */
    public function test_configuration_log_includes_user_information()
    {
        // Create doctor and user
        $user = User::factory()->create([
            'role' => 'doctor',
            'name' => 'Dr. Smith'
        ]);
        $doctor = Doctor::factory()->create([
            'user_id' => $user->id,
            'sms_provider' => 'twilio'
        ]);

        // Set up mock expectations
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->once()
            ->with('doctor_sms_provider_changed', Mockery::on(function ($details) use ($user, $doctor) {
                return $details['user_id'] === $user->id &&
                       $details['user_role'] === 'doctor' &&
                       $details['model_type'] === 'doctor' &&
                       $details['model_id'] === $doctor->id;
            }));

        // Update doctor's SMS provider
        $doctor->sms_provider = 'plivo';
        $doctor->save();

        // Assert that the observer was triggered with correct user info
        $this->assertTrue(true);
    }

    /** @test */
    public function test_observer_handles_null_user()
    {
        // Create doctor without user
        $doctor = Doctor::factory()->create([
            'user_id' => null,
            'sms_provider' => 'twilio'
        ]);

        // Set up mock expectations
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->once()
            ->with('doctor_sms_provider_changed', Mockery::on(function ($details) use ($doctor) {
                return $details['user_id'] === null &&
                       $details['user_role'] === 'system' &&
                       $details['model_type'] === 'doctor' &&
                       $details['model_id'] === $doctor->id;
            }));

        // Update doctor's SMS provider
        $doctor->sms_provider = 'plivo';
        $doctor->save();

        // Assert that the observer handled null user correctly
        $this->assertTrue(true);
    }

    /** @test */
    public function test_observer_only_triggers_on_sms_provider_change()
    {
        // Create doctor and user
        $user = User::factory()->create(['role' => 'doctor']);
        $doctor = Doctor::factory()->create([
            'user_id' => $user->id,
            'sms_provider' => 'twilio',
            'bio' => 'Original bio'
        ]);

        // Set up mock expectations - should not be called for non-sms_provider changes
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->never();

        // Update multiple fields but not SMS provider
        $doctor->bio = 'Updated bio';
        $doctor->phone = '+1234567890';
        $doctor->save();

        // Assert that the observer was not triggered
        $this->assertTrue(true);
    }
}