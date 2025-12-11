<?php

namespace App\Exceptions\Handlers;

use App\Exceptions\KioskConfigurationException;
use App\Exceptions\KioskException;
use App\Exceptions\KioskPaymentException;
use App\Exceptions\KioskSecurityException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KioskExceptionHandler
{
    /**
     * Handle kiosk-specific exceptions
     */
    public static function handle(\Throwable $exception, Request $request): ?\Illuminate\Http\Response
    {
        if ($exception instanceof KioskException) {
            return $exception->render($request);
        }

        // Handle common exceptions that might occur in kiosk context
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return self::handleValidationException($exception, $request);
        }

        if ($exception instanceof \Illuminate\Database\QueryException) {
            return self::handleDatabaseException($exception, $request);
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return self::handleAuthorizationException($exception, $request);
        }

        return null; // Let Laravel handle it
    }

    /**
     * Handle validation exceptions in kiosk context
     */
    protected static function handleValidationException(\Illuminate\Validation\ValidationException $exception, Request $request)
    {
        Log::warning('Kiosk validation failed', [
            'errors' => $exception->errors(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
        ]);

        return response()->json([
            'success' => false,
            'error' => 'VALIDATION_ERROR',
            'message' => 'Please check your input and try again.',
            'errors' => $exception->errors(),
        ], 422);
    }

    /**
     * Handle database exceptions in kiosk context
     */
    protected static function handleDatabaseException(\Illuminate\Database\QueryException $exception, Request $request)
    {
        Log::error('Kiosk database error', [
            'error' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
        ]);

        return response()->json([
            'success' => false,
            'error' => 'DATABASE_ERROR',
            'message' => 'A system error occurred. Please try again later.',
        ], 500);
    }

    /**
     * Handle authorization exceptions in kiosk context
     */
    protected static function handleAuthorizationException(\Illuminate\Auth\Access\AuthorizationException $exception, Request $request)
    {
        Log::warning('Kiosk authorization failed', [
            'error' => $exception->getMessage(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
        ]);

        return response()->json([
            'success' => false,
            'error' => 'AUTHORIZATION_ERROR',
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }

    /**
     * Create a standardized kiosk error response
     */
    public static function createErrorResponse(
        string $errorCode,
        string $message,
        int $statusCode = 400,
        array $additionalData = []
    ): \Illuminate\Http\JsonResponse {
        $response = [
            'success' => false,
            'error' => $errorCode,
            'message' => $message,
            ...$additionalData
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Create a standardized kiosk success response
     */
    public static function createSuccessResponse(
        array $data = [],
        string $message = null
    ): \Illuminate\Http\JsonResponse {
        $response = [
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response);
    }
}
