<?php

namespace App\Services;

use App\Models\Document;
use App\Models\WorkflowTask;
use App\Models\User;
use App\Services\ComplianceMonitoringService;
use App\Services\AuditLoggingService;
use App\Services\ComplianceDocumentCheckerService;
use App\Services\DocumentVersionControlService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class DocumentWorkflowEngine
{
    protected ComplianceMonitoringService $complianceService;
    protected ComplianceDocumentCheckerService $complianceChecker;
    protected DocumentVersionControlService $versionControl;

    public function __construct(
        ComplianceMonitoringService $complianceService,
        ComplianceDocumentCheckerService $complianceChecker,
        DocumentVersionControlService $versionControl
    ) {
        $this->complianceService = $complianceService;
        $this->complianceChecker = $complianceChecker;
        $this->versionControl = $versionControl;
    }

    /**
     * Initialize a document workflow
     */
    public function initializeWorkflow(Document $document, array $options = []): Document
    {
        // Set initial workflow state
        $document->workflow_state = 'created';
        $document->status = 'draft';

        // Initialize metadata
        $metadata = $document->metadata ?? [];
        $metadata['workflow_initialized_at'] = now();
        $metadata['workflow_version'] = '1.0';
        $document->metadata = array_merge($metadata, $options);

        $document->save();

        // Log workflow initialization
        AuditLoggingService::logComplianceAudit('document_workflow_initialized', $document->id, [
            'document_type' => $document->document_type,
            'workflow_state' => $document->workflow_state,
            'metadata' => $document->metadata,
        ]);

        // Trigger compliance monitoring
        $this->complianceService->monitorDocumentCreation($document);

        return $document;
    }

    /**
     * Submit a document for review
     */
    public function submitForReview(Document $document, User $submittedBy, array $options = []): bool
    {
        if (!$document->canTransitionTo('submitted')) {
            Log::warning('Invalid workflow transition attempted', [
                'document_id' => $document->id,
                'current_state' => $document->workflow_state,
                'attempted_state' => 'submitted',
            ]);
            return false;
        }

        // Update document
        $document->updated_by = $submittedBy->id;
        $document->transitionTo('submitted', [
            'submitted_by' => $submittedBy->id,
            'submitted_at' => now(),
            'submission_options' => $options,
        ]);

        // Create initial review task
        $this->createReviewTask($document, $options);

        // Log submission
        AuditLoggingService::logComplianceAudit('document_submitted_for_review', $document->id, [
            'submitted_by' => $submittedBy->id,
            'document_type' => $document->document_type,
            'options' => $options,
        ]);

        // Trigger compliance monitoring
        $this->complianceService->monitorDocumentSubmission($document);

        // Fire event
        Event::dispatch('document.submitted', [$document, $submittedBy, $options]);

        return true;
    }

    /**
     * Start document review process
     */
    public function startReview(Document $document, User $reviewer, array $options = []): bool
    {
        if (!$document->canTransitionTo('under_review')) {
            return false;
        }

        $document->updated_by = $reviewer->id;
        $document->transitionTo('under_review', [
            'review_started_by' => $reviewer->id,
            'review_started_at' => now(),
            'review_options' => $options,
        ]);

        // Update review task
        $this->updateReviewTask($document, 'in_progress', $reviewer);

        // Log review start
        AuditLoggingService::logComplianceAudit('document_review_started', $document->id, [
            'reviewer_id' => $reviewer->id,
            'document_type' => $document->document_type,
        ]);

        return true;
    }

    /**
     * Approve a document
     */
    public function approveDocument(Document $document, User $approver, string $comments = null, array $options = []): bool
    {
        if (!$document->canTransitionTo('approved')) {
            return false;
        }

        // Perform final compliance check before approval
        $complianceCheck = $this->complianceChecker->checkDocumentCompliance($document, $approver, [
            'check_type' => 'pre_approval',
            'require_full_compliance' => true,
        ]);

        // If compliance check fails and strict mode is enabled, prevent approval
        if (isset($options['strict_compliance']) && $options['strict_compliance'] && !$complianceCheck['compliance_status']) {
            Log::warning('Document approval blocked due to compliance violations', [
                'document_id' => $document->id,
                'approver_id' => $approver->id,
                'compliance_status' => $complianceCheck['compliance_status'],
                'critical_issues' => $complianceCheck['critical_issues'],
            ]);
            return false;
        }

        $document->updated_by = $approver->id;
        $document->transitionTo('approved', [
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_comments' => $comments,
            'approval_options' => $options,
            'final_compliance_check' => $complianceCheck,
        ]);

        // Create version snapshot at approval
        if (isset($options['create_version_snapshot']) && $options['create_version_snapshot']) {
            try {
                $this->versionControl->createVersion(
                    $document,
                    $document->content ?? '',
                    $approver,
                    'Document approved - final version',
                    [
                        'approval_snapshot' => true,
                        'compliance_verified' => $complianceCheck['compliance_status'],
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Failed to create approval version snapshot', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Complete review task
        $this->completeReviewTask($document, 'approved', $approver, $comments);

        // Log approval with compliance details
        AuditLoggingService::logComplianceAudit('document_approved', $document->id, [
            'approver_id' => $approver->id,
            'document_type' => $document->document_type,
            'comments' => $comments,
            'compliance_status' => $complianceCheck['compliance_status'],
            'compliance_score' => $complianceCheck['overall_score'],
        ]);

        // Fire event
        Event::dispatch('document.approved', [$document, $approver, $comments, $complianceCheck]);

        return true;
    }

    /**
     * Reject a document
     */
    public function rejectDocument(Document $document, User $rejector, string $reason, array $options = []): bool
    {
        if (!$document->canTransitionTo('rejected')) {
            return false;
        }

        $document->updated_by = $rejector->id;
        $document->rejection_reason = $reason;
        $document->transitionTo('rejected', [
            'rejected_by' => $rejector->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'rejection_options' => $options,
        ]);

        // Complete review task with rejection
        $this->completeReviewTask($document, 'rejected', $rejector, $reason);

        // Log rejection
        AuditLoggingService::logComplianceAudit('document_rejected', $document->id, [
            'rejector_id' => $rejector->id,
            'document_type' => $document->document_type,
            'reason' => $reason,
        ]);

        // Fire event
        Event::dispatch('document.rejected', [$document, $rejector, $reason]);

        return true;
    }

    /**
     * Escalate a document for higher-level review
     */
    public function escalateDocument(Document $document, User $escalator, string $reason, array $options = []): bool
    {
        if (!$document->canTransitionTo('escalated')) {
            return false;
        }

        $document->updated_by = $escalator->id;
        $document->transitionTo('escalated', [
            'escalated_by' => $escalator->id,
            'escalated_at' => now(),
            'escalation_reason' => $reason,
            'escalation_options' => $options,
        ]);

        // Create escalation task
        $this->createEscalationTask($document, $escalator, $reason, $options);

        // Log escalation
        AuditLoggingService::logComplianceAudit('document_escalated', $document->id, [
            'escalator_id' => $escalator->id,
            'document_type' => $document->document_type,
            'reason' => $reason,
        ]);

        // Fire event
        Event::dispatch('document.escalated', [$document, $escalator, $reason]);

        return true;
    }

    /**
     * Archive a document
     */
    public function archiveDocument(Document $document, User $archiver, array $options = []): bool
    {
        if (!$document->canTransitionTo('archived')) {
            return false;
        }

        $document->updated_by = $archiver->id;
        $document->transitionTo('archived', [
            'archived_by' => $archiver->id,
            'archived_at' => now(),
            'archive_options' => $options,
        ]);

        // Log archiving
        AuditLoggingService::logComplianceAudit('document_archived', $document->id, [
            'archiver_id' => $archiver->id,
            'document_type' => $document->document_type,
        ]);

        return true;
    }

    /**
     * Get workflow status for a document
     */
    public function getWorkflowStatus(Document $document): array
    {
        return [
            'document_id' => $document->id,
            'current_state' => $document->workflow_state,
            'status' => $document->status,
            'available_transitions' => $document->getAvailableTransitions(),
            'workflow_tasks' => $document->workflowTasks()->get()->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'assigned_to' => $task->assigned_to,
                    'due_date' => $task->due_date,
                    'priority' => $task->priority,
                ];
            }),
            'metadata' => $document->metadata,
            'compliance_data' => $document->compliance_data,
        ];
    }

    /**
     * Create a review task for the document
     */
    protected function createReviewTask(Document $document, array $options = []): WorkflowTask
    {
        $assigneeId = $options['assign_to'] ?? $this->getDefaultReviewer($document);

        return WorkflowTask::create([
            'task_type' => 'document_review',
            'taskable_type' => Document::class,
            'taskable_id' => $document->id,
            'title' => "Review {$document->document_type}: {$document->title}",
            'description' => "Review and approve/reject the submitted {$document->document_type} document",
            'task_data' => [
                'document_type' => $document->document_type,
                'priority' => $options['priority'] ?? 'normal',
                'review_requirements' => $options['review_requirements'] ?? [],
            ],
            'priority' => $options['priority'] ?? 'normal',
            'status' => 'pending',
            'assigned_to' => $assigneeId,
            'due_date' => $options['due_date'] ?? now()->addDays(7),
        ]);
    }

    /**
     * Update review task status
     */
    protected function updateReviewTask(Document $document, string $status, User $user): void
    {
        $task = $document->workflowTasks()->where('task_type', 'document_review')->first();
        if ($task) {
            $task->status = $status;
            $task->save();
        }
    }

    /**
     * Complete review task
     */
    protected function completeReviewTask(Document $document, string $outcome, User $user, string $comments = null): void
    {
        $task = $document->workflowTasks()->where('task_type', 'document_review')->first();
        if ($task) {
            $task->status = 'completed';
            $task->completed_at = now();
            $task->task_data = array_merge($task->task_data ?? [], [
                'outcome' => $outcome,
                'completed_by' => $user->id,
                'comments' => $comments,
            ]);
            $task->save();
        }
    }

    /**
     * Create escalation task
     */
    protected function createEscalationTask(Document $document, User $escalator, string $reason, array $options = []): WorkflowTask
    {
        $assigneeId = $options['escalate_to'] ?? $this->getEscalationAssignee($document);

        return WorkflowTask::create([
            'task_type' => 'document_escalation',
            'taskable_type' => Document::class,
            'taskable_id' => $document->id,
            'title' => "Escalated Review: {$document->title}",
            'description' => "Escalated {$document->document_type} review: {$reason}",
            'task_data' => [
                'escalated_by' => $escalator->id,
                'escalation_reason' => $reason,
                'original_reviewer' => $options['original_reviewer'] ?? null,
                'escalation_level' => $options['escalation_level'] ?? 1,
            ],
            'priority' => 'high',
            'status' => 'pending',
            'assigned_to' => $assigneeId,
            'due_date' => now()->addDays(3), // Shorter deadline for escalated items
        ]);
    }

    /**
     * Get default reviewer for a document type
     */
    protected function getDefaultReviewer(Document $document): ?int
    {
        // This would integrate with user roles/permissions system
        // For now, return null - would be set by calling code
        return null;
    }

    /**
     * Get escalation assignee
     */
    protected function getEscalationAssignee(Document $document): ?int
    {
        // This would determine who to escalate to based on document type and escalation rules
        // For now, return null - would be set by calling code
        return null;
    }
}
