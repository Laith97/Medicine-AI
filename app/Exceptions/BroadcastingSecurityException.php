<?php

namespace App\Exceptions;

/**
 * Exception thrown when broadcasting security violations occur
 *
 * Handles unauthorized channel access, invalid payload signatures,
 * and other security-related broadcasting failures.
 */
class BroadcastingSecurityException extends BroadcastingException
{
    protected $violationType;
    protected $securityContext;

    public function __construct(
        string $message = "Broadcasting security violation",
        string $violationType = 'unauthorized_access',
        array $securityContext = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, array_merge($securityContext, [
            'violation_type' => $violationType
        ]), $code, $previous);

        $this->violationType = $violationType;
        $this->securityContext = $securityContext;
    }

    /**
     * Get the type of security violation
     */
    public function getViolationType(): string
    {
        return $this->violationType;
    }

    /**
     * Get security context
     */
    public function getSecurityContext(): array
    {
        return $this->securityContext;
    }

    /**
     * Create exception for unauthorized channel access
     */
    public static function unauthorizedChannel(string $channel, ?int $userId = null): self
    {
        $exception = new self(
            "Unauthorized access to broadcasting channel: {$channel}",
            'unauthorized_channel_access',
            ['channel' => $channel, 'user_id' => $userId]
        );

        return $exception->setChannel($channel)->setUserId($userId);
    }

    /**
     * Create exception for invalid payload signature
     */
    public static function invalidPayloadSignature(string $expected, string $received): self
    {
        return new self(
            "Invalid payload signature for broadcasting",
            'invalid_payload_signature',
            [
                'expected_signature' => $expected,
                'received_signature' => $received
            ]
        );
    }

    /**
     * Create exception for payload tampering
     */
    public static function payloadTampering(string $channel, string $event): self
    {
        $exception = new self(
            "Broadcasting payload tampering detected",
            'payload_tampering',
            ['channel' => $channel, 'event' => $event]
        );

        return $exception->setChannel($channel)->setEvent($event);
    }

    /**
     * Create exception for rate limit bypass attempt
     */
    public static function rateLimitBypass(string $channel, int $attemptedRequests): self
    {
        $exception = new self(
            "Rate limit bypass attempt detected",
            'rate_limit_bypass',
            ['channel' => $channel, 'attempted_requests' => $attemptedRequests]
        );

        return $exception->setChannel($channel);
    }
}
