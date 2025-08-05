<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubUserPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $routeName = $request->route()->getName();

        // Skip permission check for main users (they have full access based on role)
        if ($user->isMainUser()) {
            return $next($request);
        }

        // For sub-users, check if they have permission to access this route
        if (!$user->canAccessRoute($routeName)) {
            abort(403, 'Access denied. You do not have permission to access this page.');
        }

        return $next($request);
    }
}