<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\NotificationCompressionService;
use Symfony\Component\HttpFoundation\Response;

class NotificationCompression
{
    private NotificationCompressionService $compressionService;

    public function __construct(NotificationCompressionService $compressionService)
    {
        $this->compressionService = $compressionService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only compress JSON responses
        if (!$response instanceof JsonResponse) {
            return $response;
        }

        // Check if client supports gzip
        if (!$this->compressionService->clientSupportsGzip()) {
            return $response;
        }

        $content = $response->getContent();

        // Only compress if content is large enough
        if (strlen($content) < 1024) {
            return $response;
        }

        $compressedContent = $this->compressionService->compressResponse($content);

        // Only use compressed response if it's actually smaller
        if (strlen($compressedContent) < strlen($content)) {
            $response->setContent($compressedContent);
            $response->headers->set('Content-Encoding', 'gzip');
            $response->headers->set('Content-Length', strlen($compressedContent));
        }

        return $response;
    }
}
