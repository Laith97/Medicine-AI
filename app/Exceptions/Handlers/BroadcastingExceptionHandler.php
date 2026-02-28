<?php

namespace App\Exceptions\Handlers;

use App\Exceptions\BroadcastingException;
use App\Exceptions\BroadcastingSecurityException;
use App\Exceptions\BroadcastingConnectionException;
use App\Exceptions\BroadcastingRateLimitException;
use App\Exceptions\BroadcastingValidationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Exception handler for broadcasting-related exceptions
 *
 * Provides standardized error responses and logging for real-time broadcasting operations.
 */
class BroadcastingExceptionHandler
{
    /**
     * Handle broadcasting exceptions
     */
    public function handle(BroadcastingException $exception, Request $request): JsonResponse
    {
        // Log the exception with full context
        $this->logBroadcastingException($exception);

        // Record error metrics
        $this->recordErrorMetrics($exception);

        // Return appropriate JSON response
        return $this->renderBroadcastingError($exception, $request);
    }

    /**
     * Log broadcasting exception with structured data
     */
    protected function logBroadcastingException(BroadcastingException $exception): void
    {
        $context = $exception->getFullContext();

        // Determine log level based on exception type
        $logLevel = $this->getLogLevelForException($exception);

        Log::{$logLevel}('Broadcasting exception occurred', [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'context' => $context,
            'user_id' => $context['user_id'] ?? null,
            'channel' => $context['channel'] ?? null,
            'event' => $context['event'] ?? null,
            'retryable' => $context['retryable'] ?? false,
            'retry_after' => $context['retry_after'] ?? null,
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Get appropriate log level for exception type
     */
    protected function getLogLevelForException(BroadcastingException $exception): string
    {
        $exceptionClass = get_class($exception);

        switch ($exceptionClass) {
            case BroadcastingSecurityException::class:
                return 'warning';
            case BroadcastingRateLimitException::class:
                return 'info';
            case BroadcastingConnectionException::class:
                return 'error';
            case BroadcastingValidationException::class:
                return 'warning';
            default:
                return 'error';
        }
    }

    /**
     * Record error metrics for monitoring
     */
    protected function recordErrorMetrics(BroadcastingException $exception): void
    {
        $metricsKey = 'broadcasting:errors:' . date('Y-m-d-H');
        $metrics = Cache::get($metricsKey, [
            'total' => 0,
            'by_type' => [],
            'by_channel' => [],
            'by_user' => []
        ]);

        $metrics['total']++;

        $exceptionType = get_class($exception);
        $metrics['by_type'][$exceptionType] = ($metrics['by_type'][$exceptionType] ?? 0) + 1;

        if ($exception->getChannel()) {
            $channel = $exception->getChannel();
            $metrics['by_channel'][$channel] = ($metrics['by_channel'][$channel] ?? 0) + 1;
        }

        if ($exception->getUserId()) {
            $userId = $exception->getUserId();
            $metrics['by_user'][$userId] = ($metrics['by_user'][$userId] ?? 0) + 1;
        }

        Cache::put($metricsKey, $metrics, 3600); // Keep for 1 hour
    }

    /**
     * Render appropriate JSON response for broadcasting errors
     */
    protected function renderBroadcastingError(BroadcastingException $exception, Request $request): JsonResponse
    {
        $statusCode = $this->getHttpStatusCodeForException($exception);
        $errorResponse = $this->buildErrorResponse($exception);

        return response()->json($errorResponse, $statusCode);
    }

    /**
     * Get HTTP status code for exception type
     */
    protected function getHttpStatusCodeForException(BroadcastingException $exception): int
    {
        $exceptionClass = get_class($exception);

        switch ($exceptionClass) {
            case BroadcastingSecurityException::class:
                return 403; // Forbidden
            case BroadcastingRateLimitException::class:
                return 429; // Too Many Requests
            case BroadcastingValidationException::class:
                return 422; // Unprocessable Entity
            case BroadcastingConnectionException::class:
                return 503; // Service Unavailable
            default:
                return 500; // Internal Server Error
        }
    }

    /**
     * Build standardized error response
     */
    protected function buildErrorResponse(BroadcastingException $exception): array
    {
        $response = [
            'error' => [
                'type' => $this->getErrorTypeForException($exception),
                'message' => $this->getUserFriendlyMessage($exception),
                'code' => $exception->getCode() ?: $this->getDefaultErrorCode($exception),
                'timestamp' => now()->toISOString()
            ]
        ];

        // Add retry information if applicable
        if ($exception->isRetryable()) {
            $response['error']['retryable'] = true;
            $response['error']['retry_after'] = $exception->getRetryAfter();
        }

        // Add validation errors for validation exceptions
        if ($exception instanceof BroadcastingValidationException) {
            $response['error']['validation_errors'] = $exception->getValidationErrors();
        }

        // Add rate limit information
        if ($exception instanceof BroadcastingRateLimitException) {
            $response['error']['rate_limit'] = [
                'limit_type' => $exception->getLimitType(),
                'current_attempts' => $exception->getCurrentAttempts(),
                'max_attempts' => $exception->getMaxAttempts(),
                'retry_after' => $exception->getRetryAfter()
            ];
        }

        // Add security context for security exceptions (without sensitive data)
        if ($exception instanceof BroadcastingSecurityException) {
            $response['error']['security'] = [
                'violation_type' => $exception->getViolationType(),
                'channel' => $exception->getChannel()
            ];
        }

        // Add connection info for connection exceptions
        if ($exception instanceof BroadcastingConnectionException) {
            $response['error']['connection'] = [
                'type' => $exception->getConnectionType(),
                'pool_stats' => $exception->getPoolStats()
            ];
        }

        return $response;
    }

    /**
     * Get error type identifier
     */
    protected function getErrorTypeForException(BroadcastingException $exception): string
    {
        $exceptionClass = get_class($exception);

        switch ($exceptionClass) {
            case BroadcastingSecurityException::class:
                return 'broadcasting_security_error';
            case BroadcastingRateLimitException::class:
                return 'broadcasting_rate_limit_error';
            case BroadcastingValidationException::class:
                return 'broadcasting_validation_error';
            case BroadcastingConnectionException::class:
                return 'broadcasting_connection_error';
            default:
                return 'broadcasting_error';
        }
    }

    /**
     * Get user-friendly error message
     */
    protected function getUserFriendlyMessage(BroadcastingException $exception): string
    {
        // Return user-friendly messages instead of technical error details
        $exceptionClass = get_class($exception);

        switch ($exceptionClass) {
            case BroadcastingSecurityException::class:
                return 'Access denied to real-time updates';
            case BroadcastingRateLimitException::class:
                return 'Too many requests. Please wait before trying again.';
            case BroadcastingValidationException::class:
                return 'Invalid data provided for real-time update';
            case BroadcastingConnectionException::class:
                return 'Real-time service temporarily unavailable';
            default:
                return 'An error occurred with real-time updates';
        }
    }

    /**
     * Get default error code for exception type
     */
    protected function getDefaultErrorCode(BroadcastingException $exception): string
    {
        $exceptionClass = get_class($exception);

        switch ($exceptionClass) {
            case BroadcastingSecurityException::class:
                return 'BROADCASTING_SECURITY_ERROR';
            case BroadcastingRateLimitException::class:
                return 'BROADCASTING_RATE_LIMIT_ERROR';
            case BroadcastingValidationException::class:
                return 'BROADCASTING_VALIDATION_ERROR';
            case BroadcastingConnectionException::class:
                return 'BROADCASTING_CONNECTION_ERROR';
            default:
                return 'BROADCASTING_ERROR';
        }
    }

    /**
     * Handle specific exception types with custom logic
     */
    public function handleSecurityException(BroadcastingSecurityException $exception, Request $request): JsonResponse
    {
        // Additional security logging
        Log::warning('Broadcasting security violation', [
            'violation_type' => $exception->getViolationType(),
            'user_id' => $exception->getUserId(),
            'channel' => $exception->getChannel(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return $this->handle($exception, $request);
    }

    /**
     * Handle rate limit exceptions with additional headers
     */
    public function handleRateLimitException(BroadcastingRateLimitException $exception, Request $request): JsonResponse
    {
        $response = $this->handle($exception, $request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $exception->getMaxAttempts());
        $response->headers->set('X-RateLimit-Remaining', max(0, $exception->getMaxAttempts() - $exception->getCurrentAttempts()));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($exception->getRetryAfter())->timestamp);
        $response->headers->set('Retry-After', $exception->getRetryAfter());

        return $response;
    }
}
