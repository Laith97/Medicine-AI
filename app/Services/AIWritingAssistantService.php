<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\User;
use App\Models\Patient;
use App\Services\OpenAIClient;
use App\Services\AuditLoggingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AIWritingAssistantService
{
    protected OpenAIClient $openAIClient;
    protected ComplianceMonitoringService $complianceService;

    public function __construct(OpenAIClient $openAIClient, ComplianceMonitoringService $complianceService)
    {
        $this->openAIClient = $openAIClient;
        $this->complianceService = $complianceService;
    }

    /**
     * Generate compliance document content using AI
     */
    public function generateDocumentContent(
        DocumentTemplate $template,
        array $contextData,
        User $user,
        array $options = []
    ): array {
        try {
            // Prepare AI prompt with template and context
            $prompt = $this->buildGenerationPrompt($template, $contextData, $options);

            // Generate content using GPT
            $generatedContent = $this->openAIClient->ask($prompt);

            // Validate generated content against compliance rules
            $validationResult = $this->validateGeneratedContent($generatedContent, $template, $contextData);

            // Log AI generation activity
            AuditLoggingService::logComplianceAudit('ai_document_generation', $template->id, [
                'user_id' => $user->id,
                'template_type' => $template->template_type,
                'generation_options' => $options,
                'validation_passed' => $validationResult['is_valid'],
                'content_length' => strlen($generatedContent),
            ]);

            return [
                'content' => $generatedContent,
                'validation_result' => $validationResult,
                'metadata' => [
                    'generated_at' => now(),
                    'generated_by' => $user->id,
                    'ai_model' => 'gpt-3.5-turbo',
                    'template_version' => $template->updated_at,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('AI document generation failed', [
                'template_id' => $template->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to generate document content: ' . $e->getMessage());
        }
    }

    /**
     * Enhance existing document content with AI suggestions
     */
    public function enhanceDocumentContent(
        string $existingContent,
        DocumentTemplate $template,
        array $contextData,
        User $user,
        array $enhancementOptions = []
    ): array {
        try {
            $prompt = $this->buildEnhancementPrompt($existingContent, $template, $contextData, $enhancementOptions);

            $enhancedContent = $this->openAIClient->ask($prompt);

            // Validate enhanced content
            $validationResult = $this->validateGeneratedContent($enhancedContent, $template, $contextData);

            AuditLoggingService::logComplianceAudit('ai_document_enhancement', $template->id, [
                'user_id' => $user->id,
                'enhancement_type' => $enhancementOptions['type'] ?? 'general',
                'original_length' => strlen($existingContent),
                'enhanced_length' => strlen($enhancedContent),
            ]);

            return [
                'original_content' => $existingContent,
                'enhanced_content' => $enhancedContent,
                'validation_result' => $validationResult,
                'changes_summary' => $this->summarizeChanges($existingContent, $enhancedContent),
            ];

        } catch (\Exception $e) {
            Log::error('AI document enhancement failed', [
                'template_id' => $template->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to enhance document content: ' . $e->getMessage());
        }
    }

    /**
     * Generate document sections based on patient data
     */
    public function generatePatientSpecificSections(
        Patient $patient,
        string $sectionType,
        array $medicalContext = [],
        User $user
    ): array {
        try {
            $prompt = $this->buildPatientSectionPrompt($patient, $sectionType, $medicalContext);

            $generatedSection = $this->openAIClient->ask($prompt);

            // Validate patient data privacy compliance
            $privacyValidation = $this->validatePatientDataUsage($generatedSection, $patient);

            AuditLoggingService::logComplianceAudit('ai_patient_section_generation', $patient->id, [
                'user_id' => $user->id,
                'section_type' => $sectionType,
                'patient_id' => $patient->id,
                'privacy_compliant' => $privacyValidation['is_compliant'],
            ]);

            return [
                'section_content' => $generatedSection,
                'section_type' => $sectionType,
                'patient_id' => $patient->id,
                'privacy_validation' => $privacyValidation,
                'metadata' => [
                    'generated_at' => now(),
                    'generated_by' => $user->id,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('AI patient section generation failed', [
                'patient_id' => $patient->id,
                'section_type' => $sectionType,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to generate patient section: ' . $e->getMessage());
        }
    }

    /**
     * Build AI prompt for document generation
     */
    protected function buildGenerationPrompt(DocumentTemplate $template, array $contextData, array $options): string
    {
        $templateContent = $template->template_content;
        $placeholders = $template->placeholders ?? [];

        $prompt = "Generate a compliance document based on the following template and context.\n\n";
        $prompt .= "TEMPLATE TYPE: {$template->template_type}\n";
        $prompt .= "TEMPLATE DESCRIPTION: {$template->description}\n\n";

        $prompt .= "TEMPLATE CONTENT:\n{$templateContent}\n\n";

        $prompt .= "AVAILABLE CONTEXT DATA:\n";
        foreach ($contextData as $key => $value) {
            $prompt .= "- {$key}: {$value}\n";
        }

        $prompt .= "\nPLACEHOLDERS TO FILL:\n";
        foreach ($placeholders as $key => $config) {
            $required = $config['required'] ? 'REQUIRED' : 'OPTIONAL';
            $type = $config['type'] ?? 'text';
            $prompt .= "- {{ {$key} }} ({$type}, {$required})\n";
        }

        $prompt .= "\nINSTRUCTIONS:\n";
        $prompt .= "1. Generate complete, professional document content\n";
        $prompt .= "2. Fill all required placeholders with appropriate content\n";
        $prompt .= "3. Ensure HIPAA compliance and patient privacy\n";
        $prompt .= "4. Use medical terminology accurately\n";
        $prompt .= "5. Follow healthcare documentation standards\n";

        if (isset($options['tone'])) {
            $prompt .= "6. Use {$options['tone']} tone\n";
        }

        if (isset($options['length'])) {
            $prompt .= "7. Keep content approximately {$options['length']}\n";
        }

        return $prompt;
    }

    /**
     * Build AI prompt for content enhancement
     */
    protected function buildEnhancementPrompt(
        string $existingContent,
        DocumentTemplate $template,
        array $contextData,
        array $options
    ): string {
        $enhancementType = $options['type'] ?? 'general';

        $prompt = "Enhance the following document content while maintaining compliance and accuracy.\n\n";
        $prompt .= "ENHANCEMENT TYPE: {$enhancementType}\n";
        $prompt .= "TEMPLATE TYPE: {$template->template_type}\n\n";

        $prompt .= "EXISTING CONTENT:\n{$existingContent}\n\n";

        $prompt .= "CONTEXT DATA:\n";
        foreach ($contextData as $key => $value) {
            $prompt .= "- {$key}: {$value}\n";
        }

        $prompt .= "\nENHANCEMENT INSTRUCTIONS:\n";

        switch ($enhancementType) {
            case 'clarity':
                $prompt .= "- Improve clarity and readability\n- Use simpler language where appropriate\n- Maintain medical accuracy\n";
                break;
            case 'compliance':
                $prompt .= "- Strengthen compliance language\n- Add required legal disclaimers\n- Ensure HIPAA compliance\n";
                break;
            case 'completeness':
                $prompt .= "- Add missing required sections\n- Include all necessary details\n- Ensure comprehensive coverage\n";
                break;
            default:
                $prompt .= "- Improve overall quality and professionalism\n- Enhance medical terminology accuracy\n- Optimize for healthcare standards\n";
        }

        $prompt .= "- Preserve all patient-specific information\n- Maintain document structure\n- Keep original meaning intact\n";

        return $prompt;
    }

    /**
     * Build AI prompt for patient-specific sections
     */
    protected function buildPatientSectionPrompt(Patient $patient, string $sectionType, array $medicalContext): string
    {
        $prompt = "Generate a {$sectionType} section for a medical document based on patient information.\n\n";

        $prompt .= "PATIENT INFORMATION:\n";
        $prompt .= "- Name: {$patient->first_name} {$patient->last_name}\n";
        $prompt .= "- Date of Birth: {$patient->date_of_birth}\n";
        $prompt .= "- Gender: {$patient->gender}\n";
        $prompt .= "- Medical Record Number: {$patient->medical_record_number}\n";

        if (!empty($medicalContext)) {
            $prompt .= "\nMEDICAL CONTEXT:\n";
            foreach ($medicalContext as $key => $value) {
                $prompt .= "- {$key}: {$value}\n";
            }
        }

        $prompt .= "\nSECTION REQUIREMENTS:\n";

        switch ($sectionType) {
            case 'medical_history':
                $prompt .= "- Summarize relevant medical history\n- Include chronic conditions\n- Note allergies and medications\n- Use professional medical terminology\n";
                break;
            case 'assessment':
                $prompt .= "- Provide clinical assessment\n- Include vital signs and observations\n- Document current condition\n- Note any concerns or recommendations\n";
                break;
            case 'treatment_plan':
                $prompt .= "- Outline treatment recommendations\n- Include medication instructions\n- Specify follow-up requirements\n- Note patient education needs\n";
                break;
            case 'consent':
                $prompt .= "- Include informed consent language\n- Explain procedures and risks\n- Document patient understanding\n- Add HIPAA privacy notices\n";
                break;
            default:
                $prompt .= "- Generate appropriate medical documentation\n- Maintain clinical accuracy\n- Follow healthcare standards\n";
        }

        $prompt .= "\nCOMPLIANCE REQUIREMENTS:\n";
        $prompt .= "- Protect patient privacy (HIPAA compliant)\n- Use appropriate medical terminology\n- Include necessary disclaimers\n- Document accurately and completely\n";

        return $prompt;
    }

    /**
     * Validate generated content against compliance rules
     */
    protected function validateGeneratedContent(string $content, DocumentTemplate $template, array $contextData): array
    {
        $violations = [];

        // Check for required compliance elements
        $complianceRules = $template->getComplianceRules();

        foreach ($complianceRules as $rule) {
            switch ($rule['type']) {
                case 'hipaa_required_fields':
                    $violations = array_merge($violations, $this->checkHipaaCompliance($content, $rule));
                    break;
                case 'content_validation':
                    $violations = array_merge($violations, $this->validateContentRequirements($content, $rule));
                    break;
            }
        }

        // Check for sensitive data exposure
        $privacyViolations = $this->checkPrivacyCompliance($content, $contextData);
        $violations = array_merge($violations, $privacyViolations);

        return [
            'is_valid' => empty($violations),
            'violations' => $violations,
            'compliance_score' => max(0, 100 - (count($violations) * 10)),
        ];
    }

    /**
     * Check HIPAA compliance in generated content
     */
    protected function checkHipaaCompliance(string $content, array $rule): array
    {
        $violations = [];
        $requiredElements = $rule['required_elements'] ?? [];

        foreach ($requiredElements as $element) {
            if (!str_contains(strtolower($content), strtolower($element))) {
                $violations[] = "Missing required HIPAA element: {$element}";
            }
        }

        return $violations;
    }

    /**
     * Validate content requirements
     */
    protected function validateContentRequirements(string $content, array $rule): array
    {
        $violations = [];

        if (isset($rule['min_length']) && strlen($content) < $rule['min_length']) {
            $violations[] = "Content too short (minimum {$rule['min_length']} characters required)";
        }

        if (isset($rule['required_phrases'])) {
            foreach ($rule['required_phrases'] as $phrase) {
                if (!str_contains(strtolower($content), strtolower($phrase))) {
                    $violations[] = "Missing required phrase: {$phrase}";
                }
            }
        }

        return $violations;
    }

    /**
     * Check privacy compliance
     */
    protected function checkPrivacyCompliance(string $content, array $contextData): array
    {
        $violations = [];

        // Check for accidental PII exposure
        $sensitivePatterns = [
            'ssn' => '/\d{3}-\d{2}-\d{4}/',
            'phone' => '/\d{3}-\d{3}-\d{4}/',
            'email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
        ];

        foreach ($sensitivePatterns as $type => $pattern) {
            if (preg_match($pattern, $content)) {
                $violations[] = "Potential {$type} exposure detected in generated content";
            }
        }

        return $violations;
    }

    /**
     * Validate patient data usage in generated content
     */
    protected function validatePatientDataUsage(string $content, Patient $patient): array
    {
        $issues = [];

        // Check if patient identifiers are properly handled
        $patientName = strtolower($patient->first_name . ' ' . $patient->last_name);
        if (str_contains(strtolower($content), $patientName)) {
            // This is expected in medical documents, but we should verify context
            $issues[] = "Patient name included - verify appropriate use";
        }

        // Check for unauthorized data exposure
        if (str_contains($content, $patient->medical_record_number)) {
            $issues[] = "Medical record number exposed - ensure necessary for document";
        }

        return [
            'is_compliant' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Summarize changes between original and enhanced content
     */
    protected function summarizeChanges(string $original, string $enhanced): array
    {
        $originalLength = strlen($original);
        $enhancedLength = strlen($enhanced);
        $lengthDifference = $enhancedLength - $originalLength;

        // Simple word count comparison
        $originalWords = str_word_count($original);
        $enhancedWords = str_word_count($enhanced);

        return [
            'original_length' => $originalLength,
            'enhanced_length' => $enhancedLength,
            'length_change' => $lengthDifference,
            'original_words' => $originalWords,
            'enhanced_words' => $enhancedWords,
            'word_change' => $enhancedWords - $originalWords,
        ];
    }

    /**
     * Get AI generation statistics for a user
     */
    public function getUserGenerationStats(User $user, array $dateRange = []): array
    {
        // This would query audit logs for AI generation activities
        // Implementation depends on how audit logs are structured
        return [
            'total_generations' => 0,
            'successful_generations' => 0,
            'failed_generations' => 0,
            'most_used_template_types' => [],
            'average_generation_time' => 0,
        ];
    }
}
