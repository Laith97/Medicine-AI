<?php

namespace Tests\Unit\Services;

use App\Services\WhatsAppNotificationService;
use App\Services\TwilioWhatsAppProvider;
use App\Services\WhatsAppBusinessProvider;
use Tests\TestCase;

class WhatsAppNotificationServiceTest extends TestCase
{
    protected WhatsAppNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WhatsAppNotificationService();
    }

    /**
     * Test send returns array with success key
     */
    public function test_send_returns_array_with_success_key(): void
    {
        $result = $this->service->send('+1234567890', 'Test message', [
            'provider_key' => 'twilio',
            'provider_config' => [
                'account_sid' => 'invalid_sid',
                'auth_token' => 'invalid_token',
                'from' => 'whatsapp:+1234567890',
            ],
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * Test send returns error on unsupported provider
     */
    public function test_send_returns_error_on_unsupported_provider(): void
    {
        $result = $this->service->send('+1234567890', 'Test message', [
            'provider_key' => 'invalid_provider',
            'provider_config' => [],
        ]);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Unsupported WhatsApp provider: invalid_provider', $result['error']);
    }

    /**
     * Test send catches exceptions and returns error array
     */
    public function test_send_catches_exception_and_returns_error(): void
    {
        // Providing empty config will cause validation to fail
        $result = $this->service->send('+1234567890', 'Test message', [
            'provider_key' => 'twilio',
            'provider_config' => [], // Missing required fields
        ]);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test getUserConfigurations method exists
     */
    public function test_get_user_configurations_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getUserConfigurations'));
    }

    /**
     * Test getHospitalConfigurations method exists
     */
    public function test_get_hospital_configurations_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getHospitalConfigurations'));
    }

    /**
     * Test isUserConfigured method exists
     */
    public function test_is_user_configured_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'isUserConfigured'));
    }

    /**
     * Test isHospitalConfigured method exists
     */
    public function test_is_hospital_configured_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'isHospitalConfigured'));
    }

    /**
     * Test send with graph_api provider
     */
    public function test_send_with_graph_api_provider(): void
    {
        $result = $this->service->send('+1234567890', 'Test message', [
            'provider_key' => 'graph_api',
            'provider_config' => [
                'access_token' => 'invalid_token',
                'phone_number_id' => 'invalid_id',
            ],
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }
}
