<?php

namespace App\Exceptions;

/**
 * Exception thrown when broadcasting connection issues occur
 *
 * Handles Pusher connection failures, pool exhaustion, timeouts,
 * and other connection-related broadcasting problems.
 */
class BroadcastingConnectionException extends BroadcastingException
{
    protected $connectionType;
    protected $poolStats;
    protected $retryable = true;

    public function __construct(
        string $message = "Broadcasting connection error",
        string $connectionType = 'pusher',
        array $poolStats = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, array_merge($poolStats, [
            'connection_type' => $connectionType
        ]), $code, $previous);

        $this->connectionType = $connectionType;
        $this->poolStats = $poolStats;
        $this->setRetryable(true, 5); // Default 5 second retry
    }

    /**
     * Get connection type
     */
    public function getConnectionType(): string
    {
        return $this->connectionType;
    }

    /**
     * Get connection pool statistics
     */
    public function getPoolStats(): array
    {
        return $this->poolStats;
    }

    /**
     * Create exception for connection pool exhaustion
     */
    public static function poolExhausted(array $poolStats = []): self
    {
        return new self(
            "Broadcasting connection pool exhausted",
            'connection_pool',
            array_merge($poolStats, ['error_type' => 'pool_exhausted'])
        );
    }

    /**
     * Create exception for connection timeout
     */
    public static function connectionTimeout(int $timeoutSeconds, string $operation = 'broadcast'): self
    {
        $exception = new self(
            "Broadcasting connection timeout during {$operation}",
            'timeout',
            [
                'timeout_seconds' => $timeoutSeconds,
                'operation' => $operation,
                'error_type' => 'connection_timeout'
            ]
        );

        return $exception->setRetryable(true, 10); // Longer retry for timeouts
    }

    /**
     * Create exception for Pusher service unavailable
     */
    public static function serviceUnavailable(string $reason = 'unknown'): self
    {
        $exception = new self(
            "Broadcasting service unavailable: {$reason}",
            'pusher_service',
            [
                'reason' => $reason,
                'error_type' => 'service_unavailable'
            ]
        );

        return $exception->setRetryable(true, 30); // Longer retry for service issues
    }

    /**
     * Create exception for authentication failure
     */
    public static function authenticationFailed(string $reason = 'invalid_credentials'): self
    {
        return new self(
            "Broadcasting authentication failed: {$reason}",
            'authentication',
            [
                'reason' => $reason,
                'error_type' => 'authentication_failed'
            ]
        );
    }

    /**
     * Create exception for connection limit exceeded
     */
    public static function connectionLimitExceeded(int $currentConnections, int $maxConnections): self
    {
        return new self(
            "Broadcasting connection limit exceeded",
            'connection_limit',
            [
                'current_connections' => $currentConnections,
                'max_connections' => $maxConnections,
                'error_type' => 'connection_limit_exceeded'
            ]
        );
    }

    /**
     * Create exception for network connectivity issues
     */
    public static function networkError(string $details = ''): self
    {
        $exception = new self(
            "Broadcasting network connectivity error" . ($details ? ": {$details}" : ""),
            'network',
            [
                'details' => $details,
                'error_type' => 'network_error'
            ]
        );

        return $exception->setRetryable(true, 15);
    }
}
