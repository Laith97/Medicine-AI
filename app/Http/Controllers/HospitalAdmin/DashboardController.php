<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            // Allow access if admin is impersonating
            if (session()->has('impersonating_admin_id')) {
                return $next($request);
            }
            
            // Allow super admin direct access
            if ($user->role === 'admin') {
                return $next($request);
            }
            
            if (!$user->isHospitalAdmin()) {
                abort(403, 'Access denied. Hospital admin role required.');
            }
            
            if (!$user->hospital) {
                abort(403, 'Access denied. Hospital association required.');
            }
            
            return $next($request);
        });
    }

    /**
     * Display the hospital admin dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        // Get hospital statistics
        $statistics = $user->getHospitalAdminStatistics();
        
        // Get recent activities
        $recentDoctors = $hospital->doctors()
            ->with('doctor')
            ->latest()
            ->limit(5)
            ->get();
        
        // Get monthly statistics
        $monthlyStats = $this->getMonthlyStatistics($hospital);
        
        // Get subscription info
        $subscriptionInfo = $this->getSubscriptionInfo($user);
        
        return view('hospital-admin.dashboard', compact(
            'hospital',
            'statistics',
            'recentDoctors',
            'monthlyStats',
            'subscriptionInfo'
        ));
    }

    /**
     * Get monthly statistics for the hospital
     */
    private function getMonthlyStatistics($hospital)
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        $doctors = $hospital->doctors()->with('doctor')->get();
        
        $monthlyAppointments = 0;
        $monthlyRevenue = 0;
        $monthlyReviews = 0;
        
        foreach ($doctors as $doctor) {
            if ($doctor->doctor) {
                $monthlyAppointments += $doctor->doctor->appointments()
                    ->whereBetween('appointment_date', [$startOfMonth, $endOfMonth])
                    ->count();
                
                $monthlyReviews += $doctor->doctor->reviews()
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count();
            }
        }
        
        return [
            'appointments' => $monthlyAppointments,
            'revenue' => $monthlyRevenue,
            'reviews' => $monthlyReviews,
        ];
    }

    /**
     * Get subscription information
     */
    private function getSubscriptionInfo($user)
    {
        $setting = $user->monthlyInvoiceSetting;
        
        if (!$setting) {
            return [
                'status' => 'not_configured',
                'message' => 'Subscription not configured',
            ];
        }
        
        return [
            'status' => $user->getSubscriptionStatus(),
            'ends_at' => $user->getSubscriptionEndDate(),
            'days_remaining' => $user->getDaysRemainingInCurrentPeriod(),
            'is_active' => $setting->is_active,
            'monthly_price' => $setting->monthly_price,
            'yearly_price' => $setting->yearly_price,
        ];
    }
}