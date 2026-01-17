<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitorTranscriptionHealth extends Command
{
    protected $signature = 'medical:monitor-transcription';
    protected $description = 'Monitor the health of the real-time transcription system';

    public function handle()
    {
        $this->info('Checking transcription system health...');

        $metrics = [
            'realtime_latency_ms' => $this->calculateLatency(),
            'transcription_accuracy' => $this->calculateAccuracy(),
            'active_sessions' => $this->getActiveSessionsCount(),
            'error_rate' => $this->calculateErrorRate(),
        ];

        $this->table(
            ['Metric', 'Value', 'Status'],
            [
                ['Latency (ms)', $metrics['realtime_latency_ms'], $metrics['realtime_latency_ms'] < 1000 ? 'OK' : 'HIGH'],
                ['Accuracy', ($metrics['transcription_accuracy'] * 100) . '%', $metrics['transcription_accuracy'] > 0.85 ? 'OK' : 'LOW'],
                ['Active Sessions', $metrics['active_sessions'], 'INFO'],
                ['Error Rate', ($metrics['error_rate'] * 100) . '%', $metrics['error_rate'] < 0.05 ? 'OK' : 'HIGH'],
            ]
        );

        // Alerting logic
        if ($metrics['realtime_latency_ms'] > 1000) {
            $this->alert('High transcription latency detected!');
            Log::warning('High transcription latency detected', $metrics);
        }

        if ($metrics['transcription_accuracy'] < 0.85) {
            $this->alert('Low transcription accuracy detected!');
            Log::warning('Low transcription accuracy detected', $metrics);
        }

        return 0;
    }

    private function calculateLatency()
    {
        // Mock implementation: In reality, this would query metrics from Redis or logs
        return rand(200, 1200);
    }

    private function calculateAccuracy()
    {
        // Mock implementation
        return rand(80, 99) / 100;
    }

    private function getActiveSessionsCount()
    {
        // Mock implementation
        return rand(0, 5);
    }

    private function calculateErrorRate()
    {
        // Mock implementation
        return rand(0, 10) / 100;
    }
}
