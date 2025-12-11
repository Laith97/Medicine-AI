<?php

namespace App\Listeners;

use App\Events\DocumentUpdated;
use App\Services\ComplianceMonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MonitorDocumentUpdate implements ShouldQueue
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
    public function handle(DocumentUpdated $event): void
    {
        try {
            // Include change information in the data for rule evaluation
            $data = array_merge($event->document->toArray(), [
                'changes' => $event->changes,
                'updated_fields' => array_keys($event->changes),
            ]);

            $result = $this->complianceService->evaluateRules(
                'document_updated',
                get_class($event->document),
                $data,
                $event->document
            );

            // Log any violations
            if (!empty($result['violations'])) {
                Log::warning('Compliance violations detected on document update', [
                    'document_type' => get_class($event->document),
                    'document_id' => $event->document->id,
                    'user_id' => $event->userId,
                    'changes' => $event->changes,
                    'violations' => $result['violations'],
                ]);
            }

            // Log compliance actions taken
            if (!empty($result['compliant_actions'])) {
                Log::info('Compliance actions executed on document update', [
                    'document_type' => get_class($event->document),
                    'document_id' => $event->document->id,
                    'user_id' => $event->userId,
                    'changes' => $event->changes,
                    'actions' => $result['compliant_actions'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error monitoring document update compliance', [
                'document_type' => get_class($event->document),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId,
                'changes' => $event->changes ?? [],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
