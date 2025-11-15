<?php

namespace App\Console\Commands;

use App\Models\VoiceAssistantPerformanceMetric;
use Illuminate\Console\Command;

class CheckVoiceAssistantPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voice-assistant:check-performance {--cleanup : Clean up old metrics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check voice assistant performance and send alerts if needed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking Voice Assistant Performance...');

        // Check for performance alerts
        $this->info('Checking for performance alerts...');
        VoiceAssistantPerformanceMetric::checkPerformanceAlerts();
        $this->info('Performance alerts check completed.');

        // Clean up old metrics if requested
        if ($this->option('cleanup')) {
            $this->info('Cleaning up old performance metrics...');
            $deletedCount = VoiceAssistantPerformanceMetric::cleanupOldMetrics();
            $this->info("Cleaned up {$deletedCount} old metric records.");
        }

        // Show current performance summary
        $this->showPerformanceSummary();

        $this->info('Voice Assistant performance check completed successfully.');
    }

    /**
     * Show performance summary
     */
    private function showPerformanceSummary()
    {
        $this->info('Performance Summary (Last 24 hours):');

        $metrics = VoiceAssistantPerformanceMetric::where('created_at', '>=', now()->subDay())->get();

        if ($metrics->isEmpty()) {
            $this->warn('No performance metrics found for the last 24 hours.');
            return;
        }

        $totalSessions = $metrics->count();
        $successfulSessions = $metrics->where('overall_success', true)->count();
        $successRate = $totalSessions > 0 ? round(($successfulSessions / $totalSessions) * 100, 1) : 0;
        $avgProcessingTime = round($metrics->avg('total_processing_time'), 2);
        $serverImprovementRate = round(($metrics->where('server_better_than_live', true)->count() / $totalSessions) * 100, 1);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Sessions', $totalSessions],
                ['Success Rate', $successRate . '%'],
                ['Average Processing Time', $avgProcessingTime . 's'],
                ['Server Improvement Rate', $serverImprovementRate . '%'],
            ]
        );

        // Show top error types (get all errors, not doctor-specific)
        $errorStats = VoiceAssistantPerformanceMetric::where('created_at', '>=', now()->subDay())
            ->whereNotNull('error_type')
            ->selectRaw('error_type, COUNT(*) as count')
            ->groupBy('error_type')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        if (!empty($errorStats)) {
            $this->info('Top Error Types:');
            $this->table(
                ['Error Type', 'Count', 'Percentage'],
                collect($errorStats)->map(function ($error) use ($totalSessions) {
                    return [
                        $error['error_type'],
                        $error['count'],
                        round(($error['count'] / $totalSessions) * 100, 1) . '%'
                    ];
                })->toArray()
            );
        }
    }
}
