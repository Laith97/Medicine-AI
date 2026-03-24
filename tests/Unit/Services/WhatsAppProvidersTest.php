<?php

namespace Tests\Unit\Services\WhatsAppProviders;

use App\Services\TwilioWhatsAppProvider;
use App\Services\WhatsAppBusinessProvider;
use App\Services\WhatsAppProviderInterface;
use Tests\TestCase;

class WhatsAppProvidersTest extends TestCase
{
    /**
     * Test TwilioWhatsAppProvider implements interface
     */
    public function test_twilio_provider_implements_interface(): void
    {
        $provider = new TwilioWhatsAppProvider([
            'account_sid' => 'test_sid',
            'auth_token' => 'test_token',
            'from' => 'whatsapp:+1234567890',
        ]);

        $this->assertInstanceOf(WhatsAppProviderInterface::class, $provider);
    }

    /**
     * Test TwilioWhatsAppProvider getName
     */
    public function test_twilio_get_name(): void
    {
        $provider = new TwilioWhatsAppProvider([
            'account_sid' => 'test_sid',
            'auth_token' => 'test_token',
            'from' => 'whatsapp:+1234567890',
        ]);

        $this->assertEquals('Twilio WhatsApp', $provider->getName());
    }

    /**
     * Test TwilioWhatsAppProvider getKey
     */
    public function test_twilio_get_key(): void
    {
        $provider = new TwilioWhatsAppProvider([
            'account_sid' => 'test_sid',
            'auth_token' => 'test_token',
            'from' => 'whatsapp:+1234567890',
        ]);

        $this->assertEquals('twilio', $provider->getKey());
    }

    /**
     * Test TwilioWhatsAppProvider validateConfig throws on missing account_sid
     */
    public function test_twilio_throws_on_missing_account_sid(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required config: account_sid');

        new TwilioWhatsAppProvider([
            'auth_token' => 'test_token',
            'from' => 'whatsapp:+1234567890',
        ]);
    }

    /**
     * Test TwilioWhatsAppProvider validateConfig throws on missing auth_token
     */
    public function test_twilio_throws_on_missing_auth_token(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required config: auth_token');

        new TwilioWhatsAppProvider([
            'account_sid' => 'test_sid',
            'from' => 'whatsapp:+1234567890',
        ]);
    }

    /**
     * Test TwilioWhatsAppProvider validateConfig throws on missing from
     */
    public function test_twilio_throws_on_missing_from(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required config: from');

        new TwilioWhatsAppProvider([
            'account_sid' => 'test_sid',
            'auth_token' => 'test_token',
        ]);
    }

    /**
     * Test WhatsAppBusinessProvider implements interface
     */
    public function test_graph_api_provider_implements_interface(): void
    {
        $provider = new WhatsAppBusinessProvider([
            'access_token' => 'test_token',
            'phone_number_id' => 'test_phone_id',
        ]);

        $this->assertInstanceOf(WhatsAppProviderInterface::class, $provider);
    }

    /**
     * Test WhatsAppBusinessProvider getName
     */
    public function test_graph_api_get_name(): void
    {
        $provider = new WhatsAppBusinessProvider([
            'access_token' => 'test_token',
            'phone_number_id' => 'test_phone_id',
        ]);

        $this->assertEquals('WhatsApp Business API', $provider->getName());
    }

    /**
     * Test WhatsAppBusinessProvider getKey
     */
    public function test_graph_api_get_key(): void
    {
        $provider = new WhatsAppBusinessProvider([
            'access_token' => 'test_token',
            'phone_number_id' => 'test_phone_id',
        ]);

        $this->assertEquals('graph_api', $provider->getKey());
    }

    /**
     * Test WhatsAppBusinessProvider validateConfig throws on missing access_token
     */
    public function test_graph_api_throws_on_missing_access_token(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required config: access_token');

        new WhatsAppBusinessProvider([
            'phone_number_id' => 'test_phone_id',
        ]);
    }

    /**
     * Test WhatsAppBusinessProvider validateConfig throws on missing phone_number_id
     */
    public function test_graph_api_throws_on_missing_phone_number_id(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing required config: phone_number_id');

        new WhatsAppBusinessProvider([
            'access_token' => 'test_token',
        ]);
    }

    /**
     * Test TwilioWhatsAppProvider sendMessage returns failure when not configured properly
     */
    public function test_twilio_send_returns_failure_array_when_client_fails(): void
    {
        // Use invalid credentials - this will fail when trying to send
        $provider = new TwilioWhatsAppProvider([
            'account_sid' => 'invalid_sid',
            'auth_token' => 'invalid_token',
            'from' => 'whatsapp:+1234567890',
        ]);

        $result = $provider->sendMessage('+1987654321', 'Test message');

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test TwilioWhatsAppProvider sendMessage returns array with success key
     */
    public function test_twilio_send_returns_array_with_success_key(): void
    {
        $provider = new TwilioWhatsAppProvider([
            'account_sid' => 'test_sid',
            'auth_token' => 'test_token',
            'from' => 'whatsapp:+1234567890',
        ]);

        $result = $provider->sendMessage('+1987654321', 'Test message');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * Test WhatsAppBusinessProvider sendMessage returns failure when not configured
     */
    public function test_graph_api_send_returns_failure_array(): void
    {
        $provider = new WhatsAppBusinessProvider([
            'access_token' => 'invalid_token',
            'phone_number_id' => 'invalid_id',
        ]);

        $result = $provider->sendMessage('+1987654321', 'Test message');

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test TwilioWhatsAppProvider formatPhoneNumber adds whatsapp prefix
     */
    public function test_twilio_format_number_adds_whatsapp_prefix(): void
    {
        $provider = new TwilioWhatsAppProvider([
            'account_sid' => 'test_sid',
            'auth_token' => 'test_token',
            'from' => 'whatsapp:+1234567890',
        ]);

        // Use reflection to access protected method
        $reflection = new \ReflectionMethod($provider, 'formatWhatsAppNumber');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($provider, '+1987654321');
        $this->assertEquals('whatsapp:+1987654321', $result);
    }

    /**
     * Test TwilioWhatsAppProvider formatPhoneNumber throws on invalid number
     */
    public function test_twilio_format_number_throws_on_invalid_number(): void
    {
        $provider = new TwilioWhatsAppProvider([
            'account_sid' => 'test_sid',
            'auth_token' => 'test_token',
            'from' => 'whatsapp:+1234567890',
        ]);

        $reflection = new \ReflectionMethod($provider, 'formatWhatsAppNumber');
        $reflection->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid phone number format');

        $reflection->invoke($provider, '123'); // Too short
    }

    /**
     * Test WhatsAppBusinessProvider formatNumber cleans digits
     */
    public function test_graph_api_format_number_cleans_digits(): void
    {
        $provider = new WhatsAppBusinessProvider([
            'access_token' => 'test_token',
            'phone_number_id' => 'test_id',
        ]);

        $reflection = new \ReflectionMethod($provider, 'formatNumber');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($provider, '+1 (987) 654-3210');
        $this->assertEquals('19876543210', $result);
    }

    /**
     * Test WhatsAppBusinessProvider formatNumber throws on invalid number
     */
    public function test_graph_api_format_number_throws_on_invalid(): void
    {
        $provider = new WhatsAppBusinessProvider([
            'access_token' => 'test_token',
            'phone_number_id' => 'test_id',
        ]);

        $reflection = new \ReflectionMethod($provider, 'formatNumber');
        $reflection->setAccessible(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid phone number format');

        $reflection->invoke($provider, '123'); // Too short
    }
}
