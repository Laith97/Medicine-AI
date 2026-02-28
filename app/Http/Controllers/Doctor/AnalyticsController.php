<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\LandingPageVisit;
use App\Models\BlogPost;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'doctor']);
    }

    public function index()
    {
        $doctor = $this->getEffectiveDoctor();
        $period = 30; // Default to 30 days

        $stats = $this->getStats($doctor->id, $period);
        $dailyVisits = $this->getDailyVisits($doctor->id, $period);
        $deviceStats = $this->getDeviceStats($doctor->id, $period);
        $topBlogPosts = $this->getTopBlogPosts($doctor->id);
        $topReferrers = $this->getTopReferrers($doctor->id, $period);

        return view('doctor.analytics.index', compact(
            'stats',
            'dailyVisits',
            'deviceStats',
            'topBlogPosts',
            'topReferrers'
        ));
    }

    public function getData(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();
        $period = $request->get('period', 30);

        $stats = $this->getStats($doctor->id, $period);
        $dailyVisits = $this->getDailyVisits($doctor->id, $period);
        $deviceStats = $this->getDeviceStats($doctor->id, $period);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'dailyVisits' => $dailyVisits,
            'deviceStats' => $deviceStats
        ]);
    }

    private function getStats($doctorId, $period)
    {
        $startDate = now()->subDays($period);

        // Landing page visits (with table check)
        $totalVisits = 0;
        $uniqueVisitors = 0;
        if (\Schema::hasTable('landing_page_visits')) {
            try {
                $totalVisits = LandingPageVisit::where('doctor_id', $doctorId)
                    ->where('visited_at', '>=', $startDate)
                    ->count();

                $uniqueVisitors = LandingPageVisit::where('doctor_id', $doctorId)
                    ->where('visited_at', '>=', $startDate)
                    ->distinct('ip_address')
                    ->count();
            } catch (\Exception $e) {
                // Silently handle missing columns
            }
        }

        // Blog views (with table check)
        $blogViews = 0;
        if (\Schema::hasTable('blog_posts')) {
            try {
                $blogViews = BlogPost::where('doctor_id', $doctorId)
                    ->sum('views_count');
            } catch (\Exception $e) {
                // Silently handle missing columns
            }
        }

        // Chat sessions (with table check)
        $chatSessions = 0;
        if (\Schema::hasTable('chat_sessions')) {
            try {
                $chatSessions = ChatSession::where('doctor_id', $doctorId)
                    ->where('created_at', '>=', $startDate)
                    ->count();
            } catch (\Exception $e) {
                // Silently handle missing columns
            }
        }

        return [
            'total_visits' => $totalVisits,
            'unique_visitors' => $uniqueVisitors,
            'blog_views' => $blogViews,
            'chat_sessions' => $chatSessions,
        ];
    }

    private function getDailyVisits($doctorId, $period)
    {
        if (!\Schema::hasTable('landing_page_visits')) {
            return collect();
        }

        try {
            return LandingPageVisit::select(
                    DB::raw('DATE(visited_at) as date'),
                    DB::raw('COUNT(*) as visits'),
                    DB::raw('COUNT(DISTINCT ip_address) as unique_visitors')
                )
                ->where('doctor_id', $doctorId)
                ->where('visited_at', '>=', now()->subDays($period))
                ->groupBy(DB::raw('DATE(visited_at)'))
                ->orderBy('date')
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    private function getDeviceStats($doctorId, $period)
    {
        if (!\Schema::hasTable('landing_page_visits')) {
            return collect();
        }

        try {
            return LandingPageVisit::select('device_type', DB::raw('COUNT(*) as visits'))
                ->where('doctor_id', $doctorId)
                ->where('visited_at', '>=', now()->subDays($period))
                ->whereNotNull('device_type')
                ->groupBy('device_type')
                ->orderByDesc('visits')
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    private function getTopBlogPosts($doctorId)
    {
        if (!\Schema::hasTable('blog_posts')) {
            return collect();
        }

        try {
            return BlogPost::where('doctor_id', $doctorId)
                ->where('is_published', true)
                ->orderByDesc('views_count')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    private function getTopReferrers($doctorId, $period)
    {
        if (!\Schema::hasTable('landing_page_visits')) {
            return collect();
        }

        try {
            return LandingPageVisit::select('referrer_url', DB::raw('COUNT(*) as visits'))
                ->where('doctor_id', $doctorId)
                ->where('visited_at', '>=', now()->subDays($period))
                ->whereNotNull('referrer_url')
                ->where('referrer_url', '!=', '')
                ->groupBy('referrer_url')
                ->orderByDesc('visits')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Landing page specific analytics
     */
    public function landingPageAnalytics()
    {
        $doctor = $this->getEffectiveDoctor();
        $period = 30; // Default to 30 days

        $stats = $this->getLandingPageStats($doctor->id, $period);
        $dailyVisits = $this->getDailyVisits($doctor->id, $period);
        $deviceStats = $this->getDeviceStats($doctor->id, $period);
        $topReferrers = $this->getTopReferrers($doctor->id, $period);
        $browserStats = $this->getBrowserStats($doctor->id, $period);

        return view('doctor.analytics.landing-page', compact(
            'stats',
            'dailyVisits',
            'deviceStats',
            'topReferrers',
            'browserStats',
            'doctor'
        ));
    }

    /**
     * Get landing page analytics data via AJAX
     */
    public function getLandingPageAnalyticsData(Request $request)
    {
        $doctor = $this->getEffectiveDoctor();
        $period = $request->get('period', 30);

        $stats = $this->getLandingPageStats($doctor->id, $period);
        $dailyVisits = $this->getDailyVisits($doctor->id, $period);
        $deviceStats = $this->getDeviceStats($doctor->id, $period);

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'dailyVisits' => $dailyVisits,
            'deviceStats' => $deviceStats
        ]);
    }

    /**
     * Get landing page specific stats
     */
    private function getLandingPageStats($doctorId, $period)
    {
        $startDate = now()->subDays($period);

        // Landing page visits (with table check)
        $totalVisits = 0;
        $uniqueVisitors = 0;
        $avgSessionTime = 0;

        if (\Schema::hasTable('landing_page_visits')) {
            try {
                $totalVisits = LandingPageVisit::where('doctor_id', $doctorId)
                    ->where('visited_at', '>=', $startDate)
                    ->count();

                $uniqueVisitors = LandingPageVisit::where('doctor_id', $doctorId)
                    ->where('visited_at', '>=', $startDate)
                    ->distinct('ip_address')
                    ->count();

                $avgSessionTime = LandingPageVisit::where('doctor_id', $doctorId)
                    ->where('visited_at', '>=', $startDate)
                    ->whereNotNull('session_duration')
                    ->avg('session_duration') ?? 0;
            } catch (\Exception $e) {
                // Silently handle missing columns
            }
        }

        return [
            'total_visits' => $totalVisits,
            'unique_visitors' => $uniqueVisitors,
            'avg_session_time' => round($avgSessionTime, 2),
            'bounce_rate' => $totalVisits > 0 ? round((($totalVisits - $uniqueVisitors) / $totalVisits) * 100, 2) : 0
        ];
    }

    /**
     * Get browser statistics
     */
    private function getBrowserStats($doctorId, $period)
    {
        if (!\Schema::hasTable('landing_page_visits')) {
            return collect();
        }

        try {
            return LandingPageVisit::select('browser', DB::raw('COUNT(*) as visits'))
                ->where('doctor_id', $doctorId)
                ->where('visited_at', '>=', now()->subDays($period))
                ->whereNotNull('browser')
                ->groupBy('browser')
                ->orderByDesc('visits')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get effective doctor for the current user
     */
    protected function getEffectiveDoctor()
    {
        $user = auth()->user();

        // If user is a doctor, return their doctor profile
        if ($user->role === 'doctor' && $user->doctor) {
            return $user->doctor;
        }

        // If user is a sub-user, return their parent doctor's profile
        if ($user->isSubUser() && $user->parentUser && $user->parentUser->doctor) {
            return $user->parentUser->doctor;
        }

        return null;
    }
}
