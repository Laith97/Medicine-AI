<?php

namespace Tests\Unit\Services\SmsProviders;

use App\Services\SmsProviders\MsegatProvider;
use App\Services\SmsProviders\TaqnyatProvider;
use App\Services\SmsProviders\SMSALAProvider;
use App\Services\SmsProviders\ConnectSaudiProvider;
use Tests\TestCase;

class SaudiSmsProvidersTest extends TestCase
{
    /**
     * Test MsegatProvider isConfigured with empty config
     */
    public function test_msegat_is_not_configured_with_empty_config(): void
    {
        $provider = new MsegatProvider([]);
        $this->assertFalse($provider->isConfigured());
    }

    /**
     * Test MsegatProvider isConfigured with partial config
     */
    public function test_msegat_is_not_configured_with_partial_config(): void
    {
        $provider = new MsegatProvider([
            'email' => 'test@example.com',
        ]);
        $this->assertFalse($provider->isConfigured());

        $provider = new MsegatProvider([
            'password' => 'password123',
        ]);
        $this->assertFalse($provider->isConfigured());
    }

    /**
     * Test MsegatProvider isConfigured with full config
     */
    public function test_msegat_is_configured_with_full_config(): void
    {
        $provider = new MsegatProvider([
            'email' => 'test@example.com',
            'password' => 'password123',
            'sender_name' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());
    }

    /**
     * Test TaqnyatProvider isConfigured
     */
    public function test_taqnyat_is_configured(): void
    {
        $provider = new TaqnyatProvider([
            'bearer_token' => 'test-token',
            'sender_name' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());

        $provider = new TaqnyatProvider([]);
        $this->assertFalse($provider->isConfigured());
    }

    /**
     * Test SMSALAProvider isConfigured
     */
    public function test_smsala_is_configured(): void
    {
        $provider = new SMSALAProvider([
            'api_key' => 'test-api-key',
            'sender_id' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());

        $provider = new SMSALAProvider([]);
        $this->assertFalse($provider->isConfigured());
    }

    /**
     * Test ConnectSaudiProvider isConfigured
     */
    public function test_connectsaudi_is_configured(): void
    {
        $provider = new ConnectSaudiProvider([
            'account_id' => 'test-account',
            'api_key' => 'test-api-key',
            'sender_name' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());

        $provider = new ConnectSaudiProvider([]);
        $this->assertFalse($provider->isConfigured());
    }

    /**
     * Test MsegatProvider getName
     */
    public function test_msegat_get_name(): void
    {
        $provider = new MsegatProvider([]);
        $this->assertEquals('Msegat', $provider->getName());
    }

    /**
     * Test TaqnyatProvider getName
     */
    public function test_taqnyat_get_name(): void
    {
        $provider = new TaqnyatProvider([]);
        $this->assertEquals('Taqnyat', $provider->getName());
    }

    /**
     * Test SMSALAProvider getName
     */
    public function test_smsala_get_name(): void
    {
        $provider = new SMSALAProvider([]);
        $this->assertEquals('SMSALA', $provider->getName());
    }

    /**
     * Test ConnectSaudiProvider getName
     */
    public function test_connectsaudi_get_name(): void
    {
        $provider = new ConnectSaudiProvider([]);
        $this->assertEquals('ConnectSaudi', $provider->getName());
    }

    /**
     * Test MsegatProvider getKey
     */
    public function test_msegat_get_key(): void
    {
        $provider = new MsegatProvider([]);
        $this->assertEquals('msegat', $provider->getKey());
    }

    /**
     * Test TaqnyatProvider getKey
     */
    public function test_taqnyat_get_key(): void
    {
        $provider = new TaqnyatProvider([]);
        $this->assertEquals('taqnyat', $provider->getKey());
    }

    /**
     * Test SMSALAProvider getKey
     */
    public function test_smsala_get_key(): void
    {
        $provider = new SMSALAProvider([]);
        $this->assertEquals('smsala', $provider->getKey());
    }

    /**
     * Test ConnectSaudiProvider getKey
     */
    public function test_connectsaudi_get_key(): void
    {
        $provider = new ConnectSaudiProvider([]);
        $this->assertEquals('connectsaudi', $provider->getKey());
    }

    /**
     * Test MsegatProvider getConfigRequirements
     */
    public function test_msegat_get_config_requirements(): void
    {
        $provider = new MsegatProvider([]);
        $requirements = $provider->getConfigRequirements();

        $this->assertIsArray($requirements);
        $this->assertArrayHasKey('email', $requirements);
        $this->assertArrayHasKey('password', $requirements);
        $this->assertArrayHasKey('sender_name', $requirements);
    }

    /**
     * Test TaqnyatProvider getConfigRequirements
     */
    public function test_taqnyat_get_config_requirements(): void
    {
        $provider = new TaqnyatProvider([]);
        $requirements = $provider->getConfigRequirements();

        $this->assertIsArray($requirements);
        $this->assertArrayHasKey('bearer_token', $requirements);
        $this->assertArrayHasKey('sender_name', $requirements);
    }

    /**
     * Test SMSALAProvider getConfigRequirements
     */
    public function test_smsala_get_config_requirements(): void
    {
        $provider = new SMSALAProvider([]);
        $requirements = $provider->getConfigRequirements();

        $this->assertIsArray($requirements);
        $this->assertArrayHasKey('api_key', $requirements);
        $this->assertArrayHasKey('sender_id', $requirements);
    }

    /**
     * Test ConnectSaudiProvider getConfigRequirements
     */
    public function test_connectsaudi_get_config_requirements(): void
    {
        $provider = new ConnectSaudiProvider([]);
        $requirements = $provider->getConfigRequirements();

        $this->assertIsArray($requirements);
        $this->assertArrayHasKey('account_id', $requirements);
        $this->assertArrayHasKey('api_key', $requirements);
        $this->assertArrayHasKey('sender_name', $requirements);
    }

    /**
     * Test MsegatProvider send returns failure when not configured
     */
    public function test_msegat_send_returns_failure_when_not_configured(): void
    {
        $provider = new MsegatProvider([]);
        $result = $provider->send('0501234567', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertEquals('Msegat configuration is incomplete', $result['message']);
    }

    /**
     * Test TaqnyatProvider send returns failure when not configured
     */
    public function test_taqnyat_send_returns_failure_when_not_configured(): void
    {
        $provider = new TaqnyatProvider([]);
        $result = $provider->send('0501234567', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertEquals('Taqnyat configuration is incomplete', $result['message']);
    }

    /**
     * Test SMSALAProvider send returns failure when not configured
     */
    public function test_smsala_send_returns_failure_when_not_configured(): void
    {
        $provider = new SMSALAProvider([]);
        $result = $provider->send('0501234567', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertEquals('SMSALA configuration is incomplete', $result['message']);
    }

    /**
     * Test ConnectSaudiProvider send returns failure when not configured
     */
    public function test_connectsaudi_send_returns_failure_when_not_configured(): void
    {
        $provider = new ConnectSaudiProvider([]);
        $result = $provider->send('0501234567', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertEquals('ConnectSaudi configuration is incomplete', $result['message']);
    }

    /**
     * Test MsegatProvider with config array keys variations
     */
    public function test_msegat_accepts_config_with_various_keys(): void
    {
        // Test with underscore keys
        $provider = new MsegatProvider([
            'user_email' => 'test@example.com',
            'password' => 'password123',
            'sender_name' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());

        // Test with camelCase keys
        $provider = new MsegatProvider([
            'email' => 'test@example.com',
            'password' => 'password123',
            'senderName' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());
    }

    /**
     * Test TaqnyatProvider with config array keys variations
     */
    public function test_taqnyat_accepts_config_with_various_keys(): void
    {
        // Test with underscore
        $provider = new TaqnyatProvider([
            'bearer_token' => 'test-token',
            'sender_name' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());

        // Test with camelCase
        $provider = new TaqnyatProvider([
            'api_key' => 'test-token',  // Alternative key
            'senderName' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());
    }

    /**
     * Test SMSALAProvider with config array keys variations
     */
    public function test_smsala_accepts_config_with_various_keys(): void
    {
        // Test with underscore
        $provider = new SMSALAProvider([
            'api_key' => 'test-key',
            'sender_id' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());

        // Test with camelCase
        $provider = new SMSALAProvider([
            'apiKey' => 'test-key',
            'senderId' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());
    }

    /**
     * Test ConnectSaudiProvider with config array keys variations
     */
    public function test_connectsaudi_accepts_config_with_various_keys(): void
    {
        // Test with underscore
        $provider = new ConnectSaudiProvider([
            'account_id' => 'test-account',
            'api_key' => 'test-key',
            'sender_name' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());

        // Test with camelCase
        $provider = new ConnectSaudiProvider([
            'accountId' => 'test-account',
            'apiKey' => 'test-key',
            'senderName' => 'TestSender',
        ]);
        $this->assertTrue($provider->isConfigured());
    }
}
