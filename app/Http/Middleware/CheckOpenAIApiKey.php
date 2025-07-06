<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class CheckOpenAIApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = env('OPENAI_API_KEY');
        
        // If no API key is set, redirect with error
        if (empty($apiKey)) {
            return redirect()->route('home')->with('openai_api_error', 'OpenAI API key is not configured. Please contact the administrator.');
        }
        
        // Only check the API key validity on routes that use OpenAI
        if ($request->is('openai*')) {
            try {
                // Make a simple API call to check if the key is valid
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->get('https://api.openai.com/v1/models');
                
                // If we get an error status code, the key might be invalid
                if ($response->status() !== 200) {
                    $errorMessage = 'Your OpenAI API key appears to be invalid or expired. Please contact the administrator.';
                    
                    // If it's an AJAX request, return JSON
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMessage
                        ], 401);
                    }
                    
                    // Otherwise redirect with error message
                    return redirect()->route('home')->with('openai_api_error', $errorMessage);
                }
            } catch (\Exception $e) {
                $errorMessage = 'Unable to connect to OpenAI API. Please try again later or contact the administrator.';
                
                // If it's an AJAX request, return JSON
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 500);
                }
                
                // Otherwise redirect with error message
                return redirect()->route('home')->with('openai_api_error', $errorMessage);
            }
        }
        
        return $next($request);
    }
}