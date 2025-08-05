<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Handle sub-users - they inherit their parent's role for access purposes
        $effectiveRole = $user->role;
        if ($user->isSubUser() && $user->parentUser) {
            $effectiveRole = $user->parentUser->role;
        }

        if ($effectiveRole !== $role) {
            abort(403, "Access denied. {$role} role required.");
        }

        return $next($request);
    }
}
