<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class PayloadCompressionService
{
    protected const COMPRESSION_THRESHOLD = 1024; // Compress payloads larger than 1KB
    protected const COMPRESSION_LEVEL = 6; // Balance between speed and compression ratio

    /**
     * Compress payload data if it exceeds threshold
     */
    public function compress(array $data): array
    {
        $jsonData = json_encode($data);
        $dataSize = strlen($jsonData);

        // Only compress if data is large enough
        if ($dataSize < self::COMPRESSION_THRESHOLD) {
            return [
                'data' => $data,
                'compressed' => false,
                'original_size' => $dataSize,
                'compression_ratio' => 1.0
            ];
        }

        try {
            $compressed = gzcompress($jsonData, self::COMPRESSION_LEVEL);
            $compressedSize = strlen($compressed);
            $compressionRatio = $dataSize > 0 ? $compressedSize / $dataSize : 1.0;

            Log::debug('Payload compressed', [
                'original_size' => $dataSize,
                'compressed_size' => $compressedSize,
                'compression_ratio' => round($compressionRatio, 3)
            ]);

            return [
                'data' => base64_encode($compressed),
                'compressed' => true,
                'original_size' => $dataSize,
                'compressed_size' => $compressedSize,
                'compression_ratio' => $compressionRatio
            ];
        } catch (Exception $e) {
            Log::warning('Failed to compress payload, using uncompressed data', [
                'error' => $e->getMessage(),
                'data_size' => $dataSize
            ]);

            return [
                'data' => $data,
                'compressed' => false,
                'original_size' => $dataSize,
                'compression_ratio' => 1.0
            ];
        }
    }

    /**
     * Decompress payload data
     */
    public function decompress(array $compressedPayload): array
    {
        if (!isset($compressedPayload['compressed']) || !$compressedPayload['compressed']) {
            return $compressedPayload['data'];
        }

        try {
            $compressedData = base64_decode($compressedPayload['data']);
            $decompressed = gzuncompress($compressedData);

            if ($decompressed === false) {
                throw new Exception('Failed to decompress data');
            }

            $originalData = json_decode($decompressed, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to decode decompressed JSON: ' . json_last_error_msg());
            }

            Log::debug('Payload decompressed', [
                'compressed_size' => strlen($compressedData),
                'decompressed_size' => strlen($decompressed)
            ]);

            return $originalData;
        } catch (Exception $e) {
            Log::error('Failed to decompress payload', [
                'error' => $e->getMessage(),
                'compressed_size' => isset($compressedPayload['compressed_size']) ? $compressedPayload['compressed_size'] : 'unknown'
            ]);

            // Return original data if decompression fails
            return $compressedPayload['data'];
        }
    }

    /**
     * Check if payload should be compressed based on size
     */
    public function shouldCompress(array $data): bool
    {
        $jsonData = json_encode($data);
        return strlen($jsonData) >= self::COMPRESSION_THRESHOLD;
    }

    /**
     * Get compression statistics
     */
    public function getCompressionStats(): array
    {
        return [
            'compression_threshold' => self::COMPRESSION_THRESHOLD,
            'compression_level' => self::COMPRESSION_LEVEL,
            'algorithm' => 'gzip',
            'enabled' => true
        ];
    }

    /**
     * Compress appointment data specifically
     */
    public function compressAppointmentData(array $appointmentData): array
    {
        // Remove unnecessary fields for real-time updates to reduce payload size
        $compressedData = $this->stripUnnecessaryFields($appointmentData);

        return $this->compress($compressedData);
    }

    /**
     * Strip unnecessary fields from appointment data for real-time broadcasts
     */
    protected function stripUnnecessaryFields(array $data): array
    {
        $fieldsToKeep = [
            'id',
            'appointment_number',
            'appointment_date',
            'status',
            'appointment_type',
            'doctor_id',
            'doctor_name',
            'patient_id',
            'patient_name',
            'duration',
            'reason',
            'notes',
            'updated_at',
            'created_at'
        ];

        $stripped = [];
        foreach ($fieldsToKeep as $field) {
            if (isset($data[$field])) {
                $stripped[$field] = $data[$field];
            }
        }

        // Handle nested objects
        if (isset($data['doctor']) && is_array($data['doctor'])) {
            $stripped['doctor'] = [
                'id' => $data['doctor']['id'] ?? null,
                'name' => $data['doctor']['name'] ?? ($data['doctor']['user']['name'] ?? 'Unknown Doctor')
            ];
        }

        if (isset($data['patient']) && is_array($data['patient'])) {
            $stripped['patient'] = [
                'id' => $data['patient']['id'] ?? null,
                'name' => $data['patient']['name'] ?? ($data['patient']['guest_name'] ?? 'Guest Patient')
            ];
        }

        return $stripped;
    }
}
