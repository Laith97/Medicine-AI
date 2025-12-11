<?php

namespace App\Exceptions;

class WaitlistValidationException extends WaitlistException
{
    public function __construct(string $message = 'Waitlist validation failed', array $errors = [], array $context = [])
    {
        parent::__construct($message, $errors, $context, 422);
    }
}
