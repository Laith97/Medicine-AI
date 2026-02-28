<?php

namespace Tests\Unit\WebSockets;

use App\WebSockets\MedicalAudioSocket;
use Tests\TestCase;
use Mockery;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Firebase\JWT\JWT as FirebaseJWT;
use App\Models\User;
use App\Models\Appointment;

class MedicalAudioSocketTest extends TestCase
{
    protected $medicalAudioSocket;
    protected $connection;
    protected $message;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->medicalAudioSocket = new MedicalAudioSocket();
        
        // Mock connection
        $this->connection = Mockery::mock(ConnectionInterface::class);
        $this->connection->shouldReceive('close')->andReturnNull();
        $this->connection->shouldReceive('send')->andReturnNull();
        $this->connection->resourceId = 1;
        
        // Mock message
        $this->message = Mockery::mock(MessageInterface::class);
    }

    public function test_on_open_with_valid_context()
    {
        // Create a mock token and visit
        $token = FirebaseJWT::encode(['sub' => 1, 'exp' => time() + 3600, 'iat' => time()], config('app.key'));
        $user = User::factory()->create(['id' => 1]);
        $appointment = Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => 1,
            'patient_id' => 2
        ]);
        
        // Mock HTTP request with query parameters
        $httpRequest = Mockery::mock('stdClass');
        $httpRequest->shouldReceive('getUri->getQuery')
            ->andReturn('token=' . $token . '&visit_id=1&language=en');
        
        $this->connection->httpRequest = $httpRequest;
        
        // Since onOpen doesn't return anything, verify it runs without exception
        $this->medicalAudioSocket->onOpen($this->connection);
        
        $this->assertTrue(true); // Pass if no exception occurs
    }

    public function test_on_open_with_invalid_token()
    {
        // Set up connection with invalid token
        $httpRequest = Mockery::mock('stdClass');
        $httpRequest->shouldReceive('getUri->getQuery')
            ->andReturn('token=invalid_token&visit_id=1');
        
        $this->connection->httpRequest = $httpRequest;
        
        $this->connection->shouldReceive('close')->times(1);
        
        $this->medicalAudioSocket->onOpen($this->connection);
        
        $this->assertTrue(true); // Pass if no exception occurs
    }

    public function test_on_open_with_missing_parameters()
    {
        $httpRequest = Mockery::mock('stdClass');
        $httpRequest->shouldReceive('getUri->getQuery')
            ->andReturn(''); // Empty query
        
        $this->connection->httpRequest = $httpRequest;
        
        $this->connection->shouldReceive('close')->times(1);
        
        $this->medicalAudioSocket->onOpen($this->connection);
        
        $this->assertTrue(true); // Pass if no exception occurs
    }

    public function test_on_message_with_audio_chunk()
    {
        // First establish connection with valid context
        $token = FirebaseJWT::encode(['sub' => 1, 'exp' => time() + 3600, 'iat' => time()], config('app.key'));
        $httpRequest = Mockery::mock('stdClass');
        $httpRequest->shouldReceive('getUri->getQuery')
            ->andReturn('token=' . $token . '&visit_id=1&language=en');
        
        $this->connection->httpRequest = $httpRequest;
        $this->medicalAudioSocket->onOpen($this->connection);
        
        // Mock message with audio chunk data
        $payload = json_encode([
            'type' => 'audio_chunk',
            'data' => [123, 456, 789], // Sample audio data
            'timestamp' => time(),
            'sequence' => 1
        ]);
        
        $this->message->shouldReceive('getPayload')->andReturn($payload);
        
        // Since processAudioChunk doesn't return anything, verify it runs without exception
        $this->medicalAudioSocket->onMessage($this->connection, $this->message);
        
        $this->assertTrue(true); // Pass if no exception occurs
    }

    public function test_on_message_with_invalid_payload()
    {
        // First establish connection
        $token = FirebaseJWT::encode(['sub' => 1, 'exp' => time() + 3600, 'iat' => time()], config('app.key'));
        $httpRequest = Mockery::mock('stdClass');
        $httpRequest->shouldReceive('getUri->getQuery')
            ->andReturn('token=' . $token . '&visit_id=1&language=en');
        
        $this->connection->httpRequest = $httpRequest;
        $this->medicalAudioSocket->onOpen($this->connection);
        
        // Mock message with invalid payload
        $this->message->shouldReceive('getPayload')->andReturn('invalid_json');
        
        // Should handle invalid JSON gracefully
        $this->medicalAudioSocket->onMessage($this->connection, $this->message);
        
        $this->assertTrue(true); // Pass if no exception occurs
    }

    public function test_on_close()
    {
        // First establish connection
        $token = FirebaseJWT::encode(['sub' => 1, 'exp' => time() + 3600, 'iat' => time()], config('app.key'));
        $httpRequest = Mockery::mock('stdClass');
        $httpRequest->shouldReceive('getUri->getQuery')
            ->andReturn('token=' . $token . '&visit_id=1&language=en');
        
        $this->connection->httpRequest = $httpRequest;
        $this->medicalAudioSocket->onOpen($this->connection);
        
        // Verify onClose runs without exception
        $this->medicalAudioSocket->onClose($this->connection);
        
        $this->assertTrue(true); // Pass if no exception occurs
    }

    public function test_on_error()
    {
        $exception = new \Exception('Test exception');
        
        // Since onError handles exceptions gracefully, verify it runs without re-throwing
        $this->connection->shouldReceive('send');
        
        $this->medicalAudioSocket->onError($this->connection, $exception);
        
        $this->assertTrue(true); // Pass if no exception occurs
    }

    public function test_validate_medical_context_with_valid_token()
    {
        $user = User::factory()->create(['id' => 1]);
        $appointment = Appointment::factory()->create([
            'id' => 1,
            'doctor_id' => 1,
            'patient_id' => 2
        ]);
        
        $token = FirebaseJWT::encode(['sub' => 1, 'exp' => time() + 3600, 'iat' => time()], config('app.key'));
        $params = [
            'token' => $token,
            'visit_id' => 1
        ];
        
        // Access private method using reflection
        $reflection = new \ReflectionClass($this->medicalAudioSocket);
        $method = $reflection->getMethod('validateMedicalContext');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->medicalAudioSocket, $params);
        
        $this->assertTrue($result);
    }

    public function test_validate_medical_context_with_invalid_token()
    {
        $params = [
            'token' => 'invalid_token',
            'visit_id' => 1
        ];
        
        // Access private method using reflection
        $reflection = new \ReflectionClass($this->medicalAudioSocket);
        $method = $reflection->getMethod('validateMedicalContext');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->medicalAudioSocket, $params);
        
        $this->assertFalse($result);
    }

    public function test_validate_medical_context_with_missing_params()
    {
        $params = [];
        
        // Access private method using reflection
        $reflection = new \ReflectionClass($this->medicalAudioSocket);
        $method = $reflection->getMethod('validateMedicalContext');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->medicalAudioSocket, $params);
        
        $this->assertFalse($result);
    }

    public function test_validate_medical_context_with_expired_token()
    {
        $token = FirebaseJWT::encode(['sub' => 1, 'exp' => time() - 3600, 'iat' => time() - 3600], config('app.key'));
        $params = [
            'token' => $token,
            'visit_id' => 1
        ];
        
        // Access private method using reflection
        $reflection = new \ReflectionClass($this->medicalAudioSocket);
        $method = $reflection->getMethod('validateMedicalContext');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->medicalAudioSocket, $params);
        
        $this->assertFalse($result);
    }
}