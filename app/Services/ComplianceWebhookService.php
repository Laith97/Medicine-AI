<?php

namespace App\Services;

use App\Models\ComplianceEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ComplianceWebhookService
{
    protected array $webhookEndpoints = [];

    /**
     * Register a webhook endpoint.
     */
    public function registerWebhook(string $name, array $config): void
    {
        $this->webhookEndpoints[$name] = array_merge([
            'url' => '',
            'secret' => '',
            'events' => [],
            'headers' => [],
            'timeout' => 30,
            'retry_attempts' => 3,
            'enabled' => true,
        ], $config);
    }

    /**
     * Unregister a webhook endpoint.
     */
    public function unregisterWebhook(string $name): void
    {
        unset($this->webhookEndpoints[$name]);
    }

    /**
     * Get registered webhook endpoints.
     */
    public function getWebhooks(): array
    {
        return $this->webhookEndpoints;
    }

    /**
     * Send webhook notification for a compliance event.
     */
    public function sendComplianceEventWebhook(ComplianceEvent $event): array
    {
        $results = [];

        foreach ($this->webhookEndpoints as $name => $config) {
            if (!$config['enabled']) {
                continue;
            }

            // Check if this webhook should receive this event type
            if (!empty($config['events']) && !in_array($event->event_type, $config['events'])) {
                continue;
            }

            $result = $this->sendWebhook($name, $config, [
                'event_id' => $event->id,
                'event_type' => $event->event_type,
                'event_category' => $event->event_category,
                'timestamp' => $event->event_timestamp->toISOString(),
                'user_id' => $event->user_id,
                'user_role' => $event->user_role,
                'resource_type' => $event->resource_type,
                'resource_id' => $event->resource_id,
                'action_performed' => $event->action_performed,
                'severity_level' => $event->severity_level,
                'ip_address' => $event->ip_address,
                'compliance_context' => $event->compliance_context,
                'event_data' => $event->event_data,
            ]);

            $results[$name] = $result;
        }

        return $results;
    }

    /**
     * Send webhook for compliance violation.
     */
    public function sendComplianceViolationWebhook(array $violationData): array
    {
        $results = [];

        foreach ($this->webhookEndpoints as $name => $config) {
            if (!$config['enabled']) {
                continue;
            }

            // Check if this webhook should receive violation events
            if (!empty($config['events']) && !in_array('compliance_violation', $config['events'])) {
                continue;
            }

            $result = $this->sendWebhook($name, $config, array_merge([
                'event_type' => 'compliance_violation',
                'event_category' => 'security',
                'timestamp' => now()->toISOString(),
            ], $violationData));

            $results[$name] = $result;
        }

        return $results;
    }

    /**
     * Send webhook for audit trail export.
     */
    public function sendAuditTrailExportWebhook(string $exportPath, array $exportMetadata): array
    {
        $results = [];

        foreach ($this->webhookEndpoints as $name => $config) {
            if (!$config['enabled']) {
                continue;
            }

            // Check if this webhook should receive audit events
            if (!empty($config['events']) && !in_array('audit_export', $config['events'])) {
                continue;
            }

            $result = $this->sendWebhook($name, $config, array_merge([
                'event_type' => 'audit_export',
                'event_category' => 'audit',
                'timestamp' => now()->toISOString(),
                'export_path' => $exportPath,
            ], $exportMetadata));

            $results[$name] = $result;
        }

        return $results;
    }

    /**
     * Send webhook for compliance report generation.
     */
    public function sendComplianceReportWebhook(array $reportData): array
    {
        $results = [];

        foreach ($this->webhookEndpoints as $name => $config) {
            if (!$config['enabled']) {
                continue;
            }

            // Check if this webhook should receive report events
            if (!empty($config['events']) && !in_array('compliance_report', $config['events'])) {
                continue;
            }

            $result = $this->sendWebhook($name, $config, array_merge([
                'event_type' => 'compliance_report',
                'event_category' => 'reporting',
                'timestamp' => now()->toISOString(),
            ], $reportData));

            $results[$name] = $result;
        }

        return $results;
    }

    /**
     * Send webhook to a specific endpoint.
     */
    protected function sendWebhook(string $name, array $config, array $payload): array
    {
        $signature = $this->generateSignature($payload, $config['secret']);

        $headers = array_merge($config['headers'], [
            'Content-Type' => 'application/json',
            'X-Webhook-Signature' => $signature,
            'X-Webhook-ID' => Str::uuid(),
            'X-Webhook-Source' => 'compliance-system',
        ]);

        $attempts = 0;
        $maxAttempts = $config['retry_attempts'] ?? 3;

        while ($attempts < $maxAttempts) {
            try {
                $response = Http::timeout($config['timeout'] ?? 30)
                    ->withHeaders($headers)
                    ->post($config['url'], $payload);

                if ($response->successful()) {
                    Log::info("Webhook sent successfully", [
                        'webhook' => $name,
                        'event_type' => $payload['event_type'] ?? 'unknown',
                        'status_code' => $response->status(),
                    ]);

                    return [
                        'success' => true,
                        'webhook' => $name,
                        'status_code' => $response->status(),
                        'response' => $response->body(),
                        'attempts' => $attempts + 1,
                    ];
                } else {
                    Log::warning("Webhook failed", [
                        'webhook' => $name,
                        'event_type' => $payload['event_type'] ?? 'unknown',
                        'status_code' => $response->status(),
                        'response' => $response->body(),
                        'attempt' => $attempts + 1,
                    ]);

                    $attempts++;
                }
            } catch (\Exception $e) {
                Log::error("Webhook exception", [
                    'webhook' => $name,
                    'event_type' => $payload['event_type'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'attempt' => $attempts + 1,
                ]);

                $attempts++;
            }
        }

        return [
            'success' => false,
            'webhook' => $name,
            'error' => 'Failed after ' . $maxAttempts . ' attempts',
            'attempts' => $attempts,
        ];
    }

    /**
     * Generate webhook signature for security.
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha256', $payloadJson, $secret);
    }

    /**
     * Verify webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process incoming webhook from external system.
     */
    public function processIncomingWebhook(array $payload, string $signature, string $source): array
    {
        // Find the webhook configuration for this source
        $config = collect($this->webhookEndpoints)->first(function ($config) use ($source) {
            return ($config['source'] ?? '') === $source;
        });

        if (!$config) {
            return [
                'success' => false,
                'error' => 'Unknown webhook source',
            ];
        }

        // Verify signature
        if (!$this->verifySignature(json_encode($payload), $signature, $config['secret'])) {
            return [
                'success' => false,
                'error' => 'Invalid signature',
            ];
        }

        // Process the webhook payload
        return $this->processWebhookPayload($payload, $source);
    }

    /**
     * Process webhook payload based on event type.
     */
    protected function processWebhookPayload(array $payload, string $source): array
    {
        $eventType = $payload['event_type'] ?? 'unknown';

        switch ($eventType) {
            case 'external_compliance_check':
                return $this->processExternalComplianceCheck($payload, $source);

            case 'external_audit_request':
                return $this->processExternalAuditRequest($payload, $source);

            case 'external_system_sync':
                return $this->processExternalSystemSync($payload, $source);

            default:
                Log::info("Unknown webhook event type received", [
                    'source' => $source,
                    'event_type' => $eventType,
                ]);

                return [
                    'success' => true,
                    'message' => 'Event type not processed',
                    'event_type' => $eventType,
                ];
        }
    }

    /**
     * Process external compliance check request.
     */
    protected function processExternalComplianceCheck(array $payload, string $source): array
    {
        // Log the external compliance check
        app(ComplianceAuditTrailService::class)->logComplianceEvent(
            'external_compliance_check',
            'integration',
            [
                'action_performed' => 'external_check',
                'event_data' => $payload,
                'compliance_context' => [
                    'external_source' => $source,
                    'check_type' => $payload['check_type'] ?? 'unknown',
                ],
            ]
        );

        // Here you would implement the actual compliance check logic
        // For now, return a success response
        return [
            'success' => true,
            'message' => 'External compliance check processed',
            'check_id' => $payload['check_id'] ?? null,
            'result' => 'compliant', // This would be determined by actual check
        ];
    }

    /**
     * Process external audit request.
     */
    protected function processExternalAuditRequest(array $payload, string $source): array
    {
        // Log the external audit request
        app(ComplianceAuditTrailService::class)->logComplianceEvent(
            'external_audit_request',
            'integration',
            [
                'action_performed' => 'audit_request',
                'event_data' => $payload,
                'compliance_context' => [
                    'external_source' => $source,
                    'request_type' => $payload['request_type'] ?? 'unknown',
                ],
            ]
        );

        // Generate audit data response
        $auditService = app(ComplianceAuditTrailService::class);
        $auditData = $auditService->generateComprehensiveAuditReport(
            now()->subDays($payload['days'] ?? 30),
            now()
        );

        return [
            'success' => true,
            'message' => 'External audit request processed',
            'audit_data' => $auditData,
        ];
    }

    /**
     * Process external system sync request.
     */
    protected function processExternalSystemSync(array $payload, string $source): array
    {
        // Log the external system sync
        app(ComplianceAuditTrailService::class)->logComplianceEvent(
            'external_system_sync',
            'integration',
            [
                'action_performed' => 'system_sync',
                'event_data' => $payload,
                'compliance_context' => [
                    'external_source' => $source,
                    'sync_type' => $payload['sync_type'] ?? 'unknown',
                ],
            ]
        );

        // Here you would implement the actual sync logic
        // For now, return a success response
        return [
            'success' => true,
            'message' => 'External system sync processed',
            'sync_id' => $payload['sync_id'] ?? null,
            'records_processed' => 0, // This would be determined by actual sync
        ];
    }

    /**
     * Test webhook endpoint.
     */
    public function testWebhook(string $name): array
    {
        $config = $this->webhookEndpoints[$name] ?? null;

        if (!$config) {
            return [
                'success' => false,
                'error' => "Webhook '{$name}' not found",
            ];
        }

        $testPayload = [
            'event_type' => 'webhook_test',
            'event_category' => 'test',
            'timestamp' => now()->toISOString(),
            'test_data' => 'This is a test webhook from the compliance system',
        ];

        return $this->sendWebhook($name, $config, $testPayload);
    }
}
