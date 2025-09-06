<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EnsureJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // If the response is not already a JSON response, convert it
        if (!$response instanceof JsonResponse) {
            // If we get an HTML response (like a login redirect), convert it to JSON
            if (strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required. Please log in again.',
                    'authenticated' => false,
                    'count' => 0
                ], 401);
            }

            // For other non-JSON responses, convert them to JSON
            return response()->json([
                'success' => false,
                'error' => 'Invalid response format',
                'authenticated' => false,
                'count' => 0
            ], 500);
        }

        return $response;
    }
}
