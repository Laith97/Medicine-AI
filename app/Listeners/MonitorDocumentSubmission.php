<?php

namespace App\Listeners;

use App\Events\DocumentSubmitted;
use App\Services\ComplianceMonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MonitorDocumentSubmission implements ShouldQueue
{
    protected $complianceService;

    /**
     * Create the event listener.
     */
    public function __construct(ComplianceMonitoringService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Handle the event.
     */
    public function handle(DocumentSubmitted $event): void
    {
        try {
            // Include submission information in the data for rule evaluation
            $data = array_merge($event->document->toArray(), [
                'submission_type' => $event->submissionType,
                'submitted_at' => now(),
                'submitted_by' => $event->userId,
            ]);

            $result = $this->complianceService->evaluateRules(
                'document_submitted',
                get_class($event->document),
                $data,
                $event->document
            );

            // Log any violations
            if (!empty($result['violations'])) {
                Log::warning('Compliance violations detected on document submission', [
                    'document_type' => get_class($event->document),
                    'document_id' => $event->document->id,
                    'user_id' => $event->userId,
                    'submission_type' => $event->submissionType,
                    'violations' => $result['violations'],
                ]);
            }

            // Log compliance actions taken
            if (!empty($result['compliant_actions'])) {
                Log::info('Compliance actions executed on document submission', [
                    'document_type' => get_class($event->document),
                    'document_id' => $event->document->id,
                    'user_id' => $event->userId,
                    'submission_type' => $event->submissionType,
                    'actions' => $result['compliant_actions'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error monitoring document submission compliance', [
                'document_type' => get_class($event->document),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId,
                'submission_type' => $event->submissionType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
