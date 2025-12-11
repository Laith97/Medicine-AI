<?php

namespace App\Exceptions;

class KioskSecurityException extends KioskException
{
    public function __construct(
        string $message = 'Kiosk security violation',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            'KIOSK_SECURITY_ERROR',
            403,
            $context,
            $previous
        );
    }
}
