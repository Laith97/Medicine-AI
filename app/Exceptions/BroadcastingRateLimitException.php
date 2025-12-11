<?php

namespace App\Exceptions;

/**
 * Exception thrown when broadcasting rate limits are exceeded
 *
 * Handles rate limiting for appointment broadcasts, channel subscriptions,
 * and other real-time operations to prevent system abuse.
 */
class BroadcastingRateLimitException extends BroadcastingException
{
    protected $limitType;
    protected $currentAttempts;
    protected $maxAttempts;
    protected $retryAfter;
    protected $retryable = true;

    public function __construct(
        string $message = "Broadcasting rate limit exceeded",
        string $limitType = 'general',
        int $currentAttempts = 0,
        int $maxAttempts = 0,
        int $retryAfter = 60,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, [
            'limit_type' => $limitType,
            'current_attempts' => $currentAttempts,
            'max_attempts' => $maxAttempts,
            'retry_after' => $retryAfter
        ], $code, $previous);

        $this->limitType = $limitType;
        $this->currentAttempts = $currentAttempts;
        $this->maxAttempts = $maxAttempts;
        $this->retryAfter = $retryAfter;
        $this->setRetryable(true, $retryAfter);
    }

    /**
     * Get the type of rate limit that was exceeded
     */
    public function getLimitType(): string
    {
        return $this->limitType;
    }

    /**
     * Get current number of attempts
     */
    public function getCurrentAttempts(): int
    {
        return $this->currentAttempts;
    }

    /**
     * Get maximum allowed attempts
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Get retry delay in seconds
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Create exception for status change rate limiting
     */
    public static function statusChangeLimitExceeded(int $currentAttempts, int $maxAttempts, int $retryAfter = 60): self
    {
        return new self(
            "Appointment status change rate limit exceeded",
            'status_change',
            $currentAttempts,
            $maxAttempts,
            $retryAfter
        );
    }

    /**
     * Create exception for broadcast burst rate limiting
     */
    public static function burstLimitExceeded(int $currentAttempts, int $maxAttempts, int $retryAfter = 10): self
    {
        return new self(
            "Broadcasting burst rate limit exceeded",
            'burst',
            $currentAttempts,
            $maxAttempts,
            $retryAfter
        );
    }

    /**
     * Create exception for channel subscription rate limiting
     */
    public static function subscriptionLimitExceeded(int $currentAttempts, int $maxAttempts, int $retryAfter = 30): self
    {
        return new self(
            "Channel subscription rate limit exceeded",
            'subscription',
            $currentAttempts,
            $maxAttempts,
            $retryAfter
        );
    }

    /**
     * Create exception for list update rate limiting
     */
    public static function listUpdateLimitExceeded(int $currentAttempts, int $maxAttempts, int $retryAfter = 120): self
    {
        return new self(
            "Appointment list update rate limit exceeded",
            'list_update',
            $currentAttempts,
            $maxAttempts,
            $retryAfter
        );
    }

    /**
     * Create exception for user-specific rate limiting
     */
    public static function userLimitExceeded(int $userId, string $operation, int $currentAttempts, int $maxAttempts, int $retryAfter = 60): self
    {
        $exception = new self(
            "User {$userId} rate limit exceeded for {$operation}",
            'user_specific',
            $currentAttempts,
            $maxAttempts,
            $retryAfter
        );

        return $exception->setUserId($userId);
    }

    /**
     * Create exception for channel-specific rate limiting
     */
    public static function channelLimitExceeded(string $channel, int $currentAttempts, int $maxAttempts, int $retryAfter = 30): self
    {
        $exception = new self(
            "Channel {$channel} rate limit exceeded",
            'channel_specific',
            $currentAttempts,
            $maxAttempts,
            $retryAfter
        );

        return $exception->setChannel($channel);
    }
}
