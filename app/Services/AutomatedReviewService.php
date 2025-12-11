<?php

namespace App\Services;

use App\Models\Document;
use App\Models\WorkflowTask;
use App\Models\User;
use App\Services\DocumentWorkflowEngine;
use App\Services\ComplianceMonitoringService;
use App\Services\AuditLoggingService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AutomatedReviewService
{
    protected DocumentWorkflowEngine $workflowEngine;
    protected ComplianceMonitoringService $complianceService;
    protected NotificationService $notificationService;

    public function __construct(
        DocumentWorkflowEngine $workflowEngine,
        ComplianceMonitoringService $complianceService,
        NotificationService $notificationService
    ) {
        $this->workflowEngine = $workflowEngine;
        $this->complianceService = $complianceService;
        $this->notificationService = $notificationService;
    }

    /**
     * Start automated review process for a document
     */
    public function startAutomatedReview(Document $document, array $reviewConfig = []): bool
    {
        if (!$document->canTransitionTo('under_review')) {
            return false;
        }

        // Get or create review configuration
        $config = $this->getReviewConfiguration($document, $reviewConfig);

        // Create sequential review tasks
        $this->createSequentialReviewTasks($document, $config);

        // Start the first review
        $firstTask = $document->workflowTasks()
            ->where('task_type', 'document_review')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->first();

        if ($firstTask) {
            $this->assignReviewTask($firstTask, $config);
        }

        // Log automated review start
        AuditLoggingService::logComplianceAudit('automated_review_started', $document->id, [
            'review_config' => $config,
            'document_type' => $document->document_type,
        ]);

        return true;
    }

    /**
     * Process a review decision
     */
    public function processReviewDecision(Document $document, WorkflowTask $task, User $reviewer, string $decision, string $comments = null, array $options = []): bool
    {
        // Validate decision
        if (!in_array($decision, ['approve', 'reject', 'escalate'])) {
            throw new \InvalidArgumentException('Invalid review decision');
        }

        // Update task
        $task->status = 'completed';
        $task->completed_at = now();
        $task->task_data = array_merge($task->task_data ?? [], [
            'decision' => $decision,
            'reviewer_id' => $reviewer->id,
            'comments' => $comments,
            'decision_timestamp' => now(),
            'review_options' => $options,
        ]);
        $task->save();

        // Process decision
        switch ($decision) {
            case 'approve':
                return $this->processApproval($document, $task, $reviewer, $comments, $options);
            case 'reject':
                return $this->processRejection($document, $task, $reviewer, $comments, $options);
            case 'escalate':
                return $this->processEscalation($document, $task, $reviewer, $comments, $options);
        }

        return false;
    }

    /**
     * Process approval decision
     */
    protected function processApproval(Document $document, WorkflowTask $task, User $reviewer, ?string $comments, array $options): bool
    {
        // Check if this is the final approval
        $pendingTasks = $document->workflowTasks()
            ->where('task_type', 'document_review')
            ->where('status', 'pending')
            ->count();

        if ($pendingTasks === 0) {
            // Final approval - approve the document
            return $this->workflowEngine->approveDocument($document, $reviewer, $comments, $options);
        } else {
            // Move to next review step
            return $this->advanceToNextReviewStep($document, $task);
        }
    }

    /**
     * Process rejection decision
     */
    protected function processRejection(Document $document, WorkflowTask $task, User $reviewer, ?string $comments, array $options): bool
    {
        $reason = $comments ?: 'Rejected during automated review';

        // Check if rejection should be escalated
        $config = $this->getReviewConfiguration($document);
        if ($this->shouldEscalateRejection($document, $config)) {
            return $this->workflowEngine->escalateDocument($document, $reviewer, "Rejection requires escalation: {$reason}", $options);
        }

        return $this->workflowEngine->rejectDocument($document, $reviewer, $reason, $options);
    }

    /**
     * Process escalation decision
     */
    protected function processEscalation(Document $document, WorkflowTask $task, User $reviewer, ?string $comments, array $options): bool
    {
        $reason = $comments ?: 'Escalated during automated review';

        return $this->workflowEngine->escalateDocument($document, $reviewer, $reason, $options);
    }

    /**
     * Advance to next review step
     */
    protected function advanceToNextReviewStep(Document $document, WorkflowTask $completedTask): bool
    {
        $nextTask = $document->workflowTasks()
            ->where('task_type', 'document_review')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->first();

        if ($nextTask) {
            $config = $this->getReviewConfiguration($document);
            $this->assignReviewTask($nextTask, $config);

            // Log step advancement
            AuditLoggingService::logComplianceAudit('review_step_advanced', $document->id, [
                'from_task_id' => $completedTask->id,
                'to_task_id' => $nextTask->id,
                'step_number' => $nextTask->task_data['step_number'] ?? null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Create sequential review tasks
     */
    protected function createSequentialReviewTasks(Document $document, array $config): void
    {
        $reviewSteps = $config['steps'] ?? $this->getDefaultReviewSteps($document);

        foreach ($reviewSteps as $index => $step) {
            WorkflowTask::create([
                'task_type' => 'document_review',
                'taskable_type' => Document::class,
                'taskable_id' => $document->id,
                'title' => "Review Step " . ($index + 1) . ": {$step['name']}",
                'description' => $step['description'] ?? "Review {$document->document_type} document - Step " . ($index + 1),
                'task_data' => array_merge($step, [
                    'step_number' => $index + 1,
                    'total_steps' => count($reviewSteps),
                    'is_final_step' => ($index + 1) === count($reviewSteps),
                ]),
                'priority' => $step['priority'] ?? 'normal',
                'status' => $index === 0 ? 'pending' : 'pending', // First step is immediately pending
                'assigned_to' => null, // Will be assigned when step becomes active
                'due_date' => now()->addDays($step['due_days'] ?? 7),
            ]);
        }
    }

    /**
     * Assign a review task to appropriate reviewer
     */
    protected function assignReviewTask(WorkflowTask $task, array $config): void
    {
        $stepConfig = $task->task_data;
        $assigneeId = $this->determineTaskAssignee($task, $stepConfig, $config);

        if ($assigneeId) {
            $task->assigned_to = $assigneeId;
            $task->status = 'pending';
            $task->save();

            // Send notification
            $this->notifyTaskAssignment($task);

            // Log assignment
            AuditLoggingService::logComplianceAudit('review_task_assigned', $task->id, [
                'assignee_id' => $assigneeId,
                'step_number' => $stepConfig['step_number'] ?? null,
            ]);
        }
    }

    /**
     * Determine task assignee based on rules
     */
    protected function determineTaskAssignee(WorkflowTask $task, array $stepConfig, array $config): ?int
    {
        // Check for explicit assignee in step config
        if (isset($stepConfig['assignee_id'])) {
            return $stepConfig['assignee_id'];
        }

        // Check for role-based assignment
        if (isset($stepConfig['assignee_role'])) {
            return $this->getUserByRole($stepConfig['assignee_role']);
        }

        // Check for rule-based assignment
        if (isset($stepConfig['assignment_rule'])) {
            return $this->applyAssignmentRule($task, $stepConfig['assignment_rule']);
        }

        // Fallback to default reviewer
        return $this->getDefaultReviewer($task->taskable);
    }

    /**
     * Get review configuration for document
     */
    protected function getReviewConfiguration(Document $document, array $overrideConfig = []): array
    {
        // Get default config based on document type
        $defaultConfig = $this->getDefaultReviewConfig($document->document_type);

        // Merge with document-specific config from metadata
        $documentConfig = $document->metadata['review_config'] ?? [];

        // Merge with override config
        return array_merge($defaultConfig, $documentConfig, $overrideConfig);
    }

    /**
     * Get default review configuration for document type
     */
    protected function getDefaultReviewConfig(string $documentType): array
    {
        $configs = [
            'claim' => [
                'auto_approve_threshold' => 0.8,
                'escalation_threshold' => 0.3,
                'steps' => [
                    [
                        'name' => 'Initial Review',
                        'description' => 'Review claim details and basic compliance',
                        'assignee_role' => 'claims_processor',
                        'priority' => 'normal',
                        'due_days' => 2,
                    ],
                    [
                        'name' => 'Compliance Check',
                        'description' => 'Verify HIPAA and regulatory compliance',
                        'assignee_role' => 'compliance_officer',
                        'priority' => 'high',
                        'due_days' => 3,
                    ],
                    [
                        'name' => 'Final Approval',
                        'description' => 'Final review and approval',
                        'assignee_role' => 'supervisor',
                        'priority' => 'high',
                        'due_days' => 1,
                    ],
                ],
            ],
            'prescription' => [
                'auto_approve_threshold' => 0.9,
                'escalation_threshold' => 0.2,
                'steps' => [
                    [
                        'name' => 'Clinical Review',
                        'description' => 'Review prescription for clinical appropriateness',
                        'assignee_role' => 'pharmacist',
                        'priority' => 'high',
                        'due_days' => 1,
                    ],
                    [
                        'name' => 'Final Approval',
                        'description' => 'Final prescription approval',
                        'assignee_role' => 'prescriber',
                        'priority' => 'urgent',
                        'due_days' => 1,
                    ],
                ],
            ],
        ];

        return $configs[$documentType] ?? [
            'auto_approve_threshold' => 0.7,
            'escalation_threshold' => 0.4,
            'steps' => [
                [
                    'name' => 'Review',
                    'description' => 'Document review and approval',
                    'assignee_role' => 'reviewer',
                    'priority' => 'normal',
                    'due_days' => 7,
                ],
            ],
        ];
    }

    /**
     * Get default review steps
     */
    protected function getDefaultReviewSteps(Document $document): array
    {
        return [
            [
                'name' => 'Document Review',
                'description' => "Review {$document->document_type} document",
                'assignee_role' => 'reviewer',
                'priority' => 'normal',
                'due_days' => 7,
            ],
        ];
    }

    /**
     * Check if rejection should be escalated
     */
    protected function shouldEscalateRejection(Document $document, array $config): bool
    {
        // Check document value/criticality
        $documentValue = $document->metadata['value'] ?? 0;
        $escalationThreshold = $config['escalation_value_threshold'] ?? 10000;

        if ($documentValue >= $escalationThreshold) {
            return true;
        }

        // Check if document type requires escalation on rejection
        return in_array($document->document_type, $config['always_escalate_types'] ?? []);
    }

    /**
     * Notify task assignment
     */
    protected function notifyTaskAssignment(WorkflowTask $task): void
    {
        if (!$task->assigned_to) {
            return;
        }

        try {
            $assignee = User::find($task->assigned_to);
            if ($assignee) {
                $this->notificationService->sendNotification($assignee, [
                    'type' => 'task_assigned',
                    'title' => 'New Review Task Assigned',
                    'message' => "You have been assigned: {$task->title}",
                    'data' => [
                        'task_id' => $task->id,
                        'document_id' => $task->taskable_id,
                        'due_date' => $task->due_date,
                        'priority' => $task->priority,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send task assignment notification', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get user by role (placeholder - integrate with actual role system)
     */
    protected function getUserByRole(string $role): ?int
    {
        // This should integrate with your role/permission system
        // For now, return null
        return null;
    }

    /**
     * Apply assignment rule
     */
    protected function applyAssignmentRule(WorkflowTask $task, string $rule): ?int
    {
        // Implement rule-based assignment logic
        // For now, return null
        return null;
    }

    /**
     * Get default reviewer
     */
    protected function getDefaultReviewer(Document $document): ?int
    {
        // This should integrate with workflow engine's default reviewer logic
        return null;
    }

    /**
     * Handle overdue tasks
     */
    public function handleOverdueTasks(): void
    {
        $overdueTasks = WorkflowTask::where('task_type', 'document_review')
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueTasks as $task) {
            $this->escalateOverdueTask($task);
        }
    }

    /**
     * Escalate overdue task
     */
    protected function escalateOverdueTask(WorkflowTask $task): void
    {
        $document = $task->taskable;

        if ($document && $document instanceof Document) {
            // Mark task as overdue
            $task->task_data = array_merge($task->task_data ?? [], [
                'overdue' => true,
                'overdue_since' => now(),
            ]);
            $task->save();

            // Escalate the document
            $this->workflowEngine->escalateDocument(
                $document,
                User::find(1), // System user
                "Task overdue: {$task->title}",
                ['overdue_task_id' => $task->id]
            );

            AuditLoggingService::logComplianceAudit('review_task_escalated_overdue', $task->id, [
                'document_id' => $document->id,
                'overdue_days' => now()->diffInDays($task->due_date),
            ]);
        }
    }
}
