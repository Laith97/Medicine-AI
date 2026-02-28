<?php

namespace Tests\Feature;

use App\Models\ComplianceEvent;
use App\Models\RuleApplication;
use App\Models\User;
use App\Services\ComplianceAuditTrailService;
use App\Services\SOAPIntegrationService;
use App\Services\ComplianceWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Carbon\Carbon;

class ComplianceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ComplianceAuditTrailService $auditService;
    protected SOAPIntegrationService $soapService;
    protected ComplianceWebhookService $webhookService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditService = app(ComplianceAuditTrailService::class);
        $this->soapService = app(SOAPIntegrationService::class);
        $this->webhookService = app(ComplianceWebhookService::class);
    }

    /** @test */
    public function it_can_export_compliance_audit_trail_data()
    {
        // Create test data
        RuleApplication::factory()->count(5)->create([
            'applied_at' => now()->subDays(1),
        ]);

        $startDate = now()->subDays(2);
        $endDate = now();

        $result = $this->auditService->exportComplianceAuditTrail($startDate, $endDate);

        $this->assertStringContains('compliance-audit-trail', $result);
        $this->assertStringContains('.csv', $result);
    }

    /** @test */
    public function it_can_generate_comprehensive_audit_report()
    {
        // Create test compliance events
        ComplianceEvent::factory()->count(3)->create([
            'event_timestamp' => now()->subDays(1),
        ]);

        // Create test rule applications
        RuleApplication::factory()->count(5)->create([
            'applied_at' => now()->subDays(1),
        ]);

        $startDate = now()->subDays(2);
        $endDate = now();

        $report = $this->auditService->generateComprehensiveAuditReport($startDate, $endDate);

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('compliance_events', $report);
        $this->assertArrayHasKey('rule_applications', $report);
        $this->assertEquals(3, $report['summary']['total_compliance_events']);
        $this->assertEquals(5, $report['summary']['total_rule_applications']);
    }

    /** @test */
    public function it_can_get_compliance_analytics()
    {
        // Create test events with different types and severities
        ComplianceEvent::factory()->count(2)->create([
            'event_type' => 'compliance_violation',
            'severity_level' => 'high',
            'event_timestamp' => now()->subDays(1),
        ]);

        ComplianceEvent::factory()->count(3)->create([
            'event_type' => 'data_access',
            'severity_level' => 'low',
            'event_timestamp' => now()->subDays(1),
        ]);

        $startDate = now()->subDays(2);
        $endDate = now();

        $analytics = $this->auditService->getComplianceAnalytics($startDate, $endDate);

        $this->assertArrayHasKey('event_type_distribution', $analytics);
        $this->assertArrayHasKey('severity_distribution', $analytics);
        $this->assertArrayHasKey('violation_trends', $analytics);
        $this->assertEquals(2, $analytics['violation_trends']['total_violations']);
    }

    /** @test */
    public function it_can_convert_data_to_soap_format()
    {
        $testData = [
            'report_id' => 'test-123',
            'generated_at' => now()->toISOString(),
            'compliance_metrics' => [
                'total_applications' => 100,
                'compliance_rate' => 95.5,
            ],
        ];

        $soapXml = $this->soapService->convertToSOAP($testData, 'compliance_data');

        $this->assertStringContains('<?xml version="1.0" encoding="UTF-8"?>', $soapXml);
        $this->assertStringContains('<soap:Envelope', $soapXml);
        $this->assertStringContains('<ComplianceData', $soapXml);
        $this->assertStringContains('test-123', $soapXml);
        $this->assertStringContains('95.5', $soapXml);
    }

    /** @test */
    public function it_can_register_and_use_soap_client()
    {
        $this->soapService->registerSOAPClient('test_client', [
            'endpoint' => 'https://example.com/soap',
            'headers' => ['Authorization' => 'Bearer test-token'],
            'timeout' => 30,
        ]);

        $clients = $this->soapService->getSOAPClients();

        $this->assertArrayHasKey('test_client', $clients);
        $this->assertEquals('https://example.com/soap', $clients['test_client']['endpoint']);
    }

    /** @test */
    public function it_can_register_and_manage_webhooks()
    {
        $this->webhookService->registerWebhook('test_webhook', [
            'url' => 'https://example.com/webhook',
            'secret' => 'test-secret-key',
            'events' => ['compliance_violation', 'audit_export'],
            'timeout' => 30,
        ]);

        $webhooks = $this->webhookService->getWebhooks();

        $this->assertArrayHasKey('test_webhook', $webhooks);
        $this->assertEquals('https://example.com/webhook', $webhooks['test_webhook']['url']);
        $this->assertEquals(['compliance_violation', 'audit_export'], $webhooks['test_webhook']['events']);
    }

    /** @test */
    public function it_can_send_webhook_for_compliance_event()
    {
        // Mock HTTP client
        Http::fake([
            'https://example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $this->webhookService->registerWebhook('test_webhook', [
            'url' => 'https://example.com/webhook',
            'secret' => 'test-secret',
            'events' => ['compliance_violation'],
        ]);

        $event = ComplianceEvent::factory()->create([
            'event_type' => 'compliance_violation',
            'severity_level' => 'high',
        ]);

        $results = $this->webhookService->sendComplianceEventWebhook($event);

        $this->assertArrayHasKey('test_webhook', $results);
        $this->assertTrue($results['test_webhook']['success']);
        $this->assertEquals(200, $results['test_webhook']['status_code']);
    }

    /** @test */
    public function it_can_process_incoming_webhook()
    {
        $this->webhookService->registerWebhook('external_system', [
            'url' => 'https://external.com/webhook',
            'secret' => 'external-secret',
            'source' => 'external_system',
        ]);

        $payload = [
            'event_type' => 'external_compliance_check',
            'check_id' => 'check-123',
            'check_type' => 'hipaa_compliance',
            'timestamp' => now()->toISOString(),
        ];

        $signature = $this->webhookService->generateSignature($payload, 'external-secret');

        $response = $this->webhookService->processIncomingWebhook($payload, $signature, 'external_system');

        $this->assertTrue($response['success']);
        $this->assertEquals('External compliance check processed', $response['message']);
    }

    /** @test */
    public function it_can_log_compliance_events()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->auditService->logComplianceEvent(
            'test_event',
            'audit',
            [
                'action_performed' => 'test_action',
                'event_data' => ['test' => 'data'],
                'compliance_context' => ['test' => 'context'],
                'severity_level' => 'low',
            ]
        );

        $this->assertDatabaseHas('compliance_events', [
            'event_type' => 'test_event',
            'event_category' => 'audit',
            'user_id' => $user->id,
            'action_performed' => 'test_action',
            'severity_level' => 'low',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'compliance_event',
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_can_cleanup_old_compliance_events()
    {
        // Create old events (more than 7 years old)
        ComplianceEvent::factory()->count(3)->create([
            'event_timestamp' => now()->subYears(8),
        ]);

        // Create recent events
        ComplianceEvent::factory()->count(2)->create([
            'event_timestamp' => now()->subDays(1),
        ]);

        $deletedCount = $this->auditService->cleanupOldComplianceEvents(2555); // 7 years in days

        $this->assertEquals(3, $deletedCount);

        // Check that old events are deleted but recent ones remain
        $this->assertEquals(2, ComplianceEvent::count());
    }

    /** @test */
    public function it_handles_soap_request_failures_gracefully()
    {
        // Mock HTTP client to simulate failure
        Http::fake([
            'https://failing-endpoint.com/soap' => Http::response('Service Unavailable', 503),
        ]);

        $this->soapService->registerSOAPClient('failing_client', [
            'endpoint' => 'https://failing-endpoint.com/soap',
        ]);

        $result = $this->soapService->sendComplianceDataToSOAP([
            'test' => 'data'
        ], 'failing_client');

        $this->assertFalse($result['success']);
        $this->assertEquals(503, $result['status_code']);
    }

    /** @test */
    public function it_validates_webhook_signatures()
    {
        $payload = ['test' => 'data'];
        $secret = 'test-secret';

        $validSignature = $this->webhookService->generateSignature($payload, $secret);
        $invalidSignature = 'invalid-signature';

        $this->assertTrue($this->webhookService->verifySignature(json_encode($payload), $validSignature, $secret));
        $this->assertFalse($this->webhookService->verifySignature(json_encode($payload), $invalidSignature, $secret));
    }

    /** @test */
    public function it_can_test_webhook_endpoints()
    {
        Http::fake([
            'https://test-webhook.com/notify' => Http::response(['received' => true], 200),
        ]);

        $this->webhookService->registerWebhook('test_endpoint', [
            'url' => 'https://test-webhook.com/notify',
            'secret' => 'test-secret',
        ]);

        $result = $this->webhookService->testWebhook('test_endpoint');

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
    }

    /** @test */
    public function it_integrates_compliance_event_with_webhook_notification()
    {
        Http::fake([
            'https://compliance-webhook.com/events' => Http::response(['processed' => true], 200),
        ]);

        $this->webhookService->registerWebhook('compliance_monitor', [
            'url' => 'https://compliance-webhook.com/events',
            'secret' => 'monitor-secret',
            'events' => ['compliance_violation'],
        ]);

        // Create a compliance violation event
        $event = ComplianceEvent::factory()->create([
            'event_type' => 'compliance_violation',
            'severity_level' => 'high',
            'event_data' => [
                'violation_type' => 'unauthorized_access',
                'resource' => 'patient_record',
            ],
        ]);

        // Send webhook notification
        $results = $this->webhookService->sendComplianceEventWebhook($event);

        // Verify webhook was sent
        $this->assertTrue($results['compliance_monitor']['success']);

        // Verify the event was logged
        $this->assertDatabaseHas('compliance_events', [
            'id' => $event->id,
            'event_type' => 'compliance_violation',
        ]);
    }
}
