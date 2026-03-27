<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsPermissions;
use App\Models\StripeInvoice;
use App\Models\Subscription;
use App\Models\Review;
use App\Models\Appointment;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected AnalyticsPermissions $analyticsPermissions;

    public function __construct(AnalyticsPermissions $analyticsPermissions)
    {
        $this->analyticsPermissions = $analyticsPermissions;
    }

    /**
     * Get executive dashboard data
     */
    public function getExecutiveDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check dashboard access
        if (!$this->analyticsPermissions->canAccessDashboard($user, 'executive')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied to executive dashboard'
            ], 403);
        }

        // Get data scope for filtering
        $dataScope = $this->analyticsPermissions->getDataScope($user, 'executive');

        // Calculate revenue metrics
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $currentRevenue = StripeInvoice::where('status', 'paid')
            ->whereYear('paid_at', $currentMonth->year)
            ->whereMonth('paid_at', $currentMonth->month)
            ->sum('amount_paid');

        $lastRevenue = StripeInvoice::where('status', 'paid')
            ->whereYear('paid_at', $lastMonth->year)
            ->whereMonth('paid_at', $lastMonth->month)
            ->sum('amount_paid');

        $revenueChange = $lastRevenue > 0 ? round((($currentRevenue - $lastRevenue) / $lastRevenue) * 100, 1) : 0;
        $revenueTarget = 130000; // Configurable target

        // Calculate patient satisfaction metrics
        $currentSatisfaction = Review::approved()->avg('rating') ?? 0;
        $lastMonthSatisfaction = Review::approved()
            ->where('created_at', '<', $lastMonth->startOfMonth())
            ->avg('rating') ?? 0;
        $satisfactionChange = round($currentSatisfaction - $lastMonthSatisfaction, 1);
        $satisfactionTarget = 4.9;

        // Calculate operational efficiency (appointment completion rate)
        $totalAppointments = Appointment::whereMonth('created_at', $currentMonth->month)->count();
        $completedAppointments = Appointment::whereMonth('created_at', $currentMonth->month)
            ->where('status', 'completed')
            ->count();
        $operationalEfficiency = $totalAppointments > 0
            ? round(($completedAppointments / $totalAppointments) * 100, 1)
            : 0;
        $lastMonthEfficiency = Appointment::whereMonth('created_at', $lastMonth->month)->count() > 0
            ? round((Appointment::whereMonth('created_at', $lastMonth->month)->where('status', 'completed')->count()
                / Appointment::whereMonth('created_at', $lastMonth->month)->count()) * 100, 1)
            : 0;
        $efficiencyChange = round($operationalEfficiency - $lastMonthEfficiency, 1);
        $efficiencyTarget = 95.0;

        // Calculate clinical outcomes (positive review rate as proxy)
        $positiveReviews = Review::approved()->where('rating', '>=', 4)->count();
        $totalReviews = Review::approved()->count();
        $clinicalOutcomes = $totalReviews > 0 ? round(($positiveReviews / $totalReviews) * 100, 1) : 0;
        $lastMonthOutcomes = $totalReviews > 0 ? round(($positiveReviews / $totalReviews) * 100, 1) : 0;
        $outcomesChange = round($clinicalOutcomes - $lastMonthOutcomes, 1);
        $outcomesTarget = 90.0;

        // Generate revenue trend for last 6 months
        $revenueLabels = [];
        $revenueData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenueLabels[] = $month->format('M');
            $monthRevenue = StripeInvoice::where('status', 'paid')
                ->whereYear('paid_at', $month->year)
                ->whereMonth('paid_at', $month->month)
                ->sum('amount_paid');
            $revenueData[] = round($monthRevenue, 2);
        }

        // Patient satisfaction distribution
        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingDistribution[] = Review::approved()->where('rating', $i)->count();
        }

        // Get active alerts
        $alerts = Alert::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->severity ?? 'info',
                    'message' => $alert->message ?? $alert->title ?? 'System alert',
                    'metric' => $alert->metric_type ?? 'general',
                    'threshold' => $alert->threshold_value,
                    'current_value' => $alert->current_value
                ];
            });

        $data = [
            'summary' => [
                'revenue' => [
                    'value' => round($currentRevenue, 2),
                    'change' => $revenueChange,
                    'trend' => $revenueChange >= 0 ? 'up' : 'down',
                    'target' => $revenueTarget
                ],
                'patient_satisfaction' => [
                    'value' => round($currentSatisfaction, 1),
                    'change' => $satisfactionChange,
                    'trend' => $satisfactionChange >= 0 ? 'up' : 'down',
                    'target' => $satisfactionTarget
                ],
                'operational_efficiency' => [
                    'value' => $operationalEfficiency,
                    'change' => $efficiencyChange,
                    'trend' => $efficiencyChange >= 0 ? 'up' : 'down',
                    'target' => $efficiencyTarget
                ],
                'clinical_outcomes' => [
                    'value' => $clinicalOutcomes,
                    'change' => $outcomesChange,
                    'trend' => $outcomesChange >= 0 ? 'up' : 'down',
                    'target' => $outcomesTarget
                ]
            ],
            'charts' => [
                'revenue_trend' => [
                    'labels' => $revenueLabels,
                    'data' => $revenueData
                ],
                'patient_satisfaction_distribution' => [
                    'labels' => ['1★', '2★', '3★', '4★', '5★'],
                    'data' => $ratingDistribution
                ]
            ],
            'alerts' => $alerts->isEmpty() ? [
                [
                    'id' => 'alert_placeholder',
                    'type' => 'info',
                    'message' => 'All systems operating normally',
                    'metric' => 'general',
                    'threshold' => null,
                    'current_value' => null
                ]
            ] : $alerts->toArray()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'last_updated' => now()->toISOString(),
                'data_freshness' => 'realtime',
                'permissions' => [
                    'read' => true,
                    'export' => $this->analyticsPermissions->canAccessFeature($user, 'export_data'),
                    'customize' => $this->analyticsPermissions->canAccessFeature($user, 'dashboard_customization')
                ]
            ]
        ]);
    }

    /**
     * Get revenue analytics data
     */
    public function getRevenueAnalytics(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->analyticsPermissions->canAccessDashboard($user, 'revenue')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied to revenue dashboard'
            ], 403);
        }

        if (!$this->analyticsPermissions->canViewKpi($user, 'revenue_metrics')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied to revenue metrics'
            ], 403);
        }

        // Calculate MRR (Monthly Recurring Revenue) from active subscriptions
        // Current MRR: active subscriptions that weren't canceled this month
        $currentMRR = Subscription::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('canceled_at')
                    ->orWhere('canceled_at', '>', Carbon::now()->startOfMonth());
            })
            ->sum('amount');

        // Last month MRR: subscriptions that were active at the start of last month
        $lastMonthMRR = Subscription::where('status', 'active')
            ->where(function ($q) {
                $q->where('created_at', '<', Carbon::now()->subMonth()->startOfMonth())
                    ->where(function ($inner) {
                        $inner->whereNull('canceled_at')
                            ->orWhere('canceled_at', '>', Carbon::now()->subMonth()->startOfMonth());
                    });
            })
            ->sum('amount');

        $mrrChange = $lastMonthMRR > 0 ? round((($currentMRR - $lastMonthMRR) / $lastMonthMRR) * 100, 1) : 0;

        // Calculate ARPU (Average Revenue Per User)
        $totalActiveUsers = Subscription::where('status', 'active')->count();
        $arpu = $totalActiveUsers > 0 ? round($currentMRR / $totalActiveUsers, 2) : 0;

        // Last month active users count
        $lastMonthUsers = Subscription::where('status', 'active')
            ->where(function ($q) {
                $q->where('created_at', '<', Carbon::now()->subMonth()->startOfMonth())
                    ->where(function ($inner) {
                        $inner->whereNull('canceled_at')
                            ->orWhere('canceled_at', '>', Carbon::now()->subMonth()->startOfMonth());
                    });
            })
            ->count();

        $lastMonthARPU = $lastMonthMRR > 0 && $lastMonthUsers > 0
            ? round($lastMonthMRR / $lastMonthUsers, 2) : 0;
        $arpuChange = $lastMonthARPU > 0 ? round((($arpu - $lastMonthARPU) / $lastMonthARPU) * 100, 1) : 0;

        // Calculate churn rate
        $totalSubscribers = Subscription::count();
        $canceledThisMonth = Subscription::where('status', 'canceled')
            ->whereMonth('canceled_at', Carbon::now()->month)
            ->whereYear('canceled_at', Carbon::now()->year)
            ->count();
        $churnRate = $totalSubscribers > 0 ? round(($canceledThisMonth / $totalSubscribers) * 100, 1) : 0;

        $canceledLastMonth = Subscription::where('status', 'canceled')
            ->whereMonth('canceled_at', Carbon::now()->subMonth()->month)
            ->whereYear('canceled_at', Carbon::now()->subMonth()->year)
            ->count();
        $lastMonthChurnRate = $totalSubscribers > 0 ? round(($canceledLastMonth / $totalSubscribers) * 100, 1) : 0;
        $churnChange = round($churnRate - $lastMonthChurnRate, 1);

        // Calculate CLV (Customer Lifetime Value) - simplified: ARPU * 12 * avg subscription length
        $avgSubscriptionMonths = max(1, Subscription::where('status', 'canceled')
            ->whereNotNull('canceled_at')
            ->selectRaw('AVG(DATEDIFF(canceled_at, created_at)) as avg_months')
            ->value('avg_months') ?? 12);
        $clv = round($arpu * $avgSubscriptionMonths, 2);
        $lastMonthCLV = round($lastMonthARPU * $avgSubscriptionMonths, 2);
        $clvChange = $lastMonthCLV > 0 ? round((($clv - $lastMonthCLV) / $lastMonthCLV) * 100, 1) : 0;

        // Revenue breakdown by plan
        $breakdownByPlan = [];
        $plans = Subscription::select('plan_name', DB::raw('SUM(amount) as total_revenue, COUNT(*) as count'))
            ->where('status', 'active')
            ->groupBy('plan_name')
            ->get();

        $planTotal = $breakdownByPlan && $breakdownByPlan->sum('total_revenue') > 0
            ? $breakdownByPlan->sum('total_revenue') : 1;

        foreach ($plans as $plan) {
            $breakdownByPlan[] = [
                'plan' => $plan->plan_name ?? 'Custom',
                'revenue' => round($plan->total_revenue, 2),
                'percentage' => $planTotal > 0 ? round(($plan->total_revenue / $planTotal) * 100, 1) : 0
            ];
        }

        // Forecast next month based on trend
        $last6MonthsRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $last6MonthsRevenue[] = StripeInvoice::where('status', 'paid')
                ->whereYear('paid_at', $month->year)
                ->whereMonth('paid_at', $month->month)
                ->sum('amount_paid');
        }

        // Simple linear forecast
        $n = count($last6MonthsRevenue);
        if ($n >= 2) {
            $sumX = array_sum(array_keys($last6MonthsRevenue));
            $sumY = array_sum($last6MonthsRevenue);
            $sumXY = 0;
            $sumX2 = 0;
            foreach ($last6MonthsRevenue as $x => $y) {
                $sumXY += $x * $y;
                $sumX2 += $x * $x;
            }
            $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
            $intercept = ($sumY - $slope * $sumX) / $n;
            $nextMonthForecast = max(0, $intercept + $slope * $n);

            // Calculate confidence based on variance
            $mean = $sumY / $n;
            $variance = array_reduce($last6MonthsRevenue, function($carry, $val) use ($mean) {
                return $carry + pow($val - $mean, 2);
            }, 0) / $n;
            $stdDev = sqrt($variance);
            $confidence = max(50, min(95, 100 - ($stdDev / max($mean, 1) * 100)));
        } else {
            $nextMonthForecast = $currentMRR;
            $confidence = 50;
        }

        $data = [
            'kpis' => [
                'mrr' => ['value' => round($currentMRR, 2), 'change' => $mrrChange],
                'arpu' => ['value' => $arpu, 'change' => $arpuChange],
                'churn_rate' => ['value' => $churnRate, 'change' => $churnChange],
                'clv' => ['value' => $clv, 'change' => $clvChange]
            ],
            'breakdown' => [
                'by_plan' => $breakdownByPlan ?: [
                    ['plan' => 'No Active Plans', 'revenue' => 0, 'percentage' => 0]
                ]
            ],
            'forecast' => [
                'next_month' => round($nextMonthForecast, 2),
                'confidence' => round($confidence, 0),
                'trend' => $mrrChange >= 0 ? 'up' : 'down'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Get patient satisfaction metrics
     */
    public function getPatientSatisfaction(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->analyticsPermissions->canViewKpi($user, 'patient_satisfaction')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied to patient satisfaction metrics'
            ], 403);
        }

        // Get overall satisfaction metrics
        $totalReviews = Review::approved()->count();
        $avgSatisfaction = Review::approved()->avg('rating') ?? 0;

        // Calculate NPS (Net Promoter Score) - percentage of 5-star minus percentage of 1-2-star
        $promoters = Review::approved()->where('rating', 5)->count();
        $detractors = Review::approved()->whereIn('rating', [1, 2])->count();
        $passives = Review::approved()->where('rating', 3)->count();

        $nps = $totalReviews > 0
            ? round((($promoters - $detractors) / $totalReviews) * 100, 1)
            : 0;

        // Response rate calculation (reviews received vs appointments completed)
        $totalAppointments = Appointment::where('status', 'completed')->count();
        $responseRate = $totalAppointments > 0
            ? round(($totalReviews / $totalAppointments) * 100, 1)
            : 0;

        // Satisfaction by department (via doctors)
        $byDepartment = [];
        $departments = DB::table('reviews')
            ->join('doctors', 'reviews.doctor_id', '=', 'doctors.id')
            ->join('departments', 'doctors.department_id', '=', 'departments.id')
            ->where('reviews.is_approved', true)
            ->groupBy('departments.id', 'departments.name')
            ->select(
                'departments.name as department',
                DB::raw('AVG(reviews.rating) as satisfaction'),
                DB::raw('COUNT(reviews.id) as response_count')
            )
            ->orderBy('satisfaction', 'desc')
            ->limit(10)
            ->get();

        foreach ($departments as $dept) {
            $byDepartment[] = [
                'department' => $dept->department,
                'satisfaction' => round($dept->satisfaction, 1),
                'response_count' => (int) $dept->response_count
            ];
        }

        // Weekly satisfaction trends for last 4 weeks
        $trendLabels = [];
        $trendData = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();

            $trendLabels[] = 'Week ' . (4 - $i);
            $weekAvg = Review::approved()
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->avg('rating');
            $trendData[] = $weekAvg ? round($weekAvg, 1) : 0;
        }

        $data = [
            'overall' => [
                'nps' => $nps,
                'satisfaction_score' => round($avgSatisfaction, 1),
                'response_rate' => $responseRate
            ],
            'by_department' => $byDepartment ?: [
                ['department' => 'No Data', 'satisfaction' => 0, 'response_count' => 0]
            ],
            'trends' => [
                'labels' => $trendLabels,
                'data' => $trendData
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Export dashboard data
     */
    public function exportDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $dashboard = $request->input('dashboard', 'executive');

        // Check export permission
        if (!$this->analyticsPermissions->canAccessFeature($user, 'export_data')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Export permission denied'
            ], 403);
        }

        // Check dashboard access
        if (!$this->analyticsPermissions->canAccessDashboard($user, $dashboard)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dashboard access denied'
            ], 403);
        }

        // In real implementation, this would queue an export job
        return response()->json([
            'status' => 'success',
            'data' => [
                'export_id' => 'export_' . uniqid(),
                'status' => 'processing',
                'estimated_completion' => now()->addMinutes(5)->toISOString(),
                'download_url' => '/api/analytics/export/download/export_' . uniqid()
            ]
        ]);
    }

    /**
     * Get user's available dashboards and permissions
     */
    public function getUserPermissions(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_analytics_access' => $this->analyticsPermissions->hasAnalyticsAccess($user),
                'role_name' => $this->analyticsPermissions->getUserRoleName($user),
                'hierarchy_level' => $this->analyticsPermissions->getUserHierarchyLevel($user),
                'available_dashboards' => $this->analyticsPermissions->getAvailableDashboards($user),
                'available_features' => $this->analyticsPermissions->getAvailableFeatures($user),
                'available_kpi_categories' => $this->analyticsPermissions->getAvailableKpiCategories($user),
            ]
        ]);
    }

    /**
     * Apply data filtering to a query (helper method for other controllers)
     */
    protected function applyDataScopeFilter(Builder $query, string $dashboardName = null): Builder
    {
        $user = request()->user();
        return $this->analyticsPermissions->applyDataFilter($user, $query, $dashboardName);
    }
}
