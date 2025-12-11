<?php

namespace App\Exceptions\Handlers;

use App\Exceptions\WaitlistException;
use App\Exceptions\WaitlistValidationException;
use App\Exceptions\WaitlistSecurityException;
use App\Exceptions\WaitlistProcessingException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class WaitlistExceptionHandler
{
    /**
     * Handle waitlist-specific exceptions
     */
    public static function handle(WaitlistException $exception, Request $request)
    {
        // Log the exception with context
        self::logException($exception);

        // Return appropriate response
        return $exception->render($request);
    }

    /**
     * Handle validation exceptions specifically
     */
    public static function handleValidationException(WaitlistValidationException $exception, Request $request)
    {
        Log::warning('Waitlist validation failed', [
            'errors' => $exception->getErrors(),
            'context' => $exception->getContext(),
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return self::handle($exception, $request);
    }

    /**
     * Handle security exceptions specifically
     */
    public static function handleSecurityException(WaitlistSecurityException $exception, Request $request)
    {
        Log::error('Waitlist security violation', [
            'message' => $exception->getMessage(),
            'context' => $exception->getContext(),
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);

        return self::handle($exception, $request);
    }

    /**
     * Handle processing exceptions specifically
     */
    public static function handleProcessingException(WaitlistProcessingException $exception, Request $request)
    {
        Log::error('Waitlist processing error', [
            'message' => $exception->getMessage(),
            'context' => $exception->getContext(),
            'user_id' => Auth::id(),
            'trace' => $exception->getTraceAsString(),
        ]);

        return self::handle($exception, $request);
    }

    /**
     * Log exception details
     */
    private static function logException(WaitlistException $exception): void
    {
        $context = [
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $exception->getContext(),
            'errors' => $exception->getErrors(),
        ];

        if ($exception instanceof WaitlistSecurityException) {
            Log::critical('Waitlist security exception', $context);
        } elseif ($exception instanceof WaitlistProcessingException) {
            Log::error('Waitlist processing exception', $context);
        } elseif ($exception instanceof WaitlistValidationException) {
            Log::warning('Waitlist validation exception', $context);
        } else {
            Log::error('Waitlist exception', $context);
        }
    }
}
