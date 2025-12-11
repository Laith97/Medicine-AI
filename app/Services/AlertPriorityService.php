<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AlertPriorityService
{
    /**
     * Calculate priority score for an alert
     */
    public function calculatePriorityScore(Alert $alert): float
    {
        $score = 0.0;

        // Base severity score (0-40 points)
        $score += $this->getSeverityScore($alert->severity);

        // Rule priority score (0-20 points)
        $score += $this->getRulePriorityScore($alert->alertRule);

        // Event frequency score (0-15 points)
        $score += $this->getEventFrequencyScore($alert);

        // Business impact score (0-15 points)
        $score += $this->getBusinessImpactScore($alert);

        // Time sensitivity score (0-10 points)
        $score += $this->getTimeSensitivityScore($alert);

        // Ensure score is between 0 and 100
        return min(100.0, max(0.0, $score));
    }

    /**
     * Get severity score based on alert severity
     */
    protected function getSeverityScore(string $severity): float
    {
        return match ($severity) {
            'critical' => 40.0,
            'high' => 30.0,
            'medium' => 20.0,
            'low' => 10.0,
            'info' => 5.0,
            default => 15.0,
        };
    }

    /**
     * Get rule priority score
     */
    protected function getRulePriorityScore(AlertRule $rule): float
    {
        // Convert priority (1-10) to score (0-20)
        return ($rule->priority / 10.0) * 20.0;
    }

    /**
     * Get event frequency score - higher score for more frequent similar events
     */
    protected function getEventFrequencyScore(Alert $alert): float
    {
        $cacheKey = "alert_frequency_{$alert->event_type}_{$alert->model_type}";

        $frequency = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($alert) {
            // Count similar alerts in the last hour
            return Alert::where('event_type', $alert->event_type)
                ->where('model_type', $alert->model_type)
                ->where('created_at', '>=', now()->subHour())
                ->count();
        });

        // Score based on frequency: 0-1 = 0, 2-5 = 5, 6-10 = 10, 11+ = 15
        if ($frequency <= 1) return 0.0;
        if ($frequency <= 5) return 5.0;
        if ($frequency <= 10) return 10.0;
        return 15.0;
    }

    /**
     * Get business impact score based on model type and event data
     */
    protected function getBusinessImpactScore(Alert $alert): float
    {
        $score = 0.0;

        // Model type impact
        $score += $this->getModelTypeImpact($alert->model_type);

        // Event data impact
        $score += $this->getEventDataImpact($alert->event_data);

        return min(15.0, $score);
    }

    /**
     * Get impact score based on model type
     */
    protected function getModelTypeImpact(?string $modelType): float
    {
        if (!$modelType) return 0.0;

        return match ($modelType) {
            'App\Models\Claim' => 8.0,      // High financial impact
            'App\Models\Prescription' => 7.0, // Patient safety impact
            'App\Models\Diagnosis' => 6.0,   // Clinical impact
            'App\Models\User' => 5.0,        // User management impact
            'App\Models\Appointment' => 4.0, // Operational impact
            default => 2.0,
        };
    }

    /**
     * Get impact score based on event data
     */
    protected function getEventDataImpact(array $eventData): float
    {
        $score = 0.0;

        // Financial impact indicators
        if (isset($eventData['amount']) && $eventData['amount'] > 1000) {
            $score += 3.0;
        }

        // Patient safety indicators
        if (isset($eventData['severity']) && in_array($eventData['severity'], ['critical', 'high'])) {
            $score += 4.0;
        }

        // Regulatory compliance indicators
        if (isset($eventData['regulation_type']) || isset($eventData['compliance_violation'])) {
            $score += 3.0;
        }

        // Time-sensitive indicators
        if (isset($eventData['deadline']) || isset($eventData['expires_at'])) {
            $score += 2.0;
        }

        return $score;
    }

    /**
     * Get time sensitivity score
     */
    protected function getTimeSensitivityScore(Alert $alert): float
    {
        $score = 0.0;

        // Age of alert (newer = higher score)
        $ageInMinutes = $alert->created_at->diffInMinutes(now());
        if ($ageInMinutes < 5) $score += 3.0;
        elseif ($ageInMinutes < 15) $score += 2.0;
        elseif ($ageInMinutes < 60) $score += 1.0;

        // Escalation urgency
        if ($alert->next_escalation_at && $alert->next_escalation_at->isPast()) {
            $score += 4.0; // Overdue for escalation
        } elseif ($alert->next_escalation_at) {
            $minutesUntilEscalation = $alert->getTimeUntilEscalation();
            if ($minutesUntilEscalation !== null && $minutesUntilEscalation < 30) {
                $score += 3.0; // Escalation soon
            }
        }

        // Business hours consideration
        if (!$this->isBusinessHours()) {
            $score += 2.0; // Higher priority outside business hours
        }

        return min(10.0, $score);
    }

    /**
     * Check if current time is within business hours
     */
    protected function isBusinessHours(): bool
    {
        $now = now();
        $dayOfWeek = $now->dayOfWeek; // 0 = Sunday, 6 = Saturday
        $hour = $now->hour;

        // Monday-Friday, 9 AM - 5 PM
        return $dayOfWeek >= 1 && $dayOfWeek <= 5 && $hour >= 9 && $hour < 17;
    }

    /**
     * Update priority scores for multiple alerts
     */
    public function updatePriorityScores(array $alertIds): void
    {
        $alerts = Alert::whereIn('id', $alertIds)->get();

        foreach ($alerts as $alert) {
            try {
                $score = $this->calculatePriorityScore($alert);
                $alert->update(['priority_score' => $score]);
            } catch (\Exception $e) {
                Log::error('Failed to calculate priority score for alert', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get priority distribution statistics
     */
    public function getPriorityStatistics(): array
    {
        $stats = Alert::selectRaw('
            COUNT(*) as total,
            AVG(priority_score) as avg_score,
            MIN(priority_score) as min_score,
            MAX(priority_score) as max_score,
            COUNT(CASE WHEN priority_score >= 80 THEN 1 END) as critical_count,
            COUNT(CASE WHEN priority_score >= 60 AND priority_score < 80 THEN 1 END) as high_count,
            COUNT(CASE WHEN priority_score >= 40 AND priority_score < 60 THEN 1 END) as medium_count,
            COUNT(CASE WHEN priority_score >= 20 AND priority_score < 40 THEN 1 END) as low_count,
            COUNT(CASE WHEN priority_score < 20 THEN 1 END) as info_count
        ')->first();

        return [
            'total_alerts' => $stats->total ?? 0,
            'average_score' => round($stats->avg_score ?? 0, 2),
            'min_score' => $stats->min_score ?? 0,
            'max_score' => $stats->max_score ?? 0,
            'distribution' => [
                'critical' => $stats->critical_count ?? 0,
                'high' => $stats->high_count ?? 0,
                'medium' => $stats->medium_count ?? 0,
                'low' => $stats->low_count ?? 0,
                'info' => $stats->info_count ?? 0,
            ],
        ];
    }

    /**
     * Train priority model (placeholder for future ML implementation)
     */
    public function trainPriorityModel(): void
    {
        // This would implement actual ML training in the future
        // For now, we'll use rule-based scoring

        Log::info('Alert priority model training completed (rule-based)');
    }

    /**
     * Get priority recommendations for alert handling
     */
    public function getPriorityRecommendations(Alert $alert): array
    {
        $recommendations = [];

        if ($alert->priority_score >= 80) {
            $recommendations[] = 'Immediate attention required - escalate to senior management';
        } elseif ($alert->priority_score >= 60) {
            $recommendations[] = 'High priority - assign to specialist within 1 hour';
        } elseif ($alert->priority_score >= 40) {
            $recommendations[] = 'Medium priority - review within 4 hours';
        } elseif ($alert->priority_score >= 20) {
            $recommendations[] = 'Low priority - review within 24 hours';
        } else {
            $recommendations[] = 'Info level - monitor for patterns';
        }

        // Time-based recommendations
        if ($alert->isOverdueForEscalation()) {
            $recommendations[] = 'Overdue for escalation - immediate action required';
        }

        // Business hours consideration
        if (!$this->isBusinessHours() && $alert->priority_score >= 60) {
            $recommendations[] = 'After-hours alert - consider on-call escalation';
        }

        return $recommendations;
    }
}
