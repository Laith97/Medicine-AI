<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\User;
use App\Models\Patient;
use App\Services\ComplianceMonitoringService;
use App\Services\AuditLoggingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ComplianceDocumentCheckerService
{
    protected ComplianceMonitoringService $complianceService;

    public function __construct(ComplianceMonitoringService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Perform comprehensive compliance check on a document
     */
    public function checkDocumentCompliance(Document $document, User $user, array $options = []): array
    {
        try {
            $checkResults = [
                'hipaa_compliance' => $this->checkHipaaCompliance($document),
                'content_validation' => $this->validateDocumentContent($document),
                'privacy_protection' => $this->checkPrivacyProtection($document),
                'regulatory_requirements' => $this->checkRegulatoryRequirements($document),
                'data_integrity' => $this->validateDataIntegrity($document),
                'audit_trail' => $this->validateAuditTrail($document),
            ];

            // Calculate overall compliance score
            $overallScore = $this->calculateOverallComplianceScore($checkResults);

            // Determine compliance status
            $complianceStatus = $this->determineComplianceStatus($overallScore, $checkResults);

            // Log compliance check
            AuditLoggingService::logComplianceAudit('document_compliance_check', $document->id, [
                'user_id' => $user->id,
                'overall_score' => $overallScore,
                'compliance_status' => $complianceStatus,
                'check_results' => $checkResults,
                'check_options' => $options,
            ]);

            return [
                'document_id' => $document->id,
                'compliance_status' => $complianceStatus,
                'overall_score' => $overallScore,
                'check_timestamp' => now(),
                'checked_by' => $user->id,
                'detailed_results' => $checkResults,
                'recommendations' => $this->generateComplianceRecommendations($checkResults),
                'critical_issues' => $this->identifyCriticalIssues($checkResults),
            ];

        } catch (\Exception $e) {
            Log::error('Document compliance check failed', [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to check document compliance: ' . $e->getMessage());
        }
    }

    /**
     * Check HIPAA compliance
     */
    protected function checkHipaaCompliance(Document $document): array
    {
        $violations = [];
        $score = 100;

        // Check for required HIPAA elements
        $requiredElements = [
            'privacy_notice' => 'HIPAA Privacy Notice',
            'patient_rights' => 'Patient Rights Statement',
            'authorization_language' => 'Authorization Language',
            'minimum_necessary' => 'Minimum Necessary Disclosure',
        ];

        $metadata = $document->metadata ?? [];
        $complianceData = $document->compliance_data ?? [];

        foreach ($requiredElements as $key => $description) {
            if (!isset($complianceData[$key]) || !$complianceData[$key]) {
                $violations[] = "Missing required HIPAA element: {$description}";
                $score -= 15;
            }
        }

        // Check for PHI exposure
        $phiExposure = $this->detectPhiExposure($document);
        if (!empty($phiExposure)) {
            $violations = array_merge($violations, $phiExposure);
            $score -= count($phiExposure) * 10;
        }

        // Check consent documentation
        if (!isset($complianceData['patient_consent_obtained']) || !$complianceData['patient_consent_obtained']) {
            $violations[] = "Patient consent not properly documented";
            $score -= 20;
        }

        return [
            'is_compliant' => empty($violations),
            'score' => max(0, $score),
            'violations' => $violations,
            'hipaa_version' => $complianceData['hipaa_version'] ?? 'Unknown',
            'privacy_officer_reviewed' => $complianceData['privacy_officer_reviewed'] ?? false,
        ];
    }

    /**
     * Validate document content
     */
    protected function validateDocumentContent(Document $document): array
    {
        $violations = [];
        $score = 100;

        // Get document content (this would depend on how content is stored)
        $content = $this->getDocumentContent($document);

        if (empty($content)) {
            $violations[] = "Document content is empty or inaccessible";
            $score -= 50;
            return [
                'is_valid' => false,
                'score' => $score,
                'violations' => $violations,
            ];
        }

        // Check content completeness
        $template = $document->template ?? null;
        if ($template) {
            $placeholders = $template->placeholders ?? [];
            $unfilledPlaceholders = $this->findUnfilledPlaceholders($content, $placeholders);

            if (!empty($unfilledPlaceholders)) {
                $violations[] = "Unfilled required placeholders: " . implode(', ', $unfilledPlaceholders);
                $score -= count($unfilledPlaceholders) * 5;
            }
        }

        // Check for prohibited content
        $prohibitedPatterns = [
            '/social.security.number/i' => 'SSN mentioned in document',
            '/credit.card/i' => 'Credit card information detected',
            '/password|credential/i' => 'Credentials mentioned in document',
        ];

        foreach ($prohibitedPatterns as $pattern => $message) {
            if (preg_match($pattern, $content)) {
                $violations[] = $message;
                $score -= 25;
            }
        }

        // Check document structure
        $structureIssues = $this->validateDocumentStructure($content, $document->document_type);
        $violations = array_merge($violations, $structureIssues);
        $score -= count($structureIssues) * 5;

        return [
            'is_valid' => empty($violations),
            'score' => max(0, $score),
            'violations' => $violations,
            'content_length' => strlen($content),
            'word_count' => str_word_count($content),
        ];
    }

    /**
     * Check privacy protection measures
     */
    protected function checkPrivacyProtection(Document $document): array
    {
        $violations = [];
        $score = 100;

        $metadata = $document->metadata ?? [];
        $complianceData = $document->compliance_data ?? [];

        // Check encryption status
        if (!isset($complianceData['encrypted']) || !$complianceData['encrypted']) {
            $violations[] = "Document is not properly encrypted";
            $score -= 30;
        }

        // Check access controls
        if (!isset($complianceData['access_restricted']) || !$complianceData['access_restricted']) {
            $violations[] = "Document access controls not properly configured";
            $score -= 20;
        }

        // Check data retention policy compliance
        $retentionPolicy = $complianceData['retention_policy'] ?? [];
        if (empty($retentionPolicy) || !isset($retentionPolicy['retention_period'])) {
            $violations[] = "Data retention policy not defined";
            $score -= 15;
        }

        // Check for unauthorized disclosures
        if (isset($complianceData['unauthorized_access']) && $complianceData['unauthorized_access']) {
            $violations[] = "Document has history of unauthorized access";
            $score -= 50;
        }

        return [
            'is_protected' => empty($violations),
            'score' => max(0, $score),
            'violations' => $violations,
            'encryption_method' => $complianceData['encryption_method'] ?? 'Unknown',
            'access_level' => $complianceData['access_level'] ?? 'Unknown',
        ];
    }

    /**
     * Check regulatory requirements
     */
    protected function checkRegulatoryRequirements(Document $document): array
    {
        $violations = [];
        $score = 100;

        $documentType = $document->document_type;
        $complianceData = $document->compliance_data ?? [];

        // Document-type specific regulatory checks
        switch ($documentType) {
            case 'medical_record':
                $violations = array_merge($violations, $this->checkMedicalRecordRequirements($document));
                break;
            case 'prescription':
                $violations = array_merge($violations, $this->checkPrescriptionRequirements($document));
                break;
            case 'consent_form':
                $violations = array_merge($violations, $this->checkConsentFormRequirements($document));
                break;
            case 'billing':
                $violations = array_merge($violations, $this->checkBillingRequirements($document));
                break;
        }

        // Check for required signatures
        if (!isset($complianceData['signatures_obtained']) || !$complianceData['signatures_obtained']) {
            $violations[] = "Required signatures not obtained";
            $score -= 25;
        }

        // Check witness requirements
        if (isset($complianceData['witness_required']) && $complianceData['witness_required']) {
            if (!isset($complianceData['witness_present']) || !$complianceData['witness_present']) {
                $violations[] = "Required witness not present";
                $score -= 20;
            }
        }

        $score -= count($violations) * 5;

        return [
            'is_compliant' => empty($violations),
            'score' => max(0, $score),
            'violations' => $violations,
            'document_type' => $documentType,
            'regulatory_standard' => $complianceData['regulatory_standard'] ?? 'Unknown',
        ];
    }

    /**
     * Validate data integrity
     */
    protected function validateDataIntegrity(Document $document): array
    {
        $violations = [];
        $score = 100;

        $metadata = $document->metadata ?? [];
        $complianceData = $document->compliance_data ?? [];

        // Check document hash integrity
        if (isset($complianceData['original_hash'])) {
            $currentHash = $this->calculateDocumentHash($document);
            if ($currentHash !== $complianceData['original_hash']) {
                $violations[] = "Document integrity compromised - content has been modified";
                $score -= 100; // Critical violation
            }
        }

        // Check version consistency
        if (isset($metadata['version']) && isset($complianceData['expected_version'])) {
            if ($metadata['version'] !== $complianceData['expected_version']) {
                $violations[] = "Document version mismatch";
                $score -= 15;
            }
        }

        // Check for data corruption indicators
        $corruptionIndicators = $this->checkForDataCorruption($document);
        if (!empty($corruptionIndicators)) {
            $violations = array_merge($violations, $corruptionIndicators);
            $score -= count($corruptionIndicators) * 10;
        }

        return [
            'is_integrity_maintained' => empty($violations),
            'score' => max(0, $score),
            'violations' => $violations,
            'hash_algorithm' => $complianceData['hash_algorithm'] ?? 'Unknown',
            'last_integrity_check' => $complianceData['last_integrity_check'] ?? null,
        ];
    }

    /**
     * Validate audit trail
     */
    protected function validateAuditTrail(Document $document): array
    {
        $violations = [];
        $score = 100;

        $metadata = $document->metadata ?? [];
        $complianceData = $document->compliance_data ?? [];

        // Check audit trail completeness
        $requiredAuditEvents = ['created', 'modified', 'accessed', 'reviewed'];
        $auditTrail = $complianceData['audit_trail'] ?? [];

        foreach ($requiredAuditEvents as $event) {
            if (!isset($auditTrail[$event])) {
                $violations[] = "Missing audit trail event: {$event}";
                $score -= 10;
            }
        }

        // Check timestamps consistency
        if (isset($metadata['created_at']) && isset($document->created_at)) {
            if ($metadata['created_at'] != $document->created_at->toISOString()) {
                $violations[] = "Audit trail timestamp mismatch for creation";
                $score -= 15;
            }
        }

        // Check user accountability
        if (!isset($metadata['created_by']) || empty($metadata['created_by'])) {
            $violations[] = "Document creator not properly identified in audit trail";
            $score -= 20;
        }

        return [
            'is_audit_trail_complete' => empty($violations),
            'score' => max(0, $score),
            'violations' => $violations,
            'audit_events_count' => count($auditTrail),
            'last_audit_update' => $complianceData['last_audit_update'] ?? null,
        ];
    }

    /**
     * Calculate overall compliance score
     */
    protected function calculateOverallComplianceScore(array $checkResults): float
    {
        $weights = [
            'hipaa_compliance' => 0.3,
            'content_validation' => 0.2,
            'privacy_protection' => 0.25,
            'regulatory_requirements' => 0.15,
            'data_integrity' => 0.05,
            'audit_trail' => 0.05,
        ];

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($checkResults as $checkType => $result) {
            if (isset($weights[$checkType]) && isset($result['score'])) {
                $totalScore += $result['score'] * $weights[$checkType];
                $totalWeight += $weights[$checkType];
            }
        }

        return $totalWeight > 0 ? round($totalScore / $totalWeight, 2) : 0;
    }

    /**
     * Determine compliance status based on score and results
     */
    protected function determineComplianceStatus(float $score, array $checkResults): string
    {
        // Check for critical violations first
        $criticalViolations = $this->identifyCriticalIssues($checkResults);
        if (!empty($criticalViolations)) {
            return 'non_compliant';
        }

        if ($score >= 90) {
            return 'fully_compliant';
        } elseif ($score >= 75) {
            return 'mostly_compliant';
        } elseif ($score >= 60) {
            return 'partially_compliant';
        } else {
            return 'non_compliant';
        }
    }

    /**
     * Generate compliance recommendations
     */
    protected function generateComplianceRecommendations(array $checkResults): array
    {
        $recommendations = [];

        foreach ($checkResults as $checkType => $result) {
            if (!empty($result['violations'])) {
                $recommendations = array_merge($recommendations, $this->getRecommendationsForCheck($checkType, $result['violations']));
            }
        }

        return array_unique($recommendations);
    }

    /**
     * Identify critical compliance issues
     */
    protected function identifyCriticalIssues(array $checkResults): array
    {
        $criticalIssues = [];

        // Check for HIPAA violations
        if (isset($checkResults['hipaa_compliance']['violations'])) {
            foreach ($checkResults['hipaa_compliance']['violations'] as $violation) {
                if (str_contains(strtolower($violation), 'privacy') ||
                    str_contains(strtolower($violation), 'consent') ||
                    str_contains(strtolower($violation), 'phi')) {
                    $criticalIssues[] = $violation;
                }
            }
        }

        // Check for data integrity issues
        if (isset($checkResults['data_integrity']['violations'])) {
            $criticalIssues = array_merge($criticalIssues, $checkResults['data_integrity']['violations']);
        }

        return $criticalIssues;
    }

    /**
     * Detect PHI exposure in document
     */
    protected function detectPhiExposure(Document $document): array
    {
        $content = $this->getDocumentContent($document);
        $violations = [];

        // PHI detection patterns
        $phiPatterns = [
            '/\d{3}-\d{2}-\d{4}/' => 'SSN pattern detected',
            '/\d{10}/' => '10-digit number (potential phone or ID)',
            '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/' => 'Email address detected',
            '/\b\d{1,2}\/\d{1,2}\/\d{4}\b/' => 'Date of birth pattern detected',
        ];

        foreach ($phiPatterns as $pattern => $message) {
            if (preg_match($pattern, $content)) {
                $violations[] = $message;
            }
        }

        return $violations;
    }

    /**
     * Get document content (implementation depends on storage method)
     */
    protected function getDocumentContent(Document $document): string
    {
        // This is a placeholder - actual implementation would depend on how content is stored
        // Could be from a file, database field, or external storage
        return $document->content ?? '';
    }

    /**
     * Find unfilled placeholders in content
     */
    protected function findUnfilledPlaceholders(string $content, array $placeholders): array
    {
        $unfilled = [];

        foreach ($placeholders as $key => $config) {
            if ($config['required'] ?? false) {
                $pattern = '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/i';
                if (preg_match($pattern, $content)) {
                    $unfilled[] = $key;
                }
            }
        }

        return $unfilled;
    }

    /**
     * Validate document structure
     */
    protected function validateDocumentStructure(string $content, string $documentType): array
    {
        $issues = [];

        // Document-type specific structure validation
        switch ($documentType) {
            case 'consent_form':
                if (!preg_match('/consent|agreement/i', $content)) {
                    $issues[] = "Consent form missing consent language";
                }
                break;
            case 'prescription':
                if (!preg_match('/rx|prescription/i', $content)) {
                    $issues[] = "Prescription missing prescription indicators";
                }
                break;
        }

        return $issues;
    }

    /**
     * Check medical record specific requirements
     */
    protected function checkMedicalRecordRequirements(Document $document): array
    {
        $violations = [];
        $complianceData = $document->compliance_data ?? [];

        // Check for required medical record elements
        $requiredElements = [
            'patient_identification',
            'medical_history',
            'physical_exam',
            'assessment',
            'plan',
        ];

        foreach ($requiredElements as $element) {
            if (!isset($complianceData[$element]) || !$complianceData[$element]) {
                $violations[] = "Medical record missing required element: {$element}";
            }
        }

        return $violations;
    }

    /**
     * Check prescription specific requirements
     */
    protected function checkPrescriptionRequirements(Document $document): array
    {
        $violations = [];
        $complianceData = $document->compliance_data ?? [];

        // Check DEA compliance for controlled substances
        if (isset($complianceData['controlled_substance']) && $complianceData['controlled_substance']) {
            if (!isset($complianceData['dea_number_valid']) || !$complianceData['dea_number_valid']) {
                $violations[] = "Controlled substance prescription missing valid DEA number";
            }
        }

        return $violations;
    }

    /**
     * Check consent form specific requirements
     */
    protected function checkConsentFormRequirements(Document $document): array
    {
        $violations = [];
        $complianceData = $document->compliance_data ?? [];

        // Check for informed consent elements
        $requiredElements = [
            'procedure_description',
            'risks_disclosed',
            'benefits_explained',
            'alternatives_discussed',
            'patient_questions_answered',
        ];

        foreach ($requiredElements as $element) {
            if (!isset($complianceData[$element]) || !$complianceData[$element]) {
                $violations[] = "Consent form missing required element: {$element}";
            }
        }

        return $violations;
    }

    /**
     * Check billing document requirements
     */
    protected function checkBillingRequirements(Document $document): array
    {
        $violations = [];
        $complianceData = $document->compliance_data ?? [];

        // Check for billing compliance elements
        if (!isset($complianceData['itemized_charges']) || !$complianceData['itemized_charges']) {
            $violations[] = "Billing document missing itemized charges";
        }

        if (!isset($complianceData['insurance_information']) || !$complianceData['insurance_information']) {
            $violations[] = "Billing document missing insurance information";
        }

        return $violations;
    }

    /**
     * Calculate document hash for integrity checking
     */
    protected function calculateDocumentHash(Document $document): string
    {
        $content = $this->getDocumentContent($document);
        return hash('sha256', $content . $document->id . $document->created_at);
    }

    /**
     * Check for data corruption indicators
     */
    protected function checkForDataCorruption(Document $document): array
    {
        $issues = [];
        $content = $this->getDocumentContent($document);

        // Check for unusual character patterns that might indicate corruption
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content)) {
            $issues[] = "Document contains control characters (possible corruption)";
        }

        // Check for extremely long lines (might indicate formatting issues)
        $lines = explode("\n", $content);
        $longLines = array_filter($lines, function($line) {
            return strlen($line) > 10000;
        });

        if (count($longLines) > 0) {
            $issues[] = "Document contains unusually long lines (possible formatting corruption)";
        }

        return $issues;
    }

    /**
     * Get recommendations for specific check type
     */
    protected function getRecommendationsForCheck(string $checkType, array $violations): array
    {
        $recommendations = [];

        foreach ($violations as $violation) {
            switch ($checkType) {
                case 'hipaa_compliance':
                    $recommendations[] = "Review and update HIPAA compliance elements";
                    break;
                case 'content_validation':
                    $recommendations[] = "Complete all required document fields and placeholders";
                    break;
                case 'privacy_protection':
                    $recommendations[] = "Implement proper encryption and access controls";
                    break;
                case 'regulatory_requirements':
                    $recommendations[] = "Ensure all regulatory requirements are met for document type";
                    break;
                case 'data_integrity':
                    $recommendations[] = "Verify document integrity and consider re-creation if compromised";
                    break;
                case 'audit_trail':
                    $recommendations[] = "Complete audit trail documentation";
                    break;
            }
        }

        return $recommendations;
    }

    /**
     * Get compliance check history for a document
     */
    public function getComplianceHistory(Document $document): array
    {
        // This would query audit logs for compliance checks on this document
        return [
            'document_id' => $document->id,
            'total_checks' => 0,
            'last_check_date' => null,
            'compliance_trend' => [],
            'critical_issues_history' => [],
        ];
    }
}
