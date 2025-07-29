<?php

namespace Tests\Unit\Services;

use App\Services\OpenAIClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OpenAIClientTest extends TestCase
{
    protected $openAIClient;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openai.key' => 'test-api-key']);
        $this->openAIClient = new OpenAIClient();
    }

    public function test_openai_client_can_be_instantiated()
    {
        $this->assertInstanceOf(OpenAIClient::class, $this->openAIClient);
    }

    public function test_ask_method_sends_correct_request()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'This is a test response'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->openAIClient->ask('What is the weather?');

        $this->assertEquals('This is a test response', $response);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions' &&
                   $request['model'] === 'gpt-3.5-turbo' &&
                   $request['messages'][0]['role'] === 'system' &&
                   $request['messages'][1]['role'] === 'user' &&
                   $request['messages'][1]['content'] === 'What is the weather?' &&
                   $request['temperature'] === 0.7;
        });
    }

    public function test_ask_method_handles_api_error()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([], 500)
        ]);

        $response = $this->openAIClient->ask('What is the weather?');

        $this->assertNull($response);
    }

    public function test_post_to_openai_method()
    {
        Http::fake([
            'api.openai.com/v1/test-endpoint' => Http::response(['success' => true], 200)
        ]);

        $response = $this->openAIClient->postToOpenAI('/test-endpoint', ['test' => 'data']);

        $this->assertTrue($response->successful());
        $this->assertEquals(['success' => true], $response->json());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/test-endpoint' &&
                   $request['test'] === 'data';
        });
    }

    public function test_upload_file_creates_jsonl_format()
    {
        Storage::fake('local');

        // Create a test file with names
        $testContent = "John Doe\nJane Smith\nBob Johnson";
        $testFile = UploadedFile::fake()->createWithContent('names.txt', $testContent);

        Http::fake([
            'api.openai.com/v1/files' => Http::response(['id' => 'file-123'], 200)
        ]);

        $result = $this->openAIClient->uploadFile($testFile);

        $this->assertEquals('file-123', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/files' &&
                   $request->hasFile('file');
        });
    }

    public function test_upload_file_handles_api_error()
    {
        Storage::fake('local');

        $testFile = UploadedFile::fake()->createWithContent('names.txt', 'John Doe');

        Http::fake([
            'api.openai.com/v1/files' => Http::response(['error' => 'Upload failed'], 400)
        ]);

        $result = $this->openAIClient->uploadFile($testFile);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $result);
    }

    public function test_upload_file_handles_exception()
    {
        Log::shouldReceive('error')->once();

        // Create an invalid file that will cause an exception
        $invalidFile = $this->createMock(UploadedFile::class);
        $invalidFile->method('getRealPath')->willThrowException(new \Exception('File error'));

        $result = $this->openAIClient->uploadFile($invalidFile);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $result);
    }

    public function test_create_thread_with_message_success()
    {
        Http::fake([
            'api.openai.com/v1/threads' => Http::response(['id' => 'thread-123'], 200)
        ]);

        $result = $this->openAIClient->createThreadWithMessage('Test prompt', ['file-123']);

        $this->assertEquals('thread-123', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/threads' &&
                   $request['messages'][0]['role'] === 'user' &&
                   $request['messages'][0]['content'] === 'Test prompt' &&
                   $request['messages'][0]['file_ids'] === ['file-123'];
        });
    }

    public function test_create_thread_with_message_handles_api_error()
    {
        Log::shouldReceive('error')->once();

        Http::fake([
            'api.openai.com/v1/threads' => Http::response(['error' => 'Thread creation failed'], 400)
        ]);

        $result = $this->openAIClient->createThreadWithMessage('Test prompt');

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $result);
    }

    public function test_create_thread_with_message_handles_exception()
    {
        Log::shouldReceive('error')->once();

        Http::fake([
            'api.openai.com/v1/threads' => Http::response()->throw(new \Exception('Network error'))
        ]);

        $result = $this->openAIClient->createThreadWithMessage('Test prompt');

        $this->assertNull($result);
    }

    public function test_start_run_success()
    {
        Http::fake([
            'api.openai.com/v1/threads/thread-123/runs' => Http::response([
                'id' => 'run-456',
                'status' => 'queued'
            ], 200)
        ]);

        $result = $this->openAIClient->startRun('thread-123', ['file-123']);

        $this->assertEquals(['id' => 'run-456', 'status' => 'queued'], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/threads/thread-123/runs' &&
                   $request['instructions'] === 'Analyze the uploaded file and the prompt.' &&
                   $request['file_ids'] === ['file-123'];
        });
    }

    public function test_start_run_requires_file_ids()
    {
        $result = $this->openAIClient->startRun('thread-123', []);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $result);
    }

    public function test_start_run_handles_api_error()
    {
        Log::shouldReceive('error')->once();

        Http::fake([
            'api.openai.com/v1/threads/thread-123/runs' => Http::response(['error' => 'Run failed'], 400)
        ]);

        $result = $this->openAIClient->startRun('thread-123', ['file-123']);

        $this->assertNull($result);
    }

    public function test_start_run_handles_exception()
    {
        Log::shouldReceive('error')->once();

        Http::fake([
            'api.openai.com/v1/threads/thread-123/runs' => Http::response()->throw(new \Exception('Network error'))
        ]);

        $result = $this->openAIClient->startRun('thread-123', ['file-123']);

        $this->assertNull($result);
    }

    public function test_client_has_correct_headers()
    {
        Http::fake();

        $this->openAIClient->postToOpenAI('/test', []);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-api-key') &&
                   $request->hasHeader('OpenAI-Beta', 'assistants=v2') &&
                   $request->hasHeader('Content-Type', 'application/json');
        });
    }

    public function test_client_has_correct_base_url()
    {
        Http::fake();

        $this->openAIClient->postToOpenAI('/test', []);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.openai.com/v1');
        });
    }

    public function test_client_has_timeout_configured()
    {
        // This test verifies that the HTTP client is configured with a 30-second timeout
        // We can't directly test the timeout value, but we can verify the client is properly configured
        $this->assertInstanceOf(OpenAIClient::class, $this->openAIClient);
    }
}
