<?php

namespace App\Exceptions;

class WaitlistSecurityException extends WaitlistException
{
    public function __construct(string $message = 'Waitlist security violation', array $context = [])
    {
        parent::__construct($message, [], $context, 403);
    }
}
