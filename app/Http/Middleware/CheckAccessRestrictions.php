<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccessRestrictions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for non-authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $currentRoute = $request->route() ? $request->route()->getName() : null;

        // Skip check if route name is null or for certain routes
        $exemptRoutes = [
            'invoices.index',
            'invoices.show',
            'invoices.pay',
            'invoices.manual-payment',
            'invoices.pdf',
            'invoices.sync',
            'subscription.manage',
            'subscription.portal',
            'subscription.success',
            'subscription.pricing',
            'subscription.checkout',
            'subscription.cancel',
            'access.restricted',
            'access.check-status',
            'logout',
            'admin.login',
            'admin.login.submit',
            'admin.logout',
            'login',
            'register',
            'contact',
            'contact.store',
        ];

        if (!$currentRoute || in_array($currentRoute, $exemptRoutes)) {
            return $next($request);
        }

        // Check if user is restricted and current page is restricted
        if ($user->isRestricted() && $user->isPageRestricted($currentRoute)) {
            // If it's an AJAX request, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Access restricted',
                    'message' => $user->getRestrictionMessage(),
                    'redirect' => route('invoices.index')
                ], 403);
            }

            // For regular requests, redirect to restriction page
            return redirect()->route('access.restricted')
                ->with('restriction_message', $user->getRestrictionMessage());
        }

        return $next($request);
    }
}