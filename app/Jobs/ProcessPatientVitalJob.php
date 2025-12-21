<?php

namespace App\Jobs;

use App\Models\PatientVital;
use App\Services\RealtimeMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPatientVitalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public PatientVital $vital)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(RealtimeMonitoringService $monitoringService): void
    {
        $monitoringService->processVital($this->vital);
    }
}
