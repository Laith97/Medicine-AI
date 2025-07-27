<?php

namespace App\Http\Middleware;

use App\Models\LandingPageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLandingPageVisit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET requests to landing pages
        if ($request->isMethod('GET') && $request->route()) {
            $routeName = $request->route()->getName();

            // Track visits to doctor landing pages
            if ($routeName === 'doctor.landing' || $routeName === 'doctor.blog.post') {
                $this->trackVisit($request);
            }
        }

        return $response;
    }

    private function trackVisit(Request $request)
    {
        try {
            // Get doctor ID from route parameters
            $username = $request->route('username');

            if ($username) {
                $doctor = \App\Models\Doctor::whereHas('landingPage', function ($query) use ($username) {
                    $query->where('username', $username);
                })->first();

                if ($doctor) {
                    LandingPageVisit::recordVisit($doctor->id, $request);
                }
            }
        } catch (\Exception $e) {
            // Silently fail - don't break the page if tracking fails
            \Log::error('Failed to track landing page visit: ' . $e->getMessage());
        }
    }
}
