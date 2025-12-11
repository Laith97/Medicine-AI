<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\Document;
use App\Models\User;
use App\Services\ComplianceMonitoringService;
use App\Services\AuditLoggingService;
use App\Services\AIWritingAssistantService;
use App\Services\TemplateAutofillService;
use App\Services\DocumentVersionControlService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class DocumentTemplateService
{
    protected ComplianceMonitoringService $complianceService;
    protected AIWritingAssistantService $aiAssistant;
    protected TemplateAutofillService $autofillService;
    protected DocumentVersionControlService $versionControl;

    public function __construct(
        ComplianceMonitoringService $complianceService,
        AIWritingAssistantService $aiAssistant,
        TemplateAutofillService $autofillService,
        DocumentVersionControlService $versionControl
    ) {
        $this->complianceService = $complianceService;
        $this->aiAssistant = $aiAssistant;
        $this->autofillService = $autofillService;
        $this->versionControl = $versionControl;
    }

    /**
     * Create a new document template
     */
    public function createTemplate(array $data, User $creator): DocumentTemplate
    {
        // Validate template data
        $this->validateTemplateData($data);

        // Extract placeholders from content
        $placeholders = $this->extractPlaceholdersFromContent($data['template_content']);

        $template = DocumentTemplate::create([
            'name' => $data['name'],
            'template_type' => $data['template_type'],
            'description' => $data['description'] ?? null,
            'template_content' => $data['template_content'],
            'placeholders' => $placeholders,
            'compliance_rules' => $data['compliance_rules'] ?? [],
            'metadata' => $data['metadata'] ?? [],
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $data['is_default'] ?? false,
            'created_by' => $creator->id,
        ]);

        // If this is set as default, unset other defaults for this type
        if ($template->is_default) {
            $this->setDefaultTemplate($template);
        }

        // Log template creation
        AuditLoggingService::logComplianceAudit('document_template_created', $template->id, [
            'template_type' => $template->template_type,
            'created_by' => $creator->id,
        ]);

        return $template;
    }

    /**
     * Update an existing template
     */
    public function updateTemplate(DocumentTemplate $template, array $data, User $updater): DocumentTemplate
    {
        // Validate template data
        $this->validateTemplateData($data);

        // Extract placeholders if content changed
        if (isset($data['template_content'])) {
            $data['placeholders'] = $this->extractPlaceholdersFromContent($data['template_content']);
        }

        $template->update(array_merge($data, [
            'updated_by' => $updater->id,
        ]));

        // Handle default template logic
        if (isset($data['is_default']) && $data['is_default']) {
            $this->setDefaultTemplate($template);
        }

        // Log template update
        AuditLoggingService::logComplianceAudit('document_template_updated', $template->id, [
            'updated_by' => $updater->id,
            'changes' => array_keys($data),
        ]);

        return $template;
    }

    /**
     * Render a document from template
     */
    public function renderDocument(DocumentTemplate $template, array $data, User $renderer): Document
    {
        // Validate required placeholders
        $validationErrors = $template->validatePlaceholders($data);
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException('Template validation failed: ' . implode(', ', $validationErrors));
        }

        // Validate compliance requirements
        $complianceViolations = $template->validateCompliance($data);
        if (!empty($complianceViolations)) {
            throw new \InvalidArgumentException('Compliance validation failed: ' . implode(', ', $complianceViolations));
        }

        // Render template content
        $renderedContent = $template->render($data);

        // Create document
        $document = Document::create([
            'title' => $data['title'] ?? $template->name . ' - ' . now()->format('Y-m-d'),
            'description' => $data['description'] ?? $template->description,
            'document_type' => $template->template_type,
            'status' => 'draft',
            'workflow_state' => 'created',
            'metadata' => array_merge($data, [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'rendered_at' => now(),
                'rendered_by' => $renderer->id,
            ]),
            'compliance_data' => [
                'template_compliance_rules' => $template->compliance_rules,
                'validation_passed' => true,
                'compliance_check_timestamp' => now(),
            ],
            'created_by' => $renderer->id,
        ]);

        // Log document creation
        AuditLoggingService::logComplianceAudit('document_created_from_template', $document->id, [
            'template_id' => $template->id,
            'template_type' => $template->template_type,
            'created_by' => $renderer->id,
        ]);

        // Trigger compliance monitoring
        $this->complianceService->monitorDocumentCreation($document);

        return $document;
    }

    /**
     * Get available templates for a type
     */
    public function getTemplatesForType(string $type, bool $activeOnly = true): Collection
    {
        $query = DocumentTemplate::byType($type);

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderBy('is_default', 'desc')->orderBy('name')->get();
    }

    /**
     * Get default template for a type
     */
    public function getDefaultTemplate(string $type): ?DocumentTemplate
    {
        return DocumentTemplate::getDefaultForType($type);
    }

    /**
     * Clone an existing template
     */
    public function cloneTemplate(DocumentTemplate $template, string $newName, User $cloner): DocumentTemplate
    {
        $clonedData = $template->toArray();
        unset($clonedData['id'], $clonedData['created_at'], $clonedData['updated_at']);

        $clonedData['name'] = $newName;
        $clonedData['is_default'] = false; // Cloned templates are not default by default

        return $this->createTemplate($clonedData, $cloner);
    }

    /**
     * Validate template data
     */
    protected function validateTemplateData(array $data): void
    {
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Template name is required');
        }

        if (empty($data['template_type'])) {
            throw new \InvalidArgumentException('Template type is required');
        }

        if (empty($data['template_content'])) {
            throw new \InvalidArgumentException('Template content is required');
        }

        // Validate template type
        $validTypes = ['claim', 'prescription', 'diagnosis', 'custom'];
        if (!in_array($data['template_type'], $validTypes)) {
            throw new \InvalidArgumentException('Invalid template type. Must be one of: ' . implode(', ', $validTypes));
        }
    }

    /**
     * Extract placeholders from template content
     */
    protected function extractPlaceholdersFromContent(string $content): array
    {
        $placeholders = [];

        // Find all {{placeholder}} patterns
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);

        foreach ($matches[1] as $placeholder) {
            $parts = explode(':', $placeholder);
            $key = trim($parts[0]);
            $type = $parts[1] ?? 'text';
            $default = $parts[2] ?? null;

            // Remove optional marker
            $isOptional = str_contains($key, '?');
            if ($isOptional) {
                $key = str_replace('?', '', $key);
            }

            $placeholders[$key] = [
                'type' => $type,
                'default' => $default,
                'required' => !$isOptional,
            ];
        }

        return $placeholders;
    }

    /**
     * Set a template as the default for its type
     */
    protected function setDefaultTemplate(DocumentTemplate $template): void
    {
        // Unset default flag for other templates of the same type
        DocumentTemplate::where('template_type', $template->template_type)
            ->where('id', '!=', $template->id)
            ->update(['is_default' => false]);
    }

    /**
     * Get template usage statistics
     */
    public function getTemplateUsageStats(DocumentTemplate $template): array
    {
        $totalDocuments = $template->documents()->count();
        $documentsByStatus = $template->documents()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'total_documents' => $totalDocuments,
            'documents_by_status' => $documentsByStatus,
            'last_used' => $template->documents()->latest('created_at')->value('created_at'),
        ];
    }

    /**
     * Preview template rendering
     */
    public function previewTemplate(DocumentTemplate $template, array $sampleData = []): array
    {
        // Use default values for missing placeholders
        $placeholders = $template->placeholders ?? [];
        $previewData = [];

        foreach ($placeholders as $key => $config) {
            $previewData[$key] = $sampleData[$key] ?? $config['default'] ?? $this->getSampleValueForType($config['type']);
        }

        $renderedContent = $template->render($previewData);
        $validationErrors = $template->validatePlaceholders($previewData);
        $complianceViolations = $template->validateCompliance($previewData);

        return [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'rendered_content' => $renderedContent,
            'used_data' => $previewData,
            'validation_errors' => $validationErrors,
            'compliance_violations' => $complianceViolations,
            'is_valid' => empty($validationErrors) && empty($complianceViolations),
        ];
    }

    /**
     * Get sample value for placeholder type
     */
    protected function getSampleValueForType(string $type): string
    {
        return match ($type) {
            'date' => now()->format('Y-m-d'),
            'datetime' => now()->format('Y-m-d H:i:s'),
            'number' => '123',
            'boolean' => 'true',
            'email' => 'sample@example.com',
            default => 'Sample ' . ucfirst($type),
        };
    }

    /**
     * Generate document with AI assistance
     */
    public function generateDocumentWithAI(
        DocumentTemplate $template,
        array $contextData,
        User $user,
        array $options = []
    ): array {
        try {
            // Use AI Writing Assistant to generate content
            $aiResult = $this->aiAssistant->generateDocumentContent($template, $contextData, $user, $options);

            // Create document with AI-generated content
            $documentData = [
                'title' => $options['title'] ?? $template->name . ' - AI Generated',
                'description' => $options['description'] ?? 'AI-generated document from template',
                'document_type' => $template->template_type,
                'content' => $aiResult['content'],
                'metadata' => array_merge($aiResult['metadata'], [
                    'ai_generated' => true,
                    'generation_options' => $options,
                    'template_id' => $template->id,
                ]),
                'compliance_data' => $aiResult['validation_result'],
            ];

            $document = Document::create(array_merge($documentData, [
                'created_by' => $user->id,
                'current_version' => 1,
                'template_id' => $template->id,
            ]));

            // Create initial version
            $this->versionControl->createVersion(
                $document,
                $aiResult['content'],
                $user,
                'Initial AI-generated version',
                ['ai_generated' => true]
            );

            return [
                'document' => $document,
                'ai_result' => $aiResult,
                'validation_result' => $aiResult['validation_result'],
            ];

        } catch (\Exception $e) {
            Log::error('AI document generation failed', [
                'template_id' => $template->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to generate AI document: ' . $e->getMessage());
        }
    }

    /**
     * Auto-fill template with patient data
     */
    public function autofillTemplateWithPatientData(
        DocumentTemplate $template,
        $patient, // Can be Patient model or patient ID
        User $user,
        array $additionalContext = []
    ): array {
        try {
            // If patient is an ID, find the model
            if (is_int($patient) || is_string($patient)) {
                $patientModel = \App\Models\Patient::findOrFail($patient);
            } else {
                $patientModel = $patient;
            }

            // Use Template Autofill Service
            $autofillResult = $this->autofillService->autofillTemplate(
                $template,
                $patientModel,
                $user,
                $additionalContext
            );

            return $autofillResult;

        } catch (\Exception $e) {
            Log::error('Template autofill failed', [
                'template_id' => $template->id,
                'patient_id' => is_object($patient) ? $patient->id : $patient,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to autofill template: ' . $e->getMessage());
        }
    }

    /**
     * Create document with intelligent autofill and AI enhancement
     */
    public function createSmartDocument(
        DocumentTemplate $template,
        $patient, // Can be Patient model or patient ID
        User $user,
        array $options = []
    ): Document {
        try {
            // Step 1: Autofill template with patient data
            $autofillResult = $this->autofillTemplateWithPatientData($template, $patient, $user, $options);

            // Step 2: Use AI to enhance/generate missing content
            $enhancedContent = $autofillResult['filled_data'];

            // Generate any missing content with AI
            if (!empty($options['enhance_with_ai'])) {
                $aiResult = $this->aiAssistant->enhanceDocumentContent(
                    json_encode($enhancedContent), // Convert to string for AI processing
                    $template,
                    ['patient_id' => is_object($patient) ? $patient->id : $patient],
                    $user,
                    ['type' => 'completeness']
                );

                $enhancedContent = json_decode($aiResult['enhanced_content'], true) ?? $enhancedContent;
            }

            // Step 3: Create document
            $documentData = [
                'title' => $options['title'] ?? $template->name . ' - Smart Generated',
                'description' => $options['description'] ?? 'Smart-generated document with patient data',
                'document_type' => $template->template_type,
                'content' => json_encode($enhancedContent),
                'metadata' => array_merge($autofillResult['autofill_metadata'], [
                    'smart_generated' => true,
                    'autofill_used' => true,
                    'ai_enhanced' => !empty($options['enhance_with_ai']),
                    'template_id' => $template->id,
                ]),
                'compliance_data' => [
                    'autofill_performed' => true,
                    'validation_passed' => $autofillResult['validation_result']['is_valid'],
                    'autofill_coverage' => $autofillResult['validation_result']['fill_percentage'],
                ],
            ];

            $document = Document::create(array_merge($documentData, [
                'created_by' => $user->id,
                'current_version' => 1,
                'template_id' => $template->id,
            ]));

            // Step 4: Create initial version
            $this->versionControl->createVersion(
                $document,
                json_encode($enhancedContent),
                $user,
                'Initial smart-generated version with patient data',
                [
                    'smart_generated' => true,
                    'autofill_metadata' => $autofillResult['autofill_metadata'],
                ]
            );

            return $document;

        } catch (\Exception $e) {
            Log::error('Smart document creation failed', [
                'template_id' => $template->id,
                'patient_id' => is_object($patient) ? $patient->id : $patient,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to create smart document: ' . $e->getMessage());
        }
    }

    /**
     * Get autofill suggestions for a template and patient
     */
    public function getAutofillSuggestions(DocumentTemplate $template, $patient, User $user): array
    {
        try {
            // If patient is an ID, find the model
            if (is_int($patient) || is_string($patient)) {
                $patientModel = \App\Models\Patient::findOrFail($patient);
            } else {
                $patientModel = $patient;
            }

            return $this->autofillService->getAutofillSuggestions($template, $patientModel);

        } catch (\Exception $e) {
            Log::error('Autofill suggestions failed', [
                'template_id' => $template->id,
                'patient_id' => is_object($patient) ? $patient->id : $patient,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'template_id' => $template->id,
                'patient_id' => is_object($patient) ? $patient->id : $patient,
                'suggestions' => [],
                'total_suggestions' => 0,
                'coverage_percentage' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate AI content for specific template sections
     */
    public function generateTemplateSection(
        DocumentTemplate $template,
        string $sectionType,
        array $contextData,
        User $user,
        array $options = []
    ): array {
        try {
            // Use AI assistant to generate section content
            $aiResult = $this->aiAssistant->generatePatientSpecificSections(
                // Create a mock patient object or use context data
                (object) ['id' => $contextData['patient_id'] ?? null],
                $sectionType,
                $contextData,
                $user
            );

            return [
                'section_type' => $sectionType,
                'content' => $aiResult['section_content'],
                'metadata' => $aiResult['metadata'],
                'privacy_validation' => $aiResult['privacy_validation'],
                'template_id' => $template->id,
            ];

        } catch (\Exception $e) {
            Log::error('Template section generation failed', [
                'template_id' => $template->id,
                'section_type' => $sectionType,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to generate template section: ' . $e->getMessage());
        }
    }
}
