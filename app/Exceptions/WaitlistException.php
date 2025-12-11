<?php

namespace App\Exceptions;

use Exception;

class WaitlistException extends Exception
{
    protected $errors;
    protected $context;

    public function __construct(string $message = '', array $errors = [], array $context = [], int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
        $this->context = $context;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'errors' => $this->errors,
                'type' => class_basename($this),
            ], $this->getCode() ?: 400);
        }

        return response()->view('errors.waitlist', [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ], $this->getCode() ?: 400);
    }
}
