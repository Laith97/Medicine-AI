<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class VoiceAssistantPerformanceMetric extends Model
{
    protected $fillable = [
        'doctor_id',
        'session_id',
        'processing_type',
        'live_transcription_success',
        'server_processing_success',
        'medical_extraction_success',
        'ai_analysis_success',
        'overall_success',
        'live_transcription_time',
        'server_processing_time',
        'medical_extraction_time',
        'ai_analysis_time',
        'total_processing_time',
        'audio_file_size',
        'audio_duration',
        'audio_format',
        'audio_sample_rate',
        'audio_channels',
        'average_audio_level',
        'live_transcript_length',
        'server_transcript_length',
        'transcript_improvement_ratio',
        'server_better_than_live',
        'extracted_symptoms_count',
        'extracted_medical_history_count',
        'extracted_physical_findings_count',
        'extracted_medications_count',
        'extracted_vital_signs_count',
        'error_type',
        'error_message',
        'user_satisfaction_rating',
        'user_feedback',
        'browser_info',
        'device_type',
        'network_type',
        'connection_speed',
    ];

    protected $casts = [
        'live_transcription_success' => 'boolean',
        'server_processing_success' => 'boolean',
        'medical_extraction_success' => 'boolean',
        'ai_analysis_success' => 'boolean',
        'overall_success' => 'boolean',
        'server_better_than_live' => 'boolean',
        'live_transcription_time' => 'decimal:3',
        'server_processing_time' => 'decimal:3',
        'medical_extraction_time' => 'decimal:3',
        'ai_analysis_time' => 'decimal:3',
        'total_processing_time' => 'decimal:3',
        'audio_duration' => 'decimal:3',
        'audio_sample_rate' => 'decimal:1',
        'average_audio_level' => 'decimal:2',
        'transcript_improvement_ratio' => 'decimal:2',
        'connection_speed' => 'decimal:2',
    ];

    /**
     * Relationship with Doctor
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get success rate statistics for a doctor
     */
    public static function getSuccessRates(int $doctorId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $metrics = self::where('doctor_id', $doctorId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_sessions,
                AVG(CASE WHEN overall_success = 1 THEN 1 ELSE 0 END) * 100 as overall_success_rate,
                AVG(CASE WHEN live_transcription_success = 1 THEN 1 ELSE 0 END) * 100 as live_transcription_success_rate,
                AVG(CASE WHEN server_processing_success = 1 THEN 1 ELSE 0 END) * 100 as server_processing_success_rate,
                AVG(CASE WHEN medical_extraction_success = 1 THEN 1 ELSE 0 END) * 100 as medical_extraction_success_rate,
                AVG(CASE WHEN ai_analysis_success = 1 THEN 1 ELSE 0 END) * 100 as ai_analysis_success_rate,
                AVG(total_processing_time) as avg_processing_time,
                AVG(CASE WHEN server_better_than_live = 1 THEN 1 ELSE 0 END) * 100 as server_improvement_rate
            ')
            ->first();

        return $metrics ? $metrics->toArray() : [
            'total_sessions' => 0,
            'overall_success_rate' => 0,
            'live_transcription_success_rate' => 0,
            'server_processing_success_rate' => 0,
            'medical_extraction_success_rate' => 0,
            'ai_analysis_success_rate' => 0,
            'avg_processing_time' => 0,
            'server_improvement_rate' => 0,
        ];
    }

    /**
     * Get performance trends over time
     */
    public static function getPerformanceTrends(int $doctorId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return self::where('doctor_id', $doctorId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as sessions_count,
                AVG(CASE WHEN overall_success = 1 THEN 1 ELSE 0 END) * 100 as success_rate,
                AVG(total_processing_time) as avg_processing_time,
                AVG(CASE WHEN server_better_than_live = 1 THEN 1 ELSE 0 END) * 100 as server_improvement_rate
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get error statistics
     */
    public static function getErrorStatistics(int $doctorId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return self::where('doctor_id', $doctorId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('error_type')
            ->selectRaw('error_type, COUNT(*) as count')
            ->groupBy('error_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Record a new performance metric
     */
    public static function recordMetric(array $data): self
    {
        // Ensure doctor_id is provided in the data array
        if (!isset($data['doctor_id'])) {
            $data['doctor_id'] = auth()->id();
        }

        return self::create($data);
    }

    /**
     * Calculate overall success based on individual components
     */
    public function calculateOverallSuccess(): bool
    {
        // Overall success requires at least live transcription success
        // and either medical extraction or AI analysis success
        $hasTranscription = $this->live_transcription_success || $this->server_processing_success;
        $hasProcessing = $this->medical_extraction_success || $this->ai_analysis_success;

        return $hasTranscription && $hasProcessing;
    }

    /**
     * Check for performance issues and send alerts if needed
     */
    public static function checkPerformanceAlerts(): void
    {
        $thresholds = [
            'max_avg_processing_time' => 30.0, // 30 seconds
            'min_success_rate' => 70.0, // 70%
            'max_error_rate' => 20.0, // 20%
        ];

        // Check global performance over last 24 hours
        $recentMetrics = self::where('created_at', '>=', now()->subDay())->get();

        if ($recentMetrics->isEmpty()) {
            return;
        }

        $avgProcessingTime = $recentMetrics->avg('total_processing_time');
        $successRate = ($recentMetrics->where('overall_success', true)->count() / $recentMetrics->count()) * 100;
        $errorRate = ($recentMetrics->whereNotNull('error_type')->count() / $recentMetrics->count()) * 100;

        $alerts = [];

        if ($avgProcessingTime > $thresholds['max_avg_processing_time']) {
            $alerts[] = "Average processing time ({$avgProcessingTime}s) exceeds threshold ({$thresholds['max_avg_processing_time']}s)";
        }

        if ($successRate < $thresholds['min_success_rate']) {
            $alerts[] = "Success rate ({$successRate}%) below threshold ({$thresholds['min_success_rate']}%)";
        }

        if ($errorRate > $thresholds['max_error_rate']) {
            $alerts[] = "Error rate ({$errorRate}%) exceeds threshold ({$thresholds['max_error_rate']}%)";
        }

        if (!empty($alerts)) {
            self::sendPerformanceAlert($alerts, [
                'avg_processing_time' => $avgProcessingTime,
                'success_rate' => $successRate,
                'error_rate' => $errorRate,
                'total_sessions' => $recentMetrics->count()
            ]);
        }
    }

    /**
     * Send performance alert notification
     */
    private static function sendPerformanceAlert(array $alerts, array $metrics): void
    {
        // Log the alerts for debugging
        Log::warning('Voice Assistant Performance Alert', [
            'alerts' => $alerts,
            'metrics' => $metrics,
            'timestamp' => now()->toISOString()
        ]);

        // Send notifications to administrators
        try {
            // Get all admin users
            $admins = \App\Models\User::where('role', 'admin')->get();

            if ($admins->isNotEmpty()) {
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\VoiceAssistantPerformanceAlert($alerts, $metrics));
                }
            } else {
                // Fallback: send to the first user if no admins exist (for development)
                $fallbackUser = \App\Models\User::first();
                if ($fallbackUser) {
                    $fallbackUser->notify(new \App\Notifications\VoiceAssistantPerformanceAlert($alerts, $metrics));
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send voice assistant performance alert notification: ' . $e->getMessage());
        }
    }

    /**
     * Get performance optimization recommendations
     */
    public static function getOptimizationRecommendations(int $doctorId): array
    {
        $recommendations = [];
        $metrics = self::getSuccessRates($doctorId, 7); // Last 7 days

        // Processing time recommendations
        if ($metrics['avg_processing_time'] > 20) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'title' => 'High Processing Times',
                'description' => 'Average processing time is ' . round($metrics['avg_processing_time'], 1) . 's. Consider optimizing network or using cached responses.',
                'action' => 'Check internet connection and consider using shorter audio clips.'
            ];
        }

        // Success rate recommendations
        if ($metrics['overall_success_rate'] < 80) {
            $recommendations[] = [
                'type' => 'accuracy',
                'priority' => 'high',
                'title' => 'Low Success Rate',
                'description' => 'Overall success rate is ' . round($metrics['overall_success_rate'], 1) . '%. Audio quality or network issues may be affecting performance.',
                'action' => 'Ensure good microphone quality and stable internet connection.'
            ];
        }

        // Server improvement recommendations
        if ($metrics['server_improvement_rate'] < 50) {
            $recommendations[] = [
                'type' => 'quality',
                'priority' => 'medium',
                'title' => 'Limited Server Improvement',
                'description' => 'Server processing only improves ' . round($metrics['server_improvement_rate'], 1) . '% of transcriptions.',
                'action' => 'Check audio recording quality and consider microphone upgrades.'
            ];
        }

        return $recommendations;
    }

    /**
     * Clean up old performance metrics (keep last 90 days)
     */
    public static function cleanupOldMetrics(): int
    {
        return self::where('created_at', '<', now()->subDays(90))->delete();
    }
}
