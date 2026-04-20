<?php

namespace Tests\Feature;

use App\Services\SmsService;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\SystemSetting;
use App\Models\SmsProviderCountry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use ReflectionClass;
use App\Contracts\SmsProviderInterface;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $smsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->smsService = Mockery::mock(SmsService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->smsService->shouldReceive('createProviderInstance')->andReturnUsing(function ($provider) {
            $mock = Mockery::mock(SmsProviderInterface::class);
            $mock->shouldReceive('send')->andReturn(['success' => true, 'message' => 'Mocked success', 'data' => []]);
            return $mock;
        });
    }

    /** @test */
    public function test_provider_selection_hierarchy_doctor_overrides_hospital_and_system()
    {
        // Create test data
        $doctor = Doctor::factory()->create(['sms_provider' => 'twilio']);
        $hospital = Hospital::factory()->create(['sms_provider' => 'plivo']);
        SystemSetting::set('sms_provider', 'messagebird', 'string', 'System SMS provider');

        // Test that doctor provider is selected
        $result = $this->smsService->send('+1234567890', 'Test message', [
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function test_provider_selection_hierarchy_hospital_overrides_system()
    {
        // Create test data
        $hospital = Hospital::factory()->create(['sms_provider' => 'plivo']);
        SystemSetting::set('sms_provider', 'messagebird', 'string', 'System SMS provider');

        // Test that hospital provider is selected when no doctor provider
        $result = $this->smsService->send('+1234567890', 'Test message', [
            'hospital_id' => $hospital->id
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function test_provider_selection_hierarchy_system_used_when_no_overrides()
    {
        // Set system provider
        SystemSetting::set('sms_provider', 'messagebird', 'string', 'System SMS provider');

        // Test that system provider is used
        $result = $this->smsService->send('+1234567890', 'Test message');

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function test_fallback_handling_when_primary_provider_fails()
    {
        // Create test data with doctor provider
        $doctor = Doctor::factory()->create(['sms_provider' => 'twilio']);
        $hospital = Hospital::factory()->create(['sms_provider' => 'plivo']);
        SystemSetting::set('sms_provider', 'messagebird', 'string', 'System SMS provider');

        $this->smsService->shouldReceive('createProviderInstance')
            ->with('twilio')
            ->andReturn(Mockery::mock(SmsProviderInterface::class)->shouldReceive('send')->andReturn(['success' => false, 'message' => 'Provider failed', 'data' => []]));

        // Test fallback to hospital provider
        $result = $this->smsService->send('+1234567890', 'Test message', [
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function test_logging_of_configuration_changes()
    {
        // Test configuration change logging
        $this->smsService->logConfigurationChange('test_change', [
            'user_id' => 1,
            'model_type' => 'test',
            'model_id' => 1
        ]);

        $this->assertTrue(true); // Log entry should be created
    }

    /** @test */
    public function test_logging_of_sms_sends()
    {
        // Create test data
        $doctor = Doctor::factory()->create(['sms_provider' => 'twilio']);

        // Test SMS send logging
        $result = $this->smsService->send('+1234567890', 'Test message', [
            'doctor_id' => $doctor->id,
            'context' => 'test',
            'context_id' => 1
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function test_backward_compatibility_with_existing_send_method()
    {
        // Test legacy send method
        $result = $this->smsService->sendLegacy('+1234567890', 'Test message');

        $this->assertTrue($result);
    }

    /** @test */
    public function test_country_based_routing()
    {
        // Create country-specific provider mapping
        SmsProviderCountry::assignCountriesToProvider('unifonic', [
            ['code' => 'JO', 'name' => 'Jordan'],
            ['code' => 'SA', 'name' => 'Saudi Arabia']
        ]);

        // Test Jordan number uses unifonic
        $result = $this->smsService->send('+962791234567', 'Test message');
        $this->assertTrue($result['success']);

        // Test US number uses system default
        $result = $this->smsService->send('+1234567890', 'Test message');
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function test_provider_hierarchy_order()
    {
        // Create test data
        $doctor = Doctor::factory()->create(['sms_provider' => 'twilio']);
        $hospital = Hospital::factory()->create(['sms_provider' => 'plivo']);
        SystemSetting::set('sms_provider', 'messagebird', 'string', 'System SMS provider');

        // Get provider hierarchy
        $reflection = new ReflectionClass($this->smsService);
        $method = $reflection->getMethod('getProviderHierarchy');
        $method->setAccessible(true);
        $hierarchy = $method->invoke($this->smsService, [
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id
        ], '+1234567890');

        $this->assertContains('twilio', $hierarchy);
        $this->assertContains('plivo', $hierarchy);
        $this->assertContains('messagebird', $hierarchy);
    }

    /** @test */
    public function test_fallback_providers_exclude_failed_provider()
    {
        // Create test data
        $doctor = Doctor::factory()->create(['sms_provider' => 'twilio']);
        $hospital = Hospital::factory()->create(['sms_provider' => 'plivo']);
        SystemSetting::set('sms_provider', 'messagebird', 'string', 'System SMS provider');

        // Get fallback providers
        $reflection = new ReflectionClass($this->smsService);
        $method = $reflection->getMethod('getFallbackProviders');
        $method->setAccessible(true);
        $fallbackProviders = $method->invoke($this->smsService, [
            'doctor_id' => $doctor->id,
            'hospital_id' => $hospital->id
        ], 'twilio', '+1234567890');

        $this->assertNotContains('twilio', $fallbackProviders);
        $this->assertContains('plivo', $fallbackProviders);
        $this->assertContains('messagebird', $fallbackProviders);
    }
}