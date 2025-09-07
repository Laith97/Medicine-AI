<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NotificationCompressionService
{
    private const COMPRESSION_LEVEL = 6; // Balance between speed and compression ratio
    private const MIN_PAYLOAD_SIZE = 1024; // Only compress payloads larger than 1KB

    /**
     * Compress notification payload if beneficial
     */
    public function compressPayload(array $payload): array
    {
        $jsonPayload = json_encode($payload);
        $payloadSize = strlen($jsonPayload);

        // Only compress if payload is large enough
        if ($payloadSize < self::MIN_PAYLOAD_SIZE) {
            return $payload;
        }

        $compressed = gzcompress($jsonPayload, self::COMPRESSION_LEVEL);

        if ($compressed === false) {
            Log::warning('Failed to compress notification payload', [
                'payload_size' => $payloadSize
            ]);
            return $payload;
        }

        $compressedSize = strlen($compressed);

        // Only use compression if it actually reduces size
        if ($compressedSize >= $payloadSize) {
            Log::info('Compression not beneficial, using original payload', [
                'original_size' => $payloadSize,
                'compressed_size' => $compressedSize
            ]);
            return $payload;
        }

        $compressionRatio = round(($payloadSize - $compressedSize) / $payloadSize * 100, 2);

        Log::info('Notification payload compressed', [
            'original_size' => $payloadSize,
            'compressed_size' => $compressedSize,
            'compression_ratio' => $compressionRatio . '%'
        ]);

        return [
            '_compressed' => true,
            '_original_size' => $payloadSize,
            '_compressed_size' => $compressedSize,
            'data' => base64_encode($compressed)
        ];
    }

    /**
     * Decompress notification payload if compressed
     */
    public function decompressPayload(array $payload): array
    {
        if (!isset($payload['_compressed']) || !$payload['_compressed']) {
            return $payload;
        }

        if (!isset($payload['data'])) {
            Log::warning('Compressed payload missing data field');
            return $payload;
        }

        $compressedData = base64_decode($payload['data']);

        if ($compressedData === false) {
            Log::error('Failed to decode compressed payload');
            return $payload;
        }

        $decompressed = gzuncompress($compressedData);

        if ($decompressed === false) {
            Log::error('Failed to decompress notification payload');
            return $payload;
        }

        $originalPayload = json_decode($decompressed, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode decompressed JSON', [
                'json_error' => json_last_error_msg()
            ]);
            return $payload;
        }

        Log::info('Notification payload decompressed', [
            'original_size' => $payload['_original_size'] ?? 0,
            'compressed_size' => $payload['_compressed_size'] ?? 0
        ]);

        return $originalPayload;
    }

    /**
     * Check if client supports gzip compression
     */
    public function clientSupportsGzip(): bool
    {
        $acceptEncoding = request()->header('Accept-Encoding', '');

        return str_contains(strtolower($acceptEncoding), 'gzip');
    }

    /**
     * Get compression statistics
     */
    public function getCompressionStats(): array
    {
        return [
            'compression_level' => self::COMPRESSION_LEVEL,
            'min_payload_size' => self::MIN_PAYLOAD_SIZE,
            'client_supports_gzip' => $this->clientSupportsGzip(),
        ];
    }

    /**
     * Compress response content for HTTP transport
     */
    public function compressResponse(string $content): string
    {
        if (!$this->clientSupportsGzip()) {
            return $content;
        }

        $compressed = gzencode($content, self::COMPRESSION_LEVEL);

        if ($compressed === false) {
            Log::warning('Failed to compress HTTP response');
            return $content;
        }

        $originalSize = strlen($content);
        $compressedSize = strlen($compressed);
        $compressionRatio = round(($originalSize - $compressedSize) / $originalSize * 100, 2);

        Log::info('HTTP response compressed', [
            'original_size' => $originalSize,
            'compressed_size' => $compressedSize,
            'compression_ratio' => $compressionRatio . '%'
        ]);

        return $compressed;
    }
}
