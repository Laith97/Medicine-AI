<?php

namespace App\Services;

use App\Models\PayerRule;
use App\Models\RuleApplication;
use App\Models\Claim;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RuleEffectivenessTrackingService
{
    /**
     * Calculate rule effectiveness metrics for a given period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $payerId
     * @return array
     */
    public function calculateRuleEffectiveness(Carbon $startDate, Carbon $endDate, ?int $payerId = null): array
    {
        $query = RuleApplication::whereBetween('applied_at', [$startDate, $endDate]);

        if ($payerId) {
            $query->whereHas('rule', function ($q) use ($payerId) {
                $q->where('payer_id', $payerId);
            });
        }

        $applications = $query->with(['rule.ruleType', 'claim'])->get();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => $this->calculateSummaryMetrics($applications),
            'rule_performance' => $this->calculateRulePerformanceMetrics($applications),
            'outcome_analysis' => $this->calculateOutcomeAnalysis($applications),
            'compliance_metrics' => $this->calculateComplianceMetrics($applications),
            'trend_analysis' => $this->calculateTrendAnalysis($applications, $startDate, $endDate),
        ];
    }

    /**
     * Calculate summary metrics.
     */
    protected function calculateSummaryMetrics(Collection $applications): array
    {
        $totalApplications = $applications->count();
        $triggeredRules = $applications->where('rule_triggered', true)->count();
        $uniqueRules = $applications->pluck('rule_id')->unique()->count();
        $uniqueClaims = $applications->pluck('claim_id')->unique()->count();

        return [
            'total_applications' => $totalApplications,
            'triggered_rules' => $triggeredRules,
            'trigger_rate' => $totalApplications > 0 ? round(($triggeredRules / $totalApplications) * 100, 2) : 0,
            'unique_rules_used' => $uniqueRules,
            'unique_claims_processed' => $uniqueClaims,
            'avg_applications_per_claim' => $uniqueClaims > 0 ? round($totalApplications / $uniqueClaims, 2) : 0,
        ];
    }

    /**
     * Calculate performance metrics for individual rules.
     */
    protected function calculateRulePerformanceMetrics(Collection $applications): array
    {
        $ruleMetrics = [];

        $applications->groupBy('rule_id')->each(function ($ruleApps, $ruleId) use (&$ruleMetrics) {
            $rule = $ruleApps->first()->rule;

            $totalApps = $ruleApps->count();
            $triggeredApps = $ruleApps->where('rule_triggered', true)->count();
            $avgExecutionTime = $ruleApps->whereNotNull('execution_time_ms')->avg('execution_time_ms');

            $outcomeBreakdown = $ruleApps->where('rule_triggered', true)
                ->groupBy('outcome_status')
                ->map->count()
                ->toArray();

            $ruleMetrics[] = [
                'rule_id' => $ruleId,
                'rule_name' => $rule->ruleType->name ?? 'Unknown',
                'payer_name' => $rule->payer->name ?? 'Unknown',
                'total_applications' => $totalApps,
                'triggered_applications' => $triggeredApps,
                'trigger_rate' => $totalApps > 0 ? round(($triggeredApps / $totalApps) * 100, 2) : 0,
                'avg_execution_time_ms' => round($avgExecutionTime ?? 0, 2),
                'outcome_breakdown' => $outcomeBreakdown,
                'effectiveness_score' => $this->calculateRuleEffectivenessScore($ruleApps),
            ];
        });

        // Sort by effectiveness score descending
        usort($ruleMetrics, function ($a, $b) {
            return $b['effectiveness_score'] <=> $a['effectiveness_score'];
        });

        return $ruleMetrics;
    }

    /**
     * Calculate outcome analysis.
     */
    protected function calculateOutcomeAnalysis(Collection $applications): array
    {
        $triggeredApps = $applications->where('rule_triggered', true);
        $totalTriggered = $triggeredApps->count();

        $outcomeStats = $triggeredApps->groupBy('outcome_status')->map(function ($group, $status) use ($totalTriggered) {
            return [
                'outcome' => $status,
                'count' => $group->count(),
                'percentage' => $totalTriggered > 0 ? round(($group->count() / $totalTriggered) * 100, 2) : 0,
                'avg_execution_time' => round($group->whereNotNull('execution_time_ms')->avg('execution_time_ms') ?? 0, 2),
            ];
        })->values()->toArray();

        return [
            'outcome_distribution' => $outcomeStats,
            'most_common_outcome' => collect($outcomeStats)->sortByDesc('count')->first()['outcome'] ?? null,
            'denial_rate' => $this->calculateDenialRate($triggeredApps),
            'warning_rate' => $this->calculateWarningRate($triggeredApps),
            'auto_correction_rate' => $this->calculateAutoCorrectionRate($triggeredApps),
        ];
    }

    /**
     * Calculate compliance metrics.
     */
    protected function calculateComplianceMetrics(Collection $applications): array
    {
        $hipaaFlagged = $applications->whereNotNull('hipaa_compliance_flags')->count();
        $pastRetention = $applications->filter->isRetentionExpired()->count();
        $userAcknowledged = $applications->where('user_acknowledged', true)->count();

        $dataClassificationBreakdown = $applications->groupBy('data_classification')->map->count()->toArray();

        return [
            'hipaa_compliance_rate' => $applications->count() > 0 ?
                round((($applications->count() - $hipaaFlagged) / $applications->count()) * 100, 2) : 100,
            'data_retention_compliance' => $applications->count() > 0 ?
                round((($applications->count() - $pastRetention) / $applications->count()) * 100, 2) : 100,
            'user_acknowledgment_rate' => $applications->where('rule_triggered', true)->count() > 0 ?
                round(($userAcknowledged / $applications->where('rule_triggered', true)->count()) * 100, 2) : 0,
            'data_classification_breakdown' => $dataClassificationBreakdown,
            'audit_completeness' => $this->calculateAuditCompleteness($applications),
        ];
    }

    /**
     * Calculate trend analysis over time.
     */
    protected function calculateTrendAnalysis(Collection $applications, Carbon $startDate, Carbon $endDate): array
    {
        $days = $startDate->diffInDays($endDate) + 1;
        $trends = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayApplications = $applications->whereBetween('applied_at', [
                $date->startOfDay(),
                $date->endOfDay()
            ]);

            $trends[] = [
                'date' => $date->toDateString(),
                'total_applications' => $dayApplications->count(),
                'triggered_rules' => $dayApplications->where('rule_triggered', true)->count(),
                'denials' => $dayApplications->where('outcome_status', 'denied')->count(),
                'warnings' => $dayApplications->where('outcome_status', 'warning')->count(),
                'auto_corrections' => $dayApplications->where('outcome_status', 'corrected')->count(),
            ];
        }

        return $trends;
    }

    /**
     * Calculate rule effectiveness score.
     */
    protected function calculateRuleEffectivenessScore(Collection $ruleApps): float
    {
        $totalApps = $ruleApps->count();
        if ($totalApps === 0) return 0;

        $triggeredApps = $ruleApps->where('rule_triggered', true)->count();
        $triggerRate = $triggeredApps / $totalApps;

        // Weight factors for effectiveness score
        $denialWeight = 0.4; // Denials are most important
        $warningWeight = 0.3; // Warnings are moderately important
        $correctionWeight = 0.2; // Auto-corrections are less important
        $performanceWeight = 0.1; // Performance is least important

        $denialRate = $ruleApps->where('outcome_status', 'denied')->count() / $totalApps;
        $warningRate = $ruleApps->where('outcome_status', 'warning')->count() / $totalApps;
        $correctionRate = $ruleApps->where('outcome_status', 'corrected')->count() / $totalApps;

        $avgExecutionTime = $ruleApps->whereNotNull('execution_time_ms')->avg('execution_time_ms') ?? 100;
        $performanceScore = max(0, 1 - ($avgExecutionTime / 1000)); // Better if faster than 1 second

        $effectivenessScore = (
            $triggerRate +
            ($denialRate * $denialWeight) +
            ($warningRate * $warningWeight) +
            ($correctionRate * $correctionWeight) +
            ($performanceScore * $performanceWeight)
        ) / (1 + $denialWeight + $warningWeight + $correctionWeight + $performanceWeight);

        return round($effectivenessScore * 100, 2);
    }

    /**
     * Calculate denial rate.
     */
    protected function calculateDenialRate(Collection $triggeredApps): float
    {
        $totalTriggered = $triggeredApps->count();
        if ($totalTriggered === 0) return 0;

        $denials = $triggeredApps->where('outcome_status', 'denied')->count();
        return round(($denials / $totalTriggered) * 100, 2);
    }

    /**
     * Calculate warning rate.
     */
    protected function calculateWarningRate(Collection $triggeredApps): float
    {
        $totalTriggered = $triggeredApps->count();
        if ($totalTriggered === 0) return 0;

        $warnings = $triggeredApps->where('outcome_status', 'warning')->count();
        return round(($warnings / $totalTriggered) * 100, 2);
    }

    /**
     * Calculate auto-correction rate.
     */
    protected function calculateAutoCorrectionRate(Collection $triggeredApps): float
    {
        $totalTriggered = $triggeredApps->count();
        if ($totalTriggered === 0) return 0;

        $corrections = $triggeredApps->where('outcome_status', 'corrected')->count();
        return round(($corrections / $totalTriggered) * 100, 2);
    }

    /**
     * Calculate audit completeness.
     */
    protected function calculateAuditCompleteness(Collection $applications): float
    {
        if ($applications->isEmpty()) return 100;

        $completeRecords = $applications->filter(function ($app) {
            return !empty($app->user_id) &&
                   !empty($app->ip_address) &&
                   !empty($app->applied_at) &&
                   !is_null($app->rule_triggered);
        })->count();

        return round(($completeRecords / $applications->count()) * 100, 2);
    }

    /**
     * Get top performing rules.
     */
    public function getTopPerformingRules(int $limit = 10, Carbon $startDate = null, Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $applications = RuleApplication::whereBetween('applied_at', [$startDate, $endDate])
            ->with(['rule.ruleType', 'rule.payer'])
            ->get();

        $ruleMetrics = collect($this->calculateRulePerformanceMetrics($applications));

        return $ruleMetrics->sortByDesc('effectiveness_score')->take($limit);
    }

    /**
     * Get rules needing attention (low effectiveness).
     */
    public function getRulesNeedingAttention(float $threshold = 30.0, int $limit = 10): Collection
    {
        return $this->getTopPerformingRules(1000)
            ->where('effectiveness_score', '<', $threshold)
            ->sortBy('effectiveness_score')
            ->take($limit);
    }

    /**
     * Generate effectiveness report for a specific rule.
     */
    public function generateRuleEffectivenessReport(int $ruleId, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $applications = RuleApplication::where('rule_id', $ruleId)
            ->whereBetween('applied_at', [$startDate, $endDate])
            ->with(['rule.ruleType', 'rule.payer', 'claim'])
            ->get();

        if ($applications->isEmpty()) {
            return ['error' => 'No applications found for this rule in the specified period'];
        }

        $rule = $applications->first()->rule;

        return [
            'rule_info' => [
                'id' => $rule->id,
                'name' => $rule->ruleType->name ?? 'Unknown',
                'payer' => $rule->payer->name ?? 'Unknown',
                'priority' => $rule->priority,
                'is_active' => $rule->is_active ?? true,
            ],
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'metrics' => $this->calculateRulePerformanceMetrics($applications)[0] ?? [],
            'recent_applications' => $applications->sortByDesc('applied_at')->take(10)->map(function ($app) {
                return [
                    'id' => $app->id,
                    'applied_at' => $app->applied_at->toDateTimeString(),
                    'triggered' => $app->rule_triggered,
                    'outcome' => $app->outcome_status,
                    'execution_time' => $app->execution_time_ms,
                    'claim_id' => $app->claim_id,
                ];
            }),
        ];
    }
}
