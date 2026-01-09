<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class ClinicalDataStreamService
{
    protected $streamName = 'clinical_data_stream';

    /**
     * Push data to Redis stream or fallback to database queue
     */
    public function pushToStream(array $data)
    {
        if (class_exists('Redis')) {
            try {
                Redis::xadd($this->streamName, '*', $data);
                return;
            } catch (\Exception $e) {
                // Fallback if Redis is configured but fails
            }
        }

        // Fallback: Dispatch a job to process the data
        if (isset($data['patient_id'])) {
            $patient = \App\Models\User::find($data['patient_id']);
            if ($patient) {
                \App\Jobs\ProcessClinicalDataJob::dispatch($patient);
            }
        }
    }

    /**
     * Get stream name
     */
    public function getStreamName(): string
    {
        return $this->streamName;
    }
}
