<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SOAPIntegrationService
{
    protected array $soapClients = [];

    /**
     * Convert data to SOAP format.
     */
    public function convertToSOAP(array $data, string $template = 'default'): string
    {
        $soapEnvelope = $this->buildSOAPEnvelope($data, $template);

        return $soapEnvelope;
    }

    /**
     * Send SOAP request to external system.
     */
    public function sendSOAPRequest(string $endpoint, string $soapXml, array $headers = []): array
    {
        try {
            $defaultHeaders = [
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => '',
            ];

            $response = Http::withHeaders(array_merge($defaultHeaders, $headers))
                ->withBody($soapXml, 'text/xml')
                ->post($endpoint);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'response' => $response->body(),
                    'status_code' => $response->status(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status(),
                    'response' => $response->body(),
                    'status_code' => $response->status(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('SOAP request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse SOAP response.
     */
    public function parseSOAPResponse(string $soapXml): array
    {
        try {
            $xml = simplexml_load_string($soapXml);
            $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');

            // Extract SOAP body
            $body = $xml->xpath('//soap:Body')[0] ?? null;

            if (!$body) {
                return [
                    'success' => false,
                    'error' => 'Invalid SOAP response: missing SOAP body',
                ];
            }

            // Convert XML to array
            $result = $this->xmlToArray($body);

            return [
                'success' => true,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to parse SOAP response: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build SOAP envelope from data.
     */
    protected function buildSOAPEnvelope(array $data, string $template): string
    {
        $envelope = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $envelope .= '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . "\n";
        $envelope .= '  <soap:Body>' . "\n";

        switch ($template) {
            case 'compliance_data':
                $envelope .= $this->buildComplianceDataBody($data);
                break;
            case 'audit_trail':
                $envelope .= $this->buildAuditTrailBody($data);
                break;
            case 'health_data':
                $envelope .= $this->buildHealthDataBody($data);
                break;
            default:
                $envelope .= $this->buildGenericBody($data);
        }

        $envelope .= '  </soap:Body>' . "\n";
        $envelope .= '</soap:Envelope>';

        return $envelope;
    }

    /**
     * Build compliance data SOAP body.
     */
    protected function buildComplianceDataBody(array $data): string
    {
        $body = '    <ComplianceData xmlns="http://compliance.example.com/">' . "\n";
        $body .= '      <ComplianceReport>' . "\n";
        $body .= '        <ReportId>' . ($data['report_id'] ?? Str::uuid()) . '</ReportId>' . "\n";
        $body .= '        <GeneratedAt>' . ($data['generated_at'] ?? now()->toISOString()) . '</GeneratedAt>' . "\n";
        $body .= '        <PeriodStart>' . ($data['period_start'] ?? '') . '</PeriodStart>' . "\n";
        $body .= '        <PeriodEnd>' . ($data['period_end'] ?? '') . '</PeriodEnd>' . "\n";

        if (isset($data['compliance_metrics'])) {
            $body .= '        <ComplianceMetrics>' . "\n";
            foreach ($data['compliance_metrics'] as $key => $value) {
                $body .= '          <' . $this->toCamelCase($key) . '>' . htmlspecialchars($value) . '</' . $this->toCamelCase($key) . '>' . "\n";
            }
            $body .= '        </ComplianceMetrics>' . "\n";
        }

        if (isset($data['violations'])) {
            $body .= '        <Violations>' . "\n";
            foreach ($data['violations'] as $violation) {
                $body .= '          <Violation>' . "\n";
                $body .= '            <Type>' . htmlspecialchars($violation['type'] ?? '') . '</Type>' . "\n";
                $body .= '            <Severity>' . htmlspecialchars($violation['severity'] ?? 'low') . '</Severity>' . "\n";
                $body .= '            <Description>' . htmlspecialchars($violation['description'] ?? '') . '</Description>' . "\n";
                $body .= '          </Violation>' . "\n";
            }
            $body .= '        </Violations>' . "\n";
        }

        $body .= '      </ComplianceReport>' . "\n";
        $body .= '    </ComplianceData>' . "\n";

        return $body;
    }

    /**
     * Build audit trail SOAP body.
     */
    protected function buildAuditTrailBody(array $data): string
    {
        $body = '    <AuditTrail xmlns="http://audit.example.com/">' . "\n";
        $body .= '      <AuditReport>' . "\n";
        $body .= '        <ReportId>' . ($data['report_id'] ?? Str::uuid()) . '</ReportId>' . "\n";
        $body .= '        <PeriodStart>' . ($data['period_start'] ?? '') . '</PeriodStart>' . "\n";
        $body .= '        <PeriodEnd>' . ($data['period_end'] ?? '') . '</PeriodEnd>' . "\n";

        if (isset($data['audit_events'])) {
            $body .= '        <AuditEvents>' . "\n";
            foreach ($data['audit_events'] as $event) {
                $body .= '          <AuditEvent>' . "\n";
                $body .= '            <EventId>' . ($event['id'] ?? '') . '</EventId>' . "\n";
                $body .= '            <Timestamp>' . ($event['timestamp'] ?? '') . '</Timestamp>' . "\n";
                $body .= '            <EventType>' . htmlspecialchars($event['event_type'] ?? '') . '</EventType>' . "\n";
                $body .= '            <User>' . htmlspecialchars($event['user'] ?? '') . '</User>' . "\n";
                $body .= '            <Action>' . htmlspecialchars($event['action'] ?? '') . '</Action>' . "\n";
                $body .= '            <ResourceType>' . htmlspecialchars($event['resource_type'] ?? '') . '</ResourceType>' . "\n";
                $body .= '            <IPAddress>' . ($event['ip_address'] ?? '') . '</IPAddress>' . "\n";
                $body .= '          </AuditEvent>' . "\n";
            }
            $body .= '        </AuditEvents>' . "\n";
        }

        $body .= '      </AuditReport>' . "\n";
        $body .= '    </AuditTrail>' . "\n";

        return $body;
    }

    /**
     * Build health data SOAP body.
     */
    protected function buildHealthDataBody(array $data): string
    {
        $body = '    <HealthData xmlns="http://health.example.com/" xmlns:hl7="urn:hl7-org:v3">' . "\n";
        $body .= '      <PatientRecord>' . "\n";
        $body .= '        <PatientId>' . ($data['patient_id'] ?? '') . '</PatientId>' . "\n";
        $body .= '        <RecordDate>' . ($data['record_date'] ?? now()->toDateString()) . '</RecordDate>' . "\n";

        if (isset($data['vital_signs'])) {
            $body .= '        <VitalSigns>' . "\n";
            foreach ($data['vital_signs'] as $vital) {
                $body .= '          <VitalSign>' . "\n";
                $body .= '            <Type>' . htmlspecialchars($vital['type'] ?? '') . '</Type>' . "\n";
                $body .= '            <Value>' . ($vital['value'] ?? '') . '</Value>' . "\n";
                $body .= '            <Unit>' . ($vital['unit'] ?? '') . '</Unit>' . "\n";
                $body .= '            <Timestamp>' . ($vital['timestamp'] ?? '') . '</Timestamp>' . "\n";
                $body .= '          </VitalSign>' . "\n";
            }
            $body .= '        </VitalSigns>' . "\n";
        }

        if (isset($data['diagnoses'])) {
            $body .= '        <Diagnoses>' . "\n";
            foreach ($data['diagnoses'] as $diagnosis) {
                $body .= '          <Diagnosis>' . "\n";
                $body .= '            <Code>' . ($diagnosis['code'] ?? '') . '</Code>' . "\n";
                $body .= '            <Description>' . htmlspecialchars($diagnosis['description'] ?? '') . '</Description>' . "\n";
                $body .= '            <Date>' . ($diagnosis['date'] ?? '') . '</Date>' . "\n";
                $body .= '          </Diagnosis>' . "\n";
            }
            $body .= '        </Diagnoses>' . "\n";
        }

        $body .= '      </PatientRecord>' . "\n";
        $body .= '    </HealthData>' . "\n";

        return $body;
    }

    /**
     * Build generic SOAP body.
     */
    protected function buildGenericBody(array $data): string
    {
        $body = '    <GenericData xmlns="http://generic.example.com/">' . "\n";

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $body .= '      <' . $this->toCamelCase($key) . '>' . "\n";
                $body .= $this->arrayToXml($value, 8);
                $body .= '      </' . $this->toCamelCase($key) . '>' . "\n";
            } else {
                $body .= '      <' . $this->toCamelCase($key) . '>' . htmlspecialchars($value) . '</' . $this->toCamelCase($key) . '>' . "\n";
            }
        }

        $body .= '    </GenericData>' . "\n";

        return $body;
    }

    /**
     * Convert array to XML.
     */
    protected function arrayToXml(array $data, int $indent = 0): string
    {
        $xml = '';
        $indentStr = str_repeat(' ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $xml .= $indentStr . '<' . $this->toCamelCase($key) . '>' . "\n";
                $xml .= $this->arrayToXml($value, $indent + 2);
                $xml .= $indentStr . '</' . $this->toCamelCase($key) . '>' . "\n";
            } else {
                $xml .= $indentStr . '<' . $this->toCamelCase($key) . '>' . htmlspecialchars($value) . '</' . $this->toCamelCase($key) . '>' . "\n";
            }
        }

        return $xml;
    }

    /**
     * Convert XML to array.
     */
    protected function xmlToArray(\SimpleXMLElement $xml): array
    {
        $result = [];

        foreach ($xml->children() as $child) {
            $childName = $child->getName();

            if ($child->count() > 0) {
                $result[$childName] = $this->xmlToArray($child);
            } else {
                $result[$childName] = (string) $child;
            }
        }

        return $result;
    }

    /**
     * Convert string to CamelCase.
     */
    protected function toCamelCase(string $string): string
    {
        return Str::camel($string);
    }

    /**
     * Register a SOAP client configuration.
     */
    public function registerSOAPClient(string $name, array $config): void
    {
        $this->soapClients[$name] = $config;
    }

    /**
     * Get registered SOAP client configuration.
     */
    public function getSOAPClient(string $name): ?array
    {
        return $this->soapClients[$name] ?? null;
    }

    /**
     * Send compliance data to external SOAP service.
     */
    public function sendComplianceDataToSOAP(array $complianceData, string $clientName): array
    {
        $client = $this->getSOAPClient($clientName);

        if (!$client) {
            return [
                'success' => false,
                'error' => "SOAP client '{$clientName}' not registered",
            ];
        }

        $soapXml = $this->convertToSOAP($complianceData, 'compliance_data');

        return $this->sendSOAPRequest($client['endpoint'], $soapXml, $client['headers'] ?? []);
    }

    /**
     * Send audit trail data to external SOAP service.
     */
    public function sendAuditTrailToSOAP(array $auditData, string $clientName): array
    {
        $client = $this->getSOAPClient($clientName);

        if (!$client) {
            return [
                'success' => false,
                'error' => "SOAP client '{$clientName}' not registered",
            ];
        }

        $soapXml = $this->convertToSOAP($auditData, 'audit_trail');

        return $this->sendSOAPRequest($client['endpoint'], $soapXml, $client['headers'] ?? []);
    }

    /**
     * Get registered SOAP clients.
     */
    public function getSOAPClients(): array
    {
        return $this->soapClients;
    }
}
