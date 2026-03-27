<?php

namespace App\Http\Controllers\HospitalAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
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
     * Show hospital overview analytics
     */
    public function overview()
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        // Get key metrics
        $doctors = $hospital->doctors()->with('doctor')->get();
        $totalAppointments = 0;
        $totalPatients = 0;
        $totalRating = 0;
        $ratingCount = 0;
        
        foreach ($doctors as $doctor) {
            if ($doctor->doctor) {
                $totalAppointments += $doctor->doctor->appointments()->count();
                $totalPatients += $doctor->doctor->appointments()->distinct('patient_id')->count();
                $doctorReviews = $doctor->doctor->reviews();
                $totalRating += $doctorReviews->sum('rating');
                $ratingCount += $doctorReviews->count();
            }
        }
        
        $metrics = [
            'total_doctors' => $doctors->count(),
            'total_appointments' => $totalAppointments,
            'total_patients' => $totalPatients,
            'average_rating' => $ratingCount > 0 ? $totalRating / $ratingCount : 0,
        ];
        
        // Get chart data
        $monthlyData = $this->getMonthlyData($hospital);
        $departmentStats = $this->getDepartmentStatistics($hospital);
        
        $chartData = [
            'appointments' => $monthlyData['appointments'],
            'specialty_labels' => array_column($departmentStats, 'name'),
            'specialty_data' => array_column($departmentStats, 'doctors_count'),
        ];
        
        // TODO: Replace with actual audit log queries
        //       Query AuditLoggingService or activity_logs table for recent hospital activity
        //       Filter by hospital_id and sort by created_at DESC
        $recentActivity = [
            [
                'date' => now()->format('M d, Y'),
                'description' => 'New doctor registered',
                'doctor' => 'Dr. John Smith',
                'status' => 'Active',
                'status_color' => 'success'
            ],
            [
                'date' => now()->subDay()->format('M d, Y'),
                'description' => 'Appointment completed',
                'doctor' => 'Dr. Jane Doe',
                'status' => 'Completed',
                'status_color' => 'info'
            ],
        ];
        
        return view('hospital-admin.analytics.overview', compact(
            'hospital',
            'metrics',
            'chartData',
            'recentActivity'
        ));
    }

    /**
     * Show doctor performance analytics
     */
    public function doctors()
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        $doctors = $hospital->doctors()->with(['doctor.specialty'])->get();
        
        $doctorPerformance = [];
        foreach ($doctors as $doctor) {
            if ($doctor->doctor) {
                $doctorPerformance[] = [
                    'doctor' => $doctor,
                    'appointments_total' => $doctor->doctor->appointments()->count(),
                    'appointments_completed' => $doctor->doctor->appointments()->where('status', 'completed')->count(),
                    'appointments_this_month' => $doctor->doctor->appointments()
                        ->whereBetween('appointment_date', [now()->startOfMonth(), now()->endOfMonth()])
                        ->count(),
                    'reviews_count' => $doctor->doctor->reviews()->count(),
                    'average_rating' => $doctor->doctor->reviews()->avg('rating') ?: 0,
                    'revenue_this_month' => $this->calculateDoctorRevenue($doctor, now()->startOfMonth(), now()->endOfMonth()),
                ];
            }
        }

        // Sort by performance metrics
        usort($doctorPerformance, function($a, $b) {
            return $b['appointments_this_month'] <=> $a['appointments_this_month'];
        });
        
        return view('hospital-admin.analytics.doctors', compact(
            'hospital',
            'doctorPerformance'
        ));
    }

    /**
     * Show financial analytics
     */
    public function financial()
    {
        $user = Auth::user();
        $hospital = $user->hospital;
        
        // Get financial data
        $monthlyRevenue = $this->getMonthlyRevenue($hospital);
        $yearlyRevenue = $this->getYearlyRevenue($hospital);
        $revenueByDoctor = $this->getRevenueByDoctor($hospital);
        
        // Get subscription costs
        $subscriptionCosts = $this->getSubscriptionCosts($user);
        
        return view('hospital-admin.analytics.financial', compact(
            'hospital',
            'monthlyRevenue',
            'yearlyRevenue',
            'revenueByDoctor',
            'subscriptionCosts'
        ));
    }

    /**
     * Get monthly data for the hospital
     */
    private function getMonthlyData($hospital)
    {
        $months = [];
        $appointments = [];
        $reviews = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $months[] = $date->format('M Y');
            
            $monthlyAppointments = 0;
            $monthlyReviews = 0;
            
            $doctors = $hospital->doctors()->with('doctor')->get();
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
            
            $appointments[] = $monthlyAppointments;
            $reviews[] = $monthlyReviews;
        }
        
        return [
            'months' => $months,
            'appointments' => $appointments,
            'reviews' => $reviews,
        ];
    }

    /**
     * Get yearly data for the hospital
     */
    private function getYearlyData($hospital)
    {
        $years = [];
        $appointments = [];
        $reviews = [];
        
        for ($i = 4; $i >= 0; $i--) {
            $year = now()->subYears($i)->year;
            $years[] = $year;
            
            $yearlyAppointments = 0;
            $yearlyReviews = 0;
            
            $doctors = $hospital->doctors()->with('doctor')->get();
            foreach ($doctors as $doctor) {
                if ($doctor->doctor) {
                    $yearlyAppointments += $doctor->doctor->appointments()
                        ->whereYear('appointment_date', $year)
                        ->count();
                    
                    $yearlyReviews += $doctor->doctor->reviews()
                        ->whereYear('created_at', $year)
                        ->count();
                }
            }
            
            $appointments[] = $yearlyAppointments;
            $reviews[] = $yearlyReviews;
        }
        
        return [
            'years' => $years,
            'appointments' => $appointments,
            'reviews' => $reviews,
        ];
    }

    /**
     * Get department statistics
     */
    private function getDepartmentStatistics($hospital)
    {
        $doctors = $hospital->doctors()->with(['doctor.specialty'])->get();
        $departments = [];
        
        foreach ($doctors as $doctor) {
            if ($doctor->doctor && $doctor->doctor->specialty) {
                $specialtyName = $doctor->doctor->specialty->name;
                
                if (!isset($departments[$specialtyName])) {
                    $departments[$specialtyName] = [
                        'name' => $specialtyName,
                        'doctors_count' => 0,
                        'appointments_count' => 0,
                        'reviews_count' => 0,
                        'average_rating' => 0,
                    ];
                }
                
                $departments[$specialtyName]['doctors_count']++;
                $departments[$specialtyName]['appointments_count'] += $doctor->doctor->appointments()->count();
                $departments[$specialtyName]['reviews_count'] += $doctor->doctor->reviews()->count();
            }
        }
        
        // Calculate average ratings
        foreach ($departments as $key => $department) {
            if ($department['reviews_count'] > 0) {
                $totalRating = 0;
                $reviewCount = 0;
                
                foreach ($doctors as $doctor) {
                    if ($doctor->doctor && $doctor->doctor->specialty && 
                        $doctor->doctor->specialty->name === $department['name']) {
                        $doctorReviews = $doctor->doctor->reviews();
                        $totalRating += $doctorReviews->sum('rating');
                        $reviewCount += $doctorReviews->count();
                    }
                }
                
                $departments[$key]['average_rating'] = $reviewCount > 0 ? $totalRating / $reviewCount : 0;
            }
        }
        
        return array_values($departments);
    }

    /**
     * Calculate doctor revenue for a period
     */
    private function calculateDoctorRevenue($doctor, $startDate, $endDate)
    {
        if (!$doctor->doctor) {
            return 0;
        }
        
        // This is a simplified calculation
        // You might want to implement actual revenue tracking
        $completedAppointments = $doctor->doctor->appointments()
            ->where('status', 'completed')
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->count();
        
        return $completedAppointments * ($doctor->doctor->consultation_fee ?? 0);
    }

    /**
     * Get monthly revenue for the hospital
     */
    private function getMonthlyRevenue($hospital)
    {
        $doctors = $hospital->doctors()->with('doctor')->get();
        $totalRevenue = 0;
        
        foreach ($doctors as $doctor) {
            $totalRevenue += $this->calculateDoctorRevenue(
                $doctor, 
                now()->startOfMonth(), 
                now()->endOfMonth()
            );
        }
        
        return $totalRevenue;
    }

    /**
     * Get yearly revenue for the hospital
     */
    private function getYearlyRevenue($hospital)
    {
        $doctors = $hospital->doctors()->with('doctor')->get();
        $totalRevenue = 0;
        
        foreach ($doctors as $doctor) {
            $totalRevenue += $this->calculateDoctorRevenue(
                $doctor, 
                now()->startOfYear(), 
                now()->endOfYear()
            );
        }
        
        return $totalRevenue;
    }

    /**
     * Get revenue breakdown by doctor
     */
    private function getRevenueByDoctor($hospital)
    {
        $doctors = $hospital->doctors()->with('doctor')->get();
        $revenueByDoctor = [];
        
        foreach ($doctors as $doctor) {
            if ($doctor->doctor) {
                $revenueByDoctor[] = [
                    'doctor_name' => $doctor->name,
                    'monthly_revenue' => $this->calculateDoctorRevenue(
                        $doctor, 
                        now()->startOfMonth(), 
                        now()->endOfMonth()
                    ),
                    'yearly_revenue' => $this->calculateDoctorRevenue(
                        $doctor, 
                        now()->startOfYear(), 
                        now()->endOfYear()
                    ),
                ];
            }
        }
        
        return $revenueByDoctor;
    }

    /**
     * Get subscription costs
     */
    private function getSubscriptionCosts($user)
    {
        $setting = $user->monthlyInvoiceSetting;
        
        if (!$setting) {
            return [
                'monthly_cost' => 0,
                'yearly_cost' => 0,
                'per_doctor_cost' => 0,
            ];
        }
        
        return [
            'monthly_cost' => $setting->monthly_price ?? 0,
            'yearly_cost' => $setting->yearly_price ?? 0,
            'per_doctor_cost' => $user->hospital->doctors()->count() > 0 
                ? ($setting->monthly_price ?? 0) / $user->hospital->doctors()->count() 
                : 0,
        ];
    }
}