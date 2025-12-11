<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ComplianceAuditTrailService;
use App\Services\SOAPIntegrationService;
use App\Services\ComplianceWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ComplianceIntegrationController extends Controller
{
    protected ComplianceAuditTrailService $auditService;
    protected SOAPIntegrationService $soapService;
    protected ComplianceWebhookService $webhookService;

    public function __construct(
        ComplianceAuditTrailService $auditService,
        SOAPIntegrationService $soapService,
        ComplianceWebhookService $webhookService
    ) {
        $this->auditService = $auditService;
        $this->soapService = $soapService;
        $this->webhookService = $webhookService;
    }

    /**
     * Export compliance audit trail data.
     */
    public function exportAuditTrail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:csv,json,xml',
            'filters' => 'nullable|array',
            'filters.event_type' => 'nullable|string',
            'filters.event_category' => 'nullable|string',
            'filters.severity_level' => 'nullable|in:low,medium,high,critical',
            'filters.user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $filters = $request->filters ?? [];

            $exportPath = $this->auditService->exportComplianceAuditTrail($startDate, $endDate, $filters);

            // Send webhook notification about the export
            $this->webhookService->sendAuditTrailExportWebhook($exportPath, [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'format' => $request->input('format'),
                'filters' => $filters,
                'exported_by' => auth()->user()?->name ?? 'System',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Audit trail exported successfully',
                'data' => [
                    'export_path' => $exportPath,
                    'download_url' => route('compliance.download-export', ['path' => basename($exportPath)]),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export audit trail',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate comprehensive compliance audit report.
     */
    public function generateAuditReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            $report = $this->auditService->generateComprehensiveAuditReport($startDate, $endDate);

            // Send webhook notification about the report
            $this->webhookService->sendComplianceReportWebhook([
                'report_type' => 'comprehensive_audit',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'generated_by' => auth()->user()?->name ?? 'System',
                'total_events' => $report['summary']['total_compliance_events'],
                'total_violations' => $report['summary']['violations_count'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compliance audit report generated successfully',
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate audit report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get compliance analytics data.
     */
    public function getComplianceAnalytics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            $analytics = $this->auditService->getComplianceAnalytics($startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'Compliance analytics retrieved successfully',
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get compliance analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send data to external SOAP service.
     */
    public function sendSOAPData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string',
            'data_type' => 'required|in:compliance_data,audit_trail,health_data',
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $clientName = $request->client_name;
            $dataType = $request->data_type;
            $data = $request->data;

            $result = match ($dataType) {
                'compliance_data' => $this->soapService->sendComplianceDataToSOAP($data, $clientName),
                'audit_trail' => $this->soapService->sendAuditTrailToSOAP($data, $clientName),
                default => [
                    'success' => false,
                    'error' => 'Unsupported data type',
                ],
            };

            if ($result['success']) {
                // Log successful SOAP transmission
                $this->auditService->logComplianceEvent(
                    'soap_data_transmission',
                    'integration',
                    [
                        'action_performed' => 'send_soap_data',
                        'event_data' => [
                            'client_name' => $clientName,
                            'data_type' => $dataType,
                            'data_size' => count($data),
                        ],
                        'compliance_context' => [
                            'transmission_success' => true,
                        ],
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Data sent to SOAP service successfully',
                    'data' => $result,
                ]);
            } else {
                // Log failed SOAP transmission
                $this->auditService->logComplianceEvent(
                    'soap_data_transmission_failed',
                    'integration',
                    [
                        'action_performed' => 'send_soap_data',
                        'event_data' => [
                            'client_name' => $clientName,
                            'data_type' => $dataType,
                            'error' => $result['error'] ?? 'Unknown error',
                        ],
                        'compliance_context' => [
                            'transmission_success' => false,
                        ],
                        'severity_level' => 'medium',
                    ]
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send data to SOAP service',
                    'error' => $result['error'] ?? 'Unknown error',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SOAP data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Register a SOAP client configuration.
     */
    public function registerSOAPClient(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string',
            'endpoint' => 'required|url',
            'headers' => 'nullable|array',
            'timeout' => 'nullable|integer|min:1|max:300',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->soapService->registerSOAPClient($request->client_name, [
                'endpoint' => $request->endpoint,
                'headers' => $request->headers ?? [],
                'timeout' => $request->timeout ?? 30,
            ]);

            // Log SOAP client registration
            $this->auditService->logComplianceEvent(
                'soap_client_registered',
                'integration',
                [
                    'action_performed' => 'register_soap_client',
                    'event_data' => [
                        'client_name' => $request->client_name,
                        'endpoint' => $request->endpoint,
                    ],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'SOAP client registered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register SOAP client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Register a webhook endpoint.
     */
    public function registerWebhook(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'webhook_name' => 'required|string',
            'url' => 'required|url',
            'secret' => 'required|string|min:16',
            'events' => 'nullable|array',
            'events.*' => 'string',
            'headers' => 'nullable|array',
            'timeout' => 'nullable|integer|min:1|max:300',
            'retry_attempts' => 'nullable|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->webhookService->registerWebhook($request->webhook_name, [
                'url' => $request->url,
                'secret' => $request->secret,
                'events' => $request->events ?? [],
                'headers' => $request->headers ?? [],
                'timeout' => $request->timeout ?? 30,
                'retry_attempts' => $request->retry_attempts ?? 3,
                'enabled' => true,
            ]);

            // Log webhook registration
            $this->auditService->logComplianceEvent(
                'webhook_registered',
                'integration',
                [
                    'action_performed' => 'register_webhook',
                    'event_data' => [
                        'webhook_name' => $request->webhook_name,
                        'url' => $request->url,
                        'events' => $request->events ?? [],
                    ],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Webhook registered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register webhook',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test a webhook endpoint.
     */
    public function testWebhook(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'webhook_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->webhookService->testWebhook($request->webhook_name);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Webhook test successful' : 'Webhook test failed',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test webhook',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process incoming webhook from external system.
     */
    public function processIncomingWebhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-Webhook-Signature');
            $source = $request->header('X-Webhook-Source', 'external');

            if (!$signature) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing webhook signature',
                ], 401);
            }

            $result = $this->webhookService->processIncomingWebhook($payload, $signature, $source);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process incoming webhook',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get registered SOAP clients.
     */
    public function getSOAPClients(): JsonResponse
    {
        try {
            $clients = $this->soapService->getSOAPClients();

            return response()->json([
                'success' => true,
                'message' => 'SOAP clients retrieved successfully',
                'data' => $clients,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get SOAP clients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get registered webhooks.
     */
    public function getWebhooks(): JsonResponse
    {
        try {
            $webhooks = $this->webhookService->getWebhooks();

            return response()->json([
                'success' => true,
                'message' => 'Webhooks retrieved successfully',
                'data' => $webhooks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get webhooks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
