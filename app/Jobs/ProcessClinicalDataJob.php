<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Services\RiskCalculationEngine;

class ProcessClinicalDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes timeout

    protected $patient;

    /**
     * Create a new job instance.
     *
     * @param User $patient
     */
    public function __construct(User $patient)
    {
        $this->patient = $patient;
    }

    /**
     * Execute the job.
     *
     * @param RiskCalculationEngine $engine
     * @return void
     */
    public function handle(RiskCalculationEngine $engine)
    {
        \Log::info("Processing data for patient: {$this->patient->name}");
        $engine->processPatientData($this->patient);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        \Log::error('ProcessClinicalDataJob failed for patient ID: ' . $this->patient->id, [
            'exception' => $exception->getMessage(),
            'patient' => $this->patient->toArray()
        ]);
    }
}