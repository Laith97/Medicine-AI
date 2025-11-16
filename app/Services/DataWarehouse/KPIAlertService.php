<?php

namespace App\Services\DataWarehouse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Collection;

class KPIAlertService
{
    protected $cacheTtl = 300; // 5 minutes cache for alerts

    // Default alert thresholds (configurable)
    protected $defaultThresholds = [
        'patient_satisfaction_score' => [
            'critical_low' => 3.0,
            'warning_low' => 3.5,
            'warning_high' => 4.5,
            'critical_high' => 5.0
        ],
        'appointment_show_up_rate' => [
            'critical_low' => 70.0,
            'warning_low' => 75.0,
            'warning_high' => 90.0,
            'critical_high' => 95.0
        ],
        'average_wait_time_minutes' => [
            'critical_low' => 5.0,
            'warning_low' => 10.0,
            'warning_high' => 25.0,
            'critical_high' => 30.0
        ],
        'total_revenue' => [
            'critical_low' => 50000, // Monthly threshold
            'warning_low' => 75000,
            'warning_high' => 200000,
            'critical_high' => 250000
        ],
        'readmission_rate_30_days' => [
            'critical_low' => 5.0,
            'warning_low' => 8.0,
            'warning_high' => 15.0,
            'critical_high' => 20.0
        ],
        'provider_utilization_rate' => [
            'critical_low' => 60.0,
            'warning_low' => 70.0,
            'warning_high' => 90.0,
            'critical_high' => 95.0
        ]
    ];

    /**
     * Check all KPIs for threshold violations and generate alerts
     */
    public function checkAllKPIsForAlerts($hospitalKey = 1)
    {
        $cacheKey = "kpi_alerts_{$hospitalKey}";
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($hospitalKey) {
            return $this->performAlertCheck($hospitalKey);
        });
    }

    /**
     * Perform comprehensive alert checking
     */
    private function performAlertCheck($hospitalKey)
    {
        $alerts = [];
        $currentMetrics = $this->getCurrentKPIMetrics($hospitalKey);
        $thresholds = $this->getConfiguredThresholds($hospitalKey);

        foreach ($thresholds as $kpiName => $kpiThresholds) {
            if (isset($currentMetrics[$kpiName])) {
                $alert = $this->checkKPIThresholds(
                    $kpiName,
                    $currentMetrics[$kpiName],
                    $kpiThresholds,
                    $hospitalKey
                );

                if ($alert) {
                    $alerts[] = $alert;
                }
            }
        }

        // Process alerts (send notifications, log, etc.)
        $this->processAlerts($alerts, $hospitalKey);

        return [
            'alerts_generated' => count($alerts),
            'alerts' => $alerts,
            'checked_at' => now(),
            'hospital_key' => $hospitalKey
        ];
    }

    /**
     * Check specific KPI against its thresholds
     */
    private function checkKPIThresholds($kpiName, $currentValue, $thresholds, $hospitalKey)
    {
        if ($currentValue === null) {
            return null;
        }

        $alertLevel = $this->determineAlertLevel($currentValue, $thresholds, $kpiName);

        if ($alertLevel === 'normal') {
            return null;
        }

        // Check if this alert was already triggered recently (avoid spam)
        if ($this->isAlertAlreadyTriggered($kpiName, $alertLevel, $hospitalKey)) {
            return null;
        }

        return [
            'kpi_name' => $kpiName,
            'current_value' => $currentValue,
            'alert_level' => $alertLevel,
            'threshold_breached' => $this->getBreachedThreshold($currentValue, $thresholds, $kpiName),
            'trend_context' => $this->getTrendContext($kpiName, $hospitalKey),
            'recommended_actions' => $this->getRecommendedActions($kpiName, $alertLevel),
            'generated_at' => now(),
            'hospital_key' => $hospitalKey,
            'alert_id' => uniqid('alert_', true)
        ];
    }

    /**
     * Determine alert level based on value and thresholds
     */
    private function determineAlertLevel($value, $thresholds, $kpiName)
    {
        // Determine if higher or lower values are concerning
        $higherIsBetter = $this->isHigherBetter($kpiName);

        if ($higherIsBetter) {
            if ($value <= $thresholds['critical_low']) return 'critical';
            if ($value <= $thresholds['warning_low']) return 'warning';
            if ($value >= $thresholds['critical_high']) return 'critical';
            if ($value >= $thresholds['warning_high']) return 'warning';
        } else {
            if ($value >= $thresholds['critical_high']) return 'critical';
            if ($value >= $thresholds['warning_high']) return 'warning';
            if ($value <= $thresholds['critical_low']) return 'critical';
            if ($value <= $thresholds['warning_low']) return 'warning';
        }

        return 'normal';
    }

    /**
     * Check if higher values are better for this KPI
     */
    private function isHigherBetter($kpiName)
    {
        $lowerIsBetter = [
            'average_wait_time_minutes',
            'readmission_rate_30_days',
            'no_show_appointments',
            'cancelled_appointments'
        ];

        return !in_array($kpiName, $lowerIsBetter);
    }

    /**
     * Get which threshold was breached
     */
    private function getBreachedThreshold($value, $thresholds, $kpiName)
    {
        $higherIsBetter = $this->isHigherBetter($kpiName);

        if ($higherIsBetter) {
            if ($value <= $thresholds['critical_low']) return 'critical_low';
            if ($value <= $thresholds['warning_low']) return 'warning_low';
            if ($value >= $thresholds['critical_high']) return 'critical_high';
            if ($value >= $thresholds['warning_high']) return 'warning_high';
        } else {
            if ($value >= $thresholds['critical_high']) return 'critical_high';
            if ($value >= $thresholds['warning_high']) return 'warning_high';
            if ($value <= $thresholds['critical_low']) return 'critical_low';
            if ($value <= $thresholds['warning_low']) return 'warning_low';
        }

        return null;
    }

    /**
     * Check if alert was already triggered recently
     */
    private function isAlertAlreadyTriggered($kpiName, $alertLevel, $hospitalKey)
    {
        $recentAlert = DB::table('kpi_alerts')
            ->where('kpi_name', $kpiName)
            ->where('alert_level', $alertLevel)
            ->where('hospital_key', $hospitalKey)
            ->where('created_at', '>=', Carbon::now()->subHours(4))
            ->first();

        return $recentAlert !== null;
    }

    /**
     * Get trend context for the alert
     */
    private function getTrendContext($kpiName, $hospitalKey)
    {
        $trendService = app(TrendAnalysisService::class);
        $trend = $trendService->calculateTrend($kpiName, 7, $hospitalKey); // Last 7 days

        return [
            'direction' => $trend['trend_direction'],
            'strength' => $trend['trend_strength'],
            'change_percentage' => $trend['change_percentage'],
            'volatility' => $trend['volatility']
        ];
    }

    /**
     * Get recommended actions for the alert
     */
    private function getRecommendedActions($kpiName, $alertLevel)
    {
        $actions = [
            'patient_satisfaction_score' => [
                'critical' => ['Immediate patient feedback review', 'Staff training program', 'Process improvement audit'],
                'warning' => ['Monitor patient feedback trends', 'Staff coaching sessions', 'Patient communication enhancement']
            ],
            'appointment_show_up_rate' => [
                'critical' => ['Implement reminder system upgrade', 'Patient education campaign', 'Appointment scheduling review'],
                'warning' => ['Enhance reminder communications', 'Analyze no-show patterns', 'Staff training on patient engagement']
            ],
            'average_wait_time_minutes' => [
                'critical' => ['Immediate scheduling adjustments', 'Staffing level review', 'Process bottleneck analysis'],
                'warning' => ['Monitor wait time trends', 'Optimize appointment scheduling', 'Patient flow improvements']
            ],
            'total_revenue' => [
                'critical' => ['Revenue analysis and forecasting', 'Pricing strategy review', 'Patient acquisition assessment'],
                'warning' => ['Revenue trend monitoring', 'Cost optimization review', 'Service utilization analysis']
            ]
        ];

        return $actions[$kpiName][$alertLevel] ?? ['Review KPI performance and implement corrective actions'];
    }

    /**
     * Process alerts (send notifications, log, store)
     */
    private function processAlerts(array $alerts, $hospitalKey)
    {
        foreach ($alerts as $alert) {
            // Store alert in database
            $alertId = DB::table('kpi_alerts')->insertGetId([
                'alert_id' => $alert['alert_id'],
                'kpi_name' => $alert['kpi_name'],
                'alert_level' => $alert['alert_level'],
                'current_value' => $alert['current_value'],
                'threshold_breached' => $alert['threshold_breached'],
                'hospital_key' => $hospitalKey,
                'trend_context' => json_encode($alert['trend_context']),
                'recommended_actions' => json_encode($alert['recommended_actions']),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Fire real-time alert event
            $this->fireRealtimeAlertEvent($alert, $hospitalKey);

            // Send notifications
            $this->sendAlertNotifications($alert, $hospitalKey);

            // Log alert
            Log::channel('kpi_alerts')->info('KPI Alert Generated', [
                'alert_id' => $alert['alert_id'],
                'kpi' => $alert['kpi_name'],
                'level' => $alert['alert_level'],
                'value' => $alert['current_value'],
                'hospital' => $hospitalKey
            ]);
        }
    }

    /**
     * Fire real-time alert event
     */
    private function fireRealtimeAlertEvent($alert, $hospitalKey)
    {
        try {
            // Add recipients to alert data for broadcasting
            $alertData = $alert;
            $alertData['recipients'] = $this->getAlertRecipients($alert['alert_level'], $hospitalKey)->pluck('id')->toArray();

            // Fire Laravel event (will be handled by listeners)
            event(new \App\Events\KPIAlertTriggered($alertData, $hospitalKey));

        } catch (\Exception $e) {
            Log::error('Failed to fire real-time alert event', [
                'alert_id' => $alert['alert_id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send alert notifications to relevant users
     */
    private function sendAlertNotifications($alert, $hospitalKey)
    {
        $recipients = $this->getAlertRecipients($alert['alert_level'], $hospitalKey);

        foreach ($recipients as $recipient) {
            // Here you would send actual notifications (email, SMS, etc.)
            // For now, we'll just log the notification
            Log::info('Alert notification would be sent', [
                'recipient' => $recipient->email,
                'alert' => $alert['kpi_name'],
                'level' => $alert['alert_level']
            ]);
        }
    }

    /**
     * Get recipients for alert notifications
     */
    private function getAlertRecipients($alertLevel, $hospitalKey)
    {
        // Get users based on role and alert level
        $roles = $this->getRolesForAlertLevel($alertLevel);

        return User::whereHas('roles', function($query) use ($roles) {
            $query->whereIn('name', $roles);
        })->get();
    }

    /**
     * Get roles that should receive alerts for each level
     */
    private function getRolesForAlertLevel($alertLevel)
    {
        switch ($alertLevel) {
            case 'critical':
                return ['admin', 'hospital_admin', 'manager'];
            case 'warning':
                return ['admin', 'hospital_admin', 'manager', 'supervisor'];
            default:
                return ['admin', 'hospital_admin'];
        }
    }

    /**
     * Get current KPI metrics for alert checking
     */
    private function getCurrentKPIMetrics($hospitalKey)
    {
        $calculationService = app(KPICalculationService::class);
        return $calculationService->calculateDailyKPIs(null, $hospitalKey);
    }

    /**
     * Get configured thresholds (from database or defaults)
     */
    private function getConfiguredThresholds($hospitalKey)
    {
        // Try to get from database first, fallback to defaults
        $configured = DB::table('kpi_thresholds')
            ->where('hospital_key', $hospitalKey)
            ->where('is_active', true)
            ->pluck('thresholds', 'kpi_name')
            ->toArray();

        $thresholds = [];
        foreach ($this->defaultThresholds as $kpiName => $defaultThreshold) {
            $thresholds[$kpiName] = isset($configured[$kpiName])
                ? json_decode($configured[$kpiName], true)
                : $defaultThreshold;
        }

        return $thresholds;
    }

    /**
     * Update KPI thresholds
     */
    public function updateThresholds($hospitalKey, array $newThresholds)
    {
        foreach ($newThresholds as $kpiName => $thresholds) {
            DB::table('kpi_thresholds')->updateOrInsert(
                [
                    'hospital_key' => $hospitalKey,
                    'kpi_name' => $kpiName
                ],
                [
                    'thresholds' => json_encode($thresholds),
                    'is_active' => true,
                    'updated_at' => now()
                ]
            );
        }

        Cache::forget("kpi_alerts_{$hospitalKey}");
        return true;
    }

    /**
     * Get active alerts for a hospital
     */
    public function getActiveAlerts($hospitalKey, $limit = 50)
    {
        return DB::table('kpi_alerts')
            ->where('hospital_key', $hospitalKey)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($alert) {
                $alert->trend_context = json_decode($alert->trend_context, true);
                $alert->recommended_actions = json_decode($alert->recommended_actions, true);
                return $alert;
            });
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledgeAlert($alertId, $userId)
    {
        return DB::table('kpi_alerts')
            ->where('alert_id', $alertId)
            ->update([
                'status' => 'acknowledged',
                'acknowledged_by' => $userId,
                'acknowledged_at' => now(),
                'updated_at' => now()
            ]);
    }

    /**
     * Resolve an alert
     */
    public function resolveAlert($alertId, $userId, $resolution = null)
    {
        return DB::table('kpi_alerts')
            ->where('alert_id', $alertId)
            ->update([
                'status' => 'resolved',
                'resolved_by' => $userId,
                'resolved_at' => now(),
                'resolution' => $resolution,
                'updated_at' => now()
            ]);
    }

    /**
     * Get alert statistics
     */
    public function getAlertStatistics($hospitalKey, $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        return DB::table('kpi_alerts')
            ->where('hospital_key', $hospitalKey)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                alert_level,
                COUNT(*) as count,
                AVG(current_value) as avg_value
            ')
            ->groupBy('alert_level')
            ->get()
            ->keyBy('alert_level');
    }
}
