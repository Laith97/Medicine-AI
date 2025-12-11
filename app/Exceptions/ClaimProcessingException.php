<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ClaimProcessingException extends ClaimException
{
    protected $processingStage;

    public function __construct(
        string $message = 'Claim processing failed',
        string $processingStage = 'unknown',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        $this->processingStage = $processingStage;

        $context['processing_stage'] = $processingStage;

        parent::__construct($message, 3001, $previous, $context, true, 'error', true);
    }

    public function getProcessingStage(): string
    {
        return $this->processingStage;
    }

    public function getHttpStatusCode(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    public function getUserFriendlyMessage(): string
    {
        return "Claim processing failed during {$this->processingStage}. Please try again or contact support.";
    }
}
