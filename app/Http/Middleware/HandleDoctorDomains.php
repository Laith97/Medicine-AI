<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PublicLandingPageController;
use App\Models\DoctorLandingPage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleDoctorDomains
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $mainDomain = config('app.domain', 'medcuraai.com');

        // Check for custom domain
        if ($host !== $mainDomain && !str_ends_with($host, '.' . $mainDomain)) {
            $landingPage = DoctorLandingPage::with(['doctor.user', 'doctor.specialty'])
                ->byCustomDomain($host)
                ->published()
                ->first();

            if ($landingPage) {
                $controller = new PublicLandingPageController();
                return $controller->showByDomain($request);
            }
        }

        // Check for subdomain
        if (str_ends_with($host, '.' . $mainDomain) && $host !== $mainDomain) {
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $username = $parts[0];

                $landingPage = DoctorLandingPage::with(['doctor.user', 'doctor.specialty'])
                    ->byUsername($username)
                    ->where('subdomain_enabled', true)
                    ->published()
                    ->first();

                if ($landingPage) {
                    $controller = new PublicLandingPageController();
                    return $controller->showBySubdomain($request);
                }
            }
        }

        return $next($request);
    }
}
