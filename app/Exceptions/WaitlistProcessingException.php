<?php

namespace App\Exceptions;

class WaitlistProcessingException extends WaitlistException
{
    public function __construct(string $message = 'Waitlist processing failed', array $context = [])
    {
        parent::__construct($message, [], $context, 500);
    }
}
