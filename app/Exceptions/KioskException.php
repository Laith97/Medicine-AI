<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KioskException extends Exception
{
    protected $errorCode;
    protected $httpStatusCode;
    protected $context;

    public function __construct(
        string $message = '',
        string $errorCode = 'KIOSK_ERROR',
        int $httpStatusCode = 500,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->errorCode = $errorCode;
        $this->httpStatusCode = $httpStatusCode;
        $this->context = $context;

        // Log the exception
        $this->logException();
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the HTTP status code
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the context data
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => $this->errorCode,
            'message' => $this->message,
        ];

        // Add additional context for debugging in development
        if (config('app.debug')) {
            $response['context'] = $this->context;
            $response['trace'] = $this->getTraceAsString();
        }

        return response()->json($response, $this->httpStatusCode);
    }

    /**
     * Log the exception
     */
    protected function logException(): void
    {
        $logData = [
            'error_code' => $this->errorCode,
            'message' => $this->message,
            'context' => $this->context,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        // Determine log level based on HTTP status code
        if ($this->httpStatusCode >= 500) {
            Log::error('Kiosk Exception: ' . $this->errorCode, $logData);
        } elseif ($this->httpStatusCode >= 400) {
            Log::warning('Kiosk Exception: ' . $this->errorCode, $logData);
        } else {
            Log::info('Kiosk Exception: ' . $this->errorCode, $logData);
        }
    }
}
