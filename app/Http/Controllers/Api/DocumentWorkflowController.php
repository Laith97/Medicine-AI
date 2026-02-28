<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\WorkflowTask;
use App\Services\DocumentWorkflowEngine;
use App\Services\DocumentTemplateService;
use App\Services\AutomatedReviewService;
use App\Services\ComplianceMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DocumentWorkflowController extends Controller
{
    protected DocumentWorkflowEngine $workflowEngine;
    protected DocumentTemplateService $templateService;
    protected AutomatedReviewService $reviewService;
    protected ComplianceMonitoringService $complianceService;

    public function __construct(
        DocumentWorkflowEngine $workflowEngine,
        DocumentTemplateService $templateService,
        AutomatedReviewService $reviewService,
        ComplianceMonitoringService $complianceService
    ) {
        $this->workflowEngine = $workflowEngine;
        $this->templateService = $templateService;
        $this->reviewService = $reviewService;
        $this->complianceService = $complianceService;
    }

    /**
     * Create a new document from template
     */
    public function createDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|exists:document_templates,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'data' => 'required|array',
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
            $template = DocumentTemplate::findOrFail($request->template_id);

            $document = $this->templateService->renderDocument(
                $template,
                $request->data,
                $request->user()
            );

            // Add external metadata
            if ($request->metadata) {
                $document->metadata = array_merge($document->metadata ?? [], [
                    'external_created' => true,
                    'external_metadata' => $request->metadata,
                    'api_version' => 'v1',
                ]);
                $document->save();
            }

            Log::info('Document created via API', [
                'document_id' => $document->id,
                'template_id' => $template->id,
                'created_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document created successfully',
                'data' => [
                    'document_id' => $document->id,
                    'workflow_state' => $document->workflow_state,
                    'status' => $document->status,
                    'created_at' => $document->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Document creation failed via API', [
                'error' => $e->getMessage(),
                'template_id' => $request->template_id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Document creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit document for review
     */
    public function submitDocument(Request $request, Document $document): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'review_config' => 'nullable|array',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'due_date' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check permissions
        if ($document->created_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to submit this document',
            ], 403);
        }

        try {
            $options = array_filter([
                'priority' => $request->priority,
                'due_date' => $request->due_date,
            ]);

            $success = $this->workflowEngine->submitForReview($document, $request->user(), $options);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document submission failed - invalid workflow state',
                ], 400);
            }

            // Start automated review if configured
            if ($request->review_config) {
                $this->reviewService->startAutomatedReview($document, $request->review_config);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document submitted for review',
                'data' => [
                    'document_id' => $document->id,
                    'workflow_state' => $document->workflow_state,
                    'status' => $document->status,
                    'submitted_at' => $document->submitted_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Document submission failed via API', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Document submission failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process review decision
     */
    public function processReview(Request $request, WorkflowTask $task): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'decision' => 'required|in:approve,reject,escalate',
            'comments' => 'nullable|string|max:1000',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check permissions
        if ($task->assigned_to !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to review this task',
            ], 403);
        }

        try {
            $document = $task->taskable;

            if (!$document instanceof Document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid task type',
                ], 400);
            }

            $success = $this->reviewService->processReviewDecision(
                $document,
                $task,
                $request->user(),
                $request->decision,
                $request->comments,
                $request->options ?? []
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review processing failed',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Review decision processed',
                'data' => [
                    'task_id' => $task->id,
                    'document_id' => $document->id,
                    'decision' => $request->decision,
                    'new_workflow_state' => $document->workflow_state,
                    'new_status' => $document->status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Review processing failed via API', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Review processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get document workflow status
     */
    public function getWorkflowStatus(Document $document): JsonResponse
    {
        // Check permissions (creator or assigned reviewers)
        $userId = request()->user()->id;
        $isCreator = $document->created_by === $userId;
        $isReviewer = $document->workflowTasks()
            ->where('assigned_to', $userId)
            ->exists();

        if (!$isCreator && !$isReviewer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this document',
            ], 403);
        }

        try {
            $status = $this->workflowEngine->getWorkflowStatus($document);

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);

        } catch (\Exception $e) {
            Log::error('Workflow status retrieval failed via API', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve workflow status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get assigned tasks for current user
     */
    public function getAssignedTasks(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,in_progress,completed',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = WorkflowTask::where('assigned_to', $request->user()->id)
                ->where('task_type', 'document_review')
                ->with(['taskable']);

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->priority) {
                $query->where('priority', $request->priority);
            }

            $tasks = $query->orderBy('due_date')
                ->orderBy('priority', 'desc')
                ->limit($request->limit ?? 50)
                ->get();

            $formattedTasks = $tasks->map(function ($task) {
                return [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date,
                    'document' => $task->taskable instanceof Document ? [
                        'id' => $task->taskable->id,
                        'title' => $task->taskable->title,
                        'type' => $task->taskable->document_type,
                        'status' => $task->taskable->status,
                    ] : null,
                    'task_data' => $task->task_data,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $formattedTasks,
                    'total' => $tasks->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Assigned tasks retrieval failed via API', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assigned tasks',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available templates
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string',
            'active_only' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = DocumentTemplate::query();

            if ($request->type) {
                $query->where('template_type', $request->type);
            }

            if ($request->active_only !== false) {
                $query->where('is_active', true);
            }

            $templates = $query->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get();

            $formattedTemplates = $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'type' => $template->template_type,
                    'description' => $template->description,
                    'is_default' => $template->is_default,
                    'placeholders' => $template->placeholders,
                    'compliance_rules' => $template->compliance_rules,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'templates' => $formattedTemplates,
                    'total' => $templates->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Templates retrieval failed via API', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve templates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview template rendering
     */
    public function previewTemplate(Request $request, DocumentTemplate $template): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $preview = $this->templateService->previewTemplate(
                $template,
                $request->data ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);

        } catch (\Exception $e) {
            Log::error('Template preview failed via API', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Template preview failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get compliance status for document
     */
    public function getComplianceStatus(Document $document): JsonResponse
    {
        // Check permissions
        $userId = request()->user()->id;
        $isCreator = $document->created_by === $userId;
        $isReviewer = $document->workflowTasks()
            ->where('assigned_to', $userId)
            ->exists();

        if (!$isCreator && !$isReviewer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this document',
            ], 403);
        }

        try {
            $complianceResult = $this->complianceService->evaluateRules(
                'document_viewed',
                Document::class,
                $document->toArray(),
                $document
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'document_id' => $document->id,
                    'compliance_status' => empty($complianceResult['violations']) ? 'compliant' : 'violations_found',
                    'violations' => $complianceResult['violations'],
                    'compliant_actions' => $complianceResult['compliant_actions'],
                    'rules_evaluated' => $complianceResult['total_rules_evaluated'],
                    'compliance_data' => $document->compliance_data,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Compliance status retrieval failed via API', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve compliance status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
