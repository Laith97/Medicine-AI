<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ClaimSecurityException extends ClaimException
{
    public function __construct(
        string $message = 'Unauthorized access to claim data',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 2001, $previous, $context, true, 'warning', true);
    }

    public function getHttpStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }

    public function getUserFriendlyMessage(): string
    {
        return 'You do not have permission to access this claim information.';
    }
}
