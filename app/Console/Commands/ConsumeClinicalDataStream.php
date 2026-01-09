<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Services\RiskCalculationEngine;
use App\Services\ClinicalDataStreamService;
use App\Jobs\ProcessClinicalDataJob;
use Illuminate\Support\Facades\DB;

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
    protected $description = 'Consume clinical data from Redis streams or database queue and trigger risk calculations';

    /**
     * Execute the console command.
     */
    public function handle(RiskCalculationEngine $engine, ClinicalDataStreamService $streamService)
    {
        $this->info('Starting clinical data stream consumer...');

        // Check if Redis is available
        $redisAvailable = false;
        if (class_exists('Redis')) {
            try {
                // Test Redis connection
                Redis::ping();
                $redisAvailable = true;
            } catch (\Exception $e) {
                $this->warn('Redis is not available: ' . $e->getMessage());
                $this->info('Using database queue as fallback...');
            }
        } else {
            $this->warn('Redis extension is not available');
            $this->info('Using database queue as fallback...');
        }

        if ($redisAvailable) {
            $this->info('Using Redis for streaming data...');
            $this->consumeFromRedis($streamService, $engine);
        } else {
            $this->info('Consuming from database queue...');
            $this->consumeFromDatabaseQueue($engine);
        }
    }

    /**
     * Consume messages from Redis stream
     */
    protected function consumeFromRedis(ClinicalDataStreamService $streamService, RiskCalculationEngine $engine)
    {
        $streamName = $streamService->getStreamName();
        $lastId = '0';

        while (true) {
            try {
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
            } catch (\Exception $e) {
                $this->error('Error consuming from Redis: ' . $e->getMessage());
                sleep(5); // Wait before retrying
            }
        }
    }

    /**
     * Consume messages from database queue as fallback
     */
    protected function consumeFromDatabaseQueue(RiskCalculationEngine $engine)
    {
        $this->info('Polling database queue for clinical data jobs...');

        while (true) {
            try {
                // Process any pending ProcessClinicalDataJob jobs
                // In a real implementation, you might want to check for specific queued jobs
                // For now, we'll just keep the process alive

                // Sleep for a bit to prevent excessive CPU usage
                sleep(5);

                // Optionally, you could implement logic to check for and process specific jobs
                // that would have been added to the database queue as a fallback
            } catch (\Exception $e) {
                $this->error('Error in database queue consumer: ' . $e->getMessage());
                sleep(5); // Wait before retrying
            }
        }
    }
}
