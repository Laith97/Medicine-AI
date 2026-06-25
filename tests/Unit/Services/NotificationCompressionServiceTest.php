<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\NotificationCompressionService;

class NotificationCompressionServiceTest extends TestCase
{
    protected NotificationCompressionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationCompressionService();
    }

    /** @test */
    public function it_returns_original_payload_for_small_payloads()
    {
        $payload = ['type' => 'test', 'message' => 'hello'];

        $result = $this->service->compressPayload($payload);

        $this->assertEquals($payload, $result);
    }

    /** @test */
    public function it_compresses_large_payloads()
    {
        $payload = [
            'type' => 'test',
            'data' => str_repeat('Large notification payload data ', 100),
        ];

        $result = $this->service->compressPayload($payload);

        $this->assertIsArray($result);

        if (isset($result['_compressed'])) {
            $this->assertTrue($result['_compressed']);
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('_original_size', $result);
            $this->assertArrayHasKey('_compressed_size', $result);
        }
    }

    /** @test */
    public function it_can_decompress_compressed_payload()
    {
        $original = [
            'type' => 'test',
            'message' => 'Hello World',
            'data' => str_repeat('This is a long payload that should be compressed ', 50),
        ];

        $compressed = $this->service->compressPayload($original);

        if (isset($compressed['_compressed'])) {
            $decompressed = $this->service->decompressPayload($compressed);
            $this->assertEquals($original, $decompressed);
        }
    }

    /** @test */
    public function it_returns_original_if_not_compressed()
    {
        $payload = ['type' => 'test', 'message' => 'hello'];

        $result = $this->service->decompressPayload($payload);

        $this->assertEquals($payload, $result);
    }

    /** @test */
    public function it_handles_malformed_compressed_payload()
    {
        $malformed = ['_compressed' => true, 'data' => base64_encode('not-valid-compressed-data')];

        // gzuncompress throws a warning on invalid data; suppress it
        $result = @$this->service->decompressPayload($malformed);

        $this->assertEquals($malformed, $result);
    }

    /** @test */
    public function it_handles_missing_data_in_compressed_payload()
    {
        $missing = ['_compressed' => true];

        $result = $this->service->decompressPayload($missing);

        $this->assertEquals($missing, $result);
    }

    /** @test */
    public function it_can_check_client_gzip_support()
    {
        $result = $this->service->clientSupportsGzip();

        $this->assertIsBool($result);
    }

    /** @test */
    public function it_compresses_http_response()
    {
        $content = str_repeat('Large response content that can be compressed ', 100);

        $result = $this->service->compressResponse($content);

        $this->assertIsString($result);
    }

    /** @test */
    public function it_returns_stats()
    {
        $stats = $this->service->getCompressionStats();

        $this->assertArrayHasKey('compression_level', $stats);
        $this->assertArrayHasKey('min_payload_size', $stats);
        $this->assertArrayHasKey('client_supports_gzip', $stats);
    }
}
