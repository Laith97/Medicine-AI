<?php

namespace Tests\Unit\Services;

use App\Contracts\SmsProviderInterface;
use App\Services\SmsProviders\TwilioProvider;
use App\Services\SmsProviders\PlivoProvider;
use App\Services\SmsProviders\MessageBirdProvider;
use App\Services\SmsProviders\UnifonicProvider;
use App\Services\SmsProviders\SmsGatewayHubProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsProviderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_twilio_provider_implements_interface()
    {
        $provider = new TwilioProvider();
        $this->assertInstanceOf(SmsProviderInterface::class, $provider);
    }

    /** @test */
    public function test_twilio_provider_has_required_methods()
    {
        $provider = new TwilioProvider();
        $this->assertTrue(method_exists($provider, 'send'));
        $this->assertTrue(method_exists($provider, 'getName'));
        $this->assertTrue(method_exists($provider, 'isConfigured'));
        $this->assertTrue(method_exists($provider, 'getConfigRequirements'));
        $this->assertTrue(method_exists($provider, 'getKey'));
        $this->assertTrue(method_exists($provider, 'getMessageStatus'));
        $this->assertTrue(method_exists($provider, 'sendBulkSms'));
        $this->assertTrue(method_exists($provider, 'getDeliveryReport'));
    }

    /** @test */
    public function test_twilio_provider_returns_correct_key()
    {
        $provider = new TwilioProvider();
        $this->assertEquals('twilio', $provider->getKey());
    }

    /** @test */
    public function test_twilio_provider_returns_correct_name()
    {
        $provider = new TwilioProvider();
        $this->assertEquals('Twilio', $provider->getName());
    }

    /** @test */
    public function test_twilio_provider_config_requirements()
    {
        $provider = new TwilioProvider();
        $requirements = $provider->getConfigRequirements();

        $this->assertArrayHasKey('TWILIO_ACCOUNT_SID', $requirements);
        $this->assertArrayHasKey('TWILIO_AUTH_TOKEN', $requirements);
        $this->assertArrayHasKey('TWILIO_FROM_NUMBER', $requirements);
    }

    /** @test */
    public function test_plivo_provider_implements_interface()
    {
        $provider = new PlivoProvider();
        $this->assertInstanceOf(SmsProviderInterface::class, $provider);
    }

    /** @test */
    public function test_plivo_provider_has_required_methods()
    {
        $provider = new PlivoProvider();
        $this->assertTrue(method_exists($provider, 'send'));
        $this->assertTrue(method_exists($provider, 'getName'));
        $this->assertTrue(method_exists($provider, 'isConfigured'));
        $this->assertTrue(method_exists($provider, 'getConfigRequirements'));
        $this->assertTrue(method_exists($provider, 'getKey'));
        $this->assertTrue(method_exists($provider, 'getMessageStatus'));
        $this->assertTrue(method_exists($provider, 'sendBulkSms'));
        $this->assertTrue(method_exists($provider, 'getDeliveryReport'));
    }

    /** @test */
    public function test_plivo_provider_returns_correct_key()
    {
        $provider = new PlivoProvider();
        $this->assertEquals('plivo', $provider->getKey());
    }

    /** @test */
    public function test_plivo_provider_returns_correct_name()
    {
        $provider = new PlivoProvider();
        $this->assertEquals('Plivo', $provider->getName());
    }

    /** @test */
    public function test_plivo_provider_config_requirements()
    {
        $provider = new PlivoProvider();
        $requirements = $provider->getConfigRequirements();

        $this->assertArrayHasKey('PLIVO_AUTH_ID', $requirements);
        $this->assertArrayHasKey('PLIVO_AUTH_TOKEN', $requirements);
        $this->assertArrayHasKey('PLIVO_FROM_NUMBER', $requirements);
    }

    /** @test */
    public function test_messagebird_provider_implements_interface()
    {
        $provider = new MessageBirdProvider();
        $this->assertInstanceOf(SmsProviderInterface::class, $provider);
    }

    /** @test */
    public function test_messagebird_provider_has_required_methods()
    {
        $provider = new MessageBirdProvider();
        $this->assertTrue(method_exists($provider, 'send'));
        $this->assertTrue(method_exists($provider, 'getName'));
        $this->assertTrue(method_exists($provider, 'isConfigured'));
        $this->assertTrue(method_exists($provider, 'getConfigRequirements'));
        $this->assertTrue(method_exists($provider, 'getKey'));
        $this->assertTrue(method_exists($provider, 'getMessageStatus'));
        $this->assertTrue(method_exists($provider, 'sendBulkSms'));
        $this->assertTrue(method_exists($provider, 'getDeliveryReport'));
    }

    /** @test */
    public function test_messagebird_provider_returns_correct_key()
    {
        $provider = new MessageBirdProvider();
        $this->assertEquals('messagebird', $provider->getKey());
    }

    /** @test */
    public function test_messagebird_provider_returns_correct_name()
    {
        $provider = new MessageBirdProvider();
        $this->assertEquals('MessageBird', $provider->getName());
    }

    /** @test */
    public function test_messagebird_provider_config_requirements()
    {
        $provider = new MessageBirdProvider();
        $requirements = $provider->getConfigRequirements();

        $this->assertArrayHasKey('MESSAGEBIRD_ACCESS_KEY', $requirements);
        $this->assertArrayHasKey('MESSAGEBIRD_FROM_NUMBER', $requirements);
    }

    /** @test */
    public function test_unifonic_provider_implements_interface()
    {
        $provider = new UnifonicProvider();
        $this->assertInstanceOf(SmsProviderInterface::class, $provider);
    }

    /** @test */
    public function test_unifonic_provider_has_required_methods()
    {
        $provider = new UnifonicProvider();
        $this->assertTrue(method_exists($provider, 'send'));
        $this->assertTrue(method_exists($provider, 'getName'));
        $this->assertTrue(method_exists($provider, 'isConfigured'));
        $this->assertTrue(method_exists($provider, 'getConfigRequirements'));
        $this->assertTrue(method_exists($provider, 'getKey'));
        $this->assertTrue(method_exists($provider, 'getMessageStatus'));
        $this->assertTrue(method_exists($provider, 'sendBulkSms'));
        $this->assertTrue(method_exists($provider, 'getDeliveryReport'));
    }

    /** @test */
    public function test_unifonic_provider_returns_correct_key()
    {
        $provider = new UnifonicProvider();
        $this->assertEquals('unifonic', $provider->getKey());
    }

    /** @test */
    public function test_unifonic_provider_returns_correct_name()
    {
        $provider = new UnifonicProvider();
        $this->assertEquals('Unifonic', $provider->getName());
    }

    /** @test */
    public function test_unifonic_provider_config_requirements()
    {
        $provider = new UnifonicProvider();
        $requirements = $provider->getConfigRequirements();

        $this->assertArrayHasKey('UNIFONIC_APP_SID', $requirements);
        $this->assertArrayHasKey('UNIFONIC_SENDER_ID', $requirements);
    }

    /** @test */
    public function test_smsgatewayhub_provider_implements_interface()
    {
        $provider = new SmsGatewayHubProvider();
        $this->assertInstanceOf(SmsProviderInterface::class, $provider);
    }

    /** @test */
    public function test_smsgatewayhub_provider_has_required_methods()
    {
        $provider = new SmsGatewayHubProvider();
        $this->assertTrue(method_exists($provider, 'send'));
        $this->assertTrue(method_exists($provider, 'getName'));
        $this->assertTrue(method_exists($provider, 'isConfigured'));
        $this->assertTrue(method_exists($provider, 'getConfigRequirements'));
        $this->assertTrue(method_exists($provider, 'getKey'));
        $this->assertTrue(method_exists($provider, 'getMessageStatus'));
        $this->assertTrue(method_exists($provider, 'sendBulkSms'));
        $this->assertTrue(method_exists($provider, 'getDeliveryReport'));
    }

    /** @test */
    public function test_smsgatewayhub_provider_returns_correct_key()
    {
        $provider = new SmsGatewayHubProvider();
        $this->assertEquals('smsgatewayhub', $provider->getKey());
    }

    /** @test */
    public function test_smsgatewayhub_provider_returns_correct_name()
    {
        $provider = new SmsGatewayHubProvider();
        $this->assertEquals('SMS Gateway Hub', $provider->getName());
    }

    /** @test */
    public function test_smsgatewayhub_provider_config_requirements()
    {
        $provider = new SmsGatewayHubProvider();
        $requirements = $provider->getConfigRequirements();

        $this->assertArrayHasKey('SMSGATEWAYHUB_EMAIL', $requirements);
        $this->assertArrayHasKey('SMSGATEWAYHUB_PASSWORD', $requirements);
        $this->assertArrayHasKey('SMSGATEWAYHUB_DEVICE', $requirements);
    }
}
