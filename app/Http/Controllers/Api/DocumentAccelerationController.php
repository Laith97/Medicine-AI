<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\DocumentVersion;
use App\Services\AIWritingAssistantService;
use App\Services\TemplateAutofillService;
use App\Services\ComplianceDocumentCheckerService;
use App\Services\DocumentVersionControlService;
use App\Services\DocumentTemplateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DocumentAccelerationController extends Controller
{
    protected AIWritingAssistantService $aiAssistant;
    protected TemplateAutofillService $autofillService;
    protected ComplianceDocumentCheckerService $complianceChecker;
    protected DocumentVersionControlService $versionControl;
    protected DocumentTemplateService $templateService;

    public function __construct(
        AIWritingAssistantService $aiAssistant,
        TemplateAutofillService $autofillService,
        ComplianceDocumentCheckerService $complianceChecker,
        DocumentVersionControlService $versionControl,
        DocumentTemplateService $templateService
    ) {
        $this->aiAssistant = $aiAssistant;
        $this->autofillService = $autofillService;
        $this->complianceChecker = $complianceChecker;
        $this->versionControl = $versionControl;
        $this->templateService = $templateService;
    }

    /**
     * Generate document with AI assistance
     */
    public function generateWithAI(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|exists:document_templates,id',
            'context_data' => 'required|array',
            'options' => 'nullable|array',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $template = DocumentTemplate::findOrFail($request->template_id);

            $result = $this->templateService->generateDocumentWithAI(
                $template,
                $request->context_data,
                $request->user(),
                $request->options ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AI document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enhance existing document with AI
     */
    public function enhanceWithAI(Request $request, Document $document): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enhancement_type' => 'required|string|in:clarity,compliance,completeness',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->aiAssistant->enhanceDocumentContent(
                $document->content ?? '',
                $document->template,
                $request->options['context_data'] ?? [],
                $request->user(),
                ['type' => $request->enhancement_type] + ($request->options ?? [])
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to enhance document with AI',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate patient-specific document section
     */
    public function generatePatientSection(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'section_type' => 'required|string|in:medical_history,assessment,treatment_plan,consent',
            'medical_context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->aiAssistant->generatePatientSpecificSections(
                $request->patient_id,
                $request->section_type,
                $request->medical_context ?? [],
                $request->user()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate patient section',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto-fill template with patient data
     */
    public function autofillTemplate(Request $request, DocumentTemplate $template): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'additional_context' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->templateService->autofillTemplateWithPatientData(
                $template,
                $request->patient_id,
                $request->user(),
                $request->additional_context ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to autofill template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get autofill suggestions for template
     */
    public function getAutofillSuggestions(Request $request, DocumentTemplate $template): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->templateService->getAutofillSuggestions(
                $template,
                $request->patient_id,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get autofill suggestions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create smart document with autofill and AI enhancement
     */
    public function createSmartDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|exists:document_templates,id',
            'patient_id' => 'required|exists:patients,id',
            'options' => 'nullable|array',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $template = DocumentTemplate::findOrFail($request->template_id);

            $document = $this->templateService->createSmartDocument(
                $template,
                $request->patient_id,
                $request->user(),
                $request->options ?? []
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'document' => $document,
                    'message' => 'Smart document created successfully',
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create smart document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check document compliance
     */
    public function checkCompliance(Request $request, Document $document): JsonResponse
    {
        try {
            $result = $this->complianceChecker->checkDocumentCompliance(
                $document,
                $request->user(),
                $request->options ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check document compliance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get compliance history for document
     */
    public function getComplianceHistory(Request $request, Document $document): JsonResponse
    {
        try {
            $result = $this->complianceChecker->getComplianceHistory($document);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get compliance history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Create new version of document
     */
    public function createVersion(Request $request, Document $document): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'change_reason' => 'required|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $version = $this->versionControl->createVersion(
                $document,
                $request->input('content'),
                $request->user(),
                $request->input('change_reason'),
                $request->input('metadata', [])
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'version' => $version,
                    'message' => 'Version created successfully',
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create version',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get version history for document
     */
    public function getVersionHistory(Request $request, Document $document): JsonResponse
    {
        try {
            $result = $this->versionControl->getVersionHistory(
                $document,
                $request->query()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get version history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore document to specific version
     */
    public function restoreVersion(Request $request, Document $document, DocumentVersion $version): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $restoredVersion = $this->versionControl->restoreVersion(
                $document,
                $version,
                $request->user(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'version' => $restoredVersion,
                    'message' => 'Document restored successfully',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore version',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Compare two document versions
     */
    public function compareVersions(Request $request, Document $document): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'version1_id' => 'required|exists:document_versions,id',
            'version2_id' => 'required|exists:document_versions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $version1 = DocumentVersion::findOrFail($request->version1_id);
            $version2 = DocumentVersion::findOrFail($request->version2_id);

            $result = $this->versionControl->compareVersions($version1, $version2);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to compare versions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get audit trail for document
     */
    public function getAuditTrail(Request $request, Document $document): JsonResponse
    {
        try {
            $result = $this->versionControl->getAuditTrail(
                $document,
                $request->query()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get audit trail',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archive old versions
     */
    public function archiveVersions(Request $request, Document $document): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keep_versions' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->versionControl->archiveOldVersions(
                $document,
                $request->keep_versions ?? 10,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive versions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export version history
     */
    public function exportVersionHistory(Request $request, Document $document): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'nullable|string|in:json,csv,pdf',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $format = $request->input('format', 'json');
            $exportData = $this->versionControl->exportVersionHistory(
                $document,
                $format,
                $request->input('options', [])
            );

            $filename = "document_{$document->id}_versions.{$format}";

            return response()->json([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'content' => $exportData,
                    'format' => $format,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export version history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get version statistics
     */
    public function getVersionStatistics(Request $request, Document $document): JsonResponse
    {
        try {
            $result = $this->versionControl->getVersionStatistics($document);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get version statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate version integrity
     */
    public function validateVersionIntegrity(Request $request, Document $document, DocumentVersion $version): JsonResponse
    {
        try {
            $result = $this->versionControl->validateVersionIntegrity($version);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate version integrity',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
