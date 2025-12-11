<?php

namespace App\Exceptions;

class KioskPaymentException extends KioskException
{
    public function __construct(
        string $message = 'Kiosk payment processing error',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            'KIOSK_PAYMENT_ERROR',
            402,
            $context,
            $previous
        );
    }
}
