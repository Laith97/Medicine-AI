<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ClaimException extends Exception
{
    protected $claimId;
    protected $patientId;
    protected $hospitalId;
    protected $userId;
    protected $context;
    protected $shouldLog;
    protected $logLevel;
    protected $isHipaaSensitive;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = [],
        bool $shouldLog = true,
        string $logLevel = 'error',
        bool $isHipaaSensitive = true
    ) {
        parent::__construct($message, $code, $previous);

        $this->context = $context;
        $this->shouldLog = $shouldLog;
        $this->logLevel = $logLevel;
        $this->isHipaaSensitive = $isHipaaSensitive;

        // Extract common context
        $this->claimId = $context['claim_id'] ?? null;
        $this->patientId = $context['patient_id'] ?? null;
        $this->hospitalId = $context['hospital_id'] ?? null;
        $this->userId = $context['user_id'] ?? null;

        if ($this->shouldLog) {
            $this->logException();
        }
    }

    protected function logException(): void
    {
        $logData = [
            'exception' => get_class($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->sanitizeContextForLogging($this->context),
        ];

        // Add HIPAA compliance metadata
        if ($this->isHipaaSensitive) {
            $logData['hipaa_compliance'] = [
                'data_classification' => 'protected_health_information',
                'access_type' => 'system_error',
                'retention_period' => '7_years',
                'encryption_required' => true,
            ];
        }

        Log::{$this->logLevel}('Claim Exception: ' . $this->getMessage(), $logData);
    }

    protected function sanitizeContextForLogging(array $context): array
    {
        $sensitiveFields = [
            'patient_ssn', 'patient_dob', 'insurance_id', 'policy_number',
            'credit_card', 'bank_account', 'medical_record_number'
        ];

        $sanitized = $context;

        foreach ($sensitiveFields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '[REDACTED]';
            }
        }

        return $sanitized;
    }

    public function getClaimId(): ?int
    {
        return $this->claimId;
    }

    public function getPatientId(): ?int
    {
        return $this->patientId;
    }

    public function getHospitalId(): ?int
    {
        return $this->hospitalId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render(Request $request): JsonResponse
    {
        $statusCode = $this->getHttpStatusCode();
        $errorResponse = [
            'success' => false,
            'error' => [
                'type' => class_basename($this),
                'message' => $this->getUserFriendlyMessage(),
                'code' => $this->getCode(),
            ]
        ];

        // Add additional context for API requests
        if ($request->expectsJson()) {
            $errorResponse['error']['timestamp'] = now()->toISOString();
            $errorResponse['error']['request_id'] = $request->header('X-Request-ID');

            // Include claim ID if available (for debugging)
            if ($this->claimId && config('app.debug')) {
                $errorResponse['error']['claim_id'] = $this->claimId;
            }
        }

        return response()->json($errorResponse, $statusCode);
    }

    public function getHttpStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getUserFriendlyMessage(): string
    {
        return $this->getMessage();
    }
}
