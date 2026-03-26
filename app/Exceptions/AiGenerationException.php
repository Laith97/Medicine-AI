<?php

namespace App\Exceptions;

use Exception;

class AiGenerationException extends Exception
{
    /**
     * Create a new AI generation exception.
     */
    public function __construct(string $message = 'AI generation failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
