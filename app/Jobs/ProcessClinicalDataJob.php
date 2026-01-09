<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RiskCalculationEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessClinicalDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $patient;

    /**
     * Create a new job instance.
     */
    public function __construct(User $patient)
    {
        $this->patient = $patient;
    }

    /**
     * Execute the job.
     */
    public function handle(RiskCalculationEngine $engine): void
    {
        $engine->processPatientData($this->patient);
    }
}
