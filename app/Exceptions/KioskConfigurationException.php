<?php

namespace App\Exceptions;

class KioskConfigurationException extends KioskException
{
    public function __construct(
        string $message = 'Kiosk configuration error',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            'KIOSK_CONFIG_ERROR',
            400,
            $context,
            $previous
        );
    }
}
