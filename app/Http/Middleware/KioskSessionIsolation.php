<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class KioskSessionIsolation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a kiosk route
        if ($this->isKioskRoute($request)) {
            // Ensure kiosk session is isolated
            $this->isolateKioskSession();

            // Prevent regular user authentication for kiosk routes
            if (Auth::check()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return $next($request);
    }

    /**
     * Check if the current route is a kiosk route.
     */
    protected function isKioskRoute(Request $request): bool
    {
        $route = $request->route();

        if (!$route) {
            return false;
        }

        $routeName = $route->getName();

        return $routeName && str_starts_with($routeName, 'kiosk.');
    }

    /**
     * Isolate kiosk session from regular user sessions.
     */
    protected function isolateKioskSession(): void
    {
        // Set a specific session domain for kiosk
        config(['session.domain' => 'kiosk.' . config('session.domain')]);

        // Ensure kiosk session data is separate
        if (!session()->has('kiosk_isolated') || !session('kiosk_isolated')) {
            // Clear any non-kiosk session data
            $kioskData = [
                'kiosk_session_id' => session('kiosk_session_id'),
                'kiosk_isolated' => true,
                'voice_enabled' => session('voice_enabled', false),
                'high_contrast' => session('high_contrast', false),
            ];

            session()->flush();
            session($kioskData);
        }
    }
}
