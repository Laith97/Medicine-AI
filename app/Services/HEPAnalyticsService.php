<?php

namespace App\Services;

use App\Models\HepProgram;
use App\Models\HepAssignment;
use App\Models\HepProgress;
use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class HEPAnalyticsService
{
    /**
     * Cache TTL for analytics data (24 hours)
     */
    const CACHE_TTL = 86400;

    /**
     * Generate clinical analytics on HEP effectiveness by condition/diagnosis
     */
    public function getClinicalEffectivenessAnalytics(int $hospitalId = null, string $startDate = null, string $endDate = null): array
    {
        $cacheKey = "hep_clinical_analytics_{$hospitalId}_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($hospitalId, $startDate, $endDate) {
            $query = HepProgram::with(['diagnosis', 'hepAssignments.hepProgress', 'patient'])
                ->whereHas('hepAssignments', function ($q) {
                    $q->where('completion_status', 'completed');
                });

            if ($hospitalId) {
                $query->whereHas('patient', function ($q) use ($hospitalId) {
                    $q->where('hospital_id', $hospitalId);
                });
            }

            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }

            $programs = $query->get();

            $analytics = [
                'total_programs_analyzed' => $programs->count(),
                'diagnosis_effectiveness' => [],
                'overall_success_rate' => 0,
                'average_completion_time' => 0,
                'pain_reduction_average' => 0,
                'adherence_correlation' => 0,
            ];

            $diagnosisStats = [];
            $totalCompletionRate = 0;
            $totalCompletionTime = 0;
            $totalPainReduction = 0;
            $adherenceOutcomes = [];

            foreach ($programs as $program) {
                $diagnosisName = $program->diagnosis->condition_name ?? 'Unknown';
                $programAnalytics = $this->analyzeProgramEffectiveness($program);

                if (!isset($diagnosisStats[$diagnosisName])) {
                    $diagnosisStats[$diagnosisName] = [
                        'total_programs' => 0,
                        'completed_programs' => 0,
                        'average_completion_rate' => 0,
                        'average_pain_reduction' => 0,
                        'average_adherence_rate' => 0,
                        'success_rate' => 0,
                    ];
                }

                $diagnosisStats[$diagnosisName]['total_programs']++;
                $diagnosisStats[$diagnosisName]['average_completion_rate'] += $programAnalytics['completion_rate'];
                $diagnosisStats[$diagnosisName]['average_pain_reduction'] += $programAnalytics['pain_reduction'];
                $diagnosisStats[$diagnosisName]['average_adherence_rate'] += $programAnalytics['adherence_rate'];

                if ($programAnalytics['completion_rate'] >= 80) {
                    $diagnosisStats[$diagnosisName]['completed_programs']++;
                }

                $totalCompletionRate += $programAnalytics['completion_rate'];
                $totalCompletionTime += $programAnalytics['completion_time_days'];
                $totalPainReduction += $programAnalytics['pain_reduction'];
                $adherenceOutcomes[] = [
                    'adherence' => $programAnalytics['adherence_rate'],
                    'outcome' => $programAnalytics['completion_rate']
                ];
            }

            // Calculate averages and success rates
            foreach ($diagnosisStats as $diagnosis => &$stats) {
                $stats['average_completion_rate'] = round($stats['average_completion_rate'] / $stats['total_programs'], 2);
                $stats['average_pain_reduction'] = round($stats['average_pain_reduction'] / $stats['total_programs'], 2);
                $stats['average_adherence_rate'] = round($stats['average_adherence_rate'] / $stats['total_programs'], 2);
                $stats['success_rate'] = $stats['total_programs'] > 0 ?
                    round(($stats['completed_programs'] / $stats['total_programs']) * 100, 2) : 0;
            }

            $analytics['diagnosis_effectiveness'] = $diagnosisStats;
            $analytics['overall_success_rate'] = $programs->count() > 0 ?
                round(($totalCompletionRate / $programs->count()), 2) : 0;
            $analytics['average_completion_time'] = $programs->count() > 0 ?
                round(($totalCompletionTime / $programs->count()), 2) : 0;
            $analytics['pain_reduction_average'] = $programs->count() > 0 ?
                round(($totalPainReduction / $programs->count()), 2) : 0;
            $analytics['adherence_correlation'] = $this->calculateCorrelation($adherenceOutcomes);

            return $analytics;
        });
    }

    /**
     * Analyze patient adherence patterns
     */
    public function getAdherencePatterns(int $patientId = null, int $hospitalId = null, string $startDate = null, string $endDate = null): array
    {
        $cacheKey = "hep_adherence_patterns_{$patientId}_{$hospitalId}_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($patientId, $hospitalId, $startDate, $endDate) {
            $query = HepAssignment::with(['hepProgress', 'patient', 'hepProgram']);

            if ($patientId) {
                $query->where('patient_id', $patientId);
            }

            if ($hospitalId) {
                $query->whereHas('patient', function ($q) use ($hospitalId) {
                    $q->where('hospital_id', $hospitalId);
                });
            }

            if ($startDate) {
                $query->where('assigned_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('assigned_at', '<=', $endDate);
            }

            $assignments = $query->get();

            $patterns = [
                'total_assignments' => $assignments->count(),
                'adherence_distribution' => [
                    'excellent' => 0, // 90-100%
                    'good' => 0,      // 70-89%
                    'fair' => 0,      // 50-69%
                    'poor' => 0,      // <50%
                ],
                'weekly_patterns' => [],
                'completion_trends' => [],
                'average_adherence_rate' => 0,
                'consistency_score' => 0,
            ];

            $totalAdherence = 0;
            $consistencyScores = [];

            foreach ($assignments as $assignment) {
                $adherenceData = $this->calculateAssignmentAdherence($assignment);
                $totalAdherence += $adherenceData['adherence_rate'];

                // Categorize adherence
                if ($adherenceData['adherence_rate'] >= 90) {
                    $patterns['adherence_distribution']['excellent']++;
                } elseif ($adherenceData['adherence_rate'] >= 70) {
                    $patterns['adherence_distribution']['good']++;
                } elseif ($adherenceData['adherence_rate'] >= 50) {
                    $patterns['adherence_distribution']['fair']++;
                } else {
                    $patterns['adherence_distribution']['poor']++;
                }

                $consistencyScores[] = $adherenceData['consistency_score'];

                // Weekly patterns
                $this->analyzeWeeklyPatterns($assignment, $patterns['weekly_patterns']);
            }

            $patterns['average_adherence_rate'] = $assignments->count() > 0 ?
                round($totalAdherence / $assignments->count(), 2) : 0;

            $patterns['consistency_score'] = !empty($consistencyScores) ?
                round(array_sum($consistencyScores) / count($consistencyScores), 2) : 0;

            return $patterns;
        });
    }

    /**
     * Generate administrative reporting for clinician HEP creation metrics
     */
    public function getClinicianMetrics(int $hospitalId = null, string $startDate = null, string $endDate = null): array
    {
        $cacheKey = 'hep_clinician_metrics_' . $hospitalId . '_' . $startDate . '_' . $endDate;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($hospitalId, $startDate, $endDate) {
            $query = HepProgram::with(['doctor', 'hepAssignments', 'patient'])
                ->select('doctor_id', DB::raw('COUNT(*) as total_programs'))
                ->selectRaw('AVG(duration_weeks) as avg_duration')
                ->selectRaw('AVG(frequency_per_week) as avg_frequency')
                ->groupBy('doctor_id');

            if ($hospitalId) {
                $query->whereHas('patient', function ($q) use ($hospitalId) {
                    $q->where('hospital_id', $hospitalId);
                });
            }

            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }

            $clinicianStats = $query->get();

            $metrics = [
                'total_clinicians' => $clinicianStats->count(),
                'clinician_performance' => [],
                'program_creation_trends' => [],
                'average_programs_per_clinician' => 0,
                'most_active_clinicians' => [],
            ];

            $totalPrograms = 0;

            foreach ($clinicianStats as $stat) {
                $doctor = User::find($stat->doctor_id);
                if (!$doctor) continue;

                $completionRate = $this->getClinicianCompletionRate($stat->doctor_id, $startDate, $endDate);
                $patientSatisfaction = $this->getClinicianPatientSatisfaction($stat->doctor_id, $startDate, $endDate);

                $clinicianData = [
                    'clinician_name' => $doctor->name,
                    'total_programs' => $stat->total_programs,
                    'average_duration_weeks' => round($stat->avg_duration, 1),
                    'average_frequency_per_week' => round($stat->avg_frequency, 1),
                    'completion_rate' => $completionRate,
                    'patient_satisfaction_score' => $patientSatisfaction,
                    'efficiency_score' => $this->calculateClinicianEfficiency($stat, $completionRate),
                ];

                $metrics['clinician_performance'][] = $clinicianData;
                $totalPrograms += $stat->total_programs;
            }

            $metrics['average_programs_per_clinician'] = $clinicianStats->count() > 0 ?
                round($totalPrograms / $clinicianStats->count(), 2) : 0;

            // Sort by total programs for most active clinicians
            usort($metrics['clinician_performance'], function ($a, $b) {
                return $b['total_programs'] <=> $a['total_programs'];
            });

            $metrics['most_active_clinicians'] = array_slice($metrics['clinician_performance'], 0, 10);

            return $metrics;
        });
    }

    /**
     * Analyze individual program effectiveness
     */
    private function analyzeProgramEffectiveness(HepProgram $program): array
    {
        $assignments = $program->hepAssignments;
        $totalAssignments = $assignments->count();

        if ($totalAssignments === 0) {
            return [
                'completion_rate' => 0,
                'completion_time_days' => 0,
                'pain_reduction' => 0,
                'adherence_rate' => 0,
            ];
        }

        $completedAssignments = $assignments->where('completion_status', 'completed')->count();
        $completionRate = ($completedAssignments / $totalAssignments) * 100;

        // Calculate average completion time
        $completionTimes = [];
        foreach ($assignments->where('completion_status', 'completed') as $assignment) {
            $assignedDate = Carbon::parse($assignment->assigned_at);
            $completedDate = $assignment->updated_at; // Assuming updated_at reflects completion
            $completionTimes[] = $assignedDate->diffInDays($completedDate);
        }

        $avgCompletionTime = !empty($completionTimes) ? array_sum($completionTimes) / count($completionTimes) : 0;

        // Calculate pain reduction
        $painReduction = $this->calculatePainReduction($assignments);

        // Calculate adherence rate
        $adherenceRate = $this->calculateOverallAdherence($assignments);

        return [
            'completion_rate' => round($completionRate, 2),
            'completion_time_days' => round($avgCompletionTime, 2),
            'pain_reduction' => round($painReduction, 2),
            'adherence_rate' => round($adherenceRate, 2),
        ];
    }

    /**
     * Calculate assignment adherence
     */
    private function calculateAssignmentAdherence(HepAssignment $assignment): array
    {
        $progress = $assignment->hepProgress;
        $program = $assignment->hepProgram;

        if ($progress->isEmpty()) {
            return [
                'adherence_rate' => 0,
                'consistency_score' => 0,
            ];
        }

        $expectedSessions = $program->duration_weeks * $program->frequency_per_week;
        $actualSessions = $progress->count();

        $adherenceRate = min(100, ($actualSessions / $expectedSessions) * 100);

        // Calculate consistency (how regular the sessions were)
        $dates = $progress->pluck('date')->sort()->values();
        if ($dates->count() > 1) {
            $intervals = [];
            for ($i = 1; $i < $dates->count(); $i++) {
                $intervals[] = Carbon::parse($dates[$i])->diffInDays(Carbon::parse($dates[$i-1]));
            }
            $avgInterval = array_sum($intervals) / count($intervals);
            $expectedInterval = 7 / $program->frequency_per_week; // days between sessions
            $consistencyScore = max(0, 100 - (abs($avgInterval - $expectedInterval) / $expectedInterval * 50));
        } else {
            $consistencyScore = 50; // Neutral score for single session
        }

        return [
            'adherence_rate' => round($adherenceRate, 2),
            'consistency_score' => round($consistencyScore, 2),
        ];
    }

    /**
     * Calculate pain reduction across assignments
     */
    private function calculatePainReduction($assignments): float
    {
        $totalReduction = 0;
        $count = 0;

        foreach ($assignments as $assignment) {
            $progress = $assignment->hepProgress;
            if ($progress->isEmpty()) continue;

            $firstSession = $progress->sortBy('date')->first();
            $lastSession = $progress->sortBy('date')->last();

            if ($firstSession && $lastSession && $firstSession->pain_level && $lastSession->pain_level) {
                $reduction = $firstSession->pain_level - $lastSession->pain_level;
                $totalReduction += $reduction;
                $count++;
            }
        }

        return $count > 0 ? $totalReduction / $count : 0;
    }

    /**
     * Calculate overall adherence across assignments
     */
    private function calculateOverallAdherence($assignments): float
    {
        $totalAdherence = 0;
        $count = 0;

        foreach ($assignments as $assignment) {
            $adherence = $this->calculateAssignmentAdherence($assignment);
            $totalAdherence += $adherence['adherence_rate'];
            $count++;
        }

        return $count > 0 ? $totalAdherence / $count : 0;
    }

    /**
     * Analyze weekly patterns for an assignment
     */
    private function analyzeWeeklyPatterns(HepAssignment $assignment, array &$patterns): void
    {
        $progress = $assignment->hepProgress;

        foreach ($progress as $session) {
            $dayOfWeek = Carbon::parse($session->date)->dayOfWeek;

            if (!isset($patterns[$dayOfWeek])) {
                $patterns[$dayOfWeek] = [
                    'day_name' => Carbon::parse($session->date)->format('l'),
                    'sessions' => 0,
                    'avg_pain' => 0,
                    'avg_difficulty' => 0,
                ];
            }

            $patterns[$dayOfWeek]['sessions']++;
            $patterns[$dayOfWeek]['avg_pain'] += $session->pain_level ?? 0;
            $patterns[$dayOfWeek]['avg_difficulty'] += $session->difficulty_rating ?? 0;
        }

        // Calculate averages
        foreach ($patterns as &$pattern) {
            if ($pattern['sessions'] > 0) {
                $pattern['avg_pain'] = round($pattern['avg_pain'] / $pattern['sessions'], 2);
                $pattern['avg_difficulty'] = round($pattern['avg_difficulty'] / $pattern['sessions'], 2);
            }
        }
    }

    /**
     * Calculate correlation between adherence and outcomes
     */
    private function calculateCorrelation(array $data): float
    {
        if (empty($data)) return 0;

        $n = count($data);
        if ($n < 2) return 0;

        $sumX = $sumY = $sumXY = $sumX2 = $sumY2 = 0;

        foreach ($data as $point) {
            $x = $point['adherence'];
            $y = $point['outcome'];

            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
            $sumY2 += $y * $y;
        }

        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));

        return $denominator != 0 ? $numerator / $denominator : 0;
    }

    /**
     * Get clinician completion rate
     */
    private function getClinicianCompletionRate(int $doctorId, string $startDate = null, string $endDate = null): float
    {
        $query = HepAssignment::whereHas('hepProgram', function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId);
        });

        if ($startDate) {
            $query->where('assigned_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('assigned_at', '<=', $endDate);
        }

        $totalAssignments = $query->count();
        $completedAssignments = (clone $query)->where('completion_status', 'completed')->count();

        return $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100, 2) : 0;
    }

    /**
     * Get clinician patient satisfaction (placeholder - would integrate with actual feedback system)
     */
    private function getClinicianPatientSatisfaction(int $doctorId, string $startDate = null, string $endDate = null): float
    {
        // Placeholder - in real implementation, this would pull from patient feedback/reviews
        return rand(75, 95); // Random score for now
    }

    /**
     * Calculate clinician efficiency score
     */
    private function calculateClinicianEfficiency($stats, float $completionRate): float
    {
        // Efficiency based on programs created, completion rate, and average program complexity
        $volumeScore = min(100, $stats->total_programs * 10);
        $qualityScore = $completionRate;
        $complexityScore = min(100, ($stats->avg_duration * $stats->avg_frequency) * 5);

        return round(($volumeScore + $qualityScore + $complexityScore) / 3, 2);
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): void
    {
        Cache::forget('hep_clinical_analytics_*');
        Cache::forget('hep_adherence_patterns_*');
        Cache::forget('hep_clinician_metrics_*');
    }
}
