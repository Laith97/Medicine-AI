<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStripeConfiguration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $stripeSecret = config('stripe.secret');
        $stripeKey = config('stripe.key');
        
        // Check if Stripe is properly configured
        if (!$stripeSecret || 
            $stripeSecret === 'sk_test_your_secret_key_here' ||
            !$stripeKey || 
            $stripeKey === 'pk_test_your_publishable_key_here') {
            
            return response()->json([
                'error' => 'Stripe payment system is not configured. Please contact the administrator to set up payment processing.',
                'setup_required' => true
            ], 503);
        }
        
        return $next($request);
    }
}
