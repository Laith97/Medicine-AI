<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Services\RiskCalculationEngine;
use App\Services\ClinicalDataStreamService;

class ConsumeClinicalDataStream extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clinical:consume-stream';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume clinical data from Redis streams and trigger risk calculations';

    /**
     * Execute the console command.
     */
    public function handle(RiskCalculationEngine $engine, ClinicalDataStreamService $streamService)
    {
        if (!class_exists('Redis')) {
            $this->error('Redis extension is not available. This command requires phpredis.');
            $this->info('The system is currently using the database queue as a fallback.');
            return 1;
        }

        $this->info('Starting clinical data stream consumer...');
        $streamName = $streamService->getStreamName();
        $lastId = '0';

        while (true) {
            $messages = Redis::xread([$streamName => $lastId], 10, 1000);

            if ($messages) {
                foreach ($messages[$streamName] as $id => $data) {
                    $patientId = $data['patient_id'];
                    $patient = User::find($patientId);

                    if ($patient) {
                        $this->info("Processing data for patient: {$patient->name}");
                        $engine->processPatientData($patient);
                    }

                    $lastId = $id;
                }
            }
            
            usleep(100000); // 100ms sleep to prevent CPU spiking
        }
    }
}
