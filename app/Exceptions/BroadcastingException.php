<?php

namespace App\Exceptions;

use Exception;

/**
 * Base exception for real-time broadcasting operations
 *
 * Provides standardized error handling for appointment broadcasting,
 * connection pooling, rate limiting, and payload validation failures.
 */
class BroadcastingException extends Exception
{
    protected $broadcastingContext;
    protected $userId;
    protected $channel;
    protected $event;
    protected $shouldRetry = false;
    protected $retryAfter = null;

    public function __construct(
        string $message = "",
        array $context = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->broadcastingContext = $context;
    }

    /**
     * Set the user ID associated with this broadcasting error
     */
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Set the channel where the error occurred
     */
    public function setChannel(string $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * Set the event that caused the error
     */
    public function setEvent(string $event): self
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Mark this error as retryable
     */
    public function setRetryable(bool $retryable = true, ?int $retryAfter = null): self
    {
        $this->shouldRetry = $retryable;
        $this->retryAfter = $retryAfter;
        return $this;
    }

    /**
     * Get broadcasting context
     */
    public function getBroadcastingContext(): array
    {
        return $this->broadcastingContext ?? [];
    }

    /**
     * Get user ID
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Get channel
     */
    public function getChannel(): ?string
    {
        return $this->channel;
    }

    /**
     * Get event
     */
    public function getEvent(): ?string
    {
        return $this->event;
    }

    /**
     * Check if error is retryable
     */
    public function isRetryable(): bool
    {
        return $this->shouldRetry;
    }

    /**
     * Get retry delay in seconds
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Get full error context for logging
     */
    public function getFullContext(): array
    {
        return array_merge($this->broadcastingContext ?? [], [
            'user_id' => $this->userId,
            'channel' => $this->channel,
            'event' => $this->event,
            'retryable' => $this->shouldRetry,
            'retry_after' => $this->retryAfter,
            'exception_class' => get_class($this),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
