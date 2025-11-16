<?php

namespace App\Listeners;

use App\Events\DocumentCreated;
use App\Services\ComplianceMonitoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MonitorDocumentCreation implements ShouldQueue
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
    public function handle(DocumentCreated $event): void
    {
        try {
            $result = $this->complianceService->monitorDocumentCreation($event->document);

            // Log any violations
            if (!empty($result['violations'])) {
                Log::warning('Compliance violations detected on document creation', [
                    'document_type' => get_class($event->document),
                    'document_id' => $event->document->id,
                    'user_id' => $event->userId,
                    'violations' => $result['violations'],
                ]);
            }

            // Log compliance actions taken
            if (!empty($result['compliant_actions'])) {
                Log::info('Compliance actions executed on document creation', [
                    'document_type' => get_class($event->document),
                    'document_id' => $event->document->id,
                    'user_id' => $event->userId,
                    'actions' => $result['compliant_actions'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error monitoring document creation compliance', [
                'document_type' => get_class($event->document),
                'document_id' => $event->document->id ?? null,
                'user_id' => $event->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
