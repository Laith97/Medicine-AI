<?php

namespace App\Services\Rules\Actions;

class WarningAction
{
    /**
     * Build warning action with message.
     *
     * @param string $message
     * @param string $severity
     * @return array
     */
    public static function create(string $message, string $severity = 'medium'): array
    {
        return [
            'type' => 'warning',
            'message' => $message,
            'severity' => $severity,
        ];
    }

    /**
     * Build warning action for high severity.
     *
     * @param string $message
     * @return array
     */
    public static function high(string $message): array
    {
        return self::create($message, 'high');
    }

    /**
     * Build warning action for medium severity.
     *
     * @param string $message
     * @return array
     */
    public static function medium(string $message): array
    {
        return self::create($message, 'medium');
    }

    /**
     * Build warning action for low severity.
     *
     * @param string $message
     * @return array
     */
    public static function low(string $message): array
    {
        return self::create($message, 'low');
    }

    /**
     * Build warning action for missing documentation.
     *
     * @return array
     */
    public static function missingDocumentation(): array
    {
        return self::create(
            'Claim is missing required documentation. Please ensure all supporting documents are attached.',
            'high'
        );
    }

    /**
     * Build warning action for coding error.
     *
     * @param string $details
     * @return array
     */
    public static function codingError(string $details = ''): array
    {
        $message = 'Potential coding error detected in claim.';
        if ($details) {
            $message .= " Details: {$details}";
        }

        return self::create($message, 'high');
    }

    /**
     * Build warning action for medical necessity concern.
     *
     * @return array
     */
    public static function medicalNecessity(): array
    {
        return self::create(
            'Medical necessity may not be clearly established. Please review clinical documentation.',
            'medium'
        );
    }
}
