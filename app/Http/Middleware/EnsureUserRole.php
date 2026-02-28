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
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                    'redirect' => route('login')
                ], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Handle sub-users - they inherit their parent's role for access purposes
        $effectiveRole = $user->role;
        if ($user->isSubUser() && $user->parentUser) {
            $effectiveRole = $user->parentUser->role;
        }

        if ($effectiveRole !== $role) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Access denied. {$role} role required.",
                    'error' => 'Insufficient permissions'
                ], 403);
            }
            abort(403, "Access denied. {$role} role required.");
        }

        return $next($request);
    }
}
