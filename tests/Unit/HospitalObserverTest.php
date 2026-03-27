<?php

namespace Tests\Unit;

use App\Models\Hospital;
use App\Models\User;
use App\Observers\HospitalObserver;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class HospitalObserverTest extends TestCase
{
    use RefreshDatabase;

    protected $hospitalObserver;
    protected $smsServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->smsServiceMock = Mockery::mock(SmsService::class);
        $this->app->instance(SmsService::class, $this->smsServiceMock);
        
        $this->hospitalObserver = new HospitalObserver();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function test_configuration_log_created_when_sms_provider_changes()
    {
        // Create hospital admin and hospital
        $hospitalAdmin = User::factory()->create(['role' => 'hospital_admin']);
        $hospital = Hospital::factory()->create([
            'admin_id' => $hospitalAdmin->id,
            'sms_provider' => 'twilio'
        ]);

        // Set up mock expectations
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->once()
            ->with('hospital_sms_provider_changed', Mockery::on(function ($details) use ($hospitalAdmin, $hospital) {
                return $details['user_id'] === $hospitalAdmin->id &&
                       $details['model_type'] === 'hospital' &&
                       $details['model_id'] === $hospital->id &&
                       $details['old_provider'] === 'twilio' &&
                       $details['new_provider'] === 'plivo';
            }));

        // Update hospital's SMS provider
        $hospital->sms_provider = 'plivo';
        $hospital->save();

        // Assert that the observer was triggered
        $this->assertTrue(true);
    }

    /** @test */
    public function test_no_log_created_when_sms_provider_does_not_change()
    {
        // Create hospital admin and hospital
        $hospitalAdmin = User::factory()->create(['role' => 'hospital_admin']);
        $hospital = Hospital::factory()->create([
            'admin_id' => $hospitalAdmin->id,
            'sms_provider' => 'twilio',
            'name' => 'Original Hospital Name'
        ]);

        // Set up mock expectations - should not be called
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->never();

        // Update hospital's name (not SMS provider)
        $hospital->name = 'Updated Hospital Name';
        $hospital->save();

        // Assert that the observer was not triggered for SMS provider change
        $this->assertTrue(true);
    }

    /** @test */
    public function test_configuration_log_includes_user_information()
    {
        // Create hospital admin and hospital
        $hospitalAdmin = User::factory()->create([
            'role' => 'hospital_admin',
            'name' => 'Hospital Admin'
        ]);
        $hospital = Hospital::factory()->create([
            'admin_id' => $hospitalAdmin->id,
            'sms_provider' => 'twilio'
        ]);

        // Set up mock expectations
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->once()
            ->with('hospital_sms_provider_changed', Mockery::on(function ($details) use ($hospitalAdmin, $hospital) {
                return $details['user_id'] === $hospitalAdmin->id &&
                       $details['user_role'] === 'hospital_admin' &&
                       $details['model_type'] === 'hospital' &&
                       $details['model_id'] === $hospital->id;
            }));

        // Update hospital's SMS provider
        $hospital->sms_provider = 'plivo';
        $hospital->save();

        // Assert that the observer was triggered with correct user info
        $this->assertTrue(true);
    }

    /** @test */
    public function test_observer_handles_null_user()
    {
        // Create hospital without admin
        $hospital = Hospital::factory()->create([
            'admin_id' => null,
            'sms_provider' => 'twilio'
        ]);

        // Set up mock expectations
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->once()
            ->with('hospital_sms_provider_changed', Mockery::on(function ($details) use ($hospital) {
                return $details['user_id'] === null &&
                       $details['user_role'] === 'system' &&
                       $details['model_type'] === 'hospital' &&
                       $details['model_id'] === $hospital->id;
            }));

        // Update hospital's SMS provider
        $hospital->sms_provider = 'plivo';
        $hospital->save();

        // Assert that the observer handled null user correctly
        $this->assertTrue(true);
    }

    /** @test */
    public function test_observer_only_triggers_on_sms_provider_change()
    {
        // Create hospital admin and hospital
        $hospitalAdmin = User::factory()->create(['role' => 'hospital_admin']);
        $hospital = Hospital::factory()->create([
            'admin_id' => $hospitalAdmin->id,
            'sms_provider' => 'twilio',
            'phone' => '1234567890'
        ]);

        // Set up mock expectations - should not be called for non-sms_provider changes
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->never();

        // Update multiple fields but not SMS provider
        $hospital->phone = '0987654321';
        $hospital->email = 'updated@example.com';
        $hospital->save();

        // Assert that the observer was not triggered
        $this->assertTrue(true);
    }

    /** @test */
    public function test_observer_handles_hospital_without_admin()
    {
        // Create hospital without admin
        $hospital = Hospital::factory()->create([
            'admin_id' => null,
            'sms_provider' => 'twilio'
        ]);

        // Set up mock expectations
        $this->smsServiceMock->shouldReceive('logConfigurationChange')
            ->once()
            ->with('hospital_sms_provider_changed', Mockery::on(function ($details) use ($hospital) {
                return $details['user_id'] === null &&
                       $details['user_role'] === 'system' &&
                       $details['model_type'] === 'hospital' &&
                       $details['model_id'] === $hospital->id &&
                       $details['old_provider'] === 'twilio' &&
                       $details['new_provider'] === 'messagebird';
            }));

        // Update hospital's SMS provider
        $hospital->sms_provider = 'messagebird';
        $hospital->save();

        // Assert that the observer handled hospital without admin correctly
        $this->assertTrue(true);
    }
}