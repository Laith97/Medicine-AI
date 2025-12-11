<?php

namespace App\Exceptions;

/**
 * Exception thrown when broadcasting payload validation fails
 *
 * Handles invalid appointment data, malformed payloads, missing required fields,
 * and other validation issues in real-time broadcasting operations.
 */
class BroadcastingValidationException extends BroadcastingException
{
    protected $validationErrors;
    protected $payloadData;
    protected $validationRules;

    public function __construct(
        string $message = "Broadcasting payload validation failed",
        array $validationErrors = [],
        array $payloadData = [],
        array $validationRules = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, [
            'validation_errors' => $validationErrors,
            'payload_size' => strlen(json_encode($payloadData)),
            'validation_rules' => $validationRules
        ], $code, $previous);

        $this->validationErrors = $validationErrors;
        $this->payloadData = $payloadData;
        $this->validationRules = $validationRules;
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Get payload data that failed validation
     */
    public function getPayloadData(): array
    {
        return $this->payloadData;
    }

    /**
     * Get validation rules that were applied
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Check if specific field has validation errors
     */
    public function hasError(string $field): bool
    {
        return isset($this->validationErrors[$field]);
    }

    /**
     * Get validation errors for specific field
     */
    public function getFieldErrors(string $field): array
    {
        return $this->validationErrors[$field] ?? [];
    }

    /**
     * Create exception for invalid appointment data
     */
    public static function invalidAppointmentData(array $appointmentData, array $validationErrors): self
    {
        return new self(
            "Invalid appointment data for broadcasting",
            $validationErrors,
            $appointmentData,
            [
                'id' => 'required|integer',
                'appointment_number' => 'required|string',
                'appointment_date' => 'required|date',
                'status' => 'required|string|in:pending,confirmed,completed,cancelled,no_show',
                'appointment_type' => 'nullable|string',
                'doctor_id' => 'nullable|integer',
                'patient_id' => 'nullable|integer',
                'duration' => 'nullable|integer|min:1|max:480',
                'reason' => 'nullable|string|max:1000'
            ]
        );
    }

    /**
     * Create exception for missing required fields
     */
    public static function missingRequiredFields(array $missingFields, array $payloadData): self
    {
        return new self(
            "Missing required fields for broadcasting: " . implode(', ', $missingFields),
            array_fill_keys($missingFields, ['Field is required']),
            $payloadData,
            array_fill_keys($missingFields, 'required')
        );
    }

    /**
     * Create exception for invalid status transition
     */
    public static function invalidStatusTransition(string $currentStatus, string $newStatus, array $allowedTransitions): self
    {
        return new self(
            "Invalid appointment status transition from '{$currentStatus}' to '{$newStatus}'",
            [
                'status' => ["Invalid transition. Allowed transitions from {$currentStatus}: " . implode(', ', $allowedTransitions)]
            ],
            ['current_status' => $currentStatus, 'new_status' => $newStatus],
            [
                'status' => 'valid_transition',
                'allowed_transitions' => $allowedTransitions
            ]
        );
    }

    /**
     * Create exception for oversized payload
     */
    public static function payloadTooLarge(int $actualSize, int $maxSize): self
    {
        return new self(
            "Broadcasting payload too large: {$actualSize} bytes (max: {$maxSize} bytes)",
            [
                'payload' => ["Payload size {$actualSize} exceeds maximum {$maxSize} bytes"]
            ],
            ['size' => $actualSize],
            ['payload' => "max:{$maxSize}"]
        );
    }

    /**
     * Create exception for malformed JSON
     */
    public static function malformedJson(string $jsonError): self
    {
        return new self(
            "Malformed JSON in broadcasting payload: {$jsonError}",
            [
                'payload' => ["Invalid JSON: {$jsonError}"]
            ],
            [],
            ['payload' => 'valid_json']
        );
    }

    /**
     * Create exception for invalid event data
     */
    public static function invalidEventData(string $event, array $eventData, array $validationErrors): self
    {
        $exception = new self(
            "Invalid data for broadcasting event '{$event}'",
            $validationErrors,
            $eventData,
            []
        );

        return $exception->setEvent($event);
    }
}
