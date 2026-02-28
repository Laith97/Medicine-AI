<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Exceptions\BroadcastingSecurityException;
use App\Exceptions\BroadcastingValidationException;
use App\Services\AuditLoggingService;

/**
 * Middleware for securing broadcasting operations
 *
 * Validates channel authorization, payload integrity, and enforces
 * security policies for real-time broadcasting operations.
 */
class BroadcastingSecurityMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Validate broadcasting request
            $this->validateBroadcastingRequest($request);

            // Validate payload if present
            if ($request->has('data') || $request->has('payload')) {
                $this->validateBroadcastingPayload($request);
            }

            // Check rate limits for broadcasting operations
            $this->checkBroadcastingRateLimits($request);

            // Log security check
            $this->logSecurityCheck($request);

            return $next($request);

        } catch (BroadcastingSecurityException | BroadcastingValidationException $e) {
            // Log security violation
            AuditLoggingService::logAppointmentBroadcastSecurity(
                $request->user() ? $request->user()->id : null,
                $e->getViolationType() ?? 'security_violation',
                $request->route('channel') ?? $request->input('channel')
            );

            // Re-throw to be handled by exception handler
            throw $e;
        }
    }

    /**
     * Validate the broadcasting request structure
     */
    protected function validateBroadcastingRequest(Request $request): void
    {
        // Check if this is a broadcasting-related request
        $route = $request->route();
        if (!$route) {
            return; // Not a route-based request
        }

        $routeName = $route->getName();
        if (!$this->isBroadcastingRoute($routeName)) {
            return; // Not a broadcasting route
        }

        // Validate required headers for broadcasting
        $this->validateBroadcastingHeaders($request);

        // Validate user authentication for broadcasting
        $this->validateBroadcastingAuthentication($request);
    }

    /**
     * Check if the route is broadcasting-related
     */
    protected function isBroadcastingRoute(?string $routeName): bool
    {
        if (!$routeName) {
            return false;
        }

        $broadcastingRoutes = [
            'broadcasting.auth',
            'broadcasting.authenticate',
            'api.broadcasting.auth',
            'channels.auth',
            'pusher.auth',
            'socket.io'
        ];

        return in_array($routeName, $broadcastingRoutes) ||
               str_contains($routeName, 'broadcast') ||
               str_contains($routeName, 'channel');
    }

    /**
     * Validate required headers for broadcasting security
     */
    protected function validateBroadcastingHeaders(Request $request): void
    {
        // Check for required security headers
        $requiredHeaders = [
            'X-Requested-With' => 'XMLHttpRequest', // CSRF protection
        ];

        foreach ($requiredHeaders as $header => $expectedValue) {
            $actualValue = $request->header($header);
            if ($actualValue !== $expectedValue) {
                throw BroadcastingSecurityException::payloadTampering(
                    $request->input('channel', 'unknown'),
                    $request->input('event', 'unknown')
                );
            }
        }

        // Validate Origin header for CORS protection
        $origin = $request->header('Origin');
        if ($origin && !$this->isAllowedOrigin($origin)) {
            throw BroadcastingSecurityException::unauthorizedChannel(
                $request->input('channel', 'unknown')
            );
        }

        // Check for suspicious headers that might indicate attacks
        $suspiciousHeaders = [
            'X-Forwarded-Host',
            'X-Forwarded-Scheme',
            'X-Original-URL'
        ];

        foreach ($suspiciousHeaders as $header) {
            if ($request->hasHeader($header)) {
                AuditLoggingService::logAppointmentBroadcastSecurity(
                    $request->user() ? $request->user()->id : null,
                    'suspicious_header_detected',
                    $request->input('channel'),
                    ['header' => $header, 'value' => $request->header($header)]
                );
            }
        }
    }

    /**
     * Validate user authentication for broadcasting
     */
    protected function validateBroadcastingAuthentication(Request $request): void
    {
        $user = $request->user();

        if (!$user) {
            throw BroadcastingSecurityException::unauthorizedChannel(
                $request->input('channel', 'unknown')
            );
        }

        // Additional authentication checks
        if (!$user->hasVerifiedEmail() && config('broadcasting.require_verified_email', false)) {
            throw BroadcastingSecurityException::unauthorizedChannel(
                $request->input('channel', 'unknown'),
                $user->id
            );
        }

        // Check if user account is active
        if (method_exists($user, 'isActive') && !$user->isActive()) {
            throw BroadcastingSecurityException::unauthorizedChannel(
                $request->input('channel', 'unknown'),
                $user->id
            );
        }
    }

    /**
     * Validate broadcasting payload
     */
    protected function validateBroadcastingPayload(Request $request): void
    {
        $payload = $request->input('data') ?? $request->input('payload');

        if (!$payload) {
            return;
        }

        // Validate payload size
        $payloadSize = strlen(json_encode($payload));
        $maxSize = config('broadcasting.max_payload_size', 10240); // 10KB default

        if ($payloadSize > $maxSize) {
            throw BroadcastingValidationException::payloadTooLarge(
                $payloadSize,
                $maxSize
            );
        }

        // Validate payload structure
        $this->validatePayloadStructure($payload, $request);

        // Check for malicious content
        $this->validatePayloadContent($payload, $request);
    }

    /**
     * Validate payload structure
     */
    protected function validatePayloadStructure(array $payload, Request $request): void
    {
        $channel = $request->input('channel', '');
        $event = $request->input('event', '');

        // Channel-specific validation
        if (str_contains($channel, 'appointment')) {
            $this->validateAppointmentPayload($payload, $event);
        } elseif (str_contains($channel, 'user')) {
            $this->validateUserPayload($payload, $event);
        } elseif (str_contains($channel, 'admin')) {
            $this->validateAdminPayload($payload, $event);
        }
    }

    /**
     * Validate appointment-specific payload
     */
    protected function validateAppointmentPayload(array $payload, string $event): void
    {
        $requiredFields = [];

        switch ($event) {
            case 'appointment.status_changed':
                $requiredFields = ['appointment_id', 'old_status', 'new_status', 'changed_by'];
                break;
            case 'appointment.created':
                $requiredFields = ['appointment_id', 'appointment_data'];
                break;
            case 'appointment.updated':
                $requiredFields = ['appointment_id', 'changed_attributes'];
                break;
        }

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw BroadcastingValidationException::missingRequiredFields(
                $missingFields,
                $payload
            );
        }

        // Validate status transitions if applicable
        if (isset($payload['old_status'], $payload['new_status'])) {
            $this->validateStatusTransition($payload['old_status'], $payload['new_status']);
        }
    }

    /**
     * Validate user-specific payload
     */
    protected function validateUserPayload(array $payload, string $event): void
    {
        // User channel validations
        if ($event === 'user.notification') {
            if (!isset($payload['notification_id']) || !isset($payload['type'])) {
                throw BroadcastingValidationException::missingRequiredFields(
                    ['notification_id', 'type'],
                    $payload
                );
            }
        }
    }

    /**
     * Validate admin-specific payload
     */
    protected function validateAdminPayload(array $payload, string $event): void
    {
        // Admin channel validations - more strict
        if (!isset($payload['admin_action']) || !isset($payload['timestamp'])) {
            throw BroadcastingValidationException::missingRequiredFields(
                ['admin_action', 'timestamp'],
                $payload
            );
        }
    }

    /**
     * Validate status transition logic
     */
    protected function validateStatusTransition(string $oldStatus, string $newStatus): void
    {
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled', 'no_show'],
            'confirmed' => ['completed', 'cancelled', 'no_show'],
            'completed' => [], // Terminal state
            'cancelled' => [], // Terminal state
            'no_show' => [], // Terminal state
        ];

        if (!isset($allowedTransitions[$oldStatus]) ||
            !in_array($newStatus, $allowedTransitions[$oldStatus])) {
            throw BroadcastingValidationException::invalidStatusTransition(
                $oldStatus,
                $newStatus,
                $allowedTransitions[$oldStatus] ?? []
            );
        }
    }

    /**
     * Validate payload content for malicious patterns
     */
    protected function validatePayloadContent(array $payload, Request $request): void
    {
        $jsonPayload = json_encode($payload);

        // Check for script injection patterns
        $suspiciousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $jsonPayload)) {
                throw BroadcastingSecurityException::payloadTampering(
                    $request->input('channel', 'unknown'),
                    $request->input('event', 'unknown')
                );
            }
        }

        // Check for extremely nested structures (potential DoS)
        $maxDepth = $this->getArrayDepth($payload);
        if ($maxDepth > 10) {
            throw BroadcastingValidationException::invalidEventData(
                $request->input('event', 'unknown'),
                $payload,
                ['depth' => "Maximum nesting depth exceeded: {$maxDepth}"]
            );
        }
    }

    /**
     * Check broadcasting-specific rate limits
     */
    protected function checkBroadcastingRateLimits(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            return;
        }

        $channel = $request->input('channel', '');
        $event = $request->input('event', '');

        // Different rate limits for different operations
        $rateLimits = [
            'appointment.status_changed' => ['max' => 30, 'decay' => 60], // 30 per minute
            'appointment.created' => ['max' => 20, 'decay' => 60], // 20 per minute
            'appointment.updated' => ['max' => 50, 'decay' => 60], // 50 per minute
            'user.notification' => ['max' => 100, 'decay' => 60], // 100 per minute
        ];

        $key = "broadcasting:{$user->id}:{$event}";
        $limit = $rateLimits[$event] ?? ['max' => 60, 'decay' => 60];

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, $limit['max'])) {
            throw BroadcastingSecurityException::rateLimitBypass(
                $channel,
                \Illuminate\Support\Facades\RateLimiter::attempts($key)
            );
        }

        \Illuminate\Support\Facades\RateLimiter::hit($key, $limit['decay']);
    }

    /**
     * Log security check for auditing
     */
    protected function logSecurityCheck(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            return;
        }

        AuditLoggingService::logAppointmentBroadcastSecurity(
            $user->id,
            'security_check_passed',
            $request->input('channel'),
            [
                'event' => $request->input('event'),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip()
            ]
        );
    }

    /**
     * Check if origin is allowed
     */
    protected function isAllowedOrigin(string $origin): bool
    {
        $allowedOrigins = config('broadcasting.allowed_origins', []);

        // If no specific origins configured, allow all (not recommended for production)
        if (empty($allowedOrigins)) {
            return true;
        }

        return in_array($origin, $allowedOrigins);
    }

    /**
     * Get array nesting depth
     */
    protected function getArrayDepth(array $array): int
    {
        $maxDepth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->getArrayDepth($value) + 1;
                $maxDepth = max($maxDepth, $depth);
            }
        }

        return $maxDepth;
    }
}
