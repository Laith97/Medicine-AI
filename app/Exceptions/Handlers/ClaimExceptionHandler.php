<?php

namespace App\Exceptions\Handlers;

use App\Exceptions\ClaimException;
use App\Exceptions\ClaimValidationException;
use App\Exceptions\ClaimSecurityException;
use App\Exceptions\ClaimProcessingException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ClaimExceptionHandler
{
    /**
     * Handle claim-specific exceptions
     */
    public static function handle(\Throwable $exception, Request $request): ?JsonResponse
    {
        if ($exception instanceof ClaimValidationException) {
            return self::handleValidationException($exception, $request);
        }

        if ($exception instanceof ClaimSecurityException) {
            return self::handleSecurityException($exception, $request);
        }

        if ($exception instanceof ClaimProcessingException) {
            return self::handleProcessingException($exception, $request);
        }

        if ($exception instanceof ClaimException) {
            return self::handleGenericClaimException($exception, $request);
        }

        return null; // Not a claim exception, let Laravel handle it
    }

    /**
     * Handle claim validation exceptions
     */
    protected static function handleValidationException(ClaimValidationException $exception, Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => [
                'type' => 'validation_error',
                'message' => $exception->getUserFriendlyMessage(),
                'code' => $exception->getCode(),
                'validation_errors' => $exception->getValidationErrors(),
                'timestamp' => now()->toISOString(),
            ]
        ];

        if ($request->expectsJson()) {
            $response['error']['request_id'] = $request->header('X-Request-ID');
        }

        return response()->json($response, $exception->getHttpStatusCode());
    }

    /**
     * Handle claim security exceptions
     */
    protected static function handleSecurityException(ClaimSecurityException $exception, Request $request): JsonResponse
    {
        // Log security incidents with additional detail
        Log::warning('Claim Security Incident', [
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'claim_id' => $exception->getClaimId(),
            'patient_id' => $exception->getPatientId(),
            'hospital_id' => $exception->getHospitalId(),
            'incident_type' => 'unauthorized_claim_access',
            'hipaa_compliance' => [
                'data_classification' => 'protected_health_information',
                'access_attempt' => 'denied',
                'retention_period' => '7_years',
            ]
        ]);

        $response = [
            'success' => false,
            'error' => [
                'type' => 'security_error',
                'message' => $exception->getUserFriendlyMessage(),
                'code' => $exception->getCode(),
                'timestamp' => now()->toISOString(),
            ]
        ];

        return response()->json($response, $exception->getHttpStatusCode());
    }

    /**
     * Handle claim processing exceptions
     */
    protected static function handleProcessingException(ClaimProcessingException $exception, Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => [
                'type' => 'processing_error',
                'message' => $exception->getUserFriendlyMessage(),
                'code' => $exception->getCode(),
                'processing_stage' => $exception->getProcessingStage(),
                'timestamp' => now()->toISOString(),
            ]
        ];

        if ($request->expectsJson()) {
            $response['error']['request_id'] = $request->header('X-Request-ID');
            if (config('app.debug') && $exception->getClaimId()) {
                $response['error']['claim_id'] = $exception->getClaimId();
            }
        }

        return response()->json($response, $exception->getHttpStatusCode());
    }

    /**
     * Handle generic claim exceptions
     */
    protected static function handleGenericClaimException(ClaimException $exception, Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => [
                'type' => 'claim_error',
                'message' => $exception->getUserFriendlyMessage(),
                'code' => $exception->getCode(),
                'timestamp' => now()->toISOString(),
            ]
        ];

        if ($request->expectsJson()) {
            $response['error']['request_id'] = $request->header('X-Request-ID');
            if (config('app.debug') && $exception->getClaimId()) {
                $response['error']['claim_id'] = $exception->getClaimId();
            }
        }

        return response()->json($response, $exception->getHttpStatusCode());
    }

    /**
     * Get HIPAA-compliant error message for external display
     */
    public static function getHipaaSafeErrorMessage(\Throwable $exception): string
    {
        if ($exception instanceof ClaimException) {
            return $exception->getUserFriendlyMessage();
        }

        // Generic HIPAA-safe message for non-claim exceptions
        return 'A system error occurred while processing your request. Please try again or contact support if the issue persists.';
    }

    /**
     * Determine if an exception should trigger HIPAA breach notification
     */
    public static function requiresHipaaNotification(\Throwable $exception): bool
    {
        if (!$exception instanceof ClaimException) {
            return false;
        }

        // Security exceptions always require notification
        if ($exception instanceof ClaimSecurityException) {
            return true;
        }

        // Processing exceptions with sensitive data
        if ($exception instanceof ClaimProcessingException) {
            $context = $exception->getContext();
            return isset($context['contains_phi']) && $context['contains_phi'];
        }

        return false;
    }
}
