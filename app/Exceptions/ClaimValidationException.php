<?php

namespace App\Exceptions;

use Illuminate\Support\Collection;

class ClaimValidationException extends ClaimException
{
    protected $validationErrors;

    public function __construct(
        array $validationErrors,
        string $message = 'Claim validation failed',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        $this->validationErrors = $validationErrors;

        $context['validation_errors'] = $validationErrors;
        $context['error_count'] = count($validationErrors);

        parent::__construct($message, 1001, $previous, $context, true, 'warning', true);
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getUserFriendlyMessage(): string
    {
        $errorCount = count($this->validationErrors);
        return "Claim contains {$errorCount} validation error(s). Please review and correct the information.";
    }

    public function getHttpStatusCode(): int
    {
        return 422; // Unprocessable Entity
    }
}
