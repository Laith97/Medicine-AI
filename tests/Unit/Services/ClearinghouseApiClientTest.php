<?php

namespace Tests\Unit\Services;

use App\Models\ClearinghouseAccount;
use App\Services\ClearinghouseApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClearinghouseApiClientTest extends TestCase
{
    use RefreshDatabase;

    protected ClearinghouseAccount $account;
    protected ClearinghouseApiClient $apiClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test clearinghouse account
        $this->account = ClearinghouseAccount::factory()->create([
            'provider' => 'availity',
            'name' => 'Test Clearinghouse',
            'credentials' => [
                'sender_id' => 'TESTSENDER',
                'receiver_id' => 'TESTRECEIVER',
                'username' => 'testuser',
                'password' => 'testpass',
                'client_id' => 'test_client',
                'client_secret' => 'test_secret'
            ]
        ]);

        $this->apiClient = new ClearinghouseApiClient($this->account);
    }

    /** @test */
    public function it_initializes_with_account()
    {
        $this->assertInstanceOf(ClearinghouseApiClient::class, $this->apiClient);
        // Test that the client was initialized with the correct account
        $this->assertNotNull($this->apiClient);
    }

    /** @test */
    public function it_authenticates_successfully_with_oauth2()
    {
        // Mock successful authentication response
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'test_token_123',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'session_id' => 'session_123'
            ], 200)
        ]);

        $result = $this->invokePrivateMethod('authenticate');

        $this->assertTrue($result);

        // Check that token was cached
        $cacheKey = "clearinghouse_token_{$this->account->id}";
        $this->assertEquals('test_token_123', Cache::get($cacheKey));

        // Check that account was marked as used
        $this->account->refresh();
        $this->assertNotNull($this->account->last_used_at);
    }

    /** @test */
    public function it_handles_authentication_failure()
    {
        // Mock failed authentication response
        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'error' => 'invalid_credentials',
                'message' => 'Invalid username or password'
            ], 401)
        ]);

        $result = $this->invokePrivateMethod('authenticate');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_uses_cached_token_when_available()
    {
        // Set up cached token
        $cacheKey = "clearinghouse_token_{$this->account->id}";
        Cache::put($cacheKey, 'cached_token_123', now()->addMinutes(30));

        // Call ensureAuthenticated - should not make HTTP request
        Http::fake(); // No requests should be made

        $this->invokePrivateMethod('ensureAuthenticated');

        // Check that cached token is used
        $this->assertEquals('cached_token_123', $this->getPrivateProperty('accessToken'));
    }

    /** @test */
    public function it_submits_edi_successfully()
    {
        // Set up authentication
        $this->setPrivateProperty('accessToken', 'test_token_123');

        $ediContent = 'ISA*00*...*IEA*1*000000001~';
        $metadata = ['batch_id' => 'BATCH123', 'priority' => 'normal'];

        Http::fake([
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'batch_id' => 'BATCH123',
                'tracking_id' => 'TRACK123',
                'status' => 'accepted',
                'message' => 'Submission successful'
            ], 200)
        ]);

        $result = $this->apiClient->submitEDI($ediContent, $metadata);

        $this->assertTrue($result['success']);
        $this->assertEquals('BATCH123', $result['batch_id']);
        $this->assertEquals('TRACK123', $result['tracking_id']);
        $this->assertEquals('accepted', $result['status']);
    }

    /** @test */
    public function it_handles_edi_submission_failure()
    {
        // Set up authentication
        $this->setPrivateProperty('accessToken', 'test_token_123');

        $ediContent = 'ISA*00*...*IEA*1*000000001~';

        Http::fake([
            'https://api.availity.com/claims/v1/submit' => Http::response([
                'error' => 'validation_error',
                'message' => 'EDI content is invalid',
                'details' => ['Missing required segment']
            ], 400)
        ]);

        $result = $this->apiClient->submitEDI($ediContent);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Submission failed with status 400', $result['error']);
    }

    /** @test */
    public function it_checks_submission_status()
    {
        // Set up authentication
        $this->setPrivateProperty('accessToken', 'test_token_123');

        $batchId = 'BATCH123';

        Http::fake([
            'https://api.availity.com/claims/v1/status' => Http::response([
                'batch_id' => 'BATCH123',
                'status' => 'processed',
                'responses' => [
                    ['claim_id' => 'CLM001', 'status' => 'accepted'],
                    ['claim_id' => 'CLM002', 'status' => 'rejected', 'reason' => 'Invalid NPI']
                ],
                'last_updated' => '2024-01-15T10:30:00Z'
            ], 200)
        ]);

        $result = $this->apiClient->checkStatus($batchId);

        $this->assertTrue($result['success']);
        $this->assertEquals('processed', $result['status']);
        $this->assertCount(2, $result['responses']);
        $this->assertEquals('2024-01-15T10:30:00Z', $result['last_updated']);
    }

    /** @test */
    public function it_retrieves_responses()
    {
        // Set up authentication
        $this->setPrivateProperty('accessToken', 'test_token_123');

        $batchId = 'BATCH123';

        Http::fake([
            'https://api.availity.com/claims/v1/responses' => Http::response([
                'batch_id' => 'BATCH123',
                'responses' => [
                    [
                        'type' => '277CA',
                        'content' => 'EDI_277CA_CONTENT',
                        'received_at' => '2024-01-15T11:00:00Z'
                    ],
                    [
                        'type' => '835',
                        'content' => 'EDI_835_CONTENT',
                        'received_at' => '2024-01-15T12:00:00Z'
                    ]
                ]
            ], 200)
        ]);

        $result = $this->apiClient->getResponses($batchId);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['responses']);
        $this->assertEquals('277CA', $result['responses'][0]['type']);
        $this->assertEquals('835', $result['responses'][1]['type']);
    }

    /** @test */
    public function it_retrieves_responses_with_type_filter()
    {
        // Set up authentication
        $this->setPrivateProperty('accessToken', 'test_token_123');

        $batchId = 'BATCH123';
        $responseType = '835';

        Http::fake([
            'https://api.availity.com/claims/v1/responses*' => Http::response([
                'batch_id' => 'BATCH123',
                'responses' => [
                    [
                        'type' => '835',
                        'content' => 'EDI_835_CONTENT',
                        'received_at' => '2024-01-15T12:00:00Z'
                    ]
                ]
            ], 200)
        ]);

        $result = $this->apiClient->getResponses($batchId, $responseType);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['responses']);
        $this->assertEquals('835', $result['responses'][0]['type']);
    }

    /** @test */
    public function it_tests_connection_successfully()
    {
        // Set up authentication
        $this->setPrivateProperty('accessToken', 'test_token_123');

        Http::fake([
            'https://api.availity.com/claims/v1/status*' => Http::response([
                'status' => 'ok',
                'message' => 'Service available'
            ], 200, [], 0.5) // 500ms response time
        ]);

        $result = $this->apiClient->testConnection();

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertStringContainsString('Connection successful', $result['message']);
        $this->assertIsFloat($result['response_time']);
    }

    /** @test */
    public function it_handles_connection_test_failure()
    {
        // Set up authentication
        $this->setPrivateProperty('accessToken', 'test_token_123');

        Http::fake([
            'https://api.availity.com/claims/v1/status*' => Http::response([
                'error' => 'service_unavailable'
            ], 503)
        ]);

        $result = $this->apiClient->testConnection();

        $this->assertFalse($result['success']);
        $this->assertEquals(503, $result['status_code']);
        $this->assertStringContainsString('Connection failed', $result['message']);
    }

    /** @test */
    public function it_handles_network_exceptions_during_submission()
    {
        // Set up authentication
        $this->setPrivateProperty('accessToken', 'test_token_123');

        $ediContent = 'ISA*00*...*IEA*1*000000001~';

        // Mock network failure
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Network timeout');
        });

        $result = $this->apiClient->submitEDI($ediContent);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Exception during submission', $result['error']);
        $this->assertStringContainsString('Network timeout', $result['error']);
    }

    /** @test */
    public function it_handles_timeout_exceptions()
    {
        // Set up authentication
        $this->setPrivateProperty('accessToken', 'test_token_123');

        $batchId = 'BATCH123';

        // Mock timeout
        Http::fake(function () {
            throw new \Exception('Request timed out');
        });

        $result = $this->apiClient->checkStatus($batchId);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Exception during status check', $result['error']);
        $this->assertStringContainsString('Request timed out', $result['error']);
    }

    /** @test */
    public function it_gets_provider_config_for_availity()
    {
        $config = $this->invokePrivateMethod('getProviderConfig', ['availity']);

        $this->assertEquals('https://api.availity.com/auth/v1/token', $config['endpoints']['auth']);
        $this->assertEquals('https://api.availity.com/claims/v1/submit', $config['endpoints']['submit']);
        $this->assertEquals('https://api.availity.com/claims/v1/status', $config['endpoints']['status']);
        $this->assertEquals('https://api.availity.com/claims/v1/responses', $config['endpoints']['responses']);
        $this->assertEquals('oauth2', $config['auth_type']);
    }

    /** @test */
    public function it_gets_provider_config_for_change_healthcare()
    {
        $config = $this->invokePrivateMethod('getProviderConfig', ['change_healthcare']);

        $this->assertEquals('https://api.changehealthcare.com/auth/token', $config['endpoints']['auth']);
        $this->assertEquals('https://api.changehealthcare.com/claims/submit', $config['endpoints']['submit']);
        $this->assertEquals('oauth2', $config['auth_type']);
    }

    /** @test */
    public function it_gets_default_config_for_unknown_provider()
    {
        $config = $this->invokePrivateMethod('getProviderConfig', ['unknown_provider']);

        $this->assertStringContainsString('api.clearinghouse.com', $config['endpoints']['auth']);
        $this->assertEquals('basic', $config['auth_type']);
    }

    /** @test */
    public function it_sets_default_headers()
    {
        $headers = $this->invokePrivateMethod('getDefaultHeaders');

        $this->assertEquals('application/json', $headers['Accept']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('Medicine-AI/1.0', $headers['User-Agent']);
    }

    /** @test */
    public function it_includes_session_id_in_headers_when_available()
    {
        $this->setPrivateProperty('sessionId', 'session_12345');

        $headers = $this->invokePrivateMethod('getDefaultHeaders');

        $this->assertEquals('session_12345', $headers['X-Session-ID']);
    }

    /** @test */
    public function it_handles_authentication_with_minimal_credentials()
    {
        // Create account with minimal credentials
        $minimalAccount = ClearinghouseAccount::factory()->create([
            'provider' => 'availity',
            'credentials' => [
                'username' => 'testuser',
                'password' => 'testpass'
            ]
        ]);

        $client = new ClearinghouseApiClient($minimalAccount);

        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'minimal_token_123',
                'expires_in' => 3600
            ], 200)
        ]);

        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('authenticate');
        $method->setAccessible(true);
        $result = $method->invokeArgs($client, []);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_empty_credentials_gracefully()
    {
        // Create account with empty credentials
        $emptyAccount = ClearinghouseAccount::factory()->create([
            'provider' => 'availity',
            'credentials' => []
        ]);

        $client = new ClearinghouseApiClient($emptyAccount);

        Http::fake([
            'https://api.availity.com/auth/v1/token' => Http::response([
                'access_token' => 'empty_token_123',
                'expires_in' => 3600
            ], 200)
        ]);

        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('authenticate');
        $method->setAccessible(true);
        $result = $method->invokeArgs($client, []);

        $this->assertTrue($result);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($this->apiClient);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->apiClient, $parameters);
    }

    /**
     * Helper method to set private properties for testing
     */
    private function setPrivateProperty(string $propertyName, $value): void
    {
        $reflection = new \ReflectionClass($this->apiClient);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($this->apiClient, $value);
    }

    /**
     * Helper method to get private properties for testing
     */
    private function getPrivateProperty(string $propertyName)
    {
        $reflection = new \ReflectionClass($this->apiClient);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($this->apiClient);
    }
}
